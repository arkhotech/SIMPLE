<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Autenticacion extends MY_BackendController {

    public function  __construct() {
        parent::__construct();
    }

    public function login() {
        $data['redirect'] = $this->session->flashdata('redirect');
        $this->load->view('backend/autenticacion/login', $data);
    }

    public function login_form() {

        log_message('info', '{"method" : "login_form", "location" : "START"}');

        $this->form_validation->set_rules('email', 'E-Mail', 'required');
        $this->form_validation->set_rules('password', 'Contraseña', 'required|callback_check_password');

        $mostrar_captcha = self::mostrar_captcha($this->input->post('email'));

        if ($mostrar_captcha == TRUE) {
            log_message('debug', '{"method" : "login_form", "message" : "mostrar_captcha OK"}');
            $this->form_validation->set_rules('g-recaptcha-response', 'reCAPTCHA', 'required|callback_validate_captcha');
            $this->form_validation->set_message('validate_captcha', 'Please check the the captcha form');
        }

        $respuesta = new stdClass();
        if ($this->form_validation->run() == TRUE) {

            self::login_correcto($this->input->post('email'));
            $this->session->set_flashdata('login_erroneo', 'FALSE');

            UsuarioBackendSesion::login($this->input->post('email'), $this->input->post('password'));

            $respuesta->validacion = TRUE;
            $respuesta->redirect = $this->input->post('redirect') ? $this->input->post('redirect') : site_url('backend');
            $login_status = 'LOGIN_OK';

        } else {

            $this->session->set_flashdata('login_erroneo', 'TRUE');
            self::login_incorrecto($this->input->post('email'));

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

    public function olvido() {        
        $data['title']='Olvide mi contraseña';
        $this->load->view('backend/autenticacion/olvido',$data);
    }

    public function olvido_form() {

        $this->form_validation->set_rules('email', 'E-Mail', 'required|callback_check_usuario_existe');

        $respuesta = new stdClass();
        if ($this->form_validation->run() == TRUE) {
            $random = random_string('alnum', 16);

            $usuario = Doctrine::getTable('UsuarioBackend')->findOneByEmail($this->input->post('email'));
            $usuario->reset_token = $random;
            $usuario->save();

            $cuenta = Cuenta::cuentaSegunDominio();
            if (is_a($cuenta, 'Cuenta'))
                $this->email->from($cuenta->nombre . '@' . $this->config->item('main_domain'), $cuenta->nombre_largo);
            else
                $this->email->from('simple@'. $this->config->item('main_domain'), 'Simple');

            $this->email->to($usuario->email);
            $this->email->subject('Reestablecer contraseña');
            $this->email->message('<p>Haga click en el siguiente link para reestablecer su contraseña:</p><p><a href="'.site_url('backend/autenticacion/reestablecer?id='.$usuario->id.'&reset_token='.$random).'">'.site_url('autenticacion/reestablecer?id=' . $usuario->id . '&reset_token=' . $random) . '</a></p>');
            $this->email->send();

            $this->session->set_flashdata('message','Se le ha enviado un correo con instrucciones de como reestablecer su contraseña.');

            $respuesta->validacion = TRUE;
            $respuesta->redirect = site_url('backend/autenticacion/login');
        } else {
            $respuesta->validacion = FALSE;
            $respuesta->errores = validation_errors();
        }

        echo json_encode($respuesta);
    }

    public function reestablecer() {
        $id = $this->input->get('id');
        $reset_token = $this->input->get('reset_token');

        $usuario = Doctrine::getTable('UsuarioBackend')->find($id);

        if (!$usuario) {
            echo 'Usuario no existe';
            exit;
        }

        if (!$reset_token) {
            echo 'Faltan parametros';
            exit;
        }

        $usuario_input = new UsuarioBackend();
        $usuario_input->reset_token = $reset_token;

        if ($usuario->reset_token!=$usuario_input->reset_token or is_array($reset_token)) {
            echo 'Token incorrecto';
            exit;
        }

        $data['usuario'] = $usuario;
        $data['title'] = 'Reestablecer';
        $this->load->view('backend/autenticacion/reestablecer', $data);
    }

    public function reestablecer_form() {
        $id = $this->input->get('id');
        $reset_token = $this->input->get('reset_token');

        $usuario = Doctrine::getTable('UsuarioBackend')->find($id);

        if (!$usuario) {
            echo 'Usuario no existe';
            exit;
        }

        if (!$reset_token) {
            echo 'Faltan parametros';
            exit;
        }

        $usuario_input = new UsuarioBackend();
        $usuario_input->reset_token = $reset_token;

        if ($usuario->reset_token!=$usuario_input->reset_token or is_array($reset_token)) {
            echo 'Token incorrecto';
            exit;
        }

        $this->form_validation->set_rules('password', 'Contraseña', 'required|min_length[6]');
        $this->form_validation->set_rules('password_confirm', 'Confirmar contraseña', 'required|matches[password]');

        $respuesta = new stdClass();
        if ($this->form_validation->run() == TRUE) {
            $usuario->password = $this->input->post('password');
            $usuario->reset_token = null;
            $usuario->save();

            $this->session->set_flashdata('message', 'Su contraseña se ha reestablecido.');

            $respuesta->validacion = TRUE;
            $respuesta->redirect = site_url('backend/autenticacion/login');
        } else {
            $respuesta->validacion = FALSE;
            $respuesta->errores = validation_errors();
        }

        echo json_encode($respuesta);
    }

    function logout() {
        UsuarioBackendSesion::logout();
        redirect($this->input->server('HTTP_REFERER'));
    }

    function check_password($password) {
        $autorizacion = UsuarioBackendSesion::validar_acceso($this->input->post('email'), $this->input->post('password'));

        if ($autorizacion)
            return TRUE;

        $this->form_validation->set_message('check_password', 'E-Mail y/o contraseña incorrecta.');
        return FALSE;
    }

    function check_usuario_existe($usuario) {
        $usuario = Doctrine::getTable('UsuarioBackend')->findOneByEmail($usuario);

        if ($usuario) {
            $cuenta = Cuenta::cuentaSegunDominio();

            if ($usuario->Cuenta->id == $cuenta->id)
                return TRUE;
        }

        $this->form_validation->set_message('check_usuario_existe', 'Usuario no existe.');
        return FALSE;
    }
}
