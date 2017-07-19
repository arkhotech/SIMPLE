<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Seguridad extends MY_BackendController {

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
        $data['seguridad'] = $data['proceso']->Admin_seguridad;  
        // Seguridad

        $data['title'] = 'Triggers';
        $data['content'] = 'backend/seguridad/index';

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

    public function functions_soap(){
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

    public function upload_file(){
        try {
            $file_path = $_FILES['archivo']['tmp_name'];
            $name = $_FILES['tmp_name'];
            if ($file_path) {
                $wsdl = file_get_contents($_FILES['archivo']['tmp_name']);
                $config['upload_path'] = "uploads/wsdl/";
                $config['file_name'] = $file_path;
                $config['allowed_types'] = "*";
                $config['max_size'] = "50000";
                $config['max_width'] = "2000";
                $config['max_height'] = "2000";
                $this->load->library('upload', $config);
                if (!$this->upload->do_upload('archivo')) {
                    $data['uploadError'] = $this->upload->display_errors();
                    echo $this->upload->display_errors();
                    return;
                }
                $data['uploadSuccess'] = $this->upload->data();
                $file_path = str_replace("/", "", $file_path);
                $wsdl_url="uploads/wsdl/".$file_path.".wsdl";
                $client = new SoapClient($wsdl_url);
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
            }else{
                die('No hay archivo');
            }
        } catch (Exception $ex) {
            die('Código: '.$ex->getCode().' Mensaje: '.$ex->getMessage());
        }
        exit;
    }  

   public function converter_json(){
        $array=$this->input->post('myArrClean');
        $operaciones=$this->input->post('operaciones');
        $strlen1=count($array);

        for ($i = 1; $i <= $strlen1; $i+=2){
            $array2[$array[$i-1]]=$array[$i];
        }

        $DataTypesSoap=["float","language","Qname","boolean","gDay","long","short","byte","gMonth","Name","string","date","gMonthDay","NCName","time","dateTime","gYear","negativeInteger","token","decimal","gYearMonth","NMTOKEN","unsignedByte","double","ID","NMTOKENS","unsignedInt","duration","IDREFS","nonNegativeInteger","unsignedLong","ENTITIES","int","nonPostiveInteger","unsignedShort","ENTITY","integer","anyURI","normalizedString"];

        foreach ($array2 as $d){
            $date=$d;
            $clave = array_search($date, $DataTypesSoap);
            if ($clave==FALSE){
                foreach ($operaciones as $d){
                    $clave = array_search($date, $d);
                    if ($clave!=FALSE){
                        $count1=count($d)/2;
                        if ($count1 > count($array)){
                            $nuevo = $d;
                            unset($nuevo[0],$nuevo[1]);
                            $nuevo2 = array_reverse($nuevo);
                        }
                    }
                }
            }
        }
        for ($i = 1; $i <= count($nuevo2); $i+=2){
            $array3[$nuevo2[$i-1]]=$nuevo2[$i];
        }
        foreach ($array3 as $d){
            $date2=$d;
                $clave2 = array_search($date2, $DataTypesSoap);
            if ($clave2==FALSE){
                foreach ($operaciones as $d){
                    $clave2 = array_search($date2, $d);
                    if ($clave2!=FALSE){
                            if ($d[1]==$date2){
                                if ($d[0]=='struct'){
                                        $nuevo3 = $d;
                                        unset($nuevo3[0],$nuevo3[1]);
                                        $nuevo4 = array_reverse($nuevo3);
                                        $array4="";
                                        for ($i = 1; $i <= count($nuevo4); $i+=2){
                                            $array4[$nuevo4[$i-1]]=$nuevo4[$i];
                                        }

                                        foreach ($array3 as $key => $val){
                                            if ($val == $date2) {
                                                $array3[$key]=$array4;
                                            }
                                        }
                                }else{
                                    $nuevo4 = array_reverse($d);
                                    $i=0;
                                    $array4="";
                                    for ($i = 1; $i <= count($nuevo4); $i+=2){
                                        $array4[$nuevo4[$i-1]]=$nuevo4[$i];
                                    }
                                    foreach ($array3 as $key => $val){
                                        if ($val == $date2) {
                                            $array3[$key]=$array4;
                                        }
                                    }
                                }
                            }
                    }
                }
            }
        }
        $array3 = str_replace("\\n", " ", $array3);
        $array3 = str_replace("\\n", " ", $array3);
        $array2[$date]=$array3;
        $array2 = str_replace("\\n", " ", $array2);
        $array2 = str_replace("\\r", " ", $array2);
        $json=json_encode($array2);
        print_r($json);
        exit;
    }
}