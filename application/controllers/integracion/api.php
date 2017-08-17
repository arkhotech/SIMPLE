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
                $this->continuarProceso($proceso_id,$etapa,$this->getBody());
                break;
            case "POST":
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
            
            
            $tramite = new Tramite();
            $tramite->iniciar($proceso_id);

            log_message("INFO", "Iniciando trámite: ".$proceso_id, FALSE);

            $etapa = $tramite->getEtapasActuales()->get(0)->id;

            $respuesta = $this->ejecutar_form($etapa, 0, $input["data"]);
            log_message("INFO", "Respuesta de ejecutar inicio: ".$this->varDump($respuesta), FALSE);
            $nextStep = $respuesta["id_prox_paso"];
            $etapa_id = $respuesta["id_etapa"];


            $integrador = new FormNormalizer();
            log_message("INFO", "Obteniendo proximo formulario para proceso_id, id_tarea, nextStep: ".$proceso_id." ".$id_tarea." ".$nextStep, FALSE);
            $nextForm = $integrador->obtenerFormularios($proceso_id, $id_tarea, $nextStep);
            $this->registrarCallbackURL($input['callback'],$etapa);

            //validaciones etapa vencida, si existe o algo por el estilo

             $response = array(
                "idInstancia" => $tramite->id,
                "codigoRetorno" => 0,
                "descRetorno" => "",
                 "idEtapa" => $etapa_id,
                "idProximoPaso" => $nextStep,
                "output" => array(),
                "proximoFormulario" => $nextForm
                );
             $this->responseJson($response);
        }catch(Exception $e){
           $e->getTrace();
        }
      
    }
    
    private function continuarProceso($proceso_instance_id,$id_tarea=NULL, $body){

        log_message("INFO", "En continuar proceso, input data: ".$body);

        if($proceso_instance_id == NULL || $id_tarea == NULL){
            header("HTTP/1.1 400 Bad Request");
            return;
        }

        try{
            $input = json_decode($body,true);

            $tramite = new Tramite();
            $tramite->iniciar($proceso_instance_id);

            //llevar a etapas/ejecutar_form + número de tarea y proceso
            //Se debe seleccionar la etapa que se quiere iniciar.
            $etapa = $tramite->getEtapasActuales()->get(0)->id;
            $nextStep = $this->ejecutarEntrada($etapa,$input,true);
            $integrador = new FormNormalizer();
            $nextForm = $integrador->obtenerFormularios($proceso_instance_id, $id_tarea, $nextStep);
            $this->registrarCallbackURL($input['callback'],$etapa);

            //validaciones etapa vencida, si existe o algo por el estilo
            $response = array(
                "codigoRetorno" => 0,
                "descRetorno" => "",
                "idProximoPaso" => $nextStep,
                "output" => array(),
                "proximoFormulario" => $nextForm
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
    public function ejecutarEntrada($etapa_id,$body){

        $etapa = Doctrine::getTable('Etapa')->find($etapa_id);
        //obtener siempre el primer paso de la secuencia
        $paso = $etapa->getPasoEjecutable(0);

        log_message("INFO", "Primer paso: ".$paso->id, FALSE);

        $formulario = $paso->Formulario;
        $modo = $paso->modo;

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

        log_message("INFO", "Guardando etapa: ".$etapa_id, FALSE);
        $etapa->save();

        log_message("INFO", "Finalizando paso", FALSE);
        $etapa->finalizarPaso($paso);
       //Traer el siguiente formualrio
        $prox_paso = $etapa->getPasoEjecutable(1);
        log_message("INFO", "Proximo paso: ".$prox_paso->id, FALSE);
        return $prox_paso->id;

    }
    
    private function registrarCallbackURL($url,$etapa){
  
        $dato = new DatoSeguimiento();
        $dato->nombre = "callback_url";
        $dato->valor = "{ url:".$url."}";
        $dato->etapa_id = $etapa;
        $dato->save();
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
     * @param $etapa_id
     * @param $secuencia
     * @param $data
     */
    public function ejecutar_form($etapa_id, $secuencia, $data) {

        log_message('info', 'ejecutar_form ($etapa_id [' . $etapa_id . '], $secuencia [' . $secuencia . '])');

        $etapa = Doctrine::getTable('Etapa')->find($etapa_id);

        log_message('info', 'Recupera etapa', FALSE);

        if (!$etapa->pendiente) {
            header("HTTP/1.1 100 Esta etapa ya fue completada");
            exit;
        }
        if (!$etapa->Tarea->activa()) {
            header("HTTP/1.1 101 Esta etapa no se encuentra activa");
            exit;
        }
        if ($etapa->vencida()) {
            header("HTTP/1.1 102 Esta etapa se encuentra vencida");
            exit;
        }

        log_message('info', 'Recupera paso ejecutable', FALSE);

        $paso = $etapa->getPasoEjecutable($secuencia);
        $formulario = $paso->Formulario;

            $validar_formulario = FALSE;
            log_message('info', 'Validando formulario', FALSE);
            foreach ($formulario->Campos as $c) {
                // Validamos los campos que no sean readonly y que esten disponibles (que su campo dependiente se cumpla)
                if ($c->isEditableWithCurrentPOST($etapa_id)) {
                    $c->formValidate($etapa->id);
                    $validar_formulario = TRUE;
                }
            }
            log_message('info', 'Resultado validacion: '.$validar_formulario, FALSE);
            if (true){//!$validar_formulario){//} || $this->form_validation->run() == TRUE) {
                log_message('info', 'Entra en if', FALSE);
                // Almacenamos los campos
                foreach ($formulario->Campos as $c) {
                    log_message('info', 'Recorriendo campos: '.$c->nombre, FALSE);
                    // Almacenamos los campos que no sean readonly y que esten disponibles (que su campo dependiente se cumpla)

                    log_message('info', 'Chequea si es editable', FALSE);
                    if ($c->isEditableWithCurrentPOST($etapa_id)) {
                        log_message('info', 'lo es', FALSE);
                        $dato = Doctrine::getTable('DatoSeguimiento')->findOneByNombreAndEtapaId($c->nombre, $etapa->id);
                        if (!$dato)
                            $dato = new DatoSeguimiento();
                        $dato->nombre = $c->nombre;
                        log_message('info', 'valor: '.$data[$c->nombre], FALSE);
                        $dato->valor = $data[$c->nombre]=== false?'' :  $data[$c->nombre];

                        if (!is_object($dato->valor) && !is_array($dato->valor)) {
                            if (preg_match('/^\d{4}[\/\-]\d{2}[\/\-]\d{2}$/', $dato->valor)) {
                                $dato->valor=preg_replace("/^(\d{4})[\/\-](\d{2})[\/\-](\d{2})/i", "$3-$2-$1", $dato->valor);
                            }
                        }

                        $dato->etapa_id = $etapa->id;
                        $dato->save();
                    }
                }
                log_message('info', 'Termina con datos', FALSE);
                $etapa->save();

                log_message('info', 'Guarda etapa', FALSE);
                $etapa->finalizarPaso($paso);

                log_message('info', 'Finaliza paso', FALSE);

                $prox_paso = $etapa->getPasoEjecutable($secuencia + 1);
                if (!$prox_paso) {
                    log_message('info', 'No hay proximo paso se avanza a la sigeouinte etapa', FALSE);
                    $etapa->avanzar();
                    log_message('info', 'Etapa siguiente: '.$etapa->id, FALSE);
                } else if ($etapa->Tarea->final && $prox_paso->getReadonly() && end($etapa->getPasosEjecutables()) == $prox_paso) { //Cerrado automatico
                    log_message('info', 'Fibnal proceso, se procesa el cierre automático', FALSE);
                    $etapa->iniciarPaso($prox_paso);
                    $etapa->finalizarPaso($prox_paso);
                    $etapa->avanzar();
                } else {
                    log_message('info', 'Hay proximo paso y no es etapa final', FALSE);
                    //$respuesta->redirect = site_url('etapas/ejecutar/' . $etapa_id . '/' . ($secuencia + 1)) . ($qs ? '?' . $qs : '');
                }
            } else {
                header("HTTP/1.1 500 ".validation_errors());
                exit;
            }

        $respuesta = array(
            "id_prox_paso" => $prox_paso->id,
            "id_etapa" => $etapa->id
        );

        return $respuesta;
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
        header("HTTP/1.1 ");
    }
    
    
}
