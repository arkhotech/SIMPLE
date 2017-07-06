<?php

use InoOicClient\Flow\Basic;
use InoOicClient\Http;
use InoOicClient\Client;
use InoOicClient\Oic\Token;

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Autenticacion extends MY_Controller {

    protected $authConfig;

    public function __construct() {
        parent::__construct();
        $this->authConfig = array(
               'client_info' => array(
                   'client_id' => Cuenta::cuentaSegunDominio()->client_id,
                   'redirect_uri' => site_url('autenticacion/callback'),
                   'authorization_endpoint' => 'https://www.claveunica.gob.cl/openid/authorize',
                   'token_endpoint' => 'https://www.claveunica.gob.cl/openid/token',
                   'user_info_endpoint' => 'https://www.claveunica.gob.cl/openid/userinfo',
                   'authentication_info' => array(
                       'method' => 'client_secret_post',
                       'params' => array(
                           'client_secret' => Cuenta::cuentaSegunDominio()->client_secret
                       )
                   )
               )
           );
    }
    
    public function login_openid() {

        /*
        $this->load->library('LightOpenID');
        $redirect = $this->input->get('redirect') ? $this->input->get('redirect') : site_url();
        $this->lightopenid->returnUrl = $redirect;
        $this->lightopenid->required = array('person/guid', 'namePerson/first', 'namePerson/last', 'namePerson/secondLast', 'contact/email');
        redirect($this->lightopenid->authUrl());
        */

        $redirectlogin = $this->input->get('redirect') ? $this->input->get('redirect') : site_url();
        setcookie('redirectlogin', '', time()-3600);
        setcookie("redirectlogin", $redirectlogin, time()+3600);
        $flow = new Basic($this->authConfig);
        if (! isset($_GET['code'])) {
            try {
                $uri = $flow->getAuthorizationRequestUri('openid nombre');
                redirect($uri);
            } catch (\Exception $e) {
                printf("Exception during authorization URI creation: [%s] %s", get_class($e), $e->getMessage());
            }
        }
    }

     public function callback() {
        $flow = new Basic($this->authConfig);
        $token = $flow->getAccessToken($_GET['code']);
        $infoPersonal = $flow->getUserInfo($token);
        $rut = $infoPersonal['RUT'];
        $rut = str_replace(".", "", $rut);
        $CI = & get_instance();
        $CI->session->set_flashdata('openidcallback',1);
        $CI->session->set_flashdata('rut',$rut);
        $redirectlogin = $_COOKIE['redirectlogin'];
        redirect($redirectlogin);
    }

    function validate_captcha() {
        $CI = & get_instance();
        log_message('debug', 'validate_captcha() : secretkey [' . $CI->config->item('secretkey') . ']');
        $captcha = $this->input->post('g-recaptcha-response');
        $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=" . $CI->config->item('secretkey') . "&response=" . $captcha . "&remoteip=" . $_SERVER['REMOTE_ADDR']); 
        if ($response . 'success' == false) {
            return FALSE; 
        } else {
            return TRUE;
        }
    }

    public function login_form() {

        $this->form_validation->set_rules('usuario', 'Usuario', 'required');
        $this->form_validation->set_rules('password', 'Contraseña', 'required|callback_check_password');
        $this->form_validation->set_rules('g-recaptcha-response', 'reCAPTCHA', 'required|callback_validate_captcha');
        $this->form_validation->set_message('validate_captcha', 'Please check the the captcha form');

        $respuesta = new stdClass();
        if ($this->form_validation->run() == TRUE) {
            UsuarioSesion::login($this->input->post('usuario'), $this->input->post('password'));
            $respuesta->validacion = TRUE;
            $respuesta->redirect = $this->input->post('redirect') ? $this->input->post('redirect') : site_url();
        } else {
            $respuesta->validacion = FALSE;
            $respuesta->errores = validation_errors();
        }

        echo json_encode($respuesta);
    }

    public function login() {
        if($this->input->get('redirect'))
            $data['redirect'] = $this->input->get('redirect');
        else
            $data['redirect'] = $this->session->flashdata('redirect');

        $data['title'] = 'Login';
        $this->load->view('autenticacion/login', $data);
    }

    public function registrar() {
        $data['title'] = 'Registro';
        $this->load->view('autenticacion/registrar', $data);
    }

    public function registrar_form() {
        $this->form_validation->set_rules('usuario', 'Nombre de Usuario', 'required|alpha_dash|callback_check_usuario');
        $this->form_validation->set_rules('nombres', 'Nombres', 'required');
        $this->form_validation->set_rules('apellido_paterno', 'Apellido Paterno', 'required');
        $this->form_validation->set_rules('apellido_materno', 'Apellido Materno', 'required');
        $this->form_validation->set_rules('password', 'Contraseña', 'required|min_length[6]|matches[password_confirm]');
        $this->form_validation->set_rules('password_confirm', 'Confirmar contraseña');
        $this->form_validation->set_rules('email', 'Correo electrónico', 'required|valid_email|callback_check_email');

        $respuesta=new stdClass();
        if ($this->form_validation->run() == TRUE) {
            $usuario = new Usuario();
            $usuario->usuario = $this->input->post('usuario');
            $usuario->setPasswordWithSalt($this->input->post('password'));
            $usuario->nombres = $this->input->post('nombres');
            $usuario->apellido_paterno = $this->input->post('apellido_paterno');
            $usuario->apellido_materno = $this->input->post('apellido_materno');
            $usuario->email = $this->input->post('email');
            $usuario->save();
            
            $cuenta=Cuenta::cuentaSegunDominio();
            if(is_a($cuenta, 'Cuenta'))
                $this->email->from($cuenta->nombre.'@'.$this->config->item('main_domain'), $cuenta->nombre_largo);
            else
                $this->email->from('simple@'.$this->config->item('main_domain'), 'Simple');
            $this->email->to($usuario->email);
            $this->email->subject('Bienvenido');
            $this->email->message('<p>Usted ya es parte de la plataforma para hacer trámites en línea.</p><p>Su nombre de usuario es: '.$usuario->usuario.'</p>');
            $this->email->send();

            UsuarioSesion::login($this->input->post('usuario'), $this->input->post('password'));

            $respuesta->validacion = TRUE;
            $respuesta->redirect = site_url();
        } else {
            $respuesta->validacion = FALSE;
            $respuesta->errores = validation_errors();
        }

        echo json_encode($respuesta);
    }

    public function olvido() {        
        $data['title']='Olvide mi contraseña';
        $this->load->view('autenticacion/olvido',$data);
    }

    public function olvido_form() {
        $this->form_validation->set_rules('usuario', 'Usuario', 'required|callback_check_usuario_existe');

        $respuesta=new stdClass();
        if ($this->form_validation->run() == TRUE) {
            $random=random_string('alnum',16);
            
            $usuario = Doctrine::getTable('Usuario')->findOneByUsuarioAndOpenId($this->input->post('usuario'),0);
            if(!$usuario){
                $usuario = Doctrine::getTable('Usuario')->findOneByEmailAndOpenId($this->input->post('usuario'),0);
            }
            $usuario->reset_token=$random;
            $usuario->save();

            $cuenta=Cuenta::cuentaSegunDominio();
            if(is_a($cuenta, 'Cuenta'))
                $this->email->from($cuenta->nombre.'@'.$this->config->item('main_domain'), $cuenta->nombre_largo);
            else
                $this->email->from('simple@'.$this->config->item('main_domain'), 'Simple');
            $this->email->to($usuario->email);
            $this->email->subject('Reestablecer contraseña');
            $this->email->message('<p>Haga click en el siguiente link para reestablecer su contraseña:</p><p><a href="'.site_url('autenticacion/reestablecer?id='.$usuario->id.'&reset_token='.$usuario->reset_token).'">'.site_url('autenticacion/reestablecer?id='.$usuario->id.'&reset_token='.$usuario->reset_token).'</a></p>');
            $this->email->send();
            
            $this->session->set_flashdata('message','Se le ha enviado un correo con instrucciones de como reestablecer su contraseña.');
            
            $respuesta->validacion = TRUE;
            $respuesta->redirect = site_url('autenticacion/login');
        } else {
            $respuesta->validacion = FALSE;
            $respuesta->errores = validation_errors();
        }

        echo json_encode($respuesta);
    }
    
    public function reestablecer(){
        $id=$this->input->get('id');
        $reset_token=$this->input->get('reset_token');
        
        $usuario=Doctrine::getTable('Usuario')->find($id);
        
        if(!$usuario){
            echo 'Usuario no existe';
            exit;
        }
        if(!$reset_token){
            echo 'Faltan parametros';
            exit;
        }
        
        //$usuario_input=new Usuario();
        //$usuario_input->reset_token=$reset_token;
        
        if($usuario->reset_token!=$reset_token or is_array($reset_token)){
            echo 'Token incorrecto';
            exit;
        }
        
        $data['usuario']=$usuario;
        $data['title']='Reestablecer';
        $this->load->view('autenticacion/reestablecer',$data);  
    }
    
    public function reestablecer_form(){
        $id=$this->input->get('id');
        $reset_token=$this->input->get('reset_token');
        
        $usuario=Doctrine::getTable('Usuario')->find($id);
        
        if(!$usuario){
            echo 'Usuario no existe';
            exit;
        }
        if(!$reset_token){
            echo 'Faltan parametros';
            exit;
        }
        
        //$usuario_input=new Usuario();
        //$usuario_input->reset_token=$reset_token;

        
        
        if($usuario->reset_token!=$reset_token or is_array($reset_token)){
            echo 'Token incorrecto';
            exit;
        }
        
        $this->form_validation->set_rules('password','Contraseña','required|min_length[6]');
        $this->form_validation->set_rules('password_confirm','Confirmar contraseña','required|matches[password]');
        
        $respuesta=new stdClass();
        if ($this->form_validation->run() == TRUE) {
            $usuario->password=$this->input->post('password');
            $usuario->reset_token=null;
            $usuario->save();
            
            $this->session->set_flashdata('message','Su contraseña se ha reestablecido.');
            
            $respuesta->validacion = TRUE;
            $respuesta->redirect = site_url('autenticacion/login');
        } else {
            $respuesta->validacion = FALSE;
            $respuesta->errores = validation_errors();
        }

        echo json_encode($respuesta);
    }

    function logout() {
        UsuarioSesion::logout();
        redirect('');
    }

    function check_password($password) {
        $autorizacion = UsuarioSesion::validar_acceso($this->input->post('usuario'), $this->input->post('password'));

        if ($autorizacion)
            return TRUE;

        $this->form_validation->set_message('check_password', 'Usuario y/o contraseña incorrecta.');
        return FALSE;
    }

    function check_usuario($usuario) {
        $usuario = Doctrine::getTable('Usuario')->findOneByUsuario($usuario);

        if (!$usuario)
            return TRUE;

        $this->form_validation->set_message('check_usuario', 'Usuario ya existe.');
        return FALSE;
    }
    
    function check_email($email) {
        $usuario = Doctrine::getTable('Usuario')->findOneByEmailAndOpenId($email,0);

        if (!$usuario)
            return TRUE;

        $this->form_validation->set_message('check_email', 'Correo electrónico ya esta en uso por otro usuario.');
        return FALSE;
    }

    function check_usuario_existe($usuario_o_email) {
        $usuario = Doctrine::getTable('Usuario')->findOneByUsuarioAndOpenId($usuario_o_email,0);
        if(!$usuario){
            $usuario = Doctrine::getTable('Usuario')->findOneByEmailAndOpenId($usuario_o_email,0);
        }

        if ($usuario)
            return TRUE;

        $this->form_validation->set_message('check_usuario_existe', 'Usuario no existe.');
        return FALSE;
    }

}