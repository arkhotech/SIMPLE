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
    
    /*
     * Llamadas de la API
     * Tramote id es el identificador del proceso
     */

    public function tramites($proceso_id = null) {
        
        $method = $this->input->server('REQUEST_METHOD');
        $body = file_get_contents('php://input');
        //Tomar los segmentos desde el 3 para adelante
        $urlSegment = $this->uri->segments;
        
       $headers = $this->input->request_headers();
        print_r($headers);
        print_r($urlSegment);
        //$cuenta = Cuenta::cuentaSegunDominio();

        switch($method){
            case "GET":
                $this->listarCatalogo();
                break;
            case "PUT":
                $this->continuarProceso($input);
                break;
            case "POST":
                $this->iniciarProceso($input);
        }
        
         echo $this->input->method(FALSE);
      //  $api_token=$this->input->get('token');
        
        //obtener el token
//        
//        
//        if(!$cuenta->api_token)
//            show_404 ();
//        
//        if($cuenta->api_token!=$api_token)
//            show_error ('No tiene permisos para acceder a este recurso.', 401);
        
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
   
    function put(){
        echo "Metodo PUT";
    }
    
    function listarCatalogo(){
        echo "Metodo GET";
    }

}