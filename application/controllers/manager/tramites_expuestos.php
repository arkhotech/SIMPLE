<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Tramites_expuestos extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        
        UsuarioManagerSesion::force_login();
    }

    public function index()
    { 
        $data['cuentas'] = Doctrine::getTable('Cuenta')->findAll();
        $data['title'] = 'Trámites expuestos';
        $data['content'] = 'manager/tramites_expuestos/index';
        $data['json'] = Doctrine::getTable('Proceso')->findProcesosExpuestos();
        $this->load->view('manager/template', $data);
    }

    public function buscar_cuenta()
    {  
        $data['cuentas'] = Doctrine::getTable('Cuenta')->findAll();
        $cuenta_id=$this->input->post('cuenta_id');
        $data['json'] = Doctrine::getTable('Proceso')->findProcesosExpuestos($cuenta_id);
        $data['title'] = 'Busqueda de trámites expuestos';
        $data['content'] = 'manager/tramites_expuestos/index';
        $data['cuenta_sel'] = $cuenta_id;
        $this->load->view('manager/template', $data);
    }
}