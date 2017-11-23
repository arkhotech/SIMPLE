<?php

class Etapa extends Doctrine_Record {

    function setTableDefinition() {
        $this->hasColumn('id');
        $this->hasColumn('tarea_id');
        $this->hasColumn('tramite_id');
        $this->hasColumn('usuario_id');
        $this->hasColumn('pendiente');
        $this->hasColumn('etapa_ancestro_split_id');    //Etapa ancestro que provoco el split del flujo. (Sirve para calcular cuando se puede hacer la union del flujo)
        $this->hasColumn('vencimiento_at');
        $this->hasColumn('created_at');
        $this->hasColumn('updated_at');
        $this->hasColumn('ended_at');
    }

    function setUp() {
        parent::setUp();

        $this->actAs('Timestampable');

        $this->hasOne('Tarea', array(
            'local' => 'tarea_id',
            'foreign' => 'id'
        ));

        $this->hasOne('Tramite', array(
            'local' => 'tramite_id',
            'foreign' => 'id'
        ));

        $this->hasOne('Usuario', array(
            'local' => 'usuario_id',
            'foreign' => 'id'
        ));

        $this->hasMany('DatoSeguimiento as DatosSeguimiento', array(
            'local' => 'id',
            'foreign' => 'etapa_id'
        ));
        
        $this->hasOne('Etapa as EtapaAncestroSplit', array(
            'local'=>'etapa_ancestro_split_id',
            'foreign'=>'id'
        ));
        
        $this->hasMany('Etapa as EtapasDescendientesSplit', array(
            'local'=>'id',
            'foreign'=>'etapa_ancestro_split_id'
        ));
    }

    public function getDatosSeguimiento() {
         return Doctrine_Query::create()
                ->from("DatoSeguimiento d, d.Etapa e, e.Tramite t")
                ->where('e.pendiente = 1')
                ->andWhere('e.id=?',$this->id)
                ->execute();
    }

    //Verifica si el usuario_id tiene permisos para asignarse esta etapa del tramite.
    public function canUsuarioAsignarsela($usuario_id) {
        static $usuario;

        if(!$usuario || ($usuario->id != $usuario_id)){
            $usuario=Doctrine::getTable('Usuario')->find($usuario_id);
        }


        if ($this->Tarea->acceso_modo == 'publico')
            return true;

        if ($this->Tarea->acceso_modo == 'claveunica' && $usuario->open_id)
            return true;

        if ($this->Tarea->acceso_modo == 'registrados' && $usuario->registrado)
            return true;

        if ($this->Tarea->acceso_modo == 'grupos_usuarios') {
            $r=new Regla($this->Tarea->grupos_usuarios);
            $grupos_arr = explode(',', $r->getExpresionParaOutput($this->id));
            foreach($usuario->GruposUsuarios as $g)
                if(in_array($g->id,$grupos_arr))
                    return true;
        }




        return false;
    }

    //Avanza a la siguiente etapa.
    //Si se desea especificar el usuario a cargo de la prox etapa, se debe pasar como parametros en un array: $usuarios_a_asignar[$tarea_id]=$usuario_id.
    //Este parametro solamente es valido si la asignacion de la prox tarea es manual.
    public function avanzar($usuarios_a_asignar = null) {
        log_message('debug',"Avanzando etapa");

        Doctrine_Manager::connection()->beginTransaction();
        //Cerramos esta etapa
        $this->cerrar();

        $tp = $this->getTareasProximas();
        if ($tp->estado != 'sincontinuacion') {
            if ($tp->estado == 'completado') {
                if ($this->Tramite->getEtapasActuales()->count() == 0)
                    $this->Tramite->cerrar();
            }
            else {
                if ($tp->estado == 'pendiente') {
                    $tareas_proximas = $tp->tareas;
                    foreach ($tareas_proximas as $tarea_proxima) {
                        $etapa = new Etapa();
                        $etapa->tramite_id = $this->Tramite->id;
                        $etapa->tarea_id = $tarea_proxima->id;
                        $etapa->pendiente = 1;
                        $etapa->save();

                        $usuario_asignado_id = NULL;
                        if ($tarea_proxima->asignacion == 'ciclica') {
                            $usuarios_asignables = $etapa->getUsuarios();
                            $usuario_asignado_id = $usuarios_asignables[0]->id;
                            $ultimo_usuario = $tarea_proxima->getUltimoUsuarioAsignado($this->Tramite->Proceso->id);
                            if ($ultimo_usuario) {
                                foreach ($usuarios_asignables as $key => $u) {
                                    if ($u->id == $ultimo_usuario->id) {
                                        $usuario_asignado_id = $usuarios_asignables[($key + 1) % $usuarios_asignables->count()]->id;
                                        break;
                                    }
                                }
                            }
                        } else if ($tarea_proxima->asignacion == 'manual') {
                            $usuario_asignado_id = $usuarios_a_asignar[$tarea_proxima->id];
                        } else if ($tarea_proxima->asignacion == 'usuario') {
                            $regla = new Regla($tarea_proxima->asignacion_usuario);
                            $u = $regla->evaluar($this->id);
                            $usuario_asignado_id = $u;
                        }

                        // Para mas adelante poder calcular como hacer las uniones
                        if ($tp->conexion == 'union')
                            $etapa->etapa_ancestro_split_id = null;
                        else if ($tp->conexion == 'paralelo' || $tp->conexion == 'paralelo_evaluacion')
                            $etapa->etapa_ancestro_split_id = $this->id;
                        else
                            $etapa->etapa_ancestro_split_id = $this->etapa_ancestro_split_id;

                        $etapa->save();
                        $etapa->vencimiento_at = $etapa->calcularVencimiento();
                        $etapa->save();

                        if ($tarea_proxima->externa) {
                            $etapa->ejecutar_eventos_externos();
                        }

                        if ($usuario_asignado_id)
                            $etapa->asignar($usuario_asignado_id);
                        else
                       		$etapa->notificarTareaPendiente();
                    }
                    $this->Tramite->updated_at = date("Y-m-d H:i:s");
                    $this->Tramite->save();
                }
            }
        }
        Doctrine_Manager::connection()->commit();

    }

    //Esta funcion entrega un listado de tareas a continuar y un estado que indica como se debe proceder con esta continuacion.
    //tareas:   -Arreglo de tareas para continuar
    //estado:   -sincontinuacion: No hay reglas para continuar. No se puede avanzar de etapa.
    //          -completado: Se completa el tramite luego de esta etapa.
    //          -pendiente: Hay etapas a continuacion
    //          -standby: Hay etapas a continuacion pero no se puede avanzar todavia hasta que que se completen etapas paralelas. 
    public function getTareasProximas() {
        $resultado = new stdClass();
        $resultado->tareas = null;
        $resultado->estado = 'sincontinuacion';
        $resultado->conexion = null;

        $tarea_actual = $this->Tarea;
        $conexiones = $tarea_actual->ConexionesOrigen;

        // $tareas = null;
        foreach ($conexiones as $c) {
            if ($c->evaluarRegla($this->id)) {
                //Si no hay destino es el fin del tramite.
                if (!$c->tarea_id_destino) {
                    $resultado->tareas = null;
                    $resultado->estado = 'completado';
                    $resultado->conexion = null;
                    break;
                }

                //Si no es en paralelo, retornamos con la tarea proxima.
                if ($c->tipo == 'secuencial' || $c->tipo == 'evaluacion') {
                    $resultado->tareas = array($c->TareaDestino);
                    $resultado->estado = 'pendiente';
                    $resultado->conexion = $c->tipo;
                    break;
                }
                //Si son en paralelo, vamos juntando el grupo de tareas proximas.
                else if ($c->tipo == 'paralelo' || $c->tipo == 'paralelo_evaluacion') {
                    
                    $resultado->tareas[] = $c->TareaDestino;
                    $resultado->estado = 'pendiente';
                    $resultado->conexion = $c->tipo;
                }
                //Si es de union, chequeamos que las etapas paralelas se hayan completado antes de continuar con la proxima.
                else if ($c->tipo == 'union') {
                    if (!$this->hayEtapasParalelasPendientes()) {
                        $resultado->estado = 'pendiente';
                    } else {
                        $resultado->estado = 'standby';
                    }
                    $resultado->tareas = array($c->TareaDestino);
                    $resultado->conexion=$c->tipo;
                    break;
                }
            }
        }

        return $resultado;
    }

    public function hayEtapasParalelasPendientes() {
        if($this->etapa_ancestro_split_id){
            $n_etapas_paralelas= Doctrine_Query::create()
                    ->from('Etapa e')
                    ->where('e.etapa_ancestro_split_id = ?',$this->etapa_ancestro_split_id)
                    ->andWhere('e.pendiente = 1')
                    ->andWhere('e.id != ?',$this->id)
                    ->count();
        }else{  //Metodo antiguo (Deprecado)
            $n_etapas_paralelas = Doctrine_Query::create()
                ->from('Etapa e, e.Tarea t, t.ConexionesOrigen c, c.TareaDestino tarea_hijo, tarea_hijo.ConexionesDestino c2, c2.TareaOrigen.Etapas etapa_this')
                ->andWhere('etapa_this.id = ?', $this->id)
                ->andWhere('c.tipo = "union" AND c2.tipo="union"')
                ->andWhere('e.tramite_id = ?',$this->tramite_id)
                ->andWhere('e.pendiente = 1')
                ->andWhere('e.id != ?',$this->id)
                ->count();
        }
        
        return $n_etapas_paralelas?true:false;
    }

    public function asignar($usuario_id) {

        if ($this->Tarea->acceso_modo == 'claveunica') {
            $usuario = Doctrine::getTable('Usuario')->findOneByRut($usuario_id);
            if (!$usuario) {
                $usuario = Doctrine::getTable('Usuario')->find($usuario_id);
            }
            $usuario_id = $usuario->id;
        }
        
        if (!$this->canUsuarioAsignarsela($usuario_id))
            return;

        $this->usuario_id = $usuario_id;
        $this->save();

        if ($this->Tarea->asignacion_notificar) {
            $usuario = Doctrine::getTable('Usuario')->find($usuario_id);
            if ($usuario->email) {
                $varurl = site_url('etapas/ejecutar/' . $this->id);
                $url = ' Podrá realizarla en: ' . $varurl. ' ';
                $url = str_replace("..", ".", $url);
                $CI = & get_instance();
                $cuenta=$this->Tramite->Proceso->Cuenta;
                $CI->email->from($cuenta->nombre.'@'.$CI->config->item('main_domain'), $cuenta->nombre_largo);
                $CI->email->to($usuario->email);
                $CI->email->subject('Tiene una tarea pendiente');
                $CI->email->message('<p>' . $this->Tramite->Proceso->nombre . '</p><p>Se le ha asignado la tarea: ' . $this->Tarea->nombre . '</p><p>' .$url. '</p>');
                $CI->email->send();
            }
        }

        // Ejecutamos los eventos
        $eventos = Doctrine_Query::create()->from('Evento e')
                ->where('e.tarea_id = ? AND e.instante = ? AND e.paso_id IS NULL',array($this->Tarea->id,'antes'))
                ->execute();
        foreach ($eventos as $e) {
                $r = new Regla($e->regla);
                if ($r->evaluar($this->id))
                    $e->Accion->ejecutar($this);           
        }
    }

    public function notificarTareaPendiente(){
        if ($this->Tarea->asignacion_notificar) {

            if($this->usuario_id)
                $usuarios = Doctrine::getTable('Usuario')->findById($this->usuario_id);
            else
                $usuarios = $this->getUsuarios();

            foreach($usuarios as $usuario){
                if ($usuario->email) {
                    $varurl = '';
                    if ($this->usuario_id) {
                         $varurl=site_url('etapas/ejecutar/' . $this->id);
                    }else{
                         $varurl=site_url('etapas/sinasignar');
                    }
                    $CI = & get_instance();
                    $cuenta=$this->Tramite->Proceso->Cuenta;
                    $CI->email->from($cuenta->nombre.'@'.$CI->config->item('main_domain'), $cuenta->nombre_largo);
                    $CI->email->to($usuario->email);
                    $CI->email->subject('SIMPLE - Tiene una tarea pendiente');
                    $url = ' Podrá realizarla en: ' . $varurl . ' ';
                    $url = str_replace("..", ".", $url);
                    $CI->email->message('<p>' . $this->Tramite->Proceso->nombre . '</p><p>Tiene una tarea pendiente por realizar: ' . $this->Tarea->nombre . '</p><p>' .$url . '</p>');
                    $CI->email->send();
                }
            }

        }
    }

    public function cerrar($ejecutar_eventos = TRUE) {
        // Si ya fue cerrada, retornamos inmediatamente.
        if (!$this->pendiente)
            return;

        if ($this->Tarea->almacenar_usuario) {
            $dato = Doctrine::getTable('DatoSeguimiento')->findOneByNombreAndEtapaId($this->Tarea->almacenar_usuario_variable,$this->id);
            if (!$dato)
                $dato = new DatoSeguimiento();
            $dato->nombre = $this->Tarea->almacenar_usuario_variable;
            $dato->valor = UsuarioSesion::usuario()->id;
            $dato->etapa_id = $this->id;
            $dato->save();
        }

        // Ejecutamos los eventos
        if($ejecutar_eventos){
            $eventos=Doctrine_Query::create()->from('Evento e')
                    ->where('e.tarea_id = ? AND e.instante = ? AND e.paso_id IS NULL',array($this->Tarea->id,'despues'))
                    ->execute();
            foreach ($eventos as $e) {
                    $r = new Regla($e->regla);
                    if ($r->evaluar($this->id))
                        $e->Accion->ejecutar($this);           
            }
        }

        // Cerramos la etapa
        $this->pendiente = 0;
        $this->ended_at = date('Y-m-d H:i:s');
        $this->save();
    }

    //Retorna el paso correspondiente a la secuencia, dado los datos ingresados en el tramite hasta el momento.
    //Es decir, tomando en cuenta las condiciones para que se ejecute cada paso.
    public function getPasoEjecutable($secuencia) {
        $pasos = $this->getPasosEjecutables($this->tramite_id);
        log_message("INFO", "Cantidad de pasos: ".count($pasos), FALSE);

        if (isset($pasos[$secuencia]))
            return $pasos[$secuencia];
        log_message("debug", "retornando null", FALSE);
        return null;
    }

    //Retorna un arreglo con todos los pasos que son ejecutables dado los datos ingresados en el tramite hasta el momento.
    //Es decir, tomando en cuenta las condiciones para que se ejecute cada paso.
    public function getPasosEjecutables() {
        $pasos = array();
        foreach ($this->Tarea->Pasos as $p) {
            $r = new Regla ($p->regla);
            if ($r->evaluar($this->id))
                $pasos[] = $p;
        }

        return $pasos;
    }
    
    //Calcula la fecha en que deberia vencer esta etapa tomando en cuenta la configuracion de la tarea.
    public function calcularVencimiento() {
        if (!$this->Tarea->vencimiento)
            return NULL;
        
        $fecha = NULL;
        
       	if ($this->Tarea->vencimiento_unidad == 'D' && $this->Tarea->vencimiento_habiles)
       		 return add_working_days($this->created_at,$this->Tarea->vencimiento_valor);
       	else {
       		$tmp = new DateTime($this->created_at);
       		return $tmp->add(new DateInterval('P' . $this->Tarea->vencimiento_valor . $this->Tarea->vencimiento_unidad))->format('Y-m-d');
       		
       	}
    }

    /*
    public function getFechaVencimiento() {
        if (!($this->Tarea->vencimiento && $this->Tarea->vencimiento_valor))
            return NULL;

        //return strtotime($this->Tarea->vencimiento_valor.' '.$this->Tarea->vencimiento_unidad, mysql_to_unix($this->created_at));
        $creacion = new DateTime($this->created_at);
        //$creacion->setTime(0, 0, 0);
        return $creacion->add(new DateInterval('P' . $this->Tarea->vencimiento_valor . $this->Tarea->vencimiento_unidad));
    }
     * 
     */

    public function getFechaVencimientoAsString() {
        $now = new DateTime();
        $now->setTime(0,0,0);

        $interval = $now->diff(new DateTime($this->vencimiento_at));
        
        if($interval->invert)
            return 'vencida hace ' . ($interval->days) . ($interval->days == 1 ? ' dia' :' días');
        else
            return 'vence '. ($interval->days == 0 ? 'hoy' : 'en '.  ($interval->days) .($interval->days == 1 ? ' dia' :' días'));
    }
     

    public function vencida() {
        if (!$this->vencimiento_at)
            return FALSE;

        $vencimiento = new DateTime($this->vencimiento_at);
        $now = new DateTime();
        $now->setTime(0,0,0);

        return $vencimiento < $now;
    }

    public function iniciarPaso(Paso $paso) {
        //Ejecutamos los eventos iniciales
        $eventos=Doctrine_Query::create()->from('Evento e')
                ->where('e.paso_id = ? AND e.instante = ?',array($paso->id,'antes'))
                ->execute();
        foreach ($eventos as $e) {
                $r = new Regla($e->regla);
                if ($r->evaluar($this->id))
                    $e->Accion->ejecutar($this);
            
        }
    }

    public function finalizarPaso(Paso $paso) {
        //Ejecutamos los eventos finales
        $eventos=Doctrine_Query::create()->from('Evento e')
                ->where('e.paso_id = ? AND e.instante = ?',array($paso->id,'despues'))
                ->execute();
        foreach ($eventos as $e) {
                $r = new Regla($e->regla);
                if ($r->evaluar($this->id))
                    $e->Accion->ejecutar($this);
            
        }
    }
    
    public function toPublicArray(){
        $publicArray=array(
            'id'=>(int)$this->id,
            'estado'=>$this->pendiente?'pendiente':'completado',
            'usuario_asignado'=>$this->usuario_id?$this->Usuario->toPublicArray():null,
            'fecha_inicio' => $this->created_at,
            'fecha_modificacion' => $this->updated_at,
            'fecha_termino' => $this->ended_at,
            'fecha_vencimiento'=>$this->vencimiento_at,
            'tarea'=>$this->Tarea->toPublicArray()
        );
        
        return $publicArray;
    }
    
    //Obtiene el listado de usuarios que tienen acceso a esta tarea y que esten disponibles (no en vacaciones).
    public function getUsuarios() {
        
        return $this->Tarea->getUsuarios($this->id);
    }

    //Obtiene el listado de usuarios que tienen acceso a esta tarea y que esten disponibles (no en vacaciones).
    //Ademas, deben pertenecer a alguno de los grupos de usuarios definidos en la cuenta
    public function getUsuariosFromGruposDeUsuarioDeCuenta() {
        return $this->Tarea->getUsuariosFromGruposDeUsuarioDeCuenta($this->id);
    }

    public function getPrevisualizacion(){
        if(!$this->Tarea->previsualizacion)
            return '';

        $r = new Regla($this->Tarea->previsualizacion);

        return $r->getExpresionParaOutput($this->id);
    }
    
    

    /**
     * Retorna true si es la etapa con que se completó el tramite
     */
    public function isFinal(){
    	 
    	return $this->pendiente==0 && 
    		$this->Tramite->pendiente==0 && 
    		$this->Tramite->getUltimaEtapa()->id == $this->id;
    		 
    }

    //Ejecuta eventos externos para una tarea externa
    public function ejecutar_eventos_externos(){

        $CI = &get_instance();
        $etapa = Doctrine::getTable('Etapa')->find($this->id);
        
        // Ejecutar eventos antes de la tarea
        $eventos=Doctrine_Query::create()->from('Evento e')
                ->where('e.tarea_id = ? AND e.instante = ? AND e.paso_id IS NULL',array($this->Tarea->id,'antes'))
                ->execute();
        foreach ($eventos as $e) {
                $r = new Regla($e->regla);
                if ($r->evaluar($this->id))
                    $e->Accion->ejecutar($etapa);
        }

        $eventos_externos=Doctrine_Query::create()
                ->from('EventoExterno e')
                ->where('e.tarea_id = ?',$this->Tarea->id)
                ->orderBy('e.id asc')
                ->execute();

        foreach($eventos_externos as $keye=>$evento){

            //Ejecutar eventos antes del evento externo
            $eventos=Doctrine_Query::create()->from('Evento e')
                ->where('e.tarea_id = ? AND e.instante = ? AND e.evento_externo_id = ?',array($this->Tarea->id,'antes',$evento->id))
                ->execute();
            //echo $eventos->getSqlQuery();
            foreach ($eventos as $e) {
                $r = new Regla($e->regla);
                if ($r->evaluar($this->id))
                    $e->Accion->ejecutar($etapa);
            }

            $regla = new Regla($evento->mensaje);
            $mensaje = $regla->getExpresionParaOutput($this->id);
            $regla = new Regla($evento->url);
            $url = $regla->getExpresionParaOutput($this->id);
            $regla = new Regla($evento->opciones);
            $opciones = $regla->getExpresionParaOutput($this->id);
            $regla = new Regla($evento->regla);
            
            $acontecimiento = Doctrine::getTable('Acontecimiento')->findOneByEventoExternoIdAndEtapaId($evento->id,$this->id);
            if(!$acontecimiento)
                $acontecimiento = new Acontecimiento();
            $acontecimiento->estado = FALSE;
            if($regla->evaluar($this->id)){

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_HEADER, FALSE);
                $metodos = array('POST','PUT');
                if(in_array($evento->metodo,$metodos)){
                    curl_setopt($ch, CURLOPT_POST, TRUE);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $evento->metodo);
                    $mensaje_json = $mensaje;
                    if(!empty($evento->regla)){
                        $mensaje_json = json_decode($mensaje_json,true);
                        $mensaje_json = json_encode($mensaje_json);
                    }
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $mensaje_json);
                }
                $opciones_httpheader = array("cache-control: no-cache", "Content-Type: application/json");
                if(!is_null($opciones)){
                    array_push($opciones_httpheader, $opciones);
                }
                curl_setopt($ch, CURLOPT_HTTPHEADER, $opciones_httpheader);
                $response = curl_exec($ch);
                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $err = curl_error($ch);
                curl_close($ch);
                
                if(($httpcode==200 or $httpcode==201) && isJSON($response)){
                    $json = json_decode($response);
                    foreach($json as $key=>$value){
                        $key = str_replace("-", "_", $key);
                        $key = str_replace(" ", "_", $key);
                        $dato=Doctrine::getTable('DatoSeguimiento')->findOneByNombreAndEtapaId($key,$this->id);
                        if(!$dato)
                            $dato=new DatoSeguimiento();
                        $dato->nombre=$key;
                        $dato->valor=$value;
                        $dato->etapa_id=$this->id;
                        $dato->save();
                    }
                }
                $acontecimiento->estado = TRUE;
            }
            $acontecimiento->evento_externo_id = $evento->id;
            $acontecimiento->etapa_id = $this->id;
            $acontecimiento->save();

            //Ejecutar eventos despues del evento externo
            $eventos=Doctrine_Query::create()->from('Evento e')
                ->where('e.tarea_id = ? AND e.instante = ? AND e.evento_externo_id = ?',array($this->Tarea->id,'despues',$evento->id))
                ->execute();
            //echo $eventos->getSqlQuery();
            foreach ($eventos as $e) {
                $r = new Regla($e->regla);
                if ($r->evaluar($this->id))
                    $e->Accion->ejecutar($etapa);
            }

        }

        // Ejecutar eventos despues de la tarea
        $eventos=Doctrine_Query::create()->from('Evento e')
            ->where('e.tarea_id = ? AND e.instante = ? AND e.paso_id IS NULL',array($this->Tarea->id,'despues'))
            ->execute();
        foreach ($eventos as $e) {
                $r = new Regla($e->regla);
                if ($r->evaluar($this->id))
                    $e->Accion->ejecutar($etapa);
        }

        //Iniciar tareas próximas siempre y cuando todos los eventos externos han sido completados
        $pendientes = Doctrine_Core::getTable('Acontecimiento')->findByEtapaIdAndEstado($this->id,0)->count();
        if($pendientes==0){
            $tp = $etapa->getTareasProximas();
            if ($tp->estado == 'completado'){
                $ejecutar_eventos = FALSE;
                $this->Tramite->cerrar($ejecutar_eventos);
            }else{
                $etapa->avanzar();
            }
        }
    }

    public function getEtapaPorTareaId($id_tarea, $id_proceso) {
        $etapa = Doctrine_Query::create()
                ->from('Etapa e')
                ->where('e.tarea_id = ?',$id_tarea)
                ->andWhere('e.tramite_id = ?', $id_proceso)
            ->execute();

        return $etapa[0];
    }

    public function ejecutarColaContinuarTarea($tarea_id, $tareas_encoladas){
        log_message('debug',"Verificando si existe alguna acción de continuar tarea encolada", FALSE);
        $result = null;
        if(isset($tareas_encoladas) && count($tareas_encoladas) > 0){
            log_message('debug',"Se encuentran ".count($tareas_encoladas)." registros encolados para este trámite", FALSE);
            log_message('debug',"Buscando tarea id: ".$tarea_id, FALSE);
            foreach ($tareas_encoladas as $tarea_continuar){
                if($tarea_continuar->tarea_id == $tarea_id){
                    log_message('debug',"Continuando tarea con id ".$tarea_id, FALSE);
                    $etapa = new Etapa();
                    $etapa = $etapa->getEtapaPorTareaId($tarea_continuar->tarea_id, $tarea_continuar->tramite_id);
                    log_message("debug", "id_etapa a continuar: ".$etapa->id);
                    $tarea_continuar->procesado = 1;
                    $tarea_continuar->save();
                    $integracion = new IntegracionMediator();
                    $result = $integracion->continuarProceso($tarea_continuar->tramite_id, $etapa->id, "0", $tarea_continuar->request);
                }
            }
        }
        return $result;
    }

    private function varDump($data){
        ob_start();
        //var_dump($data);
        print_r($data);
        $ret_val = ob_get_contents();
        ob_end_clean();
        return $ret_val;
    }
}
