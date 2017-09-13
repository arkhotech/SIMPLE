<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Suscriptores extends MY_BackendController {

    public function __construct() {
        parent::__construct();
        UsuarioBackendSesion::force_login();

        if( !in_array('super', explode(',',UsuarioBackendSesion::usuario()->rol) ) && !in_array( 'modelamiento',explode(',',UsuarioBackendSesion::usuario()->rol))){
            echo 'No tiene permisos para acceder a esta seccion.';
            exit;
        }
    }

    public function listar($proceso_id) {
        log_message("INFO", "Listando suscriptores para proceso id: ".$proceso_id);
        $proceso = Doctrine::getTable('Proceso')->find($proceso_id);
        if ($proceso->cuenta_id != UsuarioBackendSesion::usuario()->cuenta_id) {
            echo 'Usuario no tiene permisos para listar los formularios de este proceso';
            exit;
        }
        $data['proceso'] = $proceso;
        $data['suscriptores'] = $data['proceso']->Suscriptores;
        $data['title'] = 'Triggers';
        $data['content'] = 'backend/suscriptores/index';
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
        $data['suscriptor'] = new Suscriptor();
        $data['content']='backend/suscriptores/editar';
        $data['title']='Registrar metodo';
        $this->load->view('backend/template',$data);
    }

    public function editar($suscriptor_id){
        $suscriptor = Doctrine::getTable('Suscriptor')->find($suscriptor_id);
        if ($suscriptor->Proceso->cuenta_id != UsuarioBackendSesion::usuario()->cuenta_id) {
            echo 'Usuario no tiene permisos para listar los formularios de este proceso';
            exit;
        }
        $data['edit'] = TRUE;
        $data['proceso'] = $suscriptor->Proceso;
        $data['suscriptor'] = $suscriptor;
        $data['content'] = 'backend/suscriptores/editar';
        $data['title'] = 'Editar Suscriptor';
        $this->load->view('backend/template',$data);
    }

    public function editar_form($suscriptor_id=NULL){
        $suscriptor=NULL;
        if($suscriptor_id){
            $suscriptor=Doctrine::getTable('Suscriptor')->find($suscriptor_id);
        }else{
            $suscriptor=new Suscriptor();
            $suscriptor->proceso_id=$this->input->post('proceso_id');
        }
        $extra=$this->input->post('extra');
        $tipoSeguridad=$extra['idSeguridad'];
        if($suscriptor->Proceso->cuenta_id!=UsuarioBackendSesion::usuario()->cuenta_id){
            echo 'Usuario no tiene permisos para editar esta accion.';
            exit;
        }
        $this->form_validation->set_rules('institucion','Institucion','required');
        $this->form_validation->set_rules('extra[webhook]','Webhook','required');

        $suscriptor->validateForm();
        if(!$suscriptor_id){
            $this->form_validation->set_rules('proceso_id','Proceso','required');
        }

        $respuesta=new stdClass();
        if($this->form_validation->run()==TRUE){
            if(!$suscriptor){
                $this->form_validation->set_rules('proceso_id','Proceso','required');
            }
            $suscriptor->institucion=$this->input->post('institucion');
            $suscriptor->extra=$this->input->post('extra',false);
            $suscriptor->save();
            $respuesta->validacion=TRUE;
            $respuesta->redirect=site_url('backend/suscriptores/listar/'.$suscriptor->Proceso->id);
        }else{
            $respuesta->validacion=FALSE;
            $respuesta->errores=validation_errors();
        }
        echo json_encode($respuesta);
    }
    
    public function eliminar($suscriptor_id){
        $suscriptor=Doctrine::getTable('Suscriptor')->find($suscriptor_id);
        if($suscriptor->Proceso->cuenta_id!=UsuarioBackendSesion::usuario()->cuenta_id){
            echo 'Usuario no tiene permisos para eliminar esta accion.';
            exit;
        }
        $proceso=$suscriptor->Proceso;
        $fecha = new DateTime ();
        // Auditar
        $registro_auditoria = new AuditoriaOperaciones ();
        $registro_auditoria->fecha = $fecha->format ( "Y-m-d H:i:s" );
        $registro_auditoria->operacion = 'Eliminación de Suscriptor';
        $usuario = UsuarioBackendSesion::usuario ();
        $registro_auditoria->usuario = $usuario->nombre . ' ' . $usuario->apellidos . ' <' . $usuario->email . '>';
        $registro_auditoria->proceso = $proceso->nombre;
        $registro_auditoria->cuenta_id = UsuarioBackendSesion::usuario()->cuenta_id;
        //Detalles
        $suscriptor_array['proceso'] = $proceso->toArray(false);
        $suscriptor_array['suscriptor'] = $suscriptor->toArray(false);
        unset($suscriptor_array['suscriptor']['proceso_id']);
        $registro_auditoria->detalles=  json_encode($suscriptor_array);
        $registro_auditoria->save();
        $suscriptor->delete();
        redirect('backend/suscriptores/listar/'.$proceso->id);
    }
    
    public function exportar($suscriptor_id)
    {

        $suscriptor = Doctrine::getTable('Suscriptor')->find($suscriptor_id);

        $json = $suscriptor->exportComplete();

        header("Content-Disposition: attachment; filename=\"".mb_convert_case(str_replace(' ','-',$suscriptor->institucion),MB_CASE_LOWER).".simple\"");
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
                $suscriptor = Accion::importComplete($input, $proceso_id);
                $suscriptor->proceso_id = $proceso_id;
                $suscriptor->save();
            } else {
                die('No se especificó archivo o ID proceso');
            }
        } catch (Exception $ex) {
            die('Código: '.$ex->getCode().' Mensaje: '.$ex->getMessage());
        }
        
        redirect($_SERVER['HTTP_REFERER']);
    } 
}