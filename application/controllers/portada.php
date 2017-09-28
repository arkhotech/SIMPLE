<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Portada extends MY_Controller {

    public function __construct() {
        parent::__construct();

    }

    public function index() {
        $pendientes=Doctrine::getTable('Etapa')->findPendientes(UsuarioSesion::usuario()->id);
        redirect('home/index');
    }

}