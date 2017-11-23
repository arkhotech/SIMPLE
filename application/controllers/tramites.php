<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Tramites extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        redirect('etapas/inbox');
    }

    /*public function participados() {
        $data['tramites']=Doctrine::getTable('Tramite')->findParticipados(UsuarioSesion::usuario()->id, Cuenta::cuentaSegunDominio());        
        $data['sidebar']='participados';
        $data['content'] = 'tramites/participados';
        $data['title'] = 'Bienvenido';
        $this->load->view('template', $data);
    }*/
	public function participados($offset = 0) {
        $this->load->library('pagination');
        $this->load->helper('form');
        $this->load->helper('url');

        $query = $this->input->post('query');
        $matches = "";
        $rowtramites = "";
        $contador = "0";
        $resultotal = "false";
        $perpage = 50;

        if ($query) {
            $this->load->library('sphinxclient');
            $this->sphinxclient->setServer ( $this->config->item ( 'sphinx_host' ), $this->config->item ( 'sphinx_port' ) );
            $this->sphinxclient->SetLimits($offset, 10000);
            $result = $this->sphinxclient->query(json_encode($query), 'tramites');

            if ($result['total'] > 0 ) {
                $resultotal="true";
            } else {
                $resultotal="false";
            }
        }

       /*
        $statement = Doctrine_Manager::getInstance()->connection();
        $results = $statement->execute("Select * from dato_seguimiento where nombre='desc_proceso_tramite' limit 1");
        $datos=$results->fetchAll();
        foreach($datos as $d){  echo $d['valor'];}
        */
        if ($resultotal == 'true') {
                $matches = array_keys($result['matches']);
                $contador = Doctrine::getTable('Tramite')->findParticipadosMatched(UsuarioSesion::usuario()->id, Cuenta::cuentaSegunDominio(), $matches, $query)->count();
                $rowtramites = Doctrine::getTable('Tramite')->findParticipados(UsuarioSesion::usuario()->id, Cuenta::cuentaSegunDominio(), $perpage, $offset, $matches, $query);
        } else {
                $rowtramites = Doctrine::getTable('Tramite')->findParticipados(UsuarioSesion::usuario()->id, Cuenta::cuentaSegunDominio(), $perpage, $offset, '0', $query);
                $contador = Doctrine::getTable('Tramite')->findParticipadosALL(UsuarioSesion::usuario()->id, Cuenta::cuentaSegunDominio())->count();
        }

        $config['base_url'] = site_url('tramites/participados');
        $config['total_rows'] = $contador;
        $config['per_page'] = $perpage;
        $config['full_tag_open'] = '<div class="pagination pagination-centered"><ul>';
        $config['full_tag_close'] = '</ul></div>';
        $config['page_query_string']=false;
        $config['query_string_segment']='offset';
        $config['first_link'] = 'Primero';
        $config['first_tag_open'] = '<li>';
        $config['first_tag_close'] = '</li>';
        $config['last_link'] = 'Último';
        $config['last_tag_open'] = '<li>';
        $config['last_tag_close'] = '</li>';
        $config['next_link'] = '»';
        $config['next_tag_open'] = '<li>';
        $config['next_tag_close'] = '</li>';
        $config['prev_link'] = '«';
        $config['prev_tag_open'] = '<li>';
        $config['prev_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li class="active"><a href="#">';
        $config['cur_tag_close'] = '</a></li>';
        $config['num_tag_open'] = '<li>';
        $config['num_tag_close'] = '</li>';

        $this->pagination->initialize($config);
        $data['tramites'] = $rowtramites;
        $data['query'] = $query;
        $data['sidebar'] = 'participados';
        $data['content'] = 'tramites/participados';
        $data['title'] = 'Bienvenido';

        $data['links'] = $this->pagination->create_links();
        $this->load->view('template_newhome', $data);
    }

    public function disponibles() {

        // $orderby=$this->input->get('orderby')?$this->input->get('orderby'):'nombre';
        // $direction=$this->input->get('direction')?$this->input->get('direction'):'asc';

        $data['procesos']=Doctrine::getTable('Proceso')->findProcesosDisponiblesParaIniciar(UsuarioSesion::usuario()->id, Cuenta::cuentaSegunDominio(), 'nombre', 'asc');

        // $data['orderby']=$orderby;
        // $data['direction']=$direction;
        $data['sidebar'] ='disponibles';
        $data['content'] = 'tramites/disponibles';
        $data['title'] = 'Trámites disponibles a iniciar';
        $this->load->view('template_newhome', $data);
    }

    public function iniciar($proceso_id) {
        log_message('Info', 'Iniciando proceso '.$proceso_id, FALSE);

        $proceso=Doctrine::getTable('Proceso')->find($proceso_id);
        //echo UsuarioSesion::usuario()->id;
        //exit;
        if(!$proceso->canUsuarioIniciarlo(UsuarioSesion::usuario()->id)){
            echo 'Usuario no puede iniciar este proceso';
            exit;
        }
   
        //Vemos si es que usuario ya tiene un tramite de proceso_id ya iniciado, y que se encuentre en su primera etapa.
        //Si es asi, hacemos que lo continue. Si no, creamos uno nuevo
        $tramite=Doctrine_Query::create()
                ->from('Tramite t, t.Proceso p, t.Etapas e, e.Tramite.Etapas hermanas')
                ->where('t.pendiente=1 AND p.activo=1 AND p.id = ? AND e.usuario_id = ?',array($proceso_id, UsuarioSesion::usuario()->id))
                ->groupBy('t.id')
                ->having('COUNT(hermanas.id) = 1')
                ->fetchOne();
        
        if(!$tramite){
            $tramite=new Tramite();
            $tramite->iniciar($proceso->id);
        }  
        
    
        $qs=$this->input->server('QUERY_STRING');
        redirect('etapas/ejecutar/'.$tramite->getEtapasActuales()->get(0)->id.($qs?'?'.$qs:''));
    }
    
    public function eliminar($tramite_id){
        $tramite=Doctrine::getTable('Tramite')->find($tramite_id);
                
        if($tramite->Etapas->count()>1){
            echo 'Tramite no se puede eliminar, ya ha avanzado mas de una etapa';
            exit;
        }
        
        if(UsuarioSesion::usuario()->id!=$tramite->Etapas[0]->usuario_id){
            echo 'Usuario no tiene permisos para eliminar este tramite';
            exit;
        }
        
        $tramite->delete();
        redirect($this->input->server('HTTP_REFERER'));
    }

}
