<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

//Esta funcion debería estar en modelo

class IntegracionMediator{
    
    private function mapType($campo){
        switch ($campo['tipo']){
            case "file": return "base64";
            case "checkbox": return "boolean";
            case "grid": return "grid";
            case "date" : return "date";
            case "subtitle" : return "string";
            case "documento" : return "base64";
            default: return "string";
        }
    }
    
    private function varDireccion($campo){
        switch ($campo['tipo']){
            case "documento": 
            case "subtitle" :
            case "paragraph":
            case "title":
            case "recaptcha":
            case "javascript":
                return "OUT";
            default: return "IN";
        }
    }
    
    /**
     * 
     * @param type $json
     * @param type $id
     * @param type $value_list Valores de los campos a exportar
     * @return type
     * @throws Exception
     */        
    function normalizarFormulario($json,$form,$etapa_id=NULL,$value_list=NULL){
        
        if($form==NULL || !is_object($form)){
            throw new ApiException("El formulario viene sin ID",500);
        }
        $pasos = array();
       
        $retval['form'] = array(
            'idForm' => $form->id,
            'idEtapa' => $etapa_id,  
            'campos' => array() );
       
        if($etapa_id === NULL){
            unset($retval['form']['idEtapa']);
        }

 
        foreach( $json['Campos'] as $campo){
            if($campo['tipo'] == "subtitle" ){
                continue;  //se ignoran los campos de tipo subtitle
            }
 
            $obligatorio = false;
            if(count($campo['validacion']) > 0){
                foreach ($campo['validacion'] as $validacion) {
                    if($validacion == "required"){
                        $obligatorio = true;
                    }
                }
            }

            log_message("INFO", "Tipo Campo: ".$campo['tipo'], FALSE);

            $valor = ($value_list!=NULL) ? $value_list[$campo['nombre']] : "";
            if($campo['tipo'] == "paragraph" || $campo['tipo'] == "subtitle" || $campo['tipo'] == "title"){
                $valor = $campo['etiqueta'];
            }

            $record = array(
                    "nombre" => $campo['nombre'],
                    "tipo_control" => $campo['tipo'],
                    "tipo" => $this->mapType($campo),  //$campo['dependiente_tipo'],
                    "descripcion" => $campo['ayuda'],
                    "obligatorio" => $obligatorio,
                    "solo_lectura" => ($campo['readonly']==0) ? false : true,
                    "dominio_valores" => ($this->mapType($campo) == "grid") ? $campo["extra"] :$campo['datos'],
                    "valor" => $valor,
                    "direccion" => ($this->varDireccion($campo)));
                
            
            array_push($retval['form']['campos'],$record);
                 
        }
        
        return $retval;
    }
    /**
     * 
     * 
     * @param type $proceso_id Identificador de tramite
     * @param type $id_tarea Identificador de tarea 
     * @param type $id_paso Identificador de paso (opcional)
     * @return array Retorna uno o varios formularios normalizados
     */
    function obtenerFormularios($proceso_id,$id_tarea, $id_paso = NULL){
        
        //Paso uno, obtener las tareas que son de inicio
        //Trae todos los formularios del proceso, si no se especifica tarea ni paso
        $result = array();
        log_message("debug", "Busqueda de siguiente formulario: $proceso_id , $id_tarea , $id_paso", FALSE);
        if($id_tarea== NULL && $id_paso == NULL){  //traer todos los formularios
            $tramite = Doctrine::getTable('Proceso')->find($proceso_id);

            foreach($tramite->Formularios as $form){
                $json = json_decode($form->exportComplete(),true);
                array_push($result,$this->normalizarFormulario($json,$form));

            }
            return $result;
        }else{
            log_message("INFO", "Recuperando tarea: ".$id_tarea, FALSE);
            $tarea = Doctrine::getTable("Tarea")->find($id_tarea);
            
            if(!$tarea){
                log_message("error", 'Id de tarea no existe', FALSE);
                throw new ApiException("Id de Tarea no existe",404);
            }
            log_message("INFO", "Comprobando proceso id: ".$tarea->proceso_id, FALSE);
            if( $tarea->proceso_id === $proceso_id ){  //Si pertenece al proceso
                foreach($tarea->Pasos as $paso ){ //Se extraen los pasos
                    log_message('debug','recuperando pasos'.$paso->orden);
                    if( $id_paso != NULL && $paso->orden != $id_paso ){
                        continue;
                    }
                    log_message('debug','Form id: '.$paso->Formulario->id);
                    $formSimple = 
                            Doctrine::getTable('Formulario') ->find($paso->Formulario->id)->exportComplete();
                    $json = json_decode($formSimple,true);

                    array_push($result,$this->normalizarFormulario($json,$paso->Formulario));
                }
                if(empty($result)){
                    throw new ApiException("Paso $id_paso no  ha sido encontrado",404);
                }
                return $result;
            }
            
        }
        
    }
    /**
     * Obtiene directamente un formulario
     * @param type $form_id
     */
    function obtenerFormulario($form_id,$etapa_id){
        
        if($form_id === NULL){
            return NULL;
        }
        if($etapa_id == NULL){
            throw new ApiException("Etapa no puede ser null", 412);
        } 
        
        $formSimple = Doctrine::getTable('Formulario') ->find($form_id);
        
        if($formSimple == NULL){
            throw new ApiException("Formulario $form_id no existe",404);
        }
        $value_list = array();
        foreach( $formSimple->Campos as $campo ){
            $value_list[$campo->nombre] = $campo->displayDatoSeguimiento($etapa_id);
        }
 
        $data = json_decode($formSimple->exportComplete(),true);
        return $this->normalizarFormulario($data,$formSimple,$etapa_id,$value_list);
    }

    /**
     * Inicia un proceso simple
     * 
     * @param type $proceso_id
     * @param type $id_tarea
     * @param type $body
     * @return type
     */
    public function iniciarProceso($proceso_id, $id_tarea, $body, $tramite_ret_id=null, $tarea_ret_id=null){
        //validar la entrada
        
        if($proceso_id == NULL || $id_tarea == NULL){
            throw new ApiException('Parametros no validos',400);
        }

        try{
            $input = json_decode($body,true);
            log_message("DEBUG", "Input: ".$this->varDump($input), FALSE);
            //Validar entrada
            if(array_key_exists('callback',$input) && !array_key_exists('callback-id',$input)){
                throw new ApiException('Callback y callback-id son valores opcionales pero deben ir juntos',400);
            }

            log_message("DEBUG", "inicio proceso", FALSE);
            
            $tramite = new Tramite();
            $tramite->iniciar($proceso_id);
            
            log_message("INFO", "Iniciando trámite: ".$proceso_id, FALSE);

            //Esto para el caso que sea un inicio entre trámites de simple
            if(isset($tramite_ret_id) && isset($tarea_ret_id)){
                log_message("INFO", "Registrando tramite retorno: ".$tramite_ret_id, FALSE);
                log_message("INFO", "Registrando tarea retorno: ".$tarea_ret_id, FALSE);
                $this->registrarRetorno($tramite->id, $tramite_ret_id, $tarea_ret_id);
            }
            //Recuper la priemra etapa
            $etapa_id = $tramite->getEtapasActuales()->get(0)->id;

            if(array_key_exists('callback',$input)){
                $this->registrarCallbackURL($input['callback'],$input['callback-id'],$etapa_id);
            }

            //Ejecuta y guarda los campos
            $result = $this->ejecutarEntrada($etapa_id, $input, 0, $tramite->id);
            
            log_message("INFO", "Preparando respuesta: ".$proceso_id, FALSE);
            //validaciones etapa vencida, si existe o algo por el estilo
             return $result['result'];
        }catch(Exception $e){
           log_message('ERROR',$e->getMessage());
           throw new ApiException($e->getMessage(), $e->getCode());
        }

    }

    private function extractVariable($body,$campo,$tramite_id){

        try{
            if(isset($body['data'][$campo->nombre])){
                //Guardar el nombre único
                if($campo->tipo === 'file'){

                    $parts = explode(".",$body['data'][$campo->nombre]['nombre']);
                    $filename = $this->random_string(10).".". $this->random_string(2).".".
                        $this->random_string(4).".".$parts[1];
                    //$body['data'][$campo->nombre]['mime-type'];
                    //$body['data'][$campo->nombre]['content'];
                    $this->saveFile($filename,
                        $tramite_id,
                        $body['data'][$campo->nombre]['content']);
                    return $filename;//$body['data'][$campo->nombre]['nombre'];
                }else{
                    return (is_array($body['data'][$campo->nombre])) ? json_encode($body['data'][$campo->nombre]) : $body['data'][$campo->nombre];
                }
            }
            return "NE";
        }catch(Exception $e){
            log_message('error',$e->getMessage());
            throw new ApiException("Error interno", 500);
        }
    }
    /**
     *
     * @param type $etapa_id
     * @param type $body
     * @return type
     */
    public function ejecutarEntrada($etapa_id,$body, $secuencia = 0, $id_proceso){
        //throw new Exception("Etapa no pertenece al proceso ingresado", 412);
        log_message("INFO", "Ejecutar Entrada", FALSE);

        $etapa = Doctrine::getTable('Etapa')->find($etapa_id);

        if (!$etapa) {
            throw new ApiException("Etapa no fue encontrada", 404);
        }
        
        log_message("INFO", "Tramite id desde etapa: ".$etapa->tramite_id, FALSE);

        if ($etapa->tramite_id != $id_proceso) {
            throw new ApiException("Etapa no pertenece al proceso ingresado", 412);
        }
        if (!$etapa->pendiente) {
            throw new ApiException("Esta etapa ya fue completada", 412);
        }
        if (!$etapa->Tarea->activa()) {
            throw new ApiException("Esta etapa no se encuentra activa", 412);
        }
        if ($etapa->vencida()) {
            throw new ApiException("Esta etapa se encuentra vencida", 412);
        }

        try{

            //obtener el primer paso de la secuencia o el pasado por parámetro
            $paso = $etapa->getPasoEjecutable($secuencia);
            
            log_message("INFO", "Paso: ".$paso, FALSE);
 
            $next_step = null;
            if(isset($paso)){
                log_message("INFO", "Paso ejecutable nro secuencia[".$secuencia."]: ".$paso->id, FALSE);

                $etapa->iniciarPaso($paso);
                
                $formulario = $paso->Formulario;

                // Almacenamos los campos
                $campos = $formulario->Campos;
                $this->validarCamposObligatorios($body,$formulario);
                //Guardar los campos
                log_message('debug','$$$$$$  Campos'.count($campos));
                if(!$paso->getReadonly()){
                    $this->saveFields($campos,$etapa,$body);
                }

                $etapa->save();
                $etapa->finalizarPaso($paso);
                //Obtiene el siguiete paso...
                $next_step = $etapa->getPasoEjecutable($secuencia+1);
            }

            log_message("INFO", "procesar_proximo_paso: $secuencia, $next_step, $etapa, $id_proceso", FALSE);
            //Verificar si es el último paso, etonces la etapa actual esta finalizada
            $result = $this->procesar_proximo_paso($secuencia, $next_step, $etapa, $id_proceso);

        }catch(Exception $e){
            log_message('ERROR',$e->getTraceAsString());
            if($e->getCode() === 400 ){
                throw new ApiException($e->getMessage(), $e->getCode());
            }
            throw new ApiException("Error interno", 500);
        }
        return $result;

    }
    
    /**
     * Gurada los campos que vienene desde el request en la base de datos
     * 
     * @param type $campos Lista de campos del formulario
     * @param type $etapa Etapa a la que pertenece el formulario
     * @param type $body Estructura "data" -> array( key => value );
     */
    public function saveFields($campos, Etapa $etapa, $body) {
       
        foreach ($campos as $c) {
            // Almacenamos los campos que no sean readonly y que esten disponibles (que su campo dependiente se cumpla)
            log_message('debug','$$$$$$$$   registrando campo: '.$c->nombre);
            if ($c->isEditableWithCurrentPOST($etapa->id, $body)) {
                $dato = Doctrine::getTable('DatoSeguimiento')->findOneByNombreAndEtapaId($c->nombre, $etapa->id);
                if (!$dato)
                    $dato = new DatoSeguimiento();
                $dato->nombre = $c->nombre;

                $dato->valor = $this->extractVariable($body, $c, $etapa->tramite_id) === false ? '' : $this->extractVariable($body, $c, $etapa->tramite_id);
                if (!is_object($dato->valor) && !is_array($dato->valor)) {
                    if (preg_match('/^\d{4}[\/\-]\d{2}[\/\-]\d{2}$/', $dato->valor)) {
                        $dato->valor = preg_replace("/^(\d{4})[\/\-](\d{2})[\/\-](\d{2})/i", "$3-$2-$1", $dato->valor);
                    }
                }

                $dato->etapa_id = $etapa->id;
                $dato->save();
            }
        }
    }

    public function validarCamposObligatorios($body,$form){
        
        $campos = $form->getCamposEntrada();
        log_message('debug','Validando campos obligatorios: '.$this->varDump($body['data']));
        $error = false;
        $campos_faltantes = "";
        foreach($campos as $c){
            if(!key_exists($c->nombre,$body['data'])){  //si no esta el campo se valida si es obligatorio
                foreach($c->validacion as $rule ){
                    if(strpos($rule,"required")>=0){  //si conteine require entonces es obligatorio
                        $campos_faltantes[]=$c->nombre;
                        $error = true;
                    }
                }
            }
        }
        
        if($error){
            throw new ApiException('Faltan parametros de entrada obligatorios: '.  json_encode($campos_faltantes),400);
        }
        
    } 

    private function registrarCallbackURL($callback,$callback_id,$etapa){
        if($callback != NULL ){
            $dato = new DatoSeguimiento();
            $dato->nombre = "callback";
            $dato->valor = $callback; //"{ url:".$url."}";
            $dato->etapa_id = $etapa;
            $dato->save();

            $dato2 = new DatoSeguimiento();
            $dato2->nombre = "callback_id";
            $dato2->valor = $callback_id;
            $dato2->etapa_id = $etapa;
            $dato2->save();
        }
    }
    
    public static function registerUserFromHeadersClaveUnica(stdClass $body){
        
        log_message('INFO','Registrando cuenta clave unica ',FALSE);
        $user =Doctrine::getTable('Usuario')->findOneByRut($body->rut);
        
        if($user == NULL){  //Registrar el usuario
            log_message('INFO','Registrando usuario: '.$body->rut,FALSE);
            $user = new Usuario();
            $user->usuario = random_string('unique');
            $user->setPasswordWithSalt(random_string('alnum', 32));
            $user->rut = $body->rut;
            $apellidos = explode(";",$body->apellidos);
            if(count($apellidos)< 2 ){
                throw new ApiException("Credenciales incompletas",403);
            }
            $user->nombres = $body->nombres;
            $user->apellido_paterno = $apellidos[0]; 
            $user->apellido_materno = $apellidos[1];
            $user->email = $body->email;
            $user->open_id = TRUE;
            $user->save();
        }
        $CI = & get_instance();
        $CI->session->set_userdata('usuario_id', $user->id);
         
    }

    private function varDump($data){
        ob_start();
        //var_dump($data);
        print_r($data);
        $ret_val = ob_get_contents();
        ob_end_clean();
        return $ret_val;
    }
    
    public function continuarProceso($id_proceso, $id_etapa, $secuencia, $body){

        log_message("debug", "En continuar proceso, input data: ".$body);

        try{
            if(!is_numeric($secuencia) || !is_numeric($id_proceso) || !is_numeric($id_etapa)){
                $data= "proc: $id_proceso; etapa: $id_etapa; sec: $secuencia";
                throw new ApiException("Parámetros no validos-> $data",400);
            }
            $input = json_decode($body,true);

            log_message("debug", "id_etapa: ".$id_etapa);
            log_message("debug", "secuencia: ".$secuencia);

            $result = $this->ejecutarEntrada($id_etapa, $input, $secuencia, $id_proceso);

            $response = array(
                "idInstancia" => $id_proceso,
                "output" => $result ['result']['output'],
                "secuencia" => $result ['result']['secuencia'],
                "estadoProceso" => $result ['result']['estadoProceso'],
                "proximoFormulario" => $result['result']['proximoFormulario']
            );
            return $response;
        }catch(Exception $e){
            log_message('error',$e->getMessage());
            throw new ApiException($e->getMessage(), $e->getCode());
        }

    }
    
    public function asignar($etapa_id) {
        $etapa = Doctrine::getTable('Etapa')->find($etapa_id);

        if ($etapa->usuario_id) {
            echo 'Etapa ya fue asignada.';
            exit;
        }

        if (!$etapa->canUsuarioAsignarsela(UsuarioSesion::usuario()->id)) {
            echo 'Usuario no puede asignarse esta etapa.';
            exit;
        }

        $etapa->asignar(UsuarioSesion::usuario()->id);

        redirect('etapas/inbox');
    }
    
    private function getFormulariosFromEtapa($_etapas,$id_proceso){
        $forms = null;
        
        if($_etapas === NULL && !is_object($_etapas) ){
            log_message('debug',"No es un objeto");
            return NULL;
        }
        $etapas = ( !is_array($_etapas)) ? array($_etapas) : $_etapas;
        
        foreach($etapas as $etapa){ //Al menos debe retornar un valor    
            log_message('debug',"******  Etapa: ". $etapa->id);
            if(!isset( $etapa))
                continue;
            $paso = $etapa->getPasoEjecutable(0);
            //Si no hay paso en la proxima etapa, entonces se pasa a la siguiente:
            if($paso === NULL){
                $etapa->avanzar();
                $next_etapas = $this->obtenerProximaEtapa($etapa, $id_proceso);
                if($next_etapas=== NULL){
                    continue;
                }
                $ret = $this->getFormulariosFromEtapa($next_etapas,$id_proceso);
                if($ret != NULL && count($ret)>0 ){
                    $forms[] = $ret[0];
                }
                
            }else{  
                $etapa->iniciarPaso($paso);
                $forms[] = $this->obtenerFormulario($paso->formulario_id,$etapa->id);
            }  
        }
        return $forms;
    }
    
    /**
     * @param $secuencia
     * @param $next_step
     * @param $etapa
     * @param $id_proceso
     * @return mixed
     */
    private function procesar_proximo_paso($secuencia, $next_step, $etapa, $id_proceso) {        
        
        $result['result']=array();
        $result['result']['proximoFormulario']=array();
        $form_norm=array();
        $etapas = array();
        $resultCola = array();
        $forms = null;
        $estado = 'undefined';
        $etapa_id = $etapa->id;
        //Si no tienes conexiones siguientes entonces es una tarea final
        
        $secuencia = $secuencia+1;
        if($next_step == NULL){ //Si es nulo, entonces termino la etapa
            //Finlaizar etapa
            $etapa->avanzar();

            $next = $etapa->getTareasProximas();
            
            log_message("INFO", "###Id etapa despues de avanzar: ".$etapa->id, FALSE);
            $cola = new ColaContinuarTramite();
            $tareas_encoladas = $cola->findTareasEncoladas($id_proceso);
            if($next->estado === 'pendiente'){
                log_message("debug","pendiente");
                    foreach($next->tareas as $tarea){
                        log_message('debug','***** Revisando una etapa '.$tarea->id." ".$id_proceso);
                        $resultCola[] = $etapa->ejecutarColaContinuarTarea($tarea->id, $tareas_encoladas);
                        $etapas[] = $etapa->getEtapaPorTareaId($tarea->id, $id_proceso);
                    }

                log_message('debug','***** mas etapas '.count($etapas));
                    //Si no hay mas etapas, es el fin
                if(isset($resultCola) && count($resultCola) > 0 && isset($resultCola[0])){
                    log_message("debug", "Result desde cola: ".$this->varDump($resultCola), FALSE);

                    $result['result'] = $resultCola[0];

                    return $result;
                }
                if(count($etapas) > 0 ){
                        $forms = $this->getFormulariosFromEtapa($etapas,$id_proceso); //etapas sin formulario
                        if($forms === NULL){  //no tiene formualrio, etapa vacia
                            //no hay formularios, entonces avanzar al siguiente
                            log_message('debug','Contando etapas');
                            log_message('debug',''.get_class($etapas[0]));
                            log_message('debug',''.  $this->varDump(get_class_methods($etapas[0])));
                            if(count($etapas) === 1 && $etapas[0].isFinal ){
                                //Etapa vacia y final, termina
                                $forms=array();
                                $estado = 'finalizado';
                            }else if(count($etapas) === 1 && !$etapas[0].isFinal){
                                //Obtener las nuevas etapas
                            }
                        }else{
                            $estado = $this->obtenerEstadoProceso($forms, $etapas[0], $id_proceso);
                        }
                    }

            
            }else if($next->estado == 'completado'){
                log_message("debug","completado");
                $estado = 'finalizado';
            }else if($next->estado == 'standby'){
                log_message("debug","standby");
                $estado = 'standby';
            }else{
                log_message('debug',"Estado : ".$next->estado);
            }
            $secuencia = 0;  //debe resetarse el paso
        }else{
            //Procesar pasos de una misma etapa
            $paso = $etapa->getPasoEjecutable($secuencia);
            $etapa->iniciarPaso($paso);
            $forms[] = $this->obtenerFormulario($paso->formulario_id,$etapa->id);
            $estado = $this->obtenerEstadoProceso($forms, $etapa, $id_proceso, $secuencia);
        }
        
        $campos = new Campo();
        log_message("INFO", "Id etapa asignado: ".$etapa_id, FALSE);
        $result['result']['idInstancia'] = $etapa->Tramite->id;
        $result['result']['output']= $campos->obtenerResultados($etapa,$this);
        $result['result']['estadoProceso'] = $estado;
        $result['result']['secuencia'] = $secuencia;      
        $result['result']['proximoFormulario'] = ($forms===NULL) ? array(): $forms;

        
        return $result;
    }

    private function obtenerEstadoProceso($forms, $etapa, $id_proceso, $secuencia = null){
        log_message("debug", "Verificando campos en proxima etapa", FALSE);
        $tieneCamposIN = false;
        foreach ($forms[0]['form']['campos'] as $record){
            log_message("debug", "Direcccion campo: ".$record["direccion"], FALSE);
            if($record["direccion"] == 'IN'){
                $tieneCamposIN = true;
                break;
            }
        }
        log_message("debug", "Hay campos IN: ".$tieneCamposIN, FALSE);
        $estado = 'activo';
        if(!$tieneCamposIN){

            $next_step = null;
            if(isset($secuencia)){
                $next_step = $etapa->getPasoEjecutable($secuencia+1);
            }

            $next_etapa = $this->obtenerProximaEtapa($etapa, $id_proceso);
            if(!isset($next_step) && $next_etapa === NULL){
                $etapa->avanzar();
                $estado = 'finalizado';
            }
        }
        return $estado;
    }
    /**
     * 
     * @param type $etapa
     * @param type $id_proceso
     * @return type Array de etapas
     */
    private function obtenerProximaEtapa($etapa, $id_proceso){
        //Obtener la siguiente tarea
        $next = $etapa->getTareasProximas();  //Acá retorna un array

        $etapas = array();

        if(isset($next)){
            //Este puede retornar un array o un objeto
            if($next->estado != 'completado'){
                foreach($next->tareas as $tarea){
                    $etapas = $etapa->getEtapaPorTareaId($tarea->id, $id_proceso);
                }
            }else{
                log_message('debug','El tramite ha sido completado');
                return NULL;
            }
        }else{
            $etapas = null;
        }
        return $etapas;
    }

    private function registrarRetorno($tramite_id, $tramite_retorno, $retorno_id){

        $tramite = Doctrine::getTable('Tramite')->find($tramite_id);
        $etapa_id = $tramite->getEtapasActuales()->get(0)->id;

        $dato = new DatoSeguimiento();
        $dato->nombre = "tramite_retorno";
        $dato->valor = $tramite_retorno;
        $dato->etapa_id = $etapa_id;
        $dato->save();

        $dato = new DatoSeguimiento();
        $dato->nombre = "tarea_retorno";
        $dato->valor = $retorno_id;
        $dato->etapa_id = $etapa_id;
        $dato->save();
    }


}



?>
