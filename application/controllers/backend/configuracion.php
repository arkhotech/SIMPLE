<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Configuracion extends MY_BackendController {

    public function __construct() {
        parent::__construct();

        UsuarioBackendSesion::force_login();
        
//        if(UsuarioBackendSesion::usuario()->rol!='super' && UsuarioBackendSesion::usuario()->rol!='configuracion'){
        if(!in_array('super', explode(',',UsuarioBackendSesion::usuario()->rol) ) && !in_array( 'configuracion',explode(',',UsuarioBackendSesion::usuario()->rol))){
            echo 'No tiene permisos para acceder a esta seccion.';
            exit;
        }
    }

    public function index() {
        redirect('backend/configuracion/misitio');
    }


    public function grupos_usuarios() {
        $data['grupos_usuarios'] = Doctrine::getTable('GrupoUsuarios')->findByCuentaId(UsuarioBackendSesion::usuario()->cuenta_id);

        $data['title'] = 'Configuración de Grupos de Usuarios';
        $data['content'] = 'backend/configuracion/grupos_usuarios';

        $this->load->view('backend/template', $data);
    }

    public function grupo_usuarios_editar($grupo_usuarios_id = NULL) {
        if ($grupo_usuarios_id) {
            $grupo_usuarios = Doctrine::getTable('GrupoUsuarios')->find($grupo_usuarios_id);

            if ($grupo_usuarios->cuenta_id != UsuarioBackendSesion::usuario()->cuenta_id) {
                echo 'No tiene permisos para editar este grupo de usuarios';
                exit;
            }

            $data['grupo_usuarios'] = $grupo_usuarios;
        }

        $data['title'] = 'Configuración de Grupo de Usuarios';
        $data['content'] = 'backend/configuracion/grupo_usuarios_editar';

        $this->load->view('backend/template', $data);
    }

    public function grupo_usuarios_editar_form($grupo_usuarios_id = NULL) {
        $grupo_usuarios=NULL;
        if ($grupo_usuarios_id) {
            $grupo_usuarios = Doctrine::getTable('GrupoUsuarios')->find($grupo_usuarios_id);

            if ($grupo_usuarios->cuenta_id != UsuarioBackendSesion::usuario()->cuenta_id) {
                echo 'No tiene permisos para editar este grupo de usuarios';
                exit;
            }
        }

        $this->form_validation->set_rules('nombre', 'Nombre', 'required');

        $respuesta=new stdClass();
        if ($this->form_validation->run() == TRUE) {
            if (!$grupo_usuarios)
                $grupo_usuarios = new GrupoUsuarios();

            $grupo_usuarios->nombre = $this->input->post('nombre');
            $grupo_usuarios->cuenta_id = UsuarioBackendSesion::usuario()->cuenta_id;
            $grupo_usuarios->setUsuariosFromArray($this->input->post('usuarios'));
            $grupo_usuarios->save();

            $respuesta->validacion = TRUE;
            $respuesta->redirect = site_url('backend/configuracion/grupos_usuarios');
        }else {
            $respuesta->validacion = FALSE;
            $respuesta->errores = validation_errors();
        }

        echo json_encode($respuesta);
    }

    public function grupo_usuarios_eliminar($grupo_usuarios_id) {
        $grupo_usuarios = Doctrine::getTable('GrupoUsuarios')->find($grupo_usuarios_id);

        if ($grupo_usuarios->cuenta_id != UsuarioBackendSesion::usuario()->cuenta_id) {
            echo 'No tiene permisos para eliminar este grupo de usuarios';
            exit;
        }

        $grupo_usuarios->delete();

        redirect('backend/configuracion/grupos_usuarios');
    }

    public function usuarios() {
        $data['usuarios'] = Doctrine::getTable('Usuario')->findByCuentaId(UsuarioBackendSesion::usuario()->cuenta_id);

        $data['title'] = 'Configuración de Usuarios';
        $data['content'] = 'backend/configuracion/usuarios';

        $this->load->view('backend/template', $data);
    }

    public function usuario_editar($usuario_id = NULL) {
        if ($usuario_id) {
            $usuario = Doctrine::getTable('Usuario')->find($usuario_id);

            if ($usuario->cuenta_id != UsuarioBackendSesion::usuario()->cuenta_id) {
                echo 'Usuario no tiene permisos para editar este usuario.';
                exit;
            }

            $data['usuario'] = $usuario;
        }
        $data['grupos_usuarios'] = Doctrine::getTable('GrupoUsuarios')->findByCuentaId(UsuarioBackendSesion::usuario()->cuenta_id);

        $data['title'] = 'Configuración de Usuarios';
        $data['content'] = 'backend/configuracion/usuario_editar';

        $this->load->view('backend/template', $data);
    }

    public function usuario_editar_form($usuario_id = NULL) {
        $usuario=NULL;
        if ($usuario_id) {
            $usuario = Doctrine::getTable('Usuario')->find($usuario_id);

            if ($usuario->cuenta_id != UsuarioBackendSesion::usuario()->cuenta_id) {
                echo 'Usuario no tiene permisos para editar este usuario.';
                exit;
            }
        }
        
        if(!$usuario){
            $this->form_validation->set_rules('usuario', 'Nombre de Usuario', 'required|alpha_dash|callback_check_existe_usuario');
            $this->form_validation->set_rules('password', 'Contraseña', 'required|min_length[6]|matches[password_confirm]');
        }
        if($this->input->post('password')){
            $this->form_validation->set_rules('password', 'Contraseña', 'required|min_length[6]|matches[password_confirm]');
            $this->form_validation->set_rules('password_confirm', 'Confirmar contraseña');
        }
        $this->form_validation->set_rules('nombres', 'Nombres', 'required');
        $this->form_validation->set_rules('apellido_paterno', 'Apellido Paterno', 'required');
        $this->form_validation->set_rules('apellido_materno', 'Apellido Materno', 'required');
        $this->form_validation->set_rules('email', 'Correo electrónico', 'valid_email|callback_check_existe_email['.($usuario?$usuario->id:'').']');

        $respuesta=new stdClass();
        if ($this->form_validation->run() == TRUE) {
            if (!$usuario){
                $usuario = new Usuario();
                $usuario->usuario = $this->input->post('usuario');
            }

            
            if($this->input->post('password')) $usuario->setPasswordWithSalt($this->input->post('password'));
            $usuario->nombres = $this->input->post('nombres');
            $usuario->apellido_paterno = $this->input->post('apellido_paterno');
            $usuario->apellido_materno = $this->input->post('apellido_materno');
            $usuario->email = $this->input->post('email');
            $usuario->vacaciones = $this->input->post('vacaciones');
            $usuario->setGruposUsuariosFromArray($this->input->post('grupos_usuarios'));
            $usuario->cuenta_id = UsuarioBackendSesion::usuario()->cuenta_id;
            $usuario->save();

            $respuesta->validacion = TRUE;
            $respuesta->redirect = site_url('backend/configuracion/usuarios');
        }else {
            $respuesta->validacion = FALSE;
            $respuesta->errores = validation_errors();
        }

        echo json_encode($respuesta);
    }

    public function usuario_eliminar($usuario_id) {
        $usuario = Doctrine::getTable('Usuario')->find($usuario_id);

        if ($usuario->cuenta_id != UsuarioBackendSesion::usuario()->cuenta_id) {
            echo 'Usuario no tiene permisos para eliminar este usuario.';
            exit;
        }
        
        if($usuario->Etapas->count()){
            $this->session->set_flashdata('message_error','No se puede eliminar usuario ya que participa en tramites existentes en el sistema.');
        }else{
            $usuario->delete();
        }

        

        redirect('backend/configuracion/usuarios');
    }
    
    public function backend_usuarios() {
        $data['usuarios'] = Doctrine::getTable('UsuarioBackend')->findByCuentaId(UsuarioBackendSesion::usuario()->cuenta_id);

        $data['title'] = 'Configuración de Usuarios';
        $data['content'] = 'backend/configuracion/backend_usuarios';

        $this->load->view('backend/template', $data);
    }
    
    public function backend_usuario_editar($usuario_id = NULL) {
        if ($usuario_id) {
            $usuario = Doctrine::getTable('UsuarioBackend')->find($usuario_id);

            if ($usuario->cuenta_id != UsuarioBackendSesion::usuario()->cuenta_id) {
                echo 'Usuario no tiene permisos para editar este usuario.';
                exit;
            }

            $data['usuario'] = $usuario;
        }

        $data['procesos'] = Doctrine_Query::create()
                            ->from('Proceso p, p.Cuenta c')
                            ->where('p.activo=1 AND c.id = ?',UsuarioBackendSesion::usuario()->cuenta_id)
                            ->orderBy('p.nombre asc')
                            ->execute();

        $data['title'] = 'Configuración de Usuarios';
        $data['content'] = 'backend/configuracion/backend_usuario_editar';

        $this->load->view('backend/template', $data);
    }
    
    public function backend_usuario_editar_form($usuario_id = NULL) {
        $usuario=NULL;
        if ($usuario_id) {
            $usuario = Doctrine::getTable('UsuarioBackend')->find($usuario_id);

            if ($usuario->cuenta_id != UsuarioBackendSesion::usuario()->cuenta_id) {
                echo 'Usuario no tiene permisos para editar este usuario.';
                exit;
            }
        }
        if(!$usuario){
            $this->form_validation->set_rules('email', 'E-Mail', 'required|valid_email|callback_check_existe_usuario_backend');
            $this->form_validation->set_rules('password', 'Contraseña', 'required|min_length[6]|matches[password_confirm]');
        }
        if($this->input->post('password')){
            $this->form_validation->set_rules('password', 'Contraseña', 'required|min_length[6]|matches[password_confirm]');
            $this->form_validation->set_rules('password_confirm', 'Confirmar contraseña');
        }
        $this->form_validation->set_rules('nombre', 'Nombre', 'required');
        $this->form_validation->set_rules('apellidos', 'Apellidos', 'required');
        $this->form_validation->set_rules('rol', 'Rol', 'required');

        $respuesta=new stdClass();
        if ($this->form_validation->run() == TRUE) {
            if (!$usuario){
                $usuario = new UsuarioBackend();
                $usuario->email = $this->input->post('email');
            }

            
            if($this->input->post('password')) $usuario->setPasswordWithSalt($this->input->post('password'));            
            $usuario->nombre = $this->input->post('nombre');
            $usuario->apellidos =  $this->input->post('apellidos');            
            /*se agrega con el fin de cubrir la necesidad de tener un usuario con muchos roles*/
            $usuario->rol =  implode("," , $this->input->post('rol'));
            $usuario->cuenta_id = UsuarioBackendSesion::usuario()->cuenta_id;
            $usuario->procesos = $this->input->post('procesos') ? implode("," , $this->input->post('procesos')) : NULL;
            $usuario->save();
            $respuesta->validacion = TRUE;
            $respuesta->redirect = site_url('backend/configuracion/backend_usuarios');
        }else {
            $respuesta->validacion = FALSE;
            $respuesta->errores = validation_errors();
        }

        echo json_encode($respuesta);
    }
    
    public function backend_usuario_eliminar($usuario_id) {
        $usuario = Doctrine::getTable('UsuarioBackend')->find($usuario_id);

        if ($usuario->cuenta_id != UsuarioBackendSesion::usuario()->cuenta_id) {
            echo 'Usuario no tiene permisos para eliminar este usuario.';
            exit;
        }

        $usuario->delete();

        redirect('backend/configuracion/backend_usuarios');
    }
    
    public function misitio(){
        $data['cuenta']=Doctrine::getTable('Cuenta')->find(UsuarioBackendSesion::usuario()->cuenta_id);
        
        $data['title'] = 'Configuración de Usuarios';
        $data['content'] = 'backend/configuracion/misitio';
        $this->load->view('backend/template', $data);
    }
    
    public function misitio_form() {      
        $this->form_validation->set_rules('nombre_largo', 'Nombre largo', 'required');

        $respuesta=new stdClass();
        if ($this->form_validation->run() == TRUE) {
            $cuenta=Doctrine::getTable('Cuenta')->find(UsuarioBackendSesion::usuario()->cuenta_id);

            $cuenta->nombre_largo=$this->input->post('nombre_largo');
            $cuenta->mensaje=$this->input->post('mensaje');
            $cuenta->logo=$this->input->post('logo');
            $cuenta->descarga_masiva=$this->input->post('descarga_masiva');
            $cuenta->save();

            $respuesta->validacion = TRUE;
            $respuesta->redirect = site_url('backend/configuracion/misitio');
        }else {
            $respuesta->validacion = FALSE;
            $respuesta->errores = validation_errors();
        }

        echo json_encode($respuesta);
    }
    
    public function plantillas(){
        $data['cuenta']=Doctrine::getTable('Cuenta')->find(UsuarioBackendSesion::usuario()->cuenta_id);
        $data['title'] = 'Configuración Plantillas';
        $data['content'] = 'backend/configuracion/plantillas';
        $this->load->view('backend/template', $data);
    }
    
    public function plantillas_form(){
        $this->form_validation->set_rules('nombre_visible','Nombre Plantilla','required');
        $this->form_validation->set_rules('nombre_plantilla', 'Archivo Plantilla', 'required');
        $respuesta=new stdClass();
        if ($this->form_validation->run() == TRUE) {
            
            $existe=Doctrine::getTable('Config')->findOneByIdparAndCuentaIdAndNombre(1,UsuarioBackendSesion::usuario()->cuenta_id,$this->input->post('nombre_plantilla'));
            if(!$existe) {
                $plantilla = new Config();
                $plantilla->idpar=1;
                $plantilla->cuenta_id=UsuarioBackendSesion::usuario()->cuenta_id;
                $plantilla->endpoint='plantilla';
                $plantilla->nombre_visible =$this->input->post('nombre_visible');
                $plantilla->nombre =$this->input->post('nombre_plantilla');

                $plantilla->save();
            } else {
                $existe->nombre_visible =$this->input->post('nombre_visible');
                $existe->nombre =$this->input->post('nombre_plantilla');
                $existe->save();   
            }
            $respuesta->validacion = TRUE;
            $respuesta->redirect = site_url('backend/configuracion/plantilla_seleccion');
         }else {
            $respuesta->validacion = FALSE;
            $respuesta->errores = validation_errors();
        }
       echo json_encode($respuesta);
    }

    public function plantilla_seleccion($plantilla_id=''){    
        if ($plantilla_id!=''){
            if ($plantilla_id!=1) {
                $cuenta_id = UsuarioBackendSesion::usuario()->cuenta_id;
                $cuentahasconfig = Doctrine::getTable('CuentaHasConfig')->findOneByIdparAndCuentaId(1,$cuenta_id);
                    if ($cuentahasconfig==FALSE){
                        $ctahascfg = new cuentahasconfig();
                        $ctahascfg->idpar = 1;
                        $ctahascfg->config_id = $plantilla_id;
                        $cuenta_id=UsuarioBackendSesion::usuario()->cuenta_id;
                        $ctahascfg->cuenta_id = $cuenta_id;
                        $ctahascfg->save();   
                       
                    } else { 
                        $cuentahasconfig->config_id = $plantilla_id;
                        $cuenta_id=UsuarioBackendSesion::usuario()->cuenta_id;
                        $cuentahasconfig->cuenta_id = $cuenta_id;
                        $cuentahasconfig->save();   
                    }
            } else 
            {
                $cuenta_id = UsuarioBackendSesion::usuario()->cuenta_id;
                $cuentahasconfig = Doctrine::getTable('CuentaHasConfig')->findByIdparAndCuentaId(1,$cuenta_id);
                $cuentahasconfig->delete();
            }

        }
            $data['config_id'] =  $plantilla_id;
            $cuenta_id=UsuarioBackendSesion::usuario()->cuenta_id;
            $data['config']=Doctrine::getTable('Config')->findByIdparAndCuentaIdOrCuentaId(1,$cuenta_id,0);
            $data['title'] = 'Selección de Plantilla';
            $data['content'] = 'backend/configuracion/plantillas_seleccion';
            $this->load->view('backend/template', $data);     
    }

    public function plantilla_eliminar($plantilla_id=''){
        if (!$plantilla_id==''){
            $cuenta_id = UsuarioBackendSesion::usuario()->cuenta_id;   
            //busco plantilla por defecto 
            $config=Doctrine::getTable('Config')->findOneByIdparAndNombre(1,'default');
            $id_default=$config->id;
            $idpar_default=$config->idpar;  

            //Busco Id de Plantilla a eliminar, almaceno valores a eliminar
            $cuentahasconfig = Doctrine::getTable('CuentaHasConfig')->findOneByIdparAndConfigIdAndCuentaId(1,$plantilla_id,$cuenta_id);
            $config=Doctrine::getTable('Config')->findOneByIdAndIdpar($plantilla_id,1);
            $nombre_eliminar = $config->nombre;
            $config->delete();
            
            if (!$cuentahasconfig === FALSE){ 
                $cuentahasconfig->idpar = $idpar_default;
                $cuentahasconfig->config_id = $id_default;
                $cuentahasconfig->cuenta_id = UsuarioBackendSesion::usuario()->cuenta_id;
                $cuentahasconfig->save();   
            }
            $source = 'uploads/themes/'.$cuenta_id.'/'. $nombre_eliminar . '/';
            $filedestino = 'application/views/themes/'.$cuenta_id.'/'. $nombre_eliminar . '/';
            $this->load->helper("file");
            delete_files($source, true);
            delete_files($filedestino, true);
            rmdir($source);
            rmdir($filedestino);
        }
            
        $cuenta_id=UsuarioBackendSesion::usuario()->cuenta_id;
        $data['config']=Doctrine::getTable('Config')->findByIdparAndCuentaIdOrCuentaId(1,$cuenta_id,0);
        $cuentahasconfig = Doctrine::getTable('CuentaHasConfig')->findOneByIdparAndCuentaId(1,UsuarioBackendSesion::usuario()->cuenta_id);
        $data['config_id'] = 1;
        if ($cuentahasconfig){
            $data['config_id'] = $cuentahasconfig->config_id;
        }    

        $data['title'] = 'Selección de Plantilla';
        $data['content'] = 'backend/configuracion/plantillas_seleccion';
        $this->load->view('backend/template', $data);
        
    }

    //public function modelador(){
      //  $data['cuenta']=Doctrine::getTable('Cuenta')->find(UsuarioBackendSesion::usuario()->cuenta_id);
        //print_r($data['cuenta']);
        //exit;
      //  $data['title'] = 'Configuración Modelador';
      //  $data['content'] = 'backend/configuracion/modelador';
      //  $this->load->view('backend/template', $data);
  //  }

    public function modelador($conector_id=''){
        if (!$conector_id==''){
            $cuenta_id = UsuarioBackendSesion::usuario()->cuenta_id;
            $cuentahasconfig = Doctrine::getTable('CuentaHasConfig')->findOneByIdparAndCuentaId(2,$cuenta_id);
            if ($cuentahasconfig==FALSE){
                $ctahascfg = new cuentahasconfig();
                $ctahascfg->idpar = 2;
                $ctahascfg->config_id = $conector_id;
                $ctahascfg->cuenta_id = $cuenta_id;
                $ctahascfg->save();   
               
            } else { 
                $cuentahasconfig->config_id = $conector_id;
                $cuentahasconfig->cuenta_id = UsuarioBackendSesion::usuario()->cuenta_id;
                $cuentahasconfig->save();   
            }

            $data['config_id'] =  $conector_id;
            $data['config']=Doctrine::getTable('Config')->findByIdpar(2);
            $data['title'] = 'Selección de Conector';
            $data['content'] = 'backend/configuracion/modelador';
            $this->load->view('backend/template', $data);    
        } else {
            
            $data['config']=Doctrine::getTable('Config')->findByIdpar(2);
            $cuentahasconfig = Doctrine::getTable('CuentaHasConfig')->findOneByIdparAndCuentaId(2,UsuarioBackendSesion::usuario()->cuenta_id);
            $data['config_id'] = 2;
            if ($cuentahasconfig){
                $data['config_id'] = $cuentahasconfig->config_id;
            }    

            $data['title'] = 'Selección de Conector';
            $data['content'] = 'backend/configuracion/modelador';
            $this->load->view('backend/template', $data);
        }
    }
    public function feriados(){
        $data['title'] = 'Feriados';
        $data['content'] = 'backend/configuracion/feriados';
        $this->load->view('backend/template', $data);
    }



    function check_existe_usuario($email){
        $u=Doctrine::getTable('Usuario')->findOneByUsuario($email);
        if(!$u)
            return TRUE;
        
        $this->form_validation->set_message('check_existe_usuario','%s ya existe');
        return FALSE;
             
    }
    
    function check_existe_email($email,$usuario_id){
        $u=Doctrine::getTable('Usuario')->findOneByEmail($email);
        
        if(!$u || ($u && $u->id==$usuario_id))
            return TRUE;
        
        $this->form_validation->set_message('check_existe_email','%s ya esta en uso por otro usuario');
        return FALSE;
             
    }
    
    function check_existe_usuario_backend($email){
        $u=Doctrine::getTable('UsuarioBackend')->findOneByEmail($email);
        if(!$u)
            return TRUE;
        
        $this->form_validation->set_message('check_existe_usuario_backend','%s ya existe en cuenta: '.$u->Cuenta->nombre);
        return FALSE;
             
    }

    function ajax_get_usuarios(){
        $query=$this->input->get('query');

        $doctrineQuery=Doctrine_Query::create()
            ->from('Usuario u')
            ->select('u.id, CONCAT(IF(u.open_id,u.rut,u.usuario),IF(u.email IS NOT NULL,CONCAT(" - ",u.email),"")) as text');

        if (strlen($query) >= 3) {
            $doctrineQuery->having('text LIKE ?', '%' . $query . '%')
                ->where('u.registrado = 1');
        } else {
            $doctrineQuery->where('u.cuenta_id = ?', UsuarioBackendSesion::usuario()->cuenta_id);
        }

        $usuarios = $doctrineQuery->execute();

        header('Content-Type: application/json');
        echo json_encode($usuarios->toArray());

    }

    function ajax_get_validacion_reglas() {

        $rule = (isset($_GET['rule'])) ? $_GET['rule'] : '';
        $proceso_id = (isset($_GET['proceso_id'])) ? $_GET['proceso_id'] : '';

        log_message('debug', 'ajax_get_validacion_reglas() $rule [' .  $rule . ']');

        $code = 200;
        header('Content-Type: application/json');

        if (strlen($rule) > 0) {
            $regla = new Regla($rule);
            $mensaje = $regla->validacionVariablesEnReglas($proceso_id);

            if (isset($mensaje) && count($mensaje) == 0) {
                $code = 202;
            } else {
                $mensaje = "Las sgtes. variables no existen: <br>" . implode(', ', $mensaje);
            }
        } else {
            $code = 202;
            $mensaje = "";
        }
        echo json_encode(array('code'=>$code, 'mensaje'=>$mensaje));
    }

    function nueva_conf_cms() {
        $url=(isset($_POST['url']))?$_POST['url']:'';
        $username=(isset($_POST['user']))?$_POST['user']:'';
        $pass=(isset($_POST['pass']))?$_POST['pass']:'';
        $carpeta=(isset($_POST['carpeta']))?$_POST['carpeta']:'';
        $titulo=(isset($_POST['titulo']))?$_POST['titulo']:'';
        $descripcion=(isset($_POST['descripcion']))?$_POST['descripcion']:'';
        $chkintegracioncms=(isset($_POST['chkintegracioncms']))?$_POST['chkintegracioncms']:0;
        $mensaje='';
        $code=0;
        if($chkintegracioncms==1){
            try{
                $cms=new Config_cms_alfresco();
                $cms->setUrlCMS($url);
                $cms->setUserName($username);
                $cms->setPassword($pass);
                $cms->setCarpetaRaiz($carpeta);
                $cms->setTitulo($titulo);
                $cms->setCuenta(UsuarioBackendSesion::usuario()->cuenta_id);
                $cms->setDescripcion($descripcion);
                $cms->setCheck($chkintegracioncms);

                $cms->save();
                $code=200;
            }catch(Exception $err){
                $mensaje=$err->getMessage();
            }
        }else{
            try{
                $cms=new Config_cms_alfresco();
                $cms->setCheck($chkintegracioncms);
                $cms->setCuenta(UsuarioBackendSesion::usuario()->cuenta_id);
                $cms->updateCheck();
                $code=200;
            }catch(Exception $err){
                $mensaje=$err->getMessage();
            }
        }
        echo json_encode(array('code'=>$code,'mensaje'=>$mensaje));
    }
    function ajax_modal_info(){
        $this->load->view('backend/configuracion/ajax_modal_info');
    }

    public function conf_services(){
        $data['title'] = 'Configuración de Services';
        $data['content'] = 'backend/configuracion/conf_services';
        $data['appkey']='';
        $data['domain']='';
        try{
            $service=new Connect_services();
            $service->setCuenta(UsuarioBackendSesion::usuario()->cuenta_id);
            $service->load_data();

            $data['appkey']=$service->getAppkey();
            $data['domain']=$service->getDomain();

        }catch(Exception $err){
            echo 'Error: '.$err->getMessage();
        }
        $this->load->view('backend/template', $data);   
    }
    
    public function datos_services(){
        $appkey=(isset($_POST['appkey']))?$_POST['appkey']:'';
        $domain=(isset($_POST['domain']))?$_POST['domain']:'';
        $uri=(isset($_POST['uri']))?$_POST['uri']:'';
        $context=(isset($_POST['context']))?$_POST['context']:'';
        $records=(isset($_POST['records']))?$_POST['records']:0;
        $mensaje='';
        $code=0;
        try{
            $service=new Connect_services();
            $service->setAppkey($appkey);
            $service->setDomain($domain);
            /*$service->setBaseService($uri);
            $service->setContext($context);
            $service->setNumeroRegistroPagina($records);*/
            $service->setCuenta(UsuarioBackendSesion::usuario()->cuenta_id);
            $service->save();
            $code=200;
        }catch(Exception $err){
            $mensaje=$err->getMessage();
        }
        echo json_encode(array('code'=>$code,'mensaje'=>$mensaje));
    }
}