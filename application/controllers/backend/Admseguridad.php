<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Admseguridad extends MY_BackendController {

    public function __construct() {
        parent::__construct();
        UsuarioBackendSesion::force_login();

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
        $data['seguridad'] = $data['proceso']->Admseguridad;
        $data['title'] = 'Triggers';
        $data['content'] = 'backend/seguridad/index';
        $this->load->view('backend/template', $data);
    }
    
    public function crear($proceso_id){
        $proceso = Doctrine::getTable('Proceso')->find($proceso_id);
        if ($proceso->cuenta_id != UsuarioBackendSesion::usuario()->cuenta_id) {
            echo 'Usuario no tiene permisos para listar los formularios de este proceso';
            exit;
        }

        $data['edit'] = FALSE;
        $data['proceso'] = $proceso;
        $data['seguridad'] = new Seguridad();
        $data['content']='backend/seguridad/editar';
        $data['title']='Registrar metodo';
        $this->load->view('backend/template',$data);
    }
    
    public function editar($seguridad_id){
        $seguridad = Doctrine::getTable('Seguridad')->find($seguridad_id);
        if ($seguridad->Proceso->cuenta_id != UsuarioBackendSesion::usuario()->cuenta_id) {
            echo 'Usuario no tiene permisos para listar los formularios de este proceso';
            exit;
        }
        $data['edit'] = TRUE;
        $data['proceso'] = $seguridad->Proceso;
        $data['seguridad'] = $seguridad;
        $data['content'] = 'backend/seguridad/editar';
        $data['title'] = 'Editar Seguridad';
        $this->load->view('backend/template',$data);
    }

    public function editar_form($seguridad_id=NULL){
        $seguridad=NULL;
        if($seguridad_id){
            $seguridad=Doctrine::getTable('Seguridad')->find($seguridad_id);
        }else{
            $seguridad=new SeguridadForm();
            $seguridad->proceso_id=$this->input->post('proceso_id');
        }
        $extra=$this->input->post('extra');
        $tipoSeguridad=$extra['tipoSeguridad'];
        if($seguridad->Proceso->cuenta_id!=UsuarioBackendSesion::usuario()->cuenta_id){
            echo 'Usuario no tiene permisos para editar esta accion.';
            exit;
        }
        $this->form_validation->set_rules('institucion','Institucion','required');
        $this->form_validation->set_rules('servicio','Servicio','required');
        $this->form_validation->set_rules('extra[tipoSeguridad]','Tipo de seguridad','required');
        switch ($tipoSeguridad){
            case 'API_KEY':
                $this->form_validation->set_rules('extra[apikey]','Llave de aplicación','required');
                break;
            case "HTTP_BASIC":
                $this->form_validation->set_rules('extra[user]','user','required');
                $this->form_validation->set_rules('extra[pass]','password','required'); 
                break;
            case "OAUTH2":
                $this->form_validation->set_rules('extra[url_auth]','Url de Autenticación','required');
                $this->form_validation->set_rules('extra[request_seg]','Request','required'); 
                break;    
        }
        $seguridad->validateForm();
        if(!$seguridad_id){
            $this->form_validation->set_rules('proceso_id','Proceso','required');
        }

        $respuesta=new stdClass();
        if($this->form_validation->run()==TRUE){
            if(!$seguridad){
                $this->form_validation->set_rules('proceso_id','Proceso','required');
            }
            $seguridad->institucion=$this->input->post('institucion');
            $seguridad->servicio=$this->input->post('servicio');
            $seguridad->extra=$this->input->post('extra',false);
            $seguridad->save();
            $respuesta->validacion=TRUE;
            $respuesta->redirect=site_url('backend/Admseguridad/listar/'.$seguridad->Proceso->id);
        }else{
            $respuesta->validacion=FALSE;
            $respuesta->errores=validation_errors();
        }
        echo json_encode($respuesta);
    }
    
    public function eliminar($seguridad_id){
        $seguridad=Doctrine::getTable('Seguridad')->find($seguridad_id);
        if($seguridad->Proceso->cuenta_id!=UsuarioBackendSesion::usuario()->cuenta_id){
            echo 'Usuario no tiene permisos para eliminar esta accion.';
            exit;
        }
        $proceso=$seguridad->Proceso;
        $fecha = new DateTime ();
        // Auditar
        $registro_auditoria = new AuditoriaOperaciones ();
        $registro_auditoria->fecha = $fecha->format ( "Y-m-d H:i:s" );
        $registro_auditoria->operacion = 'Eliminación de Seguridad';
        $usuario = UsuarioBackendSesion::usuario ();
        $registro_auditoria->usuario = $usuario->nombre . ' ' . $usuario->apellidos . ' <' . $usuario->email . '>';
        $registro_auditoria->proceso = $proceso->nombre;
        $registro_auditoria->cuenta_id = UsuarioBackendSesion::usuario()->cuenta_id;
        //Detalles
        $seguridad_array['proceso'] = $proceso->toArray(false);
        $seguridad_array['seguridad'] = $seguridad->toArray(false);
        unset($seguridad_array['seguridad']['proceso_id']);
        $registro_auditoria->detalles=  json_encode($seguridad_array);
        $registro_auditoria->save();
        $seguridad->delete();
        redirect('backend/Admseguridad/listar/'.$proceso->id);
    }
    
    public function exportar($seguridad_id)
    {

        $seguridad = Doctrine::getTable('Seguridad')->find($seguridad_id);

        $json = $seguridad->exportComplete();

        header("Content-Disposition: attachment; filename=\"".mb_convert_case(str_replace(' ','-',$seguridad->institucion),MB_CASE_LOWER).".simple\"");
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
                $seguridad = Accion::importComplete($input, $proceso_id);
                $seguridad->proceso_id = $proceso_id;            
                $seguridad->save();            
            } else {
                die('No se especificó archivo o ID proceso');
            }
        } catch (Exception $ex) {
            die('Código: '.$ex->getCode().' Mensaje: '.$ex->getMessage());
        }
        
        redirect($_SERVER['HTTP_REFERER']);
    } 
}