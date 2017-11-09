<?php
class Documentos extends MY_Controller {

    function __construct() {
        parent::__construct();

    }
    
    function get($filename){
        $id=$this->input->get('id');
        $token=$this->input->get('token');
        
        //Chequeamos permisos del frontend
        $file=Doctrine_Query::create()
                ->from('File f, f.Tramite t, t.Etapas e, e.Usuario u')
                ->where('f.id = ? AND f.llave = ? AND u.id = ?',array($id,$token,UsuarioSesion::usuario()->id))
                ->fetchOne();
        
        if(!$file){
            //Chequeamos permisos en el backend
            $file=Doctrine_Query::create()
                ->from('File f, f.Tramite.Proceso.Cuenta.UsuariosBackend u')
                ->where('f.id = ? AND f.llave = ? AND u.id = ? AND (u.rol like "%super%" OR u.rol like "%operacion%" OR u.rol like "%seguimiento%")',array($id,$token,UsuarioBackendSesion::usuario()->id))
                ->fetchOne();
            
            if(!$file){
                echo 'Usuario no tiene permisos para ver este archivo.';
                exit;
            }
        }
           
        
        $path='uploads/documentos/'.$file->filename;
        
        if(preg_match('/^\.\./', $file->filename)){
            echo 'Archivo invalido';
            exit;
        }

        if(!file_exists($path)){
            echo 'Archivo no existe';
            exit;
        }

        $friendlyName=str_replace(' ','-',convert_accented_characters(mb_convert_case($file->Tramite->Proceso->Cuenta->nombre.' '.$file->Tramite->Proceso->nombre,MB_CASE_LOWER).'-'.$file->id)).'.'.pathinfo($path,PATHINFO_EXTENSION);
  
        header('Content-Type: '. get_mime_by_extension($path));
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: attachment; filename="'.$friendlyName.'"');
        readfile($path);
    }
    
    //Acceso que utiliza applet de firma con token
    function firma_get(){
        $id=$this->input->get('id');
        $llave_firma=$this->input->get('token');
        
        if(!$id || !$llave_firma){
            $resultado=new stdClass();
            $resultado->status=1;
            $resultado->error='Faltan parametros';
            echo json_encode($resultado);
            exit;
        }
        
        $file=Doctrine_Query::create()
                ->from('File f, f.Tramite.Etapas.Usuario u')
                ->where('f.id = ? AND f.tipo = ? AND f.llave_firma = ? AND u.id = ?',array($id,'documento',$llave_firma,UsuarioSesion::usuario()->id))
                ->fetchOne();
        
        $resultado=new stdClass();
        if(!$file){
            $resultado->status=1;
            $resultado->error='Token no corresponde';
        }else{
            $resultado->status=0;
            $resultado->tipo='pdf';
            $resultado->documento=base64_encode(file_get_contents('uploads/documentos/'.$file->filename));
        }
        
        echo json_encode($resultado);
    }
    
    function firma_post(){
        $id=$this->input->post('id');
        $llave_firma=$this->input->post('token');
        $documento=$this->input->post('documento');
        
        if(!$id || !$llave_firma || !$documento){
            $resultado=new stdClass();
            $resultado->status=1;
            $resultado->error='Faltan parametros';
            echo json_encode($resultado);
            exit;
        }
        
        $file=Doctrine_Query::create()
            ->from('File f, f.Tramite.Etapas.Usuario u')
            ->where('f.id = ? AND f.tipo = ? AND f.llave_firma = ? AND u.id = ?',array($id,'documento',$llave_firma,UsuarioSesion::usuario()->id))
            ->fetchOne();
        
        $resultado=new stdClass();
        if(!$file){
            $resultado->status=1;
            $resultado->error='Token no corresponde';
        }else{
            $resultado->status=0;
            file_put_contents('uploads/documentos/'.$file->filename, base64_decode($documento));
        }
        
        echo json_encode($resultado);
    }
}
