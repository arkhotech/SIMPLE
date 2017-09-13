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
            default: return "string";
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
            throw new Exception("El formulario viene sin ID",500);
        }
        $pasos = array();
       
        $retval['form'] = array(
            'idForm' => $form->id,
            'idEtapa' => $etapa_id,  
            'campos' => array() );
       
        if($etapa_id === NULL){
            unset($retval['form']['idEtapa']);
        }
        //print_r($json);die;

//        foreach ($form->Campos as $c) {
//            // Validamos los campos que no sean readonly y que esten disponibles (que su campo dependiente se cumpla)
//            log_message("INFO", "Campo nombre: ".$c->nombre, FALSE);
// 
//            if(count($c->validacion) > 0){
//                foreach ($c->validacion as $validacion) {
//                    log_message("INFO", "Campo requerido en for: " . $validacion, FALSE);
//                    if($validacion == "required"){
//                        $valor = $this->extractVariable($body,$c,$etapa->tramite_id);
//                        log_message("INFO", "Valor para campo: " . $valor, FALSE);
//                        if($valor == "NE"){
//                            $valida_formulario = FALSE;
//                            break;
//                        }
//                    }
//                }
//            }
//        }
 
 
 
        foreach( $json['Campos'] as $campo){
            if($campo['tipo'] == "subtitle"){
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
 
            //echo $campo->nombre." ".$value_list[$campo['nombre']].". ";
            array_push($retval['form']['campos'],
 
                  array(
                    "nombre" => $campo['nombre'],
                    "tipo_control" => $campo['tipo'],
                    "tipo" => $this->mapType($campo),  //$campo['dependiente_tipo'],
                    "obligatorio" => $obligatorio,
                    "solo_lectura" => ($campo['readonly']==0) ? false : true,
                    "dominio_valores" => ($this->mapType($campo) == "grid") ? $campo["extra"] :$campo['datos'],
                    "valor" => ($value_list!=NULL) ? $value_list[$campo['nombre']] : "")//($campo['valor'] == NULL) ? $campo['valor_default'] : $campo['valor'])
                
                    );
                 
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
        log_message("INFO", "Busqueda de siguiente formulario", FALSE);
        if($id_tarea== NULL && $id_paso == NULL){  //traer todos los formularios
            $tramite = Doctrine::getTable('Proceso')->find($proceso_id);

            foreach($tramite->Formularios as $form){
                
                //$formSimple = $form;//Doctrine::getTable('Formulario')->find($form->id);
                $json = json_decode($form->exportComplete(),true);
                array_push($result,$this->c($json,$form));

            }
            return $result;
        }else{
            log_message("INFO", "Recuperando tarea: ".$id_tarea, FALSE);
            $tarea = Doctrine::getTable("Tarea")->find($id_tarea);
            log_message("INFO", "Comprobando proceso id: ".$tarea->proceso_id, FALSE);
            if( $tarea->proceso_id === $proceso_id ){  //Si pertenece al proceso
                foreach($tarea->Pasos as $paso ){ //Se extraen los pasos
                    //print_r($paso);
                    if( $id_paso != NULL && $paso->Formulario->id != $id_paso ){
                        continue;
                    }
                    $formSimple = 
                            Doctrine::getTable('Formulario') ->find($paso->Formulario->id)->exportComplete();
                    $json = json_decode($formSimple,true);
                    log_message("INFO", "Json formulario: ".$json, FALSE);
                    array_push($result,$this->normalizarFormulario($json,$paso->Formulario));
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
            throw new Exception("Etapa no puede ser null");
        } 
        
        $formSimple = Doctrine::getTable('Formulario') ->find($form_id);
        
        if($formSimple == NULL){
            throw new Exception("Fomrulario $form_id no existe");
        }
        $value_list = array();
        foreach( $formSimple->Campos as $campo ){
            $value_list[$campo->nombre] = $campo->displayDatoSeguimiento($etapa_id);
        }
 
        $data = json_decode($formSimple->exportComplete(),true);
        return $this->normalizarFormulario($data,$formSimple,$etapa_id,$value_list);
    }

    /**
     * @param $formulario array con campos del formulario de entrada para iniciar el proceso
     * @return string
     */
    function generar_swagger($formulario, $id_tramite, $id_tarea){

        //log_message("info", "Input Generar Swagger: ".$this->varDump($formulario), FALSE);
        log_message("info", "Id trámite: ".$id_tramite, FALSE);
        log_message("info", "Id tarea: ".$id_tarea, FALSE);

        if(isset($formulario) && count($formulario) > 0){
            //log_message("info", "Formulario recuperado: ".$this->varDump($formulario), FALSE);
            $data_entrada = "";
            $form = $formulario[0];
            $campos = $form["form"];
            foreach($campos["campos"] as $campo){
                //Campo tipo file será tratado como string asumiendo que el archivo viene en base64
                if($campo["tipo"] == "string" || $campo["tipo"] == "base64"){
                    if($data_entrada != "") $data_entrada .= ",";
                    $data_entrada .= "\"".$campo["nombre"]."\": {\"type\": \"string\"}";
                }else if($campo["tipo_control"] == "checkbox"){
                    if($data_entrada != "") $data_entrada .= ",";
                    $data_entrada .= "\"".$campo["nombre"]."\": {\"type\": \"array\",\"items\": {\"type\": \"string\"}}";
                }else if($campo["tipo"] == "date"){
                    if($data_entrada != "") $data_entrada .= ",";
                    $data_entrada .= "\"".$campo["nombre"]."\": {\"type\": \"string\",\"format\": \"date\"}";
                }else if($campo["tipo"] == "grid"){

                    if($data_entrada != "") $data_entrada .= ",";

                    $data_entrada .= "";

                    $columnas = array();
                    $columnas = $campo["dominio_valores"];

                    $nombres_columnas = "";
                    foreach ($columnas["columns"] as $column){
                        if($nombres_columnas != "") $nombres_columnas .= ",";
                        $nombres_columnas .= "'".$column["header"]."'";
                    }

                    $data_entrada .= "\"".$campo["nombre"]."\": {
                    \"description\": \"Formato de arreglo\n\nPrimera fila corresponde a nombres de columnas, los cuales son: [".$nombres_columnas."]\n\nFilas siguientes corresponden a los valores\nEjemplo:\n[\n  [nombre_columna_1, nombre_columna_2, .., nombre_columna_N],\n  [valor_1, valor_2, .., valor_N], .., [valor_1, valor_2, .., valor_N]\n]\n\",
                    \"type\": \"array\",\"items\": {\"type\": \"array\",\"items\": {\"type\": \"string\"}},
                    \"default\": \"[[".$nombres_columnas."]]\",";

                    $data_entrada .= "\"minItems\": 1,";
                    $data_entrada .= "\"maxItems\": ".count($columnas["columns"])."}";

                }
            }
        }

        $swagger = "";

        $nombre_host = gethostname();
        //($_SERVER['HTTPS'] ? $protocol = 'https://' : $protocol = 'http://');

        log_message("info", "HOST: ".$nombre_host, FALSE);

        if ($file = fopen("uploads/swagger/start_swagger.json", "r")) {
            log_message("debug", "Formulario recuperado", FALSE);
            while(!feof($file)) {
                $line = fgets($file);
                $line = str_replace("-DATA_ENTRADA-", $data_entrada, $line);
                $line = str_replace("-HOST-", $nombre_host, $line);
                $line = str_replace("-id_tramite-", $id_tramite, $line);
                $line = str_replace("-id_tarea-", $id_tarea, $line);
                $swagger .= $line;
            }
            fclose($file);
        }

        return $swagger;

    }
    /**
     * Inicia un proceso simple
     * 
     * @param type $proceso_id
     * @param type $id_tarea
     * @param type $body
     * @return type
     */
    public function iniciarProceso($proceso_id, $id_tarea, $body){
        //validar la entrada
        
        if($proceso_id == NULL || $id_tarea == NULL){
            throw new Exception('Parametros no validos',400);
        }

        try{
            $input = json_decode($body,true);
            log_message("DEBUG", "Input: ".$this->varDump($input), FALSE);
            //Validar entrada
            if(array_key_exists('callback',$input) && !array_key_exists('callback-id',$input)){
                throw new Exception('Callback y callback-id son valores opcionales pero deben ir juntos',400);
            }

            log_message("DEBUG", "inicio proceso", FALSE);
            
            $tramite = new Tramite();
            $tramite->iniciar($proceso_id);
            
            log_message("INFO", "Iniciando trámite: ".$proceso_id, FALSE);
            //Recuper la priemra etapa
            $etapa_id = $tramite->getEtapasActuales()->get(0)->id;
            //Ejecuta y guarda los campos
            $result = $this->ejecutarEntrada($etapa_id, $input, 0, $tramite->id);
            
            if(array_key_exists('callback',$input)){
                $this->registrarCallbackURL($input['callback'],$input['callback-id'],$etapa_id);
            }
            log_message("INFO", "Preparando respuesta: ".$proceso_id, FALSE);
            //validaciones etapa vencida, si existe o algo por el estilo
             return $result['result'];
        }catch(Exception $e){
           log_message('ERROR',$e->getMessage());
           throw $e;
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
            throw new Exception("Error interno", 500);
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
            throw new Exception("Etapa no fue encontrada", 404);
        }
        
        $respuesta = new stdClass();
        $validar_formulario = FALSE;
        // Almacenamos los campos

        log_message("INFO", "Tramite id desde etapa: ".$etapa->tramite_id, FALSE);

        
        if ($etapa->tramite_id != $id_proceso) {
            throw new Exception("Etapa no pertenece al proceso ingresado", 412);
        }
        if (!$etapa->pendiente) {
            throw new Exception("Esta etapa ya fue completada", 412);
        }
        if (!$etapa->Tarea->activa()) {
            throw new Exception("Esta etapa no se encuentra activa", 412);
        }
        if ($etapa->vencida()) {
            throw new Exception("Esta etapa se encuentra vencida", 412);
        }
        AuditoriaOperaciones::registrarAuditoria($etapa->Tarea->Proceso->nombre, "Iniciar Proceso", "Audoitoria", $body);

        try{
            //obtener el primer paso de la secuencia o el pasado por parámetro
            $paso = $etapa->getPasoEjecutable($secuencia);
            
            log_message("INFO", "Paso: ".$paso, FALSE);
 
            $next_step = null;
            if(isset($paso)){
                log_message("INFO", "Paso ejecutable nro secuencia[".$secuencia."]: ".$paso->id, FALSE);

                $etapa->iniciarPaso($paso);
                
                $formulario = $paso->Formulario;

                $respuesta = new stdClass();
                $validar_formulario = FALSE;
                // Almacenamos los campos
                $this->validarCamposObligatorios($body,$formulario->Campos);
                //Guardar los campos
                $this->saveFields($formulario->Campos,$etapa,$body);
                
                $etapa->save();
                $etapa->finalizarPaso($paso);
                //Obtiene el siguiete paso...
                $next_step = $etapa->getPasoEjecutable($secuencia+1);
            }

            log_message("INFO", "procesar_proximo_paso: $secuencia, $next_step, $etapa, $id_proceso", FALSE);
            //Verificar si es el último paso, etonces la etapa actual esta finalizada
            $result = $this->procesar_proximo_paso($secuencia, $next_step, $etapa, $id_proceso);

            //log_message("DEBUG", "Result: ".$this->varDump($result), FALSE);

        }catch(Exception $e){
            log_message('ERROR',$e->getTraceAsString());
            throw new Exception("Error interno", 500);
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
    public function saveFields($campos, $etapa, $body) {
       
        foreach ($campos as $c) {
            // Almacenamos los campos que no sean readonly y que esten disponibles (que su campo dependiente se cumpla)

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

    public function validarCamposObligatorios($body,$campos){
        log_message('DEBUG',"Revisando campos: ".$campos);
        foreach($campos as $c){
            if(!isset($body['data'][$c->nombre])){  //si no esta el campo se valida si es obligatorio
                foreach($c->validacion as $rule ){
                    if($rule === "required"){
                        throw new Exception('Faltan parametros de entrada obligatorios',400);
                    }
                }
            }
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
    
    public function registerUserFromHeadersClaveUnica($headers){
        log_message('INFO','Registrando cuenta clave unica ',FALSE);
        $user =Doctrine::getTable('Usuario')->findOneByRut($headers['Rut']);
        
        if($user == NULL){  //Registrar el usuario
            log_message('INFO','Registrando usuario: '.$headers['Rut'],FALSE);
            $user = new Usuario();
            $user->usuario = random_string('unique');
            $user->setPasswordWithSalt(random_string('alnum', 32));
            $user->rut = $headers['Rut'];
            $nombres = explode(";",$headers['Nombres']);
            if(count($nombres)< 3 ){
                throw new Exception("Credenciales incompletas",403);
//                header("HTTP/1.1 403 Forbiden. Credenciales incompletas");
//                exit;
            }
            $user->nombres = $nombres[0];//$headers['Nombres'];
            $user->apellido_paterno = $nombres[1]; //$headers['Apellido-Paterno'];
            $user->apellido_materno = $nombres[2];//$headers['Apellido-Materno'];
            $user->email = $headers['Email'];
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
    
    public function continuarProceso($id_proceso,$id_etapa,$secuencia, $body){

        log_message("INFO", "En continuar proceso, input data: ".$body);

        try{
            $input = json_decode($body,true);

            if($id_etapa == NULL || $id_secuencia=NULL ){
                header("HTTP/1.1 400 Bad Request");
                return;
            }
            //Obtener el nombre del proceso

            log_message("INFO", "id_etapa: ".$id_etapa);
            log_message("INFO", "secuencia: ".$secuencia);

            $result = $this->ejecutarEntrada($id_etapa, $input, $secuencia, $id_proceso);

            $response = array(
                "idInstancia" => $id_proceso,
                "output" => $result ['result']['output'],
                "secuencia" => $result ['result']['secuencia'],
                "estadoProceso" => $result ['result']['estadoProceso'],
                "proximoFormulario" => $result['result']['proximoFormulario']
            );
            //$this->responseJson($response);
            return $response;
        }catch(Exception $e){
            log_message('error',$e->getMessage());
            throw $e;
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
        
        if($_etapas === NULL && !is_object($etapa) ){
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
                $ret = $this->getFormulariosFromEtapa($next_etapas,$id_proceso);
                if($ret != NULL && count($ret)>0 ){
                    $forms[] = $ret[0];
                }
                
            }else{   
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
        $forms = null;
        $etapa_id = $etapa->id;
        //Si no tienes conexiones siguientes entonces es una tarea final
        
        $secuencia = $secuencia+1;
        if($next_step == NULL){ //Si es nulo, entonces termino la etapa
            //Finlaizar etapa
            $etapa->avanzar();
            $next = $etapa->getTareasProximas();
            
            log_message("INFO", "###Id etapa despues de avanzar: ".$etapa->id, FALSE);
            if($next->estado === 'pendiente'){
                log_message("debug","pendiente");
                foreach($next->tareas as $tarea){
                    log_message('debug','***** Revisando una etapa '.$tarea->id." ".$id_proceso);
                    $etapas[] = $etapa->getEtapaPorTareaId($tarea->id, $id_proceso);
                }
                
                $forms[] = $this->getFormulariosFromEtapa($etapas,$id_proceso);
                $estado = 'activo';
            }else if($next->estado == 'completado'){
                log_message("debug","completado");
                $estado = 'finalizado';
            }else if($next->estado == 'standby'){  //Etapas paralelas pendientes
                log_message("debug","standby");
                $estado = 'standby';
            }

        }else{
            
//            $paso = $etapa->getPasoEjecutable($secuencia);
//            $form_norm = $this->obtenerFormulario($paso->formulario_id,$etapa->id);
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
                log_message('debug','El trmite ha sido completado');
                return $etapas;
            }
        }else{
            $etapas = null;
        }
        return $etapas;
    }

    
}



?>
