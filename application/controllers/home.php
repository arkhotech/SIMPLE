<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Home extends MY_Controller {

    private $domain = '';
    private $appkey = '';
    private $base_services = '';
    private $context = '';
    private $records = 10;

    public function __construct() {
        parent::__construct();
        include APPPATH . 'third_party/httpful/bootstrap.php';
        $this->base_services = $this->config->item('base_service');
        $this->context = $this->config->item('context_service');
        $this->records = $this->config->item('records');
        $cuenta = UsuarioSesion::usuario()->cuenta_id;
        $cuenta = (isset($cuenta) && is_numeric($cuenta) && $cuenta > 0) ? $cuenta : 1;
        try {
            $service = new Connect_services();
            $service->setCuenta($cuenta);
            $service->load_data();
            $this->domain = $service->getDomain();
            $this->appkey = $service->getAppkey();
        } catch (Exception $err) {
            log_message ('error', 'Home Constructor ' . $err);
        }
    }

    public function index() {

        $procesos = Doctrine::getTable('Proceso')->findProcesosDisponiblesParaIniciar(UsuarioSesion::usuario()->id, Cuenta::cuentaSegunDominio(), 'nombre', 'asc');
        $cate = Doctrine::getTable('Categoria')->findAll();

        $cat_ids = array();
        $num_destacados = 0;
        $num_otros = 0;
        foreach ($procesos as $key => &$p) {
            /* if (strlen($p->nombre) > 45 ) {
                $p->nombre = substr($p->nombre, 0, 40) . '...';
            } */
            if ($p->destacado) {
                $num_destacados++;
            } else {
                $num_otros++;
            }
            array_push($cat_ids, $p->categoria_id);
        }

        $categorias = array();
        foreach ($cate as $key => $c) {
            if (in_array($c->id, $cat_ids) ) {
                array_push($categorias, $c);
            }
        }

        $data['title'] = 'Home';
        $data['num_destacados'] = $num_destacados;
        $data['num_otros'] = $num_otros;
        $data['procesos'] = $procesos;
        $data['categorias'] = $categorias;
        $data['sidebar'] = 'disponibles';
        $data['redirect'] = site_url('home/index');

        if (UsuarioSesion::usuario()->registrado) {
            $this->load->view('home/user_index', $data);
        } else {
            $this->load->view('home/index', $data);
        }
    }

    public function procesos($categoria_id) {

        $procesos = Doctrine::getTable('Proceso')->findProcesosDisponiblesParaIniciarByCategoria(UsuarioSesion::usuario()->id, $categoria_id, Cuenta::cuentaSegunDominio(),'nombre','asc');
        $categoria = Doctrine::getTable('Categoria')->find($categoria_id);

        $data['procesos'] = $procesos;
        $data['categoria'] = $categoria;
        $data['sidebar'] = 'categorias';

        if (UsuarioSesion::usuario()->registrado) {
            $this->load->view('home/user_index', $data);
        } else {
            $this->load->view('home/index', $data);
        }
    }
}
