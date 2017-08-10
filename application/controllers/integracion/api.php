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
                show_error("405 Metodo no permitido",405, "El metodo no esta implementado" );
        }
    }
    
    private function checkJsonHeader(){
        $headers = $this->input->request_headers();
        if($headers['Content-Type']==NULL || $headers['Content-Type']!="application/json"){
            show_error("415 Unsupported Media Type",415, "Se espera application/json" );
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
                $this->iniciarProceso($proceso_id,$this->getBody());
                break;
            default:
                show_error("405 Metodo no permitido",405, "El metodo no esta implementado" );
        }
        
         echo $this->input->method(FALSE);
  
         die;
        $respuesta = new stdClass();
        if ($proceso_id) {
            $tramite = Doctrine::getTable('Proceso')->find($proceso_id);
//            echo "<pre>";
//            print_r();die;
//            echo "</pre>";
            $formulario = Doctrine::getTable('Formulario')->find($tramite->Formularios[0]->id);
            $json = $formulario->exportComplete();
            //header("Content-Disposition: attachment; filename=\"".mb_convert_case(str_replace(' ','-',$formulario->nombre),MB_CASE_LOWER).".simple\"");
            
            $source = json_decode($json,true);
            $this->load->helper('Catalogo');
            normalizarFormulario("Test");
            
            //header('Content-Type: application/json');
            echo "<pre>";
            print_r($this->normalizarFormulario($source));
            echo "</pre>";
            die;
            if (!$tramite)
                show_404();

            if ($tramite->Proceso->Cuenta != $cuenta)
                show_error('No tiene permisos para acceder a este recurso.', 401);

            
            $respuesta->tramite = $tramite->toPublicArray();
        } 

        header('Content-type: application/json');
        echo json_indent(json_encode($respuesta));
    }

    //Esta funcion debería estar en modelo
   
    function normalizarFormulario($json){
        $retval = array();
        foreach( $json['Campos'] as $campo){
            
            //Seleccionar los campos que se van a utilizar solamente
            echo ">";
            array_push($retval, array( 
                $campo['nombre'],
                $campo['dependiente_tipo'],
                $campo['readonly']));
                
        }
        return $retval;
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
    
    private function iniciarProceso($idTramite,$body){
        //validar la entrada
       $response = array(
           "codigoRetorno" => 0,
           "descRetorno" => "Problemas para iniciar",
           "idProximoPaso" => 123,
           "proximoFormulario" => array()
           );
        $this->responseJson($response);
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
}