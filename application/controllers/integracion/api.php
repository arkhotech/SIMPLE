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
                //show_error("405 Metodo no permitido",405, "El metodo no esta implementado" );
        }
        
    }

    
    private function listarCatalogo(){
        $tarea=Doctrine::getTable('Proceso')->findProcesosExpuestos();
        //$tarea=Doctrine::getTable('Proceso')->findProcesosExpuestos(UsuarioBackendSesion::usuario()->cuenta_id);
        $result = array();
        $nombre_host = gethostname();
        ($_SERVER['HTTPS'] ? $protocol = 'https://' : $protocol = 'http://');
        foreach($tarea as $res ){
            array_push($result, array(
                "id" => $res['id'],
                "nombre" => $res['nombre'],
                "tarea" => $res['tarea'],
                "descripcion" => $res['previsualizacion'],
                "URL" => $protocol.$nombre_host.'/integracion/api/tramites/espec/'.$res['id'].'/'.$res['id_tarea']
            )); 
        }   
       $retval["catalogo"] = $result; 
       header('Content-type: application/json');
       echo json_indent(json_encode($retval));
       exit;
    }
    
    private function iniciarProceso($proceso_id,$id_tarea,$body){
        //validar la entrada
        
        if($proceso_id == NULL || $id_tarea == NULL){
            //show_error("400 Bad Request",400, "Uno de los parametros de entrada no ha sido especificado" );
            header("HTTP/1.1 400 Bad Request");
            return;
        }
        
        try{ 
            $input = json_decode($body,true);
            
            
            $tramite = new Tramite();
            $tramite->iniciar($proceso_id);

            //llevar a etapas/ejecutar_form + número de tarea y proceso
            //Se debe seleccionar la etapa que se quiere iniciar.
            $etapa = $tramite->getEtapasActuales()->get(0)->id;
            $nextStep = $this->ejecutarEntrada($etapa,$input,true);
            $integrador = new FormNormalizer();
            //echo $proceso_id, $id_tarea, $nextStep;die;
            $nextForm = $integrador->obtenerFormularios($proceso_id, $id_tarea, $nextStep);
            $this->registrarCallbackURL($input['callback'],$etapa);
            
            //validaciones etapa vencida, si existe o algo por el estilo
             $response = array(
                "idInstancia" => $tramite->id,
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
    
    private function continuarProceso($idProceso,$idEtapa=NULL, $body){
        $data = json_decode($body,true);
        
         $response = array(
           "codigoRetorno" => 0,
           "descRetorno" => "Problemas para iniciar",
           "idProximoPaso" => $idProceso + 1,
           "proximoFormulario" => $data['data']
           );
         $this->responseJson($response);
    }
    
    private function generarEspecificacion($operacion,$id_tramite=NULL,$id_tarea=NULL,$id_paso = NULL){
        try{
            if($operacion === "form"){
                $integrador = new FormNormalizer();
                $response = $integrador->obtenerFormularios($id_tramite, $id_tarea, $id_paso);
                $this->responseJson($response);
            }else{
                $this->load->helper('download');
                //llamar al generador de Swagger
                force_download("test.txt", "esto es una prueba");
                exit;
            }
        }catch(Exception $e){
            print_r($e);
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

        $etapa->save();

        $etapa->finalizarPaso($paso);
       //Traer el siguiente formualrio
        $prox_paso = $etapa->getPasoEjecutable(1); 
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