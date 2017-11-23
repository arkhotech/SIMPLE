<?php

class API extends MY_BackendController {
    
    public function _auth(){
        UsuarioBackendSesion::force_login();
        
//        if(UsuarioBackendSesion::usuario()->rol!='super' && UsuarioBackendSesion::usuario()->rol!='desarrollo'){
        if( !in_array('super', explode(',',UsuarioBackendSesion::usuario()->rol) ) && !in_array( 'desarrollo',explode(',',UsuarioBackendSesion::usuario()->rol))){
            echo 'No tiene permisos para acceder a esta seccion.';
            exit;
        }
    }
    
    /*
     * Documentacion de la API
     */
    
    public function index(){
        $this->_auth();
        
        $data['title']='API';
        $data['content']='backend/api/index';
        $this->load->view('backend/template',$data);
    }
    
    public function token(){
        $this->_auth();
        
        $data['cuenta']=UsuarioBackendSesion::usuario()->Cuenta;
        
        $data['title']='Configurar Código de Acceso';
        $data['content']='backend/api/token';
        $this->load->view('backend/template',$data);
    }
    
    public function token_form(){
        $this->_auth();
        
        $cuenta=UsuarioBackendSesion::usuario()->Cuenta;
        
        $this->form_validation->set_rules('api_token','Token','max_length[32]');
        
        $respuesta=new stdClass();
        if($this->form_validation->run()==true){
            $cuenta->api_token=$this->input->post('api_token');
            $cuenta->save();
            
            $respuesta->validacion=true;
            $respuesta->redirect=site_url('backend/api');
            
        }else{
            $respuesta->validacion=false;
            $respuesta->errores=validation_errors();
        }
        
        echo json_encode($respuesta);
    }
    
    public function tramites_recurso(){
        $this->_auth();
        
        $data['title']='Tramites';
        $data['content']='backend/api/tramites_recurso';
        $this->load->view('backend/template',$data);
    }
    
    public function tramites_obtener(){
        $this->_auth();
        
        $data['title']='Tramites: obtener';
        $data['content']='backend/api/tramites_obtener';
        $this->load->view('backend/template',$data);
    }
    
    public function tramites_listar(){
        $this->_auth();
        $data['title']='Tramites: listar';
        $data['content']='backend/api/tramites_listar';
        $this->load->view('backend/template',$data);
    }
    
    public function procesos_disponibles(){
        $this->_auth();
        $data['title']='Trámites disponibles como servicios';
        $data['content']='backend/api/tramites_disponibles';
        $data['json'] = Doctrine::getTable('Proceso')->findProcesosExpuestos(UsuarioBackendSesion::usuario()->cuenta_id);
        $this->load->view('backend/template',$data);
    }

    public function tramites_listarporproceso(){
        $this->_auth();
        
        $data['title']='Tramites: listar por proceso';
        $data['content']='backend/api/tramites_listarporproceso';
        $this->load->view('backend/template',$data);
    }
    
    public function procesos_recurso(){
        $this->_auth();
        
        $data['title']='Procesos';
        $data['content']='backend/api/procesos_recurso';
        $this->load->view('backend/template',$data);
    }
    
    public function procesos_obtener(){
        $this->_auth();
        
        $data['title']='Procesos: obtener';
        $data['content']='backend/api/procesos_obtener';
        $this->load->view('backend/template',$data);
    }
    
    public function procesos_listar(){
        $this->_auth();
        
        $data['title']='Procesos: listar';
        $data['content']='backend/api/procesos_listar';
        $this->load->view('backend/template',$data);
    }
    
    
    /*
     * Llamadas de la API
     */

    public function tramites($tramite_id = null) {
        $api_token=$this->input->get('token');
        
        $cuenta = Cuenta::cuentaSegunDominio();
        
        if(!$cuenta->api_token)
            show_404 ();
        
        if($cuenta->api_token!=$api_token)
            show_error ('No tiene permisos para acceder a este recurso.', 401);

        $respuesta = new stdClass();
        if ($tramite_id) {
            $tramite = Doctrine::getTable('Tramite')->find($tramite_id);

            if (!$tramite)
                show_404();

            if ($tramite->Proceso->Cuenta != $cuenta)
                show_error('No tiene permisos para acceder a este recurso.', 401);

            
            $respuesta->tramite = $tramite->toPublicArray();
        } else {
            $offset = $this->input->get('pageToken') ? 1 * base64_decode(urldecode($this->input->get('pageToken'))) : null;
            $limit = ($this->input->get('maxResults') && $this->input->get('maxResults') <= 50) ? 1 * $this->input->get('maxResults') : 10;

            $query = Doctrine_Query::create()
                    ->from('Tramite t, t.Proceso.Cuenta c')
                    ->where('c.id = ?', array($cuenta->id))
                    ->orderBy('id desc');
            if ($offset)
                $query->andWhere('id < ?', $offset);

            $ntramites_restantes = $query->count() - $limit;

            $query->limit($limit);
            $tramites = $query->execute();

            $nextPageToken = null;
            if ($ntramites_restantes > 0)
                $nextPageToken = urlencode(base64_encode($tramites[count($tramites) - 1]->id));

            $respuesta->tramites = new stdClass();
            $respuesta->tramites->titulo = 'Listado de Trámites';
            $respuesta->tramites->tipo = '#tramitesFeed';
            $respuesta->tramites->nextPageToken = $nextPageToken;
            $respuesta->tramites->items = null;
            foreach ($tramites as $t)
                $respuesta->tramites->items[] = $t->toPublicArray();
        }

        header('Content-type: application/json');
        echo json_indent(json_encode($respuesta));
    }

    public function procesos($proceso_id = null, $recurso = null) {
        $api_token=$this->input->get('token');
        
        $cuenta = Cuenta::cuentaSegunDominio();
        
        if(!$cuenta->api_token)
            show_404 ();
        
        if($cuenta->api_token!=$api_token)
            show_error ('No tiene permisos para acceder a este recurso.', 401);

        if ($proceso_id) {
            $proceso = Doctrine::getTable('Proceso')->find($proceso_id);

            if (!$proceso)
                show_404();

            if ($proceso->Cuenta != $cuenta)
                show_error('No tiene permisos para acceder a este recurso.', 401);

            if ($recurso == 'tramites') {
                $offset = $this->input->get('pageToken') ? 1 * base64_decode(urldecode($this->input->get('pageToken'))) : null;
                $limit = ($this->input->get('maxResults') && $this->input->get('maxResults') <= 50) ? 1 * $this->input->get('maxResults') : 10;

                $query = Doctrine_Query::create()
                        ->from('Tramite t, t.Proceso p')
                        ->where('p.activo=1 AND p.id = ?', array($proceso->id))
                        ->orderBy('id desc');
                if ($offset)
                    $query->andWhere('id < ?', $offset);

                $ntramites_restantes = $query->count() - $limit;

                $query->limit($limit);
                $tramites = $query->execute();

                $nextPageToken = null;
                if ($ntramites_restantes > 0)
                    $nextPageToken = urlencode(base64_encode($tramites[count($tramites) - 1]->id));

                $respuesta = new stdClass();
                $respuesta->tramites->titulo = 'Listado de Trámites';
                $respuesta->tramites->tipo = '#tramitesFeed';
                $respuesta->tramites->nextPageToken = $nextPageToken;
                $respuesta->tramites->items = null;
                foreach ($tramites as $t)
                    $respuesta->tramites->items[] = $t->toPublicArray();
            } else {

                $respuesta = new stdClass();
                $respuesta->proceso = $proceso->toPublicArray();
            }
        } else {

            $procesos = Doctrine::getTable('Proceso')->findByCuentaId($cuenta->id);

            $respuesta = new stdClass();
            $respuesta->procesos->titulo = 'Listado de Procesos';
            $respuesta->procesos->tipo = '#procesosFeed';
            $respuesta->procesos->items = null;
            foreach ($procesos as $t)
                $respuesta->procesos->items[] = $t->toPublicArray();
        }

        header('Content-type: application/json');
        echo json_indent(json_encode($respuesta));
    }

    public function notificar($tramite_id){
        $t = Doctrine::getTable('Tramite')->find($tramite_id);
        $etapa_id = $t->getUltimaEtapa()->id;
        $etapa = Doctrine::getTable('Etapa')->find($etapa_id);
        $pendientes = Doctrine_Core::getTable('Acontecimiento')->findByEtapaIdAndEstado($etapa_id,0)->count();
        if($pendientes>0){

            $post = file_get_contents('php://input');
            $json = json_decode($post);
            if(count($json)>0){
                foreach($json as $key=>$value){
                    $dato=Doctrine::getTable('DatoSeguimiento')->findOneByNombreAndEtapaId($key,$etapa_id);
                    if(!$dato)
                        $dato=new DatoSeguimiento();
                    $key = str_replace("-", "_", $key);
                    $key = str_replace(" ", "_", $key);
                    $dato->nombre=$key;
                    $dato->valor=$value;
                    $dato->etapa_id=$etapa_id;
                    $dato->save();
                }
            }        

            // Ejecutar eventos antes de la tarea
            /*
            $eventos=Doctrine_Query::create()->from('Evento e')
                    ->where('e.tarea_id = ? AND e.instante = ? AND e.paso_id IS NULL',array($e->Tarea->id,'antes'))
                    ->execute();
            foreach ($eventos as $e) {
                    $r = new Regla($e->regla);
                    if ($r->evaluar($this->id))
                        $e->Accion->ejecutar($etapa);
            }
            */

            $acontecimientos=Doctrine_Query::create()
                ->from('Acontecimiento a')
                ->where('a.etapa_id = ? AND a.estado = ?',array($etapa_id,0))
                ->orderBy('a.id asc')
                ->execute();

            
            foreach($acontecimientos as $clave => $a){

                $evento = Doctrine::getTable('EventoExterno')->find($a->EventoExterno->id);
                $regla = new Regla($evento->regla);
                $tarea_id = $a->Etapa->Tarea->id;

                //Ejecutar eventos antes del evento externo
                $eventos=Doctrine_Query::create()->from('Evento e')
                    ->where('e.tarea_id = ? AND e.instante = ? AND e.evento_externo_id = ?',array($a->Etapa->Tarea->id,'antes',$evento->id))
                    ->orderBy('e.id asc')
                    ->execute();
                foreach ($eventos as $e) {
                    $r = new Regla($e->regla);
                    if ($r->evaluar($etapa_id)){
                        $e->Accion->ejecutar($etapa);
                    }
                }

                if ($regla->evaluar($a->Etapa->id)){

                    $regla = new Regla($evento->mensaje);
                    $msg = $regla->getExpresionParaOutput($a->Etapa->id);
                    $regla = new Regla($evento->url);
                    $url = $regla->getExpresionParaOutput($a->Etapa->id);
                    $regla = new Regla($evento->opciones);
                    $opciones = $regla->getExpresionParaOutput($a->Etapa->id);

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                    curl_setopt($ch, CURLOPT_HEADER, FALSE);
                    $metodos = array('POST','PUT');
                    if(in_array($evento->metodo,$metodos)){
                        curl_setopt($ch, CURLOPT_POST, TRUE);
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $evento->metodo);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $msg);
                    }
                    $opciones_httpheader = array('cache-control: no-cache', 'Content-Type: application/json');
                    if(!is_null($opciones)){
                        array_push($opciones_httpheader, $opciones);
                    }
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $opciones_httpheader);
                    $response = curl_exec($ch);
                    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $err = curl_error($ch);
                    curl_close($ch);

                    if(($httpcode==200 or $httpcode==201) && isJSON($response)){
                        $js = json_decode($response);
                        foreach($js as $key=>$value){
                            $key = str_replace("-", "_", $key);
                            $key = str_replace(" ", "_", $key);
                            $dato=Doctrine::getTable('DatoSeguimiento')->findOneByNombreAndEtapaId($key,$a->Etapa->id);
                            if(!$dato)
                                $dato=new DatoSeguimiento();
                            $dato->nombre=$key;
                            $dato->valor=$value;
                            $dato->etapa_id=$a->Etapa->id;
                            $dato->save();
                        }
                    }
                    $a->estado = TRUE;
                    $a->save();

                    //Ejecutar eventos despues del evento externo
                    $eventos=Doctrine_Query::create()->from('Evento e')
                        ->where('e.tarea_id = ? AND e.instante = ? AND e.evento_externo_id = ?',array($tarea_id,'despues',$evento->id))
                        ->orderBy('e.id asc')
                        ->execute();
                    
                    foreach ($eventos as $e) {
                        $r = new Regla($e->regla);
                        if ($r->evaluar($etapa_id))
                            $e->Accion->ejecutar($etapa);
                    }
                }
            }

            // Ejecutar eventos despues de tareas
            /*
            $eventos=Doctrine_Query::create()
                    ->from('Evento e')
                    ->where('e.tarea_id = ? AND e.instante = ? AND e.paso_id IS NULL',array($tarea_id,'despues'))
                    ->orderBy('e.id asc')
                    ->execute();
            //echo $eventos->getSqlQuery();
            //exit;
            foreach ($eventos as $e) {
                    $r = new Regla($e->regla);
                    if ($r->evaluar($etapa_id))
                        $e->Accion->ejecutar($etapa);           
            }
            */

        }  

        $pendientes = Doctrine_Core::getTable('Acontecimiento')->findByEtapaIdAndEstado($etapa_id,0)->count();
        if($pendientes==0){
            $tp = $etapa->getTareasProximas();
            if ($tp->estado == 'completado'){
                $ejecutar_eventos = FALSE;
                $t->cerrar($ejecutar_eventos);
            }else{
                $etapa->avanzar();
            }
        }
        
        
        
    }

    /*
        Generación de trámites de forma externa
    */
    public function ejecutar($proceso_id){
        if($proceso_id){
            $proceso=Doctrine::getTable('Proceso')->find($proceso_id);
            if(!$proceso)
                show_404();
            
            //inicio de sesión con usuario no registrado
            $usuario = new Usuario();
            $usuario->usuario = random_string('unique');
            $usuario->setPasswordWithSalt(random_string('alnum', 32));
            $usuario->registrado = 0;
            $usuario->save();
            UsuarioSesion::login($usuario->usuario, $usuario->password);
            $CI = & get_instance();
            $CI->session->set_userdata('usuario_id', $usuario->id);
            if(!$proceso->canUsuarioIniciarlo(UsuarioSesion::usuario()->id)){
                echo 'Usuario no puede iniciar este proceso';
                exit;
            }
            
            //inicio del trámite
            $tramite=new Tramite();
            $tramite->iniciar($proceso->id);
            $etapa=Doctrine::getTable('Etapa')->find($tramite->getEtapasActuales()->get(0)->id);
            
            //almacenamiento de variables por POST
            $vars = $this->input->post(NULL, TRUE);
            foreach($vars as $key => $value){
                $key = str_replace("-", "_", $key);
                $key = str_replace(" ", "_", $key);
                $dato=Doctrine::getTable('DatoSeguimiento')->findOneByNombreAndEtapaId($key,$etapa->id);
                if(!$dato)
                    $dato=new DatoSeguimiento();
                $dato->nombre=$key;
                $dato->valor=$value;
                if(!is_object($dato->valor) && !is_array($dato->valor)){                            
                    if(preg_match('/^\d{4}[\/\-]\d{2}[\/\-]\d{2}$/', $dato->valor)){
                        $dato->valor=preg_replace("/^(\d{4})[\/\-](\d{2})[\/\-](\d{2})/i","$3-$2-$1",$dato->valor);
                    }
                }
                $dato->etapa_id=$etapa->id;
                $dato->save();
            }

            //pasos(formularios y campos)
            $ejecutar = true;
            $secuencia = 0;
            while($ejecutar){
                $paso = $etapa->getPasoEjecutable($secuencia);
                if(!$paso) 
                    break;

                //ejecución de eventos antes de iniciar el paso
                $etapa->iniciarPaso($paso);
                //campos del paso(formulario)
                foreach($paso->Formulario->Campos as $c){
                    $c->displayConDatoSeguimiento($etapa->id,$paso->modo);
                }
                //ejecución de eventos después de finalizar el paso
                $etapa->finalizarPaso($paso);
                $secuencia++;
            }
            $etapa->avanzar();
            
            //resultado del trámite es entregar todas las variables generadas en json
            $datos=Doctrine::getTable('DatoSeguimiento')->findByEtapaId($etapa->id);
            foreach($datos as $dato){
                $data[$dato->nombre] = $dato->valor;
            }
            header('Content-type: application/json');
            echo json_indent(json_encode($data));
        }else{
            show_404();
        }
    }

    public function testjson($evento){
        $this->_auth();
        $evento = Doctrine_Query::create()
                        ->from('EventoExterno e')
                        ->where('id = ?', array($evento))
                        ->fetchOne();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $evento->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST,"POST");
        $mensaje_json = json_decode($mensaje,true);
        $mensaje_json = json_encode($mensaje_json,JSON_UNESCAPED_SLASHES);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $evento->mensaje);
        $opciones_httpheader = array("cache-control: no-cache", "Content-Type: application/json");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $opciones_httpheader);
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        echo 'code--'.$httpcode."<br>";
        $err = curl_error($ch);
        echo 'error--'.$err."<br>";
        curl_close($ch);
        if(($httpcode==200 or $httpcode==201) && isJSON($response)){
            $json = json_decode($response);
            print_r($json);
        }
    }

    public function completados() {
        $desde=$this->input->get('desde');
        $hasta=$this->input->get('hasta');
        $respuesta = new stdClass();
        $query = Doctrine_Query::create()
                ->from('Proceso p, p.Tramites t')
                ->select('p.nombre, COUNT(t.id) as ntramites')
                ->where('t.pendiente=0');
        if($desde)
            $query = $query->andWhere('created_at >= '. "'".date('Y-m-d',strtotime($desde))."'");
        if($hasta)
            $query = $query->andWhere('ended_at <= '. "'".date('Y-m-d',strtotime($hasta))."'");

        $tramites = $query->groupBy('p.id')->execute();
        foreach($tramites as $p) {
            $respuesta->tramites[] = (object)array('cuenta'=>$p->Cuenta->nombre,'proceso_id'=>$p->id,'proceso'=>$p->nombre,'completados'=>$p->ntramites);
        }
        header('Content-type: application/json');
        echo json_indent(json_encode($respuesta));
    }

}
