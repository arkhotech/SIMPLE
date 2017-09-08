<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

//Esta funcion debería estar en modelo

class FormNormalizer{
    
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
   
    private function getValueDomain($campo=NULL){
        
        return "0";
    }
    /**
     * 
     * @param type $json
     * @param type $id
     * @param type $value_list Valores de los campos a exportar
     * @return type
     * @throws Exception
     */        
    function normalizarFormulario($json,$form,$value_list=NULL){
        
        if($form==NULL){
            throw new Exception("El formulario viene sin ID");
        }
        $pasos = array();
        //$form->Proceso->id;
//        getEtapaPorTareaId($id_tarea, $id_proceso)
        
//         $conexiones=  Doctrine_Query::create()
//                ->from('Conexion c, c.TareaOrigen.Proceso p')
//                ->where('p.activo=1 AND p.id = ?',$this->id)
//                ->execute();
       
        $retval['form'] = array('id' => $form->id, 'campos' => array() );
        //print_r($json);die;




        foreach ($formulario->Campos as $c) {
            // Validamos los campos que no sean readonly y que esten disponibles (que su campo dependiente se cumpla)
            log_message("INFO", "Campo nombre: ".$c->nombre, FALSE);

            if(count($c->validacion) > 0){
                foreach ($c->validacion as $validacion) {
                    log_message("INFO", "Campo requerido en for: " . $validacion, FALSE);
                    if($validacion == "required"){
                        $valor = $this->extractVariable($body,$c,$etapa->tramite_id);
                        log_message("INFO", "Valor para campo: " . $valor, FALSE);
                        if($valor == "NE"){
                            $valida_formulario = FALSE;
                            break;
                        }
                    }
                }
            }
        }



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
                array_push($result,$this->normalizarFormulario($json,$form));

            }
            return $result;
        }else{
            log_message("INFO", "Recuperando tarea: ".$id_tarea, FALSE);
            $tarea = Doctrine::getTable("Tarea")->find($id_tarea);
            log_message("INFO", "Comprobando proceso id: ".$tarea->proceso_id, FALSE);
            if( $tarea->proceso_id === $proceso_id ){  //Si pertenece al proceso
                foreach($tarea->Pasos as $paso ){ //Se extraen los pasos
                    //print_r($paso);
                    if( $id_paso != NULL && $paso->paso->Formulario->id != $id_paso ){
                        continue;
                    }
                    $formSimple = 
                            Doctrine::getTable('Formulario') ->find($paso->Formulario->id)->exportComplete();
                    $json = json_decode($formSimple,true);
                    array_push($result,$this->normalizarFormulario($json,$paso->Formulario->id));
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
        
        $formSimple = Doctrine::getTable('Formulario') ->find($form_id);
        if($etapa_id == NULL){
            throw new Exception("El identificador de etapa no puede ser nulo");
        } 
        if($formSimple == NULL){
            throw new Exception("Fomrulario $form_id no existe");
        }
        $value_list = array();
        foreach( $formSimple->Campos as $campo ){
            $value_list[$campo->nombre] = $campo->displayDatoSeguimiento($etapa_id);
            
            //echo $campo->displayDatoSeguimiento($etapa_id);
            //echo $campo->nombre.":".$campo->dato.",";
        }
 
        $data = json_decode($formSimple->exportComplete(),true);
        return $this->normalizarFormulario($data,$form_id,$value_list);
    }

    /**
     * @param $formulario array con campos del formulario de entrada para iniciar el proceso
     * @return string
     */
    function generar_swagger($formulario, $id_tramite, $id_tarea){

        log_message("info", "Generando Swagger", FALSE);
        log_message("info", "Id trámite: ".$id_tramite, FALSE);
        log_message("info", "Id tarea: ".$id_tarea, FALSE);

        if(isset($formulario) && count($formulario) > 0){
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








    public function iniciarProceso($proceso_id, $tramite_id, $retorno_id, $body){

        log_message("INFO", "idProcess: ".$proceso_id, FALSE);
        log_message("INFO", "Body: ".$body, FALSE);

        if($proceso_id == NULL){
            throw new Exception("Bad Request", 400);
        }

        try{
            $input = json_decode($body,true);
            log_message("INFO", "Input: ".$this->varDump($input), FALSE);
            //Validar entrada
            /*if(array_key_exists('callback',$input) && !array_key_exists('callback-id',$input)){
                throw new Exception("Bad Request", 400);
            }*/

            log_message("INFO", "inicio proceso", FALSE);

            UsuarioSesion::login('admin@admin.com', '123456');

            log_message("INFO", "carga libreria", FALSE);
            $CI = & get_instance();
            $CI->load->library('SaferEval');

            log_message("INFO", "inicia tramite", FALSE);
            $tramite = new Tramite();
            $tramite->iniciar($proceso_id);

            log_message("INFO", "Iniciando trámite: ".$proceso_id, FALSE);

            $etapa_id = $tramite->getEtapasActuales()->get(0)->id;
            $result = $this->ejecutarEntrada($etapa_id, $input, 0, $tramite->id);

            log_message("INFO", "Terminando inicio proceso: ".$this->varDump($result));

            log_message("INFO", "Registrando tramite: ".$tramite_id, FALSE);
            log_message("INFO", "Registrando tarea: ".$retorno_id, FALSE);
            $this->registrarRetorno($tramite_id, $retorno_id,$etapa_id);

            /*if(array_key_exists('callback',$input)){
                $this->registrarCallbackURL($input['callback'],$input['callback-id'],$etapa_id);
            }*/

            $response = array(
                "idInstancia" => $tramite->id,
                "output" => $result ['result']['output'],
                "idEtapa" => $result ['result']['idEtapa'],
                "secuencia" => $result ['result']['secuencia'],
                "proximoFormulario" => $result['result']['proximoForlulario']
            );

            log_message("INFO", "Respuesta inicio proceso: ".$this->varDump($response));

            $response = json_encode($response);

            log_message("INFO", "Respuesta json inicio proceso: ".$response);

            return $response;

        }catch(Exception $e){
            throw new Exception($e->getMessage(), $e->getCode());
        }

    }

    public function continuarProceso($id_tramite_actual, $id_tramite_continuar, $id_tarea, $body){

        log_message("INFO", "En continuar proceso, input data: ".$body);

        try{
            $input = json_decode($body,true);

            /*if(!isset($input["idEtapa"]) || !isset($input["secuencia"])){
                throw new Exception("Bad Request", 400);
            }*/
            //Obtener el nombre del proceso
            //$this->crearRegistroAuditoria($nombre_proceso, $body);
            //$id_etapa = $input["idEtapa"];
            $secuencia = 0;//Se asume que siempre será el Paso 1

            log_message("INFO", "id_tramite_actual: ".$id_tramite_actual);
            log_message("INFO", "id_tramite_continuar: ".$id_tramite_continuar);
            log_message("INFO", "id_tarea: ".$id_tarea);
            log_message("INFO", "secuencia: ".$secuencia);

            $etapa = new Etapa();
            $etapa = $etapa->getEtapaPorTareaId($id_tarea, $id_tramite_continuar);
            log_message("INFO", "id_etapa a continuar: ".$etapa->id);

            $result = $this->ejecutarEntrada($etapa->id, $input, $secuencia, $id_tramite_continuar);

            $response = array(
                "idInstancia" => $id_tramite_actual,
                "idEtapa" => $result ['result']['idEtapa'],
                "output" => $result ['result']['output']
            );

            log_message("INFO", "Respuesta continuar proceso: ".$this->varDump($response));

            $response = json_encode($response);

            log_message("INFO", "Respuesta json continuar proceso: ".$response);

            return $response;
        }catch(Exception $e){
            throw new Exception($e->getMessage(), $e->getCode());
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
            throw new Exception($e->getMessage(), 500);
        }
    }
    /**
     *
     * @param type $etapa_id
     * @param type $body
     * @return type
     */
    public function ejecutarEntrada($etapa_id,$body, $secuencia = 0, $id_proceso){

        log_message("INFO", "Ejecutar Entrada", FALSE);

        $etapa = Doctrine::getTable('Etapa')->find($etapa_id);

        $respuesta = new stdClass();
        $validar_formulario = FALSE;
        // Almacenamos los campos

        log_message("INFO", "Tramite id desde etapa: ".$etapa->tramite_id, FALSE);

        if (!$etapa) {
            throw new Exception("Etapa no fue encontrada", 404);
        }
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

        $this->crearRegistroAuditoria($etapa->Tarea->Proceso->nombre,$body);

        try{
            //obtener el primer paso de la secuencia o el pasado por parámetro
            $paso = $etapa->getPasoEjecutable($secuencia);

            log_message("INFO", "Paso: ".$paso, FALSE);
            log_message("INFO", "Paso ejecutable nro secuencia[".$secuencia."]: ".$paso->id, FALSE);

            $next_step = null;
            if(isset($paso)){
                $formulario = $paso->Formulario;
                $modo = $paso->modo;

                //TODO validar campos de formulario
                $respuesta = new stdClass();
                $validar_formulario = FALSE;
                // Almacenamos los campos

                foreach ($formulario->Campos as $c) {
                    // Almacenamos los campos que no sean readonly y que esten disponibles (que su campo dependiente se cumpla)

                    if ($c->isEditableWithCurrentPOST($etapa_id,$body)) {
                        $dato = Doctrine::getTable('DatoSeguimiento')->findOneByNombreAndEtapaId($c->nombre, $etapa->id);
                        if (!$dato)
                            $dato = new DatoSeguimiento();
                        $dato->nombre = $c->nombre;

                        $dato->valor = $this->extractVariable($body,$c,$etapa->tramite_id)=== false?'' :  $this->extractVariable($body,$c,$etapa->tramite_id);
                        if (!is_object($dato->valor) && !is_array($dato->valor)){
                            if (preg_match('/^\d{4}[\/\-]\d{2}[\/\-]\d{2}$/', $dato->valor)) {
                                $dato->valor=preg_replace("/^(\d{4})[\/\-](\d{2})[\/\-](\d{2})/i", "$3-$2-$1", $dato->valor);
                            }
                        }

                        $dato->etapa_id = $etapa->id;
                        $dato->save();
                    }

                }
                $etapa->save();
                $etapa->finalizarPaso($paso);
                //Obtiene el siguiete paso
                $next_step = $etapa->getPasoEjecutable($secuencia+1);
            }

            log_message("INFO", "procesar_proximo_paso: $secuencia, $next_step, $etapa, $id_proceso", FALSE);
            $result = $this->procesar_proximo_paso($secuencia, $next_step, $etapa, $id_proceso);

            log_message("INFO", "Result: ".$this->varDump($result), FALSE);

        }catch(Exception $e){
            throw new Exception($e->getMessage(), 500);
        }
        return $result;

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

    private function registrarRetorno($tramite_id, $retorno_id,$etapa){
        $dato = new DatoSeguimiento();
        $dato->nombre = "tramite_retorno";
        $dato->valor = $tramite_id;
        $dato->etapa_id = $etapa;
        $dato->save();

        $dato = new DatoSeguimiento();
        $dato->nombre = "tarea_retorno";
        $dato->valor = $retorno_id;
        $dato->etapa_id = $etapa;
        $dato->save();
    }

    /**
     * @param $secuencia
     * @param $next_step
     * @param $etapa
     * @param $id_proceso
     * @return mixed
     */
    private function procesar_proximo_paso($secuencia, $next_step, $etapa, $id_proceso) {

        try{
            $result['result']=array();
            $result['result']['proximoForlulario']=array();
            $form_norm=array();

            $etapa_id = $etapa->id;

            $integrador = new FormNormalizer();
            $secuencia = $secuencia+1;
            if($next_step == NULL){
                //Finlaizar etapa
                $etapa->avanzar();
                log_message("INFO", "Id etapa despues de avanzar: ".$etapa->id, FALSE);

                $etapa_prox = $this->obtenerProximaEtapa($etapa, $id_proceso);
                if(isset($etapa_prox) && count($etapa_prox) == 1){
                    $next_step = $etapa_prox[0]->getPasoEjecutable(0);
                    while($next_step == null){
                        //Finlaizar etapa
                        $etapa_prox[0]->avanzar();

                        $etapa_prox = $this->obtenerProximaEtapa($etapa_prox[0], $id_proceso);

                        if(!isset($etapa_prox))
                            break;

                        $next_step = $etapa_prox[0]->getPasoEjecutable(0);
                    }

                    $form_norm = $integrador->obtenerFormulario($next_step->formulario_id,$etapa->id);

                    $etapa_id = $etapa_prox[0]->id;
                    $secuencia = 1;

                }else if(isset($etapa_prox) && count($etapa_prox) > 1){
                    //TODO tareas en paralelo
                    $secuencia = null;
                }else{
                    //No existen mas etapas
                    //Pendiente definir comportamiento standby
                    $secuencia = null;
                }

            }else{

                $paso = $etapa->getPasoEjecutable($secuencia);
                $form_norm = $integrador->obtenerFormulario($paso->formulario_id,$etapa->id);
            }

            $campos = new Campo();
            log_message("INFO", "Id etapa asignado: ".$etapa_id, FALSE);
            $result['result']['proximoForlulario'] = $form_norm;
            $result['result']['idEtapa'] = $etapa_id;
            $result['result']['secuencia'] = $secuencia;
            $result['result']['output']= $campos->obtenerResultados($etapa,$this);

            log_message("INFO", "resultado: ".$this->varDump($result), FALSE);

            return $result;
        }catch(Exception $e){
            throw new Exception($e->getMessage(), 500);
        }
    }

    private function obtenerProximaEtapa($etapa, $id_proceso){

        try{
            //Obtener la siguiente tarea
            $next = $etapa->getTareasProximas();

            $etapas = array();

            if(isset($next)){
                if($next->estado != 'completado'){
                    if ($next->tipo == 'paralelo' || $next->tipo == 'paralelo_evaluacion') {
                        //etapas en paralelo
                        foreach($next->tareas as $tarea ){
                            $etapa_prox = $etapa->getEtapaPorTareaId($tarea->id, $id_proceso);
                            $etapas[] = $etapa_prox;
                        }
                    }else if ($next->tipo == 'union') {
                        if ($next->estado == 'standby') {
                            //Esperar, enviar respuesta informando que se debe esperar
                            $etapas = null;
                        }
                    }else{

                        $tarea_id = $next->tareas[0]->id;
                        $etapa_prox = $etapa->getEtapaPorTareaId($tarea_id, $id_proceso);

                        $etapas[] = $etapa_prox;

                    }
                }else{
                    $etapas = null;
                }
            }else{
                $etapas = null;
            }
        }catch(Exception $e){
            throw new Exception($e->getMessage(), 500);
        }
        return $etapas;
    }

    private function crearRegistroAuditoria($nombre_proceso,$body,$tipo = "INFO"){

        try{
            $CI = & get_instance();
            $headers = $CI->input->request_headers();
            $new_headers = array('host' => $headers['Host'],
                'Origin' => $headers['Origin'],
                'largo-mensaje' => $headers['Content-Length'],
                'Content-type' => $headers['Content-type']);

            $data['headers'] = $new_headers;
            $data['input'] = $body['data'];

            /*if(array_key_exists('callback', $body)){
                $data['response_data'] =
                    array("Callback url" => $body['callback'],
                        "Callback id" => $body['callback-id']);
            }*/

            $this->registrarAuditoria($nombre_proceso,"Iniciar Proceso" ,
                $tipo.': Auditoría de llamados desde otro proceso SIMPLE',  json_encode($data));
        }catch(Exception $e){
            throw new Exception($e->getMessage(), 500);
        }
    }

    private function registrarAuditoria($proceso_nombre,$operacion, $motivo, $detalles){
        try{
            $fecha = new DateTime();
            $registro_auditoria = new AuditoriaOperaciones ();
            $registro_auditoria->fecha = $fecha->format ( "Y-m-d H:i:s" );
            $registro_auditoria->operacion = $operacion;
            $usuario = UsuarioBackendSesion::usuario ();
            // Se necesita cambiar el usuario al usuario público.
            $registro_auditoria->usuario = 'Admin Admin <admin@admin.com>';
            $registro_auditoria->proceso = $proceso_nombre;
            $registro_auditoria->cuenta_id = 1;
            $registro_auditoria->motivo = $motivo;

            //unset($accion_array['accion']['proceso_id']);
            $registro_auditoria->detalles= 'Detalles';
            $registro_auditoria->detalles=  $detalles;//json_encode($accion_array);
            $registro_auditoria->save();
        }catch(Exception $e){
            throw new Exception($e->getMessage(), 500);
        }
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

?>