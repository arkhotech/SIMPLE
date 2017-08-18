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
    
    private function getBody(){
        return file_get_contents('php://input');
    }
    
    /*
     * Llamadas de la API
     * Tramote id es el identificador del proceso
     */
    
    public function especificacion($operacion ,$id_tramite,$id_tarea = NULL,$id_paso = NULL){
        
        //Cheque que la URL se complete correctamente
        if($operacion!= "servicio" && $operacion!= "form"){
            echo "$operacion";die;
            show_error("404 No encontrado",404, "La operación no existe" );
            exit;
        }
         
        switch($this->input->server('REQUEST_METHOD')){
            case "GET": 
                $this->generarEspecificacion($operacion,$id_tramite,$id_tarea,$id_paso);
                break;
            default:
                show_error("405 Metodo no permitido",405, "El metodo no esta implementado" );
        }
        
    }
    /**
     * 
     * @param type $tipo 
     * @param type $id_tramite
     * @param type $id_paso
     */
    public function status($tipo,$id_tramite, $rut ){
        
        if($tipo!= "tramite" ){
            show_error("404 No encontrado",404, "No se encuentra la operacion" );
            exit;
        }
        
        if($rut == NULL || $id_tramite == NULL ){
            show_error("400 Bad Request",400, "Uno de los parametros de entrada no ha sido especificado" );
        }
        
        switch($this->input->server('REQUEST_METHOD')){
            case "GET": 
                $this->obtenerStatus($id_tramite,$rut);
                break;
            default:
                header("HTTP/1.1 405 Metodo no permitido.");
        }
    }
    
    private function checkJsonHeader(){
        $headers = $this->input->request_headers();
        if($headers['Content-Type']==NULL || $headers['Content-Type']!="application/json"){
            //show_error("415 Unsupported Media Type",415, "Se espera application/json" );
            header("HTTP/1.1 415 Unsupported Media Type. Solo se permite application/json");
        }
    }
    
    public function tramites($proceso_id, $etapa = null) {
        
        //Tomar los segmentos desde el 3 para adelante
        //$urlSegment = $this->uri->segments;
        
        //$headers = $this->input->request_headers();
        
        //print_r($urlSegment);
        //$cuenta = Cuenta::cuentaSegunDominio();

        switch($method = $this->input->server('REQUEST_METHOD')){
            case "GET":
                $this->listarCatalogo();
                break;
            case "PUT":
                $this->checkJsonHeader();
                $this->continuarProceso($proceso_id,$this->getBody());
                break;
            case "POST":
                log_message("INFO", "inicio proceso", FALSE);
                $this->checkJsonHeader();
                $this->iniciarProceso($proceso_id,$etapa,$this->getBody());
                break;
            default:
                header("HTTP/1.1 405 Metodo no permitido");
        }
        
    }

    
    private function listarCatalogo(){
        $tarea=Doctrine::getTable('Proceso')->findProcesosExpuestos(UsuarioBackendSesion::usuario()->cuenta_id);
        $result = array();
        $nombre_host = gethostname();
        ($_SERVER['HTTPS'] ? $protocol = 'https://' : $protocol = 'http://');
        foreach($tarea as $res ){
            array_push($result, array(
                "id" => $res['id'],
                "nombre" => $res['nombre'],
                "tarea" => $res['tarea'],
                "version" => "1.0",
                "institucion" => "N/I",
                "descripcion" => $res['previsualizacion'],
                "URL" => $protocol.$nombre_host.'/integracion/api/especificacion/servicio/'.$res['id'].'/'.$res['id_tarea']
            )); 
        }   
       $retval["catalogo"] = $result; 
       header('Content-type: application/json');
       echo json_indent(json_encode($retval));
       exit;
    }
    
    private function iniciarProceso($proceso_id, $id_tarea, $body){
        //validar la entrada
        
        if($proceso_id == NULL || $id_tarea == NULL){
            header("HTTP/1.1 400 Bad Request");
            return;
        }
        
        try{ 
            $input = json_decode($body,true);
            log_message("INFO", "Input: ".$this->varDump($input), FALSE);
            //Validar entrada
            if(array_key_exists('callback',$input) && !array_key_exists('callback-id',$input)){
                header("HTTP/1.1 400 Bad Request");
                return;
            }

            log_message("INFO", "inicio proceso", FALSE);
            
            UsuarioSesion::login('admin@admin.com', '123456');

            log_message("INFO", "carga libreria", FALSE);
            $this->load->library('SaferEval');

            log_message("INFO", "inicia tramite", FALSE);
            $tramite = new Tramite();
            $tramite->iniciar($proceso_id);
            
            log_message("INFO", "Iniciando trámite: ".$proceso_id, FALSE);

            $etapa_id = $tramite->getEtapasActuales()->get(0)->id;
            $result = $this->ejecutarEntrada($etapa_id, $input, 0, $tramite->id);
                        
            $this->registrarCallbackURL($input['callback'],$input['callback-id'],$etapa_id);

            //validaciones etapa vencida, si existe o algo por el estilo

             $response = array(
                "idInstancia" => $tramite->id,
                "output" => $result ['result']['output'],
                 "idEtapa" => $result ['result']['idEtapa'],
                 "secuencia" => $result ['result']['secuencia'],
                "proximoFormulario" => $result['result']['proximoForlulario']
                );
             $this->responseJson($response);
        }catch(Exception $e){
           $e->getTrace();
        }
      
    }
    
    private function continuarProceso($id_proceso, $body){

        log_message("INFO", "En continuar proceso, input data: ".$body);

        try{
            $input = json_decode($body,true);

            if(!isset($input["idEtapa"]) || !isset($input["secuencia"])){
                header("HTTP/1.1 400 Bad Request");
                return;
            }

            $id_etapa = $input["idEtapa"];
            $secuencia = $input["secuencia"];

            log_message("INFO", "id_etapa: ".$id_etapa);
            log_message("INFO", "secuencia: ".$secuencia);

            $result = $this->ejecutarEntrada($id_etapa, $input, $secuencia, $id_proceso);

            $response = array(
                "idInstancia" => $id_proceso,
                "output" => $result ['result']['output'],
                "idEtapa" => $result ['result']['idEtapa'],
                "secuencia" => $result ['result']['secuencia'],
                "proximoFormulario" => $result['result']['proximoForlulario']
            );
            $this->responseJson($response);
        }catch(Exception $e){
            $e->getTrace();
        }

    }
    
    private function generarEspecificacion($operacion,$id_tramite=NULL,$id_tarea=NULL,$id_paso = NULL){
        
        if($operacion === "form"){
            $integrador = new FormNormalizer();
            $response = $integrador->obtenerFormularios($id_tramite, $id_tarea, $id_paso);
            $this->responseJson($response);
        }else{
            $this->load->helper('download');

            $integrador = new FormNormalizer();
            /* Siempre obtengo el paso número 1 para generar el swagger de la opracion iniciar trámite */
            $formulario = $integrador->obtenerFormularios($id_tramite, $id_tarea, 0);
            
            if($id_tramite == NULL || $id_tarea == NULL ){
                header("HTTP/1.1 400 Bad Request");
                exit;
            }
            

            $swagger_file = $integrador->generar_swagger($formulario);

            force_download("start_simple.json", $swagger_file);
            exit;
        }
    }

    
    private function obtenerStatus($id_tramite, $rut ){
        
        $response = array("idTramite" => $id_tramite,
            "nombreTramite" => "Hardcoded Dummy",
            "rutUsuario" => $rut,
            "nombreEtapaActual" => "Eetapa Cero");
        $this->responseJson($response);
    }
    
     private function responseJson($response){
         header('Content-type: application/json');
       echo json_indent(json_encode($response));
    }

    private function varDump($data){
        ob_start();
        //var_dump($data);
        print_r($data);
        $ret_val = ob_get_contents();
        ob_end_clean();
        return $ret_val;
    }
    
    private function extractVariable($body,$name){
        if(isset($body['data'][$name])){
            return $body['data'][$name];
        }
        return "NE";
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

        log_message("INFO", "Tramite id desde etapa: ".$etapa->tramite_id, FALSE);

        if (!$etapa) {
            header("HTTP/1.1 412 Etapa no fue encontrada");
            exit;
        }
        if ($etapa->tramite_id != $id_proceso) {
            header("HTTP/1.1 412 Etapa no pertenece al proceso ingresado");
            exit;
        }
        if (!$etapa->pendiente) {
            header("HTTP/1.1 412 Esta etapa ya fue completada");
            exit;
        }
        if (!$etapa->Tarea->activa()) {
            header("HTTP/1.1 412 Esta etapa no se encuentra activa");
            exit;
        }
        if ($etapa->vencida()) {
            header("HTTP/1.1 412 Esta etapa se encuentra vencida");
            exit;
        }

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
                        $dato->valor = $this->extractVariable($body,$c->nombre)=== false?'' :  $this->extractVariable($body,$c->nombre);

                        if (!is_object($dato->valor) && !is_array($dato->valor)) {
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

            $result = $this->procesar_proximo_paso($secuencia, $next_step, $etapa, $id_proceso);


        }catch(Exception $e){
            print_r($e->getMessage());die;
            echo $e->getMessage();
            return null;
        }
        return $result;

    }
    
    private function obtenerResultados($etapa){
        $campos = array();
        foreach($etapa->Tarea->Pasos as $paso ){
            $campos = array_merge($campos, $this->getListaExportables($paso->Formulario->id));
        }
        //ahora que estan los campos, retronar los valores de los campos
        $output=array();
        $datos = Doctrine::getTable('DatoSeguimiento')->findByEtapaId($etapa->id);  //$etapa->DatosSeguimiento
        foreach( $datos as $var ){
            if(in_array($var->nombre, $campos)){
                $output[$var->nombre] = $var->valor;
            }
        }
      
        $varexp = $this->getVariablesExportables($etapa);

        $retval = array_merge($varexp,$output);
        return $retval;
      
    }
    
    private function getVariablesExportables($etapa){
        $retval = array();
        $id_proceso = $etapa->Tarea->proceso_id;
        $accion = Doctrine::getTable("Accion")->findOneByProcesoId($id_proceso);
        
        if(isset($accion) && $accion->exponer_variable){
            $retval[$accion->extra->variable]= $this->getVariableValor($accion->nombre,$etapa);
        }
        return $retval;
    }
    
    private function getVariableValor($nombre,$etapa){
        $var = Doctrine::getTable('DatoSeguimiento')->findOneByNombreAndEtapaId( $nombre, $etapa->id);
        if($var != NULL){
            return $var->valor;
        }else{
            return "N/D";
        }
    }
    
    
    private function getListaExportables($form_id){
        $lista= array();
        $campos = Doctrine::getTable('Campo')->findByFormularioId($form_id);
        foreach($campos as $campo ){
            if($campo->exponer_campo){
                array_push($lista,$campo->nombre);
            }
        }  
        return $lista;
    }
    
    private function registrarCallbackURL($callback,$callback_id,$etapa){
        echo "Etapa:  $etapa";
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

    /**
     * @param $secuencia
     * @param $next_step
     * @param $etapa
     * @param $id_proceso
     * @return mixed
     */
    private function procesar_proximo_paso($secuencia, $next_step, $etapa, $id_proceso) {

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

            //Obtener la siguiente tarea
            $next = $etapa->getTareasProximas();
            //FIX verificar que pasa cunado es mas de un formulario

            //Si no existe la proxima etapa entonces se ha terminado

            if(isset($next->tareas)){
                if(count($next->tareas) > 1){
                    foreach($next->tareas as $tarea ){
                        $idf = $tarea->Pasos[0]->Formulario->id;
                        $form_norm = $integrador->obtenerFormulario($idf);
                        $etapa_prox = $etapa->getEtapaPorTareaId($tarea->id, $id_proceso);
                        $etapa_id = $etapa_prox->id;
                        $secuencia = 0;
                    }
                }else{
                    $form_norm = $integrador->obtenerFormulario($next->tareas[0]->Pasos[0]->Formulario->id);
                    $tarea_id = $next->tareas[0]->id;
                    $etapa_prox = $etapa->getEtapaPorTareaId($tarea_id, $id_proceso);
                    $etapa_id = $etapa_prox->id;
                    $secuencia = 0;
                }
            }else{
                $secuencia = null;
            }

        }else{
            $form_norm = $integrador->obtenerFormulario($next_step->formulario_id);
        }

        log_message("INFO", "Id etapa asignado: ".$etapa_id, FALSE);
        $result['result']['proximoForlulario'] = $form_norm;
        $result['result']['idEtapa'] = $etapa_id;
        $result['result']['secuencia'] = $secuencia;
        $result['result']['output']= $this->obtenerResultados($etapa);


        return $result;
    }


    function throwError($numero,$mensaje){
        $errorcodes = array(
							200	=> 'OK',
							201	=> 'Created',
							202	=> 'Accepted',
							203	=> 'Non-Authoritative Information',
							204	=> 'No Content',
							205	=> 'Reset Content',
							206	=> 'Partial Content',

							300	=> 'Multiple Choices',
							301	=> 'Moved Permanently',
							302	=> 'Found',
							304	=> 'Not Modified',
							305	=> 'Use Proxy',
							307	=> 'Temporary Redirect',

							400	=> 'Bad Request',
							401	=> 'Unauthorized',
							403	=> 'Forbidden',
							404	=> 'Not Found',
							405	=> 'Method Not Allowed',
							406	=> 'Not Acceptable',
							407	=> 'Proxy Authentication Required',
							408	=> 'Request Timeout',
							409	=> 'Conflict',
							410	=> 'Gone',
							411	=> 'Length Required',
							412	=> 'Precondition Failed',
							413	=> 'Request Entity Too Large',
							414	=> 'Request-URI Too Long',
							415	=> 'Unsupported Media Type',
							416	=> 'Requested Range Not Satisfiable',
							417	=> 'Expectation Failed',

							500	=> 'Internal Server Error',
							501	=> 'Not Implemented',
							502	=> 'Bad Gateway',
							503	=> 'Service Unavailable',
							504	=> 'Gateway Timeout',
							505	=> 'HTTP Version Not Supported'
						);
        header("HTTP/1.1 ".$numero." ".$errorcodes[$numero]);
    }
    
    
}
