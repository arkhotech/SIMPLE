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
        setcookie('redirectlogin', '', time() - 3600);
        setcookie("redirectlogin", $redirectlogin, time() + 3600);
        $flow = new Basic($this->authConfig);
        if (!isset($_GET['code'])) {
            try {
                $uri = $flow->getAuthorizationRequestUri('openid run name');
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
        log_message('debug', 'infoPersonal: ' . json_encode($infoPersonal));
        $infoPer = json_encode($flow->getUserInfo($token));
        $rut = $infoPersonal['RolUnico']['numero'] . '-' . $infoPersonal['RolUnico']['DV'];
        log_message('debug','rut: ' . $rut);
        $nombres = implode(" ", $infoPersonal['name']['nombres']);
        $apellidoPaterno = $infoPersonal['name']['apellidos'][0];
        $apellidoMaterno = $infoPersonal['name']['apellidos'][1];

        $CI = & get_instance();
        $CI->session->set_flashdata('openidcallback', 1);
        $CI->session->set_flashdata('rut', $rut);
        $CI->session->set_flashdata('nombres', $nombres);
        $CI->session->set_flashdata('apellidoPaterno', $apellidoPaterno);
        $CI->session->set_flashdata('apellidoMaterno', $apellidoMaterno);
        $redirectlogin = $_COOKIE['redirectlogin'];

        log_message('debug', 'flow: ' . json_encode($flow));
        redirect($redirectlogin);
    }

    public function login_form() {

        log_message('info', '{"method" : "login_form", "location" : "START"}');

        $this->form_validation->set_rules('usuario', 'Usuario', 'required');
        $this->form_validation->set_rules('password', 'Contraseña', 'required|callback_check_password');

        $mostrar_captcha = self::mostrar_captcha($this->input->post('usuario'));

        if ($mostrar_captcha == TRUE) {
            log_message('debug', '{"method" : "login_form", "message" : "mostrar_captcha OK"}');
            $this->form_validation->set_rules('g-recaptcha-response', 'reCAPTCHA', 'required|callback_validate_captcha');
            $this->form_validation->set_message('validate_captcha', 'Please check the the captcha form');
        }

        $respuesta = new stdClass();

        if ($this->form_validation->run() == TRUE) {

            self::login_correcto($this->input->post('usuario'));
            $this->session->set_flashdata('login_erroneo', 'FALSE');

            $respuestaLogin = UsuarioSesion::login($this->input->post('usuario'), $this->input->post('password'));

            $respuesta->validacion = TRUE;
            $respuesta->redirect = $this->input->post('redirect') ? $this->input->post('redirect') : site_url();
            $login_status = 'LOGIN_OK';

        } else {

            $this->session->set_flashdata('login_erroneo', 'TRUE');
            self::login_incorrecto($this->input->post('usuario'));

            $login_status = 'LOGIN_NOK';

            $respuesta->validacion = FALSE;
            $respuesta->errores = validation_errors();
        }

        echo json_encode($respuesta);

        log_message('info', '{"method" : "login_form", "location" : "END", "status" : "' . $login_status . '"}');
    }

    function validate_captcha() {
        $CI = & get_instance();
        $captcha = $this->input->post('g-recaptcha-response');
        $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=" . $CI->config->item('secretkey') . "&response=" . $captcha . "&remoteip=" . $_SERVER['REMOTE_ADDR']); 
        if ($response . 'success' == false) {
            return FALSE; 
        } else {
            return TRUE;
        }
    }

    private function login_correcto($usuario_o_email) {

        log_message('info', '{"method" : "login_correcto", "location" : "START", "parameters" : [{"usuario_o_email": "' . $usuario_o_email . '"}]}');

        try {
            // Limpia en BBDD login erroneo.
            $usuario = Doctrine::getTable('LoginErroneo')->findByUsuario($usuario_o_email);
            $usuario->delete();

            log_message('info', '{"method" : "login_correcto", "location" : "END", "status" : "OK"}');

        } catch (Exception $err) {
            log_message('error', '{"method" : "login_correcto", "location" : "END", "status" : "ERROR", "exception" : "' . $err .'"}');
        }
    }

    private function login_incorrecto($usuario_o_email) {

        log_message('info', '{"method" : "loginErroneo", "location" : "START", "parameters" : [{"usuario_o_email": "' . $usuario_o_email . '"}]}');

        try {

            $horario = new DateTime();

            // Guarda en BBDD login erroneo.
            $loginErroneo = new LoginErroneo();
            $loginErroneo->usuario = $usuario_o_email;
            $loginErroneo->horario = $horario->format("Y-m-d H:i:s");
            $loginErroneo->save();

            log_message('info', '{"method" : "loginErroneo", "location" : "END", "status" : "OK"}');

        } catch (Exception $err) {
            log_message('error', '{"method" : "loginErroneo", "location" : "END", "status" : "ERROR", "exception" : "' . $err .'"}');
        }
    }

    private function mostrar_captcha($usuario) {

        log_message('info', '{"method" : "mostrar_captcha", "location" : "START", "parameters" : [{"usuario": "' . $usuario . '"}]}');

        try {

            $horario = new DateTime();
            $horario->modify('-3 hour');

            $result = Doctrine_Query::create()
            ->select('COUNT(*) AS intentos')
            ->from('LoginErroneo')
            ->where("usuario = ? AND horario > ?", array($usuario, $horario->format("Y-m-d H:i:s")))
            ->execute();

            if ($result[0]->intentos >= 1) {
                log_message('info', '{"method" : "mostrar_captcha", "location" : "END", "status" : "OK", "return" : "TRUE"}');
                return TRUE;
            } else {
                log_message('info', '{"method" : "mostrar_captcha", "location" : "END", "status" : "OK", "return" : "FALSE"}');
                return FALSE;
            }

        } catch (Exception $err) {
            log_message('error', '{"method" : "mostrar_captcha", "location" : "END", "status" : "ERROR", "exception" : "' . $err . '"}');
        }
    }

    public function login() {

        log_message('debug', 'login() inicio');

        if($this->input->get('redirect'))
            $data['redirect'] = $this->input->get('redirect');
        else
            $data['redirect'] = $this->session->flashdata('redirect');

        $data['title'] = 'Login';
        $this->load->view('autenticacion/login', $data);
    }

    /*
     * Deprecated: Req. "Eliminar la capacidad de crear nuevos usuarios en la interfaz que utilizan los ciudadanos"
     * Proyecto: 617-143-CT17 SIMPLE Mejoras Usabilidad.
    public function registrar() {

        $data['title'] = 'Registro';
        $this->load->view('autenticacion/registrar', $data);
    }
    */

    /*
     * Deprecated: Req. "Eliminar la capacidad de crear nuevos usuarios en la interfaz que utilizan los ciudadanos"
     * Proyecto: 617-143-CT17 SIMPLE Mejoras Usabilidad.
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
    */

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
    
    public function reestablecer() {
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
    
    public function reestablecer_form() {
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