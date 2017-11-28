<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Etapas extends MY_Controller {

    public function __construct() {
        parent::__construct();
        require_once(APPPATH.'controllers/agenda.php'); //include Agenda controller
    }

    public function inbox() {
        $buscar = $this->input->get('buscar');
        $orderby=$this->input->get('orderby')?$this->input->get('orderby'):'updated_at';
        $direction=$this->input->get('direction')?$this->input->get('direction'):'desc';

        $matches = "";
        $rowetapas = "";
        $resultotal = "";

        if ($buscar) {
            $this->load->library('sphinxclient');
            $this->sphinxclient->setServer ( $this->config->item ( 'sphinx_host' ), $this->config->item ( 'sphinx_port' ) );
            $this->sphinxclient->SetLimits(0, 10000);
            $result = $this->sphinxclient->query(json_encode($buscar), 'tramites');
            if ($result['total'] > 0 ) {
                $resultotal = "true";
            } else {
                $resultotal = "false";
            }
        }

        if ($resultotal == "true") {
            $matches = array_keys($result['matches']);
            $rowetapas = Doctrine::getTable('Etapa')->findPendientes(UsuarioSesion::usuario()->id, Cuenta::cuentaSegunDominio(),$orderby,$direction, $matches, $buscar);
        } else {
            $rowetapas = Doctrine::getTable('Etapa')->findPendientes(UsuarioSesion::usuario()->id, Cuenta::cuentaSegunDominio(),$orderby,$direction, "0", $buscar);
        }

        $data['etapas'] = $rowetapas;
        $data['buscar'] = $buscar;
        $data['orderby'] = $orderby;
        $data['direction'] = $direction;
        $data['sidebar'] = 'inbox';
        $data['content'] = 'etapas/inbox';
        $data['title'] = 'Bandeja de Entrada';
        $this->load->view('template_newhome', $data);
    }

    public function sinasignar($offset = 0) {

        if (!UsuarioSesion::usuario()->registrado) {
            $this->session->set_flashdata('redirect', current_url());
            redirect('autenticacion/login');
        }

        $this->load->library('pagination');
        $buscar = $this->input->get('query');

        $matches = "";
        $rowetapas = "";
        $resultotal = false;
        $contador = "0";
        $perpage = 50;

        if ($buscar) {
            $this->load->library('sphinxclient');
            $this->sphinxclient->setServer ( $this->config->item ( 'sphinx_host' ), $this->config->item ( 'sphinx_port' ) );
            $this->sphinxclient->SetLimits($offset, 10000);
            $result = $this->sphinxclient->query(json_encode($buscar), 'tramites');             
            if ($result['total'] > 0 ) {
                $resultotal = true;
            } else {
                $resultotal = false;
            }
        }

        if ($resultotal == true) {
            $matches = array_keys($result['matches']);
            $contador = Doctrine::getTable('Etapa')->findSinAsignar(UsuarioSesion::usuario()->id, Cuenta::cuentaSegunDominio(),$matches,$buscar,0,$perpage)->count();
            $rowetapas=Doctrine::getTable('Etapa')->findSinAsignar(UsuarioSesion::usuario()->id, Cuenta::cuentaSegunDominio(),$matches,$buscar,0,$perpage);
            error_log("true" . " cantidad " .$contador);

        } else {
            $contador = Doctrine::getTable('Etapa')->findAllSinAsignar(UsuarioSesion::usuario()->id, Cuenta::cuentaSegunDominio())->count();
            $rowetapas= Doctrine::getTable('Etapa')->findSinAsignar(UsuarioSesion::usuario()->id, Cuenta::cuentaSegunDominio(),"0",$buscar,$offset,$perpage);
            error_log("false" . " cantidad " .$contador);
        }

        $config['base_url'] = site_url('etapas/sinasignar');
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
        // $data['etapas'] = Doctrine::getTable('Etapa')->findSinAsignar(UsuarioSesion::usuario()->id, Cuenta::cuentaSegunDominio());
        $data['links'] = $this->pagination->create_links();
        $data['etapas'] =$rowetapas;
        $data['query'] = $buscar;
        $data['sidebar'] = 'sinasignar';
        $data['content'] = 'etapas/sinasignar';
        $data['title'] = 'Sin Asignar';
        $this->load->view('template_newhome', $data);
    }

    public function ejecutar($etapa_id, $secuencia = 0) {

        $iframe = $this->input->get('iframe');
        $etapa = Doctrine::getTable('Etapa')->find($etapa_id);

        $data['num_pasos'] = self::num_pasos($etapa->Tarea->id);

        if (!$etapa) {
            show_404();
        }

        if ($etapa->usuario_id != UsuarioSesion::usuario()->id) {
            if (!UsuarioSesion::usuario()->registrado) {
                $this->session->set_flashdata('redirect', current_url());
                redirect('autenticacion/login');
            }
            echo 'Usuario no tiene permisos para ejecutar esta etapa.';
            exit;
        }

        if (!$etapa->pendiente) {
            echo 'Esta etapa ya fue completada';
            exit;
        }

        if (!$etapa->Tarea->activa()) {
            echo 'Esta etapa no se encuentra activa';
            exit;
        }

        if ($etapa->vencida()) {
            echo 'Esta etapa se encuentra vencida';
            exit;
        }

        $qs = $this->input->server('QUERY_STRING');
        $paso = $etapa->getPasoEjecutable($secuencia);
        log_message("info","Ejecutando paso: ".$paso, FALSE);
        if (!$paso) {
            log_message("info","Entra en no paso: ", FALSE);
            redirect('etapas/ejecutar_fin/' . $etapa->id . ($qs ? '?' . $qs : ''));
        } else if (($etapa->Tarea->final || !$etapa->Tarea->paso_confirmacion) && $paso->getReadonly() && end($etapa->getPasosEjecutables()) == $paso) { // No se requiere mas input
            $etapa->iniciarPaso($paso);
            $etapa->finalizarPaso($paso);
            $etapa->avanzar();
            redirect('etapas/ver/' . $etapa->id . '/' . (count($etapa->getPasosEjecutables())-1));
        } else {
            $etapa->iniciarPaso($paso);

            $data['secuencia'] = $secuencia;
            $data['etapa'] = $etapa;
            $data['paso'] = $paso;
            $data['qs'] = $this->input->server('QUERY_STRING');

            $data['sidebar'] = UsuarioSesion::usuario()->registrado ? 'inbox' : 'disponibles';
            $data['content'] = 'etapas/ejecutar';
            $data['title'] = $etapa->Tarea->nombre;
            $template = $this->input->get('iframe') ? 'template_iframe' : 'template';

            $this->load->view('template_newhome', $data);
        }
    }

    function num_pasos($tarea_id) {
        log_message('debug', '$etapa->Tarea->id [' . $tarea_id . ']');

        $stmn = Doctrine_Manager::getInstance()->connection();
        $sql_pasos = "SELECT COUNT(*) AS total FROM paso WHERE tarea_id=" . $tarea_id;
        $result = $stmn->prepare($sql_pasos);
        $result->execute();
        $num_pasos = $result->fetchAll();
        log_message('debug', '$num_pasos [' . $num_pasos[0][0] . ']');
        return $num_pasos[0][0];
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

    public function ejecutar_form($etapa_id, $secuencia) {

        log_message('info', 'ejecutar_form ($etapa_id [' . $etapa_id . '], $secuencia [' . $secuencia . '])');

        $etapa = Doctrine::getTable('Etapa')->find($etapa_id);

        if ($etapa->usuario_id != UsuarioSesion::usuario()->id) {
            echo 'Usuario no tiene permisos para ejecutar esta etapa.';
            exit;
        }

        if (!$etapa->pendiente) {
            echo 'Esta etapa ya fue completada';
            exit;
        }

        if (!$etapa->Tarea->activa()) {
            echo 'Esta etapa no se encuentra activa';
            exit;
        }

        if ($etapa->vencida()) {
            echo 'Esta etapa se encuentra vencida';
            exit;
        }

        $paso = $etapa->getPasoEjecutable($secuencia);
        $formulario = $paso->Formulario;
        $modo = $paso->modo;

        $respuesta = new stdClass();

        if ($modo == 'edicion') {
            $validar_formulario = FALSE;
            foreach ($formulario->Campos as $c) {
                // Validamos los campos que no sean readonly y que esten disponibles (que su campo dependiente se cumpla)
                if ($c->isEditableWithCurrentPOST($etapa_id)) {
                    $c->formValidate($etapa->id);
                    $validar_formulario = TRUE;
                }
                if ($c->tipo =='recaptcha') {
                    $this->form_validation->set_rules('g-recaptcha-response', 'Captcha', 'required|callback_validate_captcha');
                    $this->form_validation->set_message('validate_captcha', 'Please check the the captcha form');
                    $validar_formulario = TRUE;
                }
            }
            if (!$validar_formulario || $this->form_validation->run() == TRUE) {
                // Almacenamos los campos
                foreach ($formulario->Campos as $c) {
                    // Almacenamos los campos que no sean readonly y que esten disponibles (que su campo dependiente se cumpla)

                    if ($c->isEditableWithCurrentPOST($etapa_id)) {
                        $dato = Doctrine::getTable('DatoSeguimiento')->findOneByNombreAndEtapaId($c->nombre, $etapa->id);
                        if (!$dato)
                            $dato = new DatoSeguimiento();
                        $dato->nombre = $c->nombre;
                        $dato->valor = $this->input->post($c->nombre)=== false?'' : $this->input->post($c->nombre);

                        if (!is_object($dato->valor) && !is_array($dato->valor)) {
                            if (preg_match('/^\d{4}[\/\-]\d{2}[\/\-]\d{2}$/', $dato->valor)) {
                                $dato->valor=preg_replace("/^(\d{4})[\/\-](\d{2})[\/\-](\d{2})/i", "$3-$2-$1", $dato->valor);
                            }
                        }

                        $dato->etapa_id = $etapa->id;
                        $dato->save();
                    }
                }
                $etapa->save();

                $etapa->finalizarPaso($paso);

                $respuesta->validacion = TRUE;

                $qs = $this->input->server('QUERY_STRING');
                $prox_paso = $etapa->getPasoEjecutable($secuencia + 1);
                if (!$prox_paso) {
                    $respuesta->redirect = site_url('etapas/ejecutar_fin/' . $etapa_id) . ($qs ? '?' . $qs : '');
                } else if ($etapa->Tarea->final && $prox_paso->getReadonly() && end($etapa->getPasosEjecutables()) == $prox_paso) { //Cerrado automatico    
                    $etapa->iniciarPaso($prox_paso);
                    $etapa->finalizarPaso($prox_paso);
                    $etapa->avanzar();
                    $respuesta->redirect = site_url('etapas/ver/' . $etapa->id . '/' . (count($etapa->getPasosEjecutables())-1));
                } else {
                    $respuesta->redirect = site_url('etapas/ejecutar/' . $etapa_id . '/' . ($secuencia + 1)) . ($qs ? '?' . $qs : '');
                }
            } else {
                $respuesta->validacion = FALSE;
                $respuesta->errores = validation_errors();
            }
        } else if ($modo == 'visualizacion') {
            $respuesta->validacion = TRUE;

            $qs = $this->input->server('QUERY_STRING');
            $prox_paso = $etapa->getPasoEjecutable($secuencia + 1);
            if (!$prox_paso) {
                $respuesta->redirect = site_url('etapas/ejecutar_fin/' . $etapa_id) . ($qs ? '?' . $qs : '');
            } else if ($etapa->Tarea->final && $prox_paso->getReadonly() && end($etapa->getPasosEjecutables()) == $prox_paso) { //Cerrado automatico
                $etapa->iniciarPaso($prox_paso);
                $etapa->finalizarPaso($prox_paso);
                $etapa->avanzar();
                $respuesta->redirect = site_url('etapas/ver/' . $etapa->id . '/' . (count($etapa->getPasosEjecutables())-1));
            } else {
                $respuesta->redirect = site_url('etapas/ejecutar/' . $etapa_id . '/' . ($secuencia + 1)) . ($qs ? '?' . $qs : '');
            }
        }

        echo json_encode($respuesta);
    }

    public function asignar($etapa_id) {
        $etapa = Doctrine::getTable('Etapa')->find($etapa_id);

        if ($etapa->usuario_id) {
            echo 'Etapa ya fue asignada.';
            exit;
        }

        if (!$etapa->canUsuarioAsignarsela(UsuarioSesion::usuario()->id)) {
            echo 'Usuario no puede asignarse esta etapa.';
            exit;
        }

        $etapa->asignar(UsuarioSesion::usuario()->id);

        redirect('etapas/inbox');
    }

    public function ejecutar_fin($etapa_id) {
        $etapa = Doctrine::getTable('Etapa')->find($etapa_id);

        if ($etapa->usuario_id != UsuarioSesion::usuario()->id) {
            echo 'Usuario no tiene permisos para ejecutar esta etapa.';
            exit;
        }
        if (!$etapa->pendiente) {
            echo 'Esta etapa ya fue completada';
            exit;
        }
        if (!$etapa->Tarea->activa()) {
            echo 'Esta etapa no se encuentra activa';
            exit;
        }

        //if ($etapa->Tarea->asignacion!='manual') {
        //    $etapa->Tramite->avanzarEtapa();
        //    redirect();
        //    exit;
        //}

        $data['etapa'] = $etapa;
        $data['tareas_proximas'] = $etapa->getTareasProximas();
        $data['qs'] = $this->input->server('QUERY_STRING');

        $data['sidebar'] = UsuarioSesion::usuario()->registrado ? 'inbox' : 'disponibles';
        $data['content'] = 'etapas/ejecutar_fin';
        $data['title'] = $etapa->Tarea->nombre;
        $template = $this->input->get('iframe') ? 'template_iframe' : 'template_newhome';

        $this->load->view('template_newhome', $data);
    }

    public function ejecutar_fin_form($etapa_id) {
        $etapa = Doctrine::getTable('Etapa')->find($etapa_id);

        if ($etapa->usuario_id != UsuarioSesion::usuario()->id) {
            echo 'Usuario no tiene permisos para ejecutar esta etapa.';
            exit;
        }
        if (!$etapa->pendiente) {
            echo 'Esta etapa ya fue completada';
            exit;
        }
        if (!$etapa->Tarea->activa()) {
            echo 'Esta etapa no se encuentra activa';
            exit;
        }

        $respuesta = new stdClass();
        // $etapa->avanzar($this->input->post('usuarios_a_asignar'));
        $respuesta->validacion = TRUE;
        try {
            $agenda = new agenda();
            $appointments = $agenda->obtener_citas_de_tramite($etapa_id);
            if (isset($appointments) && is_array($appointments) && (count($appointments)>=1) ) {
                $json = '{"ids":[';
                $i = 0;
                foreach ($appointments as $item) {
                    if ($i == 0) {
                        $json = $json . '"' . $item . '"';
                    } else {
                        $json = $json . ',"' . $item . '"';
                    }
                    $i++;
                }
                $json = $json.']}';
                $agenda->confirmar_citas_grupo($json);
                $etapa->avanzar($this->input->post('usuarios_a_asignar'));
            } else {
                $etapa->avanzar($this->input->post('usuarios_a_asignar'));
            }

            $proximas = $etapa->getTareasProximas();

            log_message("INFO", "###Id etapa despues de avanzar: ".$etapa->id, FALSE);
            log_message("INFO", "###Id tarea despues de avanzar: ".$etapa->tarea_id, FALSE);
            $cola = new ColaContinuarTramite();
            $tareas_encoladas = $cola->findTareasEncoladas($etapa->tramite_id);
            if($proximas->estado === 'pendiente') {
                log_message("debug", "pendiente");
                foreach ($proximas->tareas as $tarea) {
                    log_message('debug', 'Ejecutando continuar de etapa ' . $tarea->id . " en trámite " . $etapa->tramite_id);
                    $etapa->ejecutarColaContinuarTarea($tarea->id, $tareas_encoladas);
                }
            }
        }catch(Exception $err){
            $respuesta->validacion = false;
            $respuesta->errores = '<div class="alert alert-error"><a class="close" data-dismiss="alert">×</a>'.$err->getMessage().'</div>';
            log_message('error',$err->getMessage());
        }
        
        if ($this->input->get('iframe')) {
            $respuesta->redirect = site_url('etapas/ejecutar_exito');
        } else {
            $respuesta->redirect = site_url();
        }
        echo json_encode($respuesta);
    }
    
    //Pagina que indica que la etapa se completo con exito. Solamente la ven los que acceden mediante iframe.
    public function ejecutar_exito() {
        $data['content'] = 'etapas/ejecutar_exito';
        $data['title'] = 'Etapa completada con éxito';
        $this->load->view('template_iframe', $data);
    }

    public function ver($etapa_id, $secuencia = 0) {
        $etapa = Doctrine::getTable('Etapa')->find($etapa_id);

        if (UsuarioSesion::usuario()->id != $etapa->usuario_id) {
            echo 'No tiene permisos para hacer seguimiento a este tramite.';
            exit;
        }

        $paso = $etapa->getPasoEjecutable($secuencia);

        $data['etapa'] = $etapa;
        $data['paso'] = $paso;
        $data['secuencia'] = $secuencia;

        $data['sidebar'] = 'participados';
        $data['title'] = 'Historial - ' . $etapa->Tarea->nombre;
        $data['content'] = 'etapas/ver';
        $this->load->view('template_newhome', $data);
    }

    public function descargar($tramites) {
        $data['tramites'] = $tramites;
        $this->load->view ('etapas/descargar',$data);
    }

    public function descargar_form() {
        if (!Cuenta::cuentaSegunDominio()->descarga_masiva) {
            echo 'Servicio no tiene permisos para descargar.';
            exit;
        }

        if (!UsuarioSesion::usuario()->registrado) {
            echo 'Usuario no tiene permisos para descargar.';
            exit;
        }
        $tramites = $this->input->post('tramites');
        $opcionesDescarga = $this->input->post('opcionesDescarga');
        $tramites = explode(",",$tramites);
        $ruta_documentos = 'uploads/documentos/';
        $ruta_generados = 'uploads/datos/';
        $ruta_tmp = 'uploads/tmp/';
        $fecha = new DateTime ();
        $fecha = date_format($fecha,"Y-m-d");

        $tipoDocumento = "";
        switch ($opcionesDescarga) {
            case 'documento':
                $tipoDocumento = 'documento';
                break;
            case 'dato':
                $tipoDocumento = 'dato';
                break;
        }

        // Recorriendo los trámites
        $this->load->library('zip');
        foreach ($tramites as $t) {

            if (empty($tipoDocumento)) {
                $files = Doctrine::getTable('File')->findByTramiteId($t);
            } else {
                $files = Doctrine::getTable('File')->findByTramiteIdAndTipo($t,$tipoDocumento);
            }
            if (count($files) > 0) {
                // Recorriendo los archivos
                foreach ($files as $f) {
                    $tr = Doctrine::getTable('Tramite')->find($t);
                    $participado = $tr->usuarioHaParticipado(UsuarioSesion::usuario()->id);
                    if (!$participado) {
                        echo 'Usuario no ha participado en el trámite.';
                        exit;
                    }
                    $nombre_documento = $tr->id;
                    $tramite_nro ='';
                    foreach ($tr->getValorDatoSeguimiento() as $tra_nro) {
                        if ($tra_nro->valor == $f->filename) {
                            $nombre_documento = $tra_nro->nombre;
                        }
                        if ($tra_nro->nombre == 'tramite_ref') {
                            $tramite_nro = $tra_nro->valor;
                        }
                    }
                    if ($f->tipo == 'documento' && !empty($nombre_documento)) {
                        $path = $ruta_documentos.$f->filename;
                        $tramite_nro = $tramite_nro != '' ? $tramite_nro : $tr->Proceso->nombre;
                        $tramite_nro = str_replace(" ","",$tramite_nro);
                        $nombre_archivo = pathinfo($path, PATHINFO_FILENAME);
                        $ext = pathinfo($path, PATHINFO_EXTENSION);
                        $new_file = $ruta_tmp.$nombre_documento.".".$nombre_archivo.".".$tramite_nro.".".$ext;
                        copy($path,$new_file);
                        $this->zip->read_file($new_file);
                        //Eliminación del archivo para no ocupar espacio en disco
                        unlink($new_file);
                    }elseif ($f->tipo == 'dato' && !empty($nombre_documento)) {
                        $path = $ruta_generados.$f->filename;
                        $this->zip->read_file($path);
                    }
                }
                if (count($tramites) > 1) {
                    $tr = Doctrine::getTable('Tramite')->find($t);
                    $tramite_nro = '';
                    foreach ($tr->getValorDatoSeguimiento() as $tra_nro) {
                        if ($tra_nro->nombre == 'tramite_ref') {
                            $tramite_nro = $tra_nro->valor;
                        }
                    }
                    $tramite_nro = $tramite_nro != '' ? $tramite_nro : $tr->Proceso->nombre;
                    $nombre = $fecha."_".$t."_".$tramite_nro;
                    //creando un zip por cada trámite
                    $this->zip->archive($ruta_tmp.$nombre.'.zip');
                    $this->zip->clear_data();
                }
            }
        }
        if (count($tramites) > 1) {
            foreach ($tramites as $t) {
                $tr = Doctrine::getTable('Tramite')->find($t);
                $tramite_nro ='';
                foreach ($tr->getValorDatoSeguimiento() as $tra_nro) {
                   if ($tra_nro->nombre == 'tramite_ref') {
                        $tramite_nro = $tra_nro->valor;
                    }                              
                }                         
                $tramite_nro = $tramite_nro != '' ? $tramite_nro : $tr->Proceso->nombre;
                $nombre = $fecha."_".$t."_".$tramite_nro;
                $this->zip->read_file($ruta_tmp.$nombre.'.zip');
            }
            
            //Eliminando los archivos antes de descargar
            foreach ($tramites as $t) {;
                $tr = Doctrine::getTable('Tramite')->find($t);
                $tramite_nro ='';
                foreach ($tr->getValorDatoSeguimiento() as $tra_nro) {
                   if ($tra_nro->nombre == 'tramite_ref') {
                        $tramite_nro = $tra_nro->valor;
                    }                              
                }                         
                $tramite_nro = $tramite_nro != '' ? $tramite_nro : $tr->Proceso->nombre;
                $nombre = $fecha."_".$t."_".$tramite_nro;
                unlink($ruta_tmp.$nombre.'.zip');
            }
            $this->zip->download('tramites.zip');
        }else{
            $tr = Doctrine::getTable('Tramite')->find($tramites);
            $tramite_nro ='';
            foreach ($tr->getValorDatoSeguimiento() as $tra_nro) {
               if ($tra_nro->nombre == 'tramite_ref') {
                    $tramite_nro = $tra_nro->valor;
                }                              
            }                         
            $tramite_nro = $tramite_nro != '' ? $tramite_nro : $tr->Proceso->nombre;
            $nombre = $fecha."_".$t."_".$tramite_nro;
            $this->zip->download($nombre.'.zip');
        }
    }

}
