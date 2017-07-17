<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Acciones extends MY_BackendController {

    public function __construct() {
        parent::__construct();

        UsuarioBackendSesion::force_login();
        
//        if(UsuarioBackendSesion::usuario()->rol!='super' && UsuarioBackendSesion::usuario()->rol!='modelamiento'){
        if( !in_array('super', explode(',',UsuarioBackendSesion::usuario()->rol) ) && !in_array( 'modelamiento',explode(',',UsuarioBackendSesion::usuario()->rol))){
            echo 'No tiene permisos para acceder a esta seccion.';
            exit;
        }
    }

    public function listar($proceso_id) {
        $proceso = Doctrine::getTable('Proceso')->find($proceso_id);

        if ($proceso->cuenta_id != UsuarioBackendSesion::usuario()->cuenta_id) {
            echo 'Usuario no tiene permisos para listar los formularios de este proceso';
            exit;
        }
        $data['proceso'] = $proceso;
        $data['acciones'] = $data['proceso']->Acciones;

        $data['title'] = 'Triggers';
        $data['content'] = 'backend/acciones/index';

        $this->load->view('backend/template', $data);
    }
    
    public function ajax_seleccionar($proceso_id){
        $proceso = Doctrine::getTable('Proceso')->find($proceso_id);

        if ($proceso->cuenta_id != UsuarioBackendSesion::usuario()->cuenta_id) {
            echo 'Usuario no tiene permisos para listar los formularios de este proceso';
            exit;
        }
        
        $data['proceso_id']=$proceso_id;
        $this->load->view('backend/acciones/ajax_seleccionar',$data);
    }
    
    public function seleccionar_form($proceso_id){
        $proceso = Doctrine::getTable('Proceso')->find($proceso_id);

        if ($proceso->cuenta_id != UsuarioBackendSesion::usuario()->cuenta_id) {
            echo 'Usuario no tiene permisos para listar los formularios de este proceso';
            exit;
        }
        
        $this->form_validation->set_rules('tipo','Tipo','required');

        $respuesta=new stdClass();
        if($this->form_validation->run()==TRUE){
            $tipo=$this->input->post('tipo');
            $respuesta->validacion=TRUE;
            $respuesta->redirect=site_url('backend/acciones/crear/'.$proceso_id.'/'.$tipo);
        }else{
            $respuesta->validacion=FALSE;
            $respuesta->errores=validation_errors();
        }
        
        echo json_encode($respuesta);
    }
    
    public function crear($proceso_id,$tipo){
        $proceso = Doctrine::getTable('Proceso')->find($proceso_id);

        if ($proceso->cuenta_id != UsuarioBackendSesion::usuario()->cuenta_id) {
            echo 'Usuario no tiene permisos para listar los formularios de este proceso';
            exit;
        }
        
        if($tipo=='enviar_correo')
            $accion=new AccionEnviarCorreo();
        else if($tipo=='webservice')
            $accion=new AccionWebservice();
        else if($tipo=='variable')
            $accion=new AccionVariable();
        else if($tipo=='rest')
            $accion=new AccionRest();
        else if($tipo=='soap')
            $accion=new AccionSoap();
        $data['edit']=FALSE;
        $data['proceso']=$proceso;
        $data['tipo']=$tipo;
        $data['accion']=$accion;
        
        $data['content']='backend/acciones/editar';
        $data['title']='Crear Acción';
        $this->load->view('backend/template',$data);
    }
    
    public function editar($accion_id){
        $accion = Doctrine::getTable('Accion')->find($accion_id);

        if ($accion->Proceso->cuenta_id != UsuarioBackendSesion::usuario()->cuenta_id) {
            echo 'Usuario no tiene permisos para listar los formularios de este proceso';
            exit;
        }
        
        
        $data['edit']=TRUE;
        $data['proceso']=$accion->Proceso;
        $data['accion']=$accion;
        
        $data['content']='backend/acciones/editar';
        $data['title']='Editar Acción';
        $this->load->view('backend/template',$data);
    }
    
    public function editar_form($accion_id=NULL){
        $accion=NULL;
        if($accion_id){
            $accion=Doctrine::getTable('Accion')->find($accion_id);
        }else{
            if($this->input->post('tipo')=='enviar_correo')
                $accion=new AccionEnviarCorreo();
            else if($this->input->post('tipo')=='webservice')
                $accion=new AccionWebservice();
            else if($this->input->post('tipo')=='variable')
                $accion=new AccionVariable();
            else if($this->input->post('tipo')=='rest')
                $accion=new AccionRest();
            else if($this->input->post('tipo')=='soap')
                $accion=new AccionSoap();
            $accion->proceso_id=$this->input->post('proceso_id');
            $accion->tipo=$this->input->post('tipo');
        }
        
        if($accion->Proceso->cuenta_id!=UsuarioBackendSesion::usuario()->cuenta_id){
                echo 'Usuario no tiene permisos para editar esta accion.';
                exit;
            }
        
        $this->form_validation->set_rules('nombre','Nombre','required');
        $accion->validateForm();
        if(!$accion_id){
            $this->form_validation->set_rules('proceso_id','Proceso','required');
            $this->form_validation->set_rules('tipo','Tipo de Campo','required');
        }

        $respuesta=new stdClass();
        if($this->form_validation->run()==TRUE){
            if(!$accion){
                
            }
            
            $accion->nombre=$this->input->post('nombre');
            $accion->extra=$this->input->post('extra',false);
            $accion->save();
            
            $respuesta->validacion=TRUE;
            $respuesta->redirect=site_url('backend/acciones/listar/'.$accion->Proceso->id);
        }else{
            $respuesta->validacion=FALSE;
            $respuesta->errores=validation_errors();
        }
        
        echo json_encode($respuesta);
    }
    
    public function eliminar($accion_id){
        $accion=Doctrine::getTable('Accion')->find($accion_id);
        
        if($accion->Proceso->cuenta_id!=UsuarioBackendSesion::usuario()->cuenta_id){
            echo 'Usuario no tiene permisos para eliminar esta accion.';
            exit;
        }
        
        $proceso=$accion->Proceso;
        $fecha = new DateTime ();
         
        // Auditar
        $registro_auditoria = new AuditoriaOperaciones ();
        $registro_auditoria->fecha = $fecha->format ( "Y-m-d H:i:s" );
        $registro_auditoria->operacion = 'Eliminación de Acción';
        $usuario = UsuarioBackendSesion::usuario ();
        $registro_auditoria->usuario = $usuario->nombre . ' ' . $usuario->apellidos . ' <' . $usuario->email . '>';
        $registro_auditoria->proceso = $proceso->nombre;
        $registro_auditoria->cuenta_id = UsuarioBackendSesion::usuario()->cuenta_id;
        
        //Detalles

        $accion_array['proceso'] = $proceso->toArray(false);
        $accion_array['accion'] = $accion->toArray(false);
        unset($accion_array['accion']['proceso_id']);
        
        $registro_auditoria->detalles=  json_encode($accion_array);
        $registro_auditoria->save();
        
        $accion->delete();
        
        redirect('backend/acciones/listar/'.$proceso->id);
        
    }
    
    public function exportar($accion_id)
    {

        $accion = Doctrine::getTable('Accion')->find($accion_id);

        $json = $accion->exportComplete();

        header("Content-Disposition: attachment; filename=\"".mb_convert_case(str_replace(' ','-',$accion->nombre),MB_CASE_LOWER).".simple\"");
        header('Content-Type: application/json');
        echo $json;

    }
    
    public function importar()
    {
        try {
            $file_path = $_FILES['archivo']['tmp_name'];
            $proceso_id = $this->input->post('proceso_id');

            if ($file_path && $proceso_id) {
                $input = file_get_contents($_FILES['archivo']['tmp_name']);
                $accion = Accion::importComplete($input, $proceso_id);
                $accion->proceso_id = $proceso_id;            
                $accion->save();            
            } else {
                die('No se especificó archivo o ID proceso');
            }
        } catch (Exception $ex) {
            die('Código: '.$ex->getCode().' Mensaje: '.$ex->getMessage());
        }
        
        redirect($_SERVER['HTTP_REFERER']);
    }

    public function functions_soap($urlsoap){
        $url=$this->input->post('urlsoap');
        $client = new SoapClient($url);
        $result['functions']=$client->__getFunctions();
        $result['types']=$client->__getTypes();
        $result['functions'] = str_replace("\\n", " ", $result['functions']);
        $result['functions'] = str_replace("\\r", " ", $result['functions']);
        $result['types'] = str_replace("\\n", " ", $result['types']);
        $result['types'] = str_replace("\\r", " ", $result['types']);
        $result = str_replace("\\n", " ", $result);
        $result = str_replace("\\r", " ", $result);
        $array = json_encode($result);
        print_r($array);
        exit;
    }  

    public function converter_json(){
        $array=$this->input->post('myArrClean');
        $array = str_replace("\\n", " ", $array);
        $array = str_replace("\\r", " ", $array);
        $json = Array();
        $strlen=count($array);
        for ($i = 1; $i <= $strlen; $i+=2){
            $json[$array[$i-1]]=$array[$i];
        }
        $json2=json_encode($json);
        print_r($json2);
        exit;
    }

}