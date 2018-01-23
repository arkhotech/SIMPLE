<?php

class Cuenta extends Doctrine_Record {

    function setTableDefinition() {
        $this->hasColumn('id');
        $this->hasColumn('nombre');
        $this->hasColumn('nombre_largo');
        $this->hasColumn('mensaje');
        $this->hasColumn('logo');
        $this->hasColumn('api_token');
        $this->hasColumn('descarga_masiva');
        $this->hasColumn('client_id');
        $this->hasColumn('client_secret');
        $this->hasColumn('ambiente');
        $this->hasColumn('vinculo_produccion');
    }

    function setUp() {
        parent::setUp();

        $this->hasMany('UsuarioBackend as UsuariosBackend', array(
            'local' => 'id',
            'foreign' => 'cuenta_id'
        ));

        $this->hasMany('Usuario as Usuarios', array(
            'local' => 'id',
            'foreign' => 'cuenta_id'
        ));

        $this->hasMany('GrupoUsuarios as GruposUsuarios', array(
            'local' => 'id',
            'foreign' => 'cuenta_id'
        ));

        $this->hasMany('Proceso as Procesos', array(
            'local' => 'id',
            'foreign' => 'cuenta_id'
        ));

        $this->hasMany('Widget as Widgets', array(
            'local' => 'id',
            'foreign' => 'cuenta_id',
            'orderBy' => 'posicion'
        ));

        $this->hasMany('HsmConfiguracion as HsmConfiguraciones', array(
            'local' => 'id',
            'foreign' => 'cuenta_id'
        ));
    }

    public function updatePosicionesWidgetsFromJSON($json) {
        $posiciones = json_decode($json);

        Doctrine_Manager::connection()->beginTransaction();
        foreach ($this->Widgets as $c) {
            $c->posicion = array_search($c->id, $posiciones);
            $c->save();
        }
        Doctrine_Manager::connection()->commit();
    }

    // Retorna el objecto cuenta perteneciente a este dominio.
    // Retorna null si no estamos en ninguna cuenta valida.
    public static function cuentaSegunDominio() {
        static $firstTime = true;
        static $cuentaSegunDominio = null;
        if ($firstTime) {
            $firstTime = false;
            $CI = &get_instance();
            $host = $CI->input->server('HTTP_HOST');
            log_message('debug', '$host: ' . $host);
            $main_domain = $CI->config->item('main_domain');
            if ($main_domain) {
                log_message('debug', '$main_domain2: ' . $main_domain);
                $main_domain = addcslashes($main_domain, '.');
                preg_match('/(.+)\.' . $main_domain . '/', $host, $matches);
                log_message('debug', '$main_domain2: ' . $main_domain);
                if (isset ($matches[1])) {
                    log_message('debug', '$matches: ' . $matches[1]);
                    $cuentaSegunDominio = Doctrine::getTable('Cuenta')->findOneByNombre($matches[1]);
                }
            } else {
                $cuentaSegunDominio = Doctrine_Query::create()->from('Cuenta c')->limit(1)->fetchOne();
            }
        }

        return $cuentaSegunDominio;
    }

    public function getLogoADesplegar() {
        if ($this->logo)
            return base_url('uploads/logos/' . $this->logo);
        else
            return base_url('assets/img/logo.png');
    }

    public function usesClaveUnicaOnly() {
        foreach ($this->Procesos as $p) {
            $tareaInicial=$p->getTareaInicial();
            if ($tareaInicial && $tareaInicial->acceso_modo!='claveunica')
                return false;
        }

        return true;
    }

    public function getAmbienteDev($cuenta_prod_id){

        $cuenta_dev = Doctrine_Query::create()
            ->from('Cuenta c')
            ->where('c.vinculo_produccion = ?', $cuenta_prod_id)
            ->execute();

        return $cuenta_dev;
    }

    public function getProcesosActivos() {

        log_message('debug', 'getProcesosActivos: ' . $this->id);

        $procesos = Doctrine_Query::create()
            ->from('Proceso p, p.Cuenta c')
            ->where('p.estado="public" AND p.activo=1 AND c.id = ?', $this->id)
            ->execute();

        return $procesos;
    }

}