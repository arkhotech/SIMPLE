<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Cuentas extends CI_Controller
{
    public function __construct() {
        parent::__construct();

        UsuarioManagerSesion::force_login();
    }

    public function index() {
        $data['cuentas'] = Doctrine::getTable('Cuenta')->findAll();

        $data['title'] = 'Cuentas';
        $data['content'] = 'manager/cuentas/index';

        $this->load->view('manager/template', $data);
    }

    public function editar($cuenta_id = null) {

        if ($cuenta_id) {
            $cuenta = Doctrine::getTable('Cuenta')->find($cuenta_id);
            $service = new Connect_services();
            $service->setCuenta($cuenta_id);
            $service->load_data();
            $calendar = $service;
            
            if ($cuenta != NULL && $cuenta->vinculo_produccion != null && strlen($cuenta->vinculo_produccion) > 0) {
                log_message('debug', 'if: $cuenta->vinculo_produccion: ' . $cuenta->vinculo_produccion);
                $data['cuentas_productivas'] = $this->getListCuentasProductivas($cuenta_id, $cuenta->vinculo_produccion);
            } else {
                log_message('debug', 'else: ' . $cuenta_id);
                $data['cuentas_productivas'] = $this->getListCuentasProductivas($cuenta_id);
            }
        } else {
            $data['cuentas_productivas'] = $this->getListCuentasProductivas();
            $cuenta = new Cuenta();
            $calendar = new Connect_services();
        }


        $data['cuenta'] = $cuenta;
        $data['calendar'] = $calendar;
        $data['title'] = $cuenta->id ? 'Editar' : 'Crear';
        $data['content'] = 'manager/cuentas/editar';

        $this->load->view('manager/template', $data);
    }

    public function editar_form($cuenta_id = null) {
        Doctrine_Manager::connection()->beginTransaction();

        try {

            if ($cuenta_id)
                $cuenta = Doctrine::getTable('Cuenta')->find($cuenta_id);
            else
                $cuenta = new Cuenta();

            $this->form_validation->set_rules('nombre', 'Nombre', 'required|url_title');
            $this->form_validation->set_rules('nombre_largo', 'Nombre largo', 'required');
            $this->form_validation->set_rules('domain', 'Dominio', 'required');

            if ($this->input->post('desarrollo') == 'on') {
                $this->form_validation->set_rules('vinculo_produccion', 'Vinculo Producción', 'required');
            }

            $respuesta = new stdClass();
            if ($this->form_validation->run() == true) {
                $cuenta->nombre = $this->input->post('nombre');
                $cuenta->nombre_largo = $this->input->post('nombre_largo');
                $cuenta->mensaje = $this->input->post('mensaje');

                if ($this->input->post('desarrollo') == 'on') {
                    $cuenta->ambiente = 'dev';
                    $cuenta->vinculo_produccion = $this->input->post('vinculo_produccion');
                    $stmn = Doctrine_Manager::getInstance()->connection();
                    $sql_desvinculo_produccion = "UPDATE cuenta SET vinculo_produccion = NULL, ambiente='prod' WHERE vinculo_produccion = " . $cuenta_id;
                    $result = $stmn->prepare($sql_desvinculo_produccion);
                    $result->execute();
                } else {
                    $cuenta->ambiente = 'prod';
                    $cuenta->vinculo_produccion = NULL;
                }

                $cuenta->logo = $this->input->post('logo');
                $cuenta->save();
                $cuenta_id = (int)$cuenta->id;

                if ($cuenta_id > 0) {
                    if ($cuenta->ambiente == 'dev') {
                        $stmn = Doctrine_Manager::getInstance()->connection();
                        $sql_desvinculo_produccion = "UPDATE cuenta SET vinculo_produccion = NULL, ambiente='prod' WHERE vinculo_produccion = " . $cuenta_id;
                        $result = $stmn->prepare($sql_desvinculo_produccion);
                        $result->execute();
                    }

                    // Calendar
                    $service = new Connect_services();
                    $service->setAppkey($this->config->item('appkey'));
                    $service->setDomain($this->input->post('domain'));
                    $service->setCuenta($cuenta_id);
                    $service->save();

                    Doctrine_Manager::connection()->commit();

                    $this->session->set_flashdata('message','Cuenta guardada con éxito.');
                    $respuesta->validacion = true;
                    $respuesta->redirect = site_url('manager/cuentas');

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

    public function eliminar($cuenta_id)
    {
        $cuenta = Doctrine::getTable('Cuenta')->find($cuenta_id);
        $cuenta->delete();

        $this->session->set_flashdata('message', 'Cuenta eliminada con éxito.');
        redirect('manager/cuentas');
    }

    // Método que obtiene todas las cuentas productivas a las cuales se puede asociar la cuenta $idcuenta
    private function getListCuentasProductivas($cuenta_id = null, $vinculo_prod = null) {

        log_message('debug', 'getListCuentasProductivas(' . $cuenta_id . ')');
        
        $stmn = Doctrine_Manager::getInstance()->connection();

        if ($cuenta_id != null && $vinculo_prod != null) {
            $sql_vinculo_produccion = "SELECT id, nombre FROM cuenta WHERE ambiente = 'prod' AND id != " . $cuenta_id . " AND id NOT IN (SELECT vinculo_produccion FROM cuenta WHERE vinculo_produccion IS NOT NULL AND vinculo_produccion != " . $vinculo_prod . ")";
        } else if ($cuenta_id != null && $vinculo_prod == null) {
            $sql_vinculo_produccion = "SELECT id, nombre FROM cuenta WHERE ambiente = 'prod' AND id != " . $cuenta_id . " AND id NOT IN (SELECT vinculo_produccion FROM cuenta WHERE vinculo_produccion IS NOT NULL)";
        } else {
            $sql_vinculo_produccion = "SELECT id, nombre FROM cuenta WHERE ambiente = 'prod' AND id NOT IN (SELECT vinculo_produccion FROM cuenta WHERE vinculo_produccion IS NOT NULL)";            
        }

        $result = $stmn->prepare($sql_vinculo_produccion);
        $result->execute();
        $vinculo_prod = $result->fetchAll();
        
        log_message('debug', 'vinculo_produccion OK ['. json_encode($vinculo_prod) . ']');
        
        return $vinculo_prod;
    }
}
