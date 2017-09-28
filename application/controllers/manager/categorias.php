<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Categorias extends CI_Controller {

    public function __construct() {
        parent::__construct();
        
        UsuarioManagerSesion::force_login();
    }

    public function index() {

        $data['categorias']=Doctrine::getTable('Categoria')->findAll();
        $data['title']='Categorias';
        $data['content']='manager/categorias/categorias';
        
        $this->load->view('manager/template',$data);
    }
    
    public function editar($id=null){
        if($id) {
            $categoria = Doctrine::getTable('Categoria')->find($id);
        } else {
            $categoria = new Categoria();
        }
        
        $data['categoria'] = $categoria;
        $data['title'] = $categoria->id?'Editar':'Crear';
        $data['content'] = 'manager/categorias/editar';

        $this->load->view('manager/template', $data);
    }
    
    public function editar_form($id = null) {
        
        Doctrine_Manager::connection()->beginTransaction();
        
        try {
            if ($id)
                $categoria = Doctrine::getTable('Categoria')->find($id);
            else
                $categoria = new Categoria();
        
            $this->form_validation->set_rules('nombre', 'Nombre','required');
            $this->form_validation->set_rules('descripcion', 'Descripcion', 'required');
            $this->form_validation->set_rules('logo', 'Icono', 'required');

            $respuesta = new stdClass();
            if ($this->form_validation->run() == true) {
                
                // Cuenta
                $categoria->nombre = $this->input->post('nombre');
                $categoria->descripcion = $this->input->post('descripcion');
                //Si no 'nologo.png' es que cargo el logo por defecto, por lo tanto el campo
                //debe ser null.
                if( $this->input->post('logo') != "nologo.png"){
                   $categoria->icon_ref = $this->input->post('logo');
                }
    
                $categoria->save();
                $id = (int)$categoria->id;
                
                if ($id > 0) {
                    Doctrine_Manager::connection()->commit();
                    
                    $this->session->set_flashdata('message','Categoria guardada con éxito.');
                    $respuesta->validacion = true;
                    $respuesta->redirect = site_url('manager/categorias');
                
                } else {
                    $respuesta->validacion = false;
                    $respuesta->errores = '<div class="alert alert-error"><a class="close" data-dismiss="alert">×</a>Ocurrió un error al guardar los datos.</div>';
                    Doctrine_Manager::connection()->rollback();
                }
            } else {
                $respuesta->validacion = false;
                $respuesta->errores = validation_errors();
                Doctrine_Manager::connection()->rollback();
            }
        } catch (Exception $ex) {
            $respuesta->validacion = false;
            $respuesta->errores = '<div class="alert alert-error"><a class="close" data-dismiss="alert">×</a>'.$ex->getMessage().'</div>';
            Doctrine_Manager::connection()->rollback();
        }        
        
        echo json_encode($respuesta);
    }
    
    public function eliminar($id){
        $categoria=Doctrine::getTable('Categoria')->find($id);
        $categoria->delete();
        
        $this->session->set_flashdata('message','Categoria eliminada con éxito.');
        redirect('manager/categorias');
    }
    
    public static function sanitize_folder_title($title)
    {
        $folder_title = trim($title);
        $folder_title = str_replace(' ', '-', $folder_title);
        $folder_title = str_replace(array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'), array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A'), $folder_title);
        $folder_title = str_replace(array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'), array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'), $folder_title);
        $folder_title = str_replace(array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'), array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'), $folder_title);
        $folder_title = str_replace(array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'), array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'),  $folder_title);
        $folder_title = str_replace(array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'), array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'), $folder_title);
        $folder_title = str_replace(array('ñ', 'Ñ', 'ç', 'Ç'), array('n', 'N', 'c', 'C',), $folder_title);
        $folder_title = str_replace(array("\\", "¨", "º", "~", "#", "@", "|", "!", "\"", "·", "$", "%", "&", "/", "(", ")", "?", "'", "¡", "¿", "[", "^", "`", "]", "+", "}", "{", "´", ">", "< ", ";", ",", ":"), '', $folder_title);
        $folder_title = substr($folder_title, 0, 45);
        
        return $folder_title;
    }

}
