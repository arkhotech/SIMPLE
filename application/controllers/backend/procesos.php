<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Procesos extends MY_BackendController {

    public function __construct() {
        parent::__construct();

        UsuarioBackendSesion::force_login();

//        if(UsuarioBackendSesion::usuario()->rol!='super' && UsuarioBackendSesion::usuario()->rol!='modelamiento'){
        if(!in_array('super', explode(',',UsuarioBackendSesion::usuario()->rol) ) && !in_array( 'modelamiento',explode(',',UsuarioBackendSesion::usuario()->rol))){
            echo 'No tiene permisos para acceder a esta seccion.';
            exit;
        }
    }

    public function index() {
        $cuenta_id = UsuarioBackendSesion::usuario()->cuenta_id;
        $data['procesos'] = Doctrine_Query::create()
                ->from('Proceso p, p.Cuenta c')
                ->where('p.activo=1 AND p.estado!="arch" AND c.id = ? 
                AND ((SELECT COUNT(proc.id) FROM Proceso proc WHERE proc.cuenta_id = ? AND (proc.root = p.id OR proc.root = p.root) AND proc.estado = "draft") = 0 
                OR p.estado = "draft")
                ',array($cuenta_id, $cuenta_id))
                ->orderBy('p.nombre asc')
                ->execute();

        $data['procesos_eliminados'] = Doctrine_Query::create()
                ->from('Proceso p, p.Cuenta c')
                ->where('p.activo=0 AND p.estado!="arch" AND c.id = ?',UsuarioBackendSesion::usuario()->cuenta_id)
                ->orderBy('p.nombre asc')
                ->execute();

        $cuenta = Doctrine::getTable('Cuenta')->find(UsuarioBackendSesion::usuario()->cuenta_id);
        $editar = true;
        if($cuenta->ambiente == 'prod'){
            $cuenta_dev = $cuenta->getAmbienteDev($cuenta->id);
            if(count($cuenta_dev) > 0){
                $editar = false;
            }
        }
        $data['editar_proceso'] = $editar;
        $data['title'] = 'Listado de Procesos';
        $data['content'] = 'backend/procesos/index';
        $this->load->view('backend/template', $data);
    }

    public function crear(){
        $proceso=new Proceso();
        $proceso->nombre='Proceso';
        $proceso->cuenta_id=UsuarioBackendSesion::usuario()->cuenta_id;
        $proceso->estado = 'draft';

        $proceso->save();

        redirect('backend/procesos/editar/'.$proceso->id);
    }

    public function eliminar($proceso_id) {

        log_message('info', 'eliminar ($proceso_id [' . $proceso_id . '])');

    	$this->form_validation->set_rules('descripcion', 'Razón', 'required');

    	$respuesta = new stdClass ();
    	if ($this->form_validation->run () == TRUE) {

	        $proceso = Doctrine::getTable('Proceso')->find($proceso_id);

	        if ($proceso->cuenta_id!=UsuarioBackendSesion::usuario()->cuenta_id) {
	            echo 'Usuario no tiene permisos para eliminar este proceso';
	            exit;
	        }
	        $fecha = new DateTime ();

	        // Auditar
	        $registro_auditoria = new AuditoriaOperaciones();
	        $registro_auditoria->fecha = $fecha->format ( "Y-m-d H:i:s" );
	        $registro_auditoria->operacion = 'Eliminación de Proceso';
	        $registro_auditoria->motivo = $this->input->post('descripcion');
	        $usuario = UsuarioBackendSesion::usuario ();
	        $registro_auditoria->usuario = $usuario->nombre . ' ' . $usuario->apellidos . ' <' . $usuario->email . '>';
	        $registro_auditoria->proceso = $proceso->nombre;
            $registro_auditoria->cuenta_id = UsuarioBackendSesion::usuario()->cuenta_id;

	        // Detalles
	        $proceso_array['proceso'] = $proceso->toArray(false);

	        $registro_auditoria->detalles = json_encode($proceso_array);
	        $registro_auditoria->save();

            if($proceso->estado != 'public') {
                $proceso->delete();
            } else {
                $proceso->delete_logico($proceso_id);
            }

        	$respuesta->validacion = TRUE;
        	$respuesta->redirect = site_url('backend/procesos/index/');
    	} else {
    		$respuesta->validacion = FALSE;
    		$respuesta->errores = validation_errors();
    	}

    	echo json_encode($respuesta);
    }

    public function editar($proceso_id) {

        log_message('info', 'editar ($proceso_id [' . $proceso_id . '])');

        $proceso = Doctrine::getTable('Proceso')->find($proceso_id);

        log_message('debug', '$proceso->estado [' . $proceso->estado . '])');

        // Verificar si es draft o un proceso publicado
        if ($proceso->estado != 'draft') { //no es draft
            //Se crea Draft
            $proceso = $this->crearDraft($proceso);
        } else {
            $root = $proceso_id;

            log_message("INFO", "Editando proceso id ".$proceso_id, FALSE);

            if (isset($proceso->root) && strlen($proceso->root) > 0) {
                $root = $proceso->root;
            }
            $proceso_draft = $proceso->findDraftProceso($root, UsuarioBackendSesion::usuario()->cuenta_id);

            log_message("INFO", "Se obtiene draft con id ".$proceso_draft->id, FALSE);

            if(isset($proceso_draft) && $proceso_draft->id > 0){
                $proceso_draft->estado = 'arch';
                $proceso_draft->save();
            }
            $proceso->estado = 'draft';
            $proceso->save();
        }
        log_message('debug', '$proceso->activo [' . $proceso->activo . '])');

        if ($proceso->cuenta_id!=UsuarioBackendSesion::usuario()->cuenta_id || $proceso->activo != true) {
            echo 'Usuario no tiene permisos para editar este proceso';
            exit;
        }

        $procesosArchivados = $proceso->findProcesosArchivados($proceso->root);
        $data['procesos_arch'] = $procesosArchivados;

        $data['proceso'] = $proceso;

        $data['proceso_id'] = $proceso_id;

        $data['title'] = 'Modelador';
        $data['content'] = 'backend/procesos/editar';
        $data['iconos'] = '';//$iconos;
        
        $this->load->view('backend/template', $data);
    }

    public function activar($proceso_id) {

        log_message('info', 'activar ($proceso_id [' . $proceso_id . '])');
        $this->form_validation->set_rules('descripcion', 'Razón', 'required');

        $respuesta = new stdClass();
        if ($this->form_validation->run() == TRUE) {

            $proceso = Doctrine::getTable('Proceso')->find($proceso_id);

            if ($proceso->cuenta_id != UsuarioBackendSesion::usuario()->cuenta_id) {
                log_message('debug', 'Usuario no tiene permisos para activar este proceso');
                echo 'Usuario no tiene permisos para activar este proceso';
                exit;
            }
            $fecha = new DateTime();

            // Auditar
            $registro_auditoria = new AuditoriaOperaciones();
            $registro_auditoria->fecha = $fecha->format ("Y-m-d H:i:s");
            $registro_auditoria->operacion = 'Activación de Proceso';
            $registro_auditoria->motivo = $this->input->post('descripcion');
            $usuario = UsuarioBackendSesion::usuario();
            $registro_auditoria->usuario = $usuario->nombre . ' ' . $usuario->apellidos . ' <' . $usuario->email . '>';
            $registro_auditoria->proceso = $proceso->nombre;
            $registro_auditoria->cuenta_id = UsuarioBackendSesion::usuario()->cuenta_id;

            // Detalles
            $proceso_array['proceso'] = $proceso->toArray(false);

            $registro_auditoria->detalles = json_encode($proceso_array);
            $registro_auditoria->save();
            log_message('debug', '$registro_auditoria->usuario: ' . $registro_auditoria->usuario);

            $q = Doctrine_Query::create()
            ->update('Proceso')
            ->set('activo', 1)
            ->where("id = ?", $proceso_id);
            $q->execute();

            $respuesta->validacion = TRUE;
            $respuesta->redirect = site_url('backend/procesos/index/');
        } else {
            $respuesta->validacion = FALSE;
            $respuesta->errores = validation_errors();
        }

        echo json_encode($respuesta);
    }

    public function ajax_editar($proceso_id){
        $proceso=Doctrine::getTable('Proceso')->find($proceso_id);
        $categorias=Doctrine::getTable('Categoria')->findAll();
        
        if($proceso->cuenta_id!=UsuarioBackendSesion::usuario()->cuenta_id){
            echo 'Usuario no tiene permisos para editar este proceso';
            exit;
        }

        $data['proceso']=$proceso;
        $data['categorias']=$categorias;
        $this->load->view('backend/procesos/ajax_editar',$data);
    }

    public function editar_form($proceso_id){
        $proceso=Doctrine::getTable('Proceso')->find($proceso_id);

        if($proceso->cuenta_id!=UsuarioBackendSesion::usuario()->cuenta_id){
            echo 'Usuario no tiene permisos para editar este proceso';
            exit;
        }

        $this->form_validation->set_rules('nombre', 'Nombre', 'required');

        $respuesta=new stdClass();
        if ($this->form_validation->run() == TRUE) {
            $proceso->nombre=$this->input->post('nombre');
            $proceso->width=$this->input->post('width');
            $proceso->height=$this->input->post('height');
            $proceso->categoria_id=$this->input->post('categoria');
            $proceso->icon_ref=$this->input->post('logo');
            if ($this->input->post('destacado')) {
                $proceso->destacado=1;
            } else {
                $proceso->destacado=0;
            }
            $proceso->save();         
            
            $respuesta->validacion=TRUE;
            $respuesta->redirect=site_url('backend/procesos/editar/'.$proceso->id);

        }else{
            $respuesta->validacion=FALSE;
            $respuesta->errores=validation_errors();
        }

        echo json_encode($respuesta);
    }

    public function ajax_crear_tarea($proceso_id,$tarea_identificador){
        $proceso=Doctrine::getTable('Proceso')->find($proceso_id);

        if($proceso->cuenta_id!=UsuarioBackendSesion::usuario()->cuenta_id){
            echo 'Usuario no tiene permisos para crear esta tarea.';
            exit;
        }

        $tarea=new Tarea();
        $tarea->proceso_id=$proceso->id;
        $tarea->identificador=$tarea_identificador;
        $tarea->nombre=$this->input->post('nombre');
        $tarea->posx=$this->input->post('posx');
        $tarea->posy=$this->input->post('posy');
        $tarea->save();

    }

    public function ajax_editar_tarea($proceso_id,$tarea_identificador){
        $tarea=Doctrine::getTable('Tarea')->findOneByProcesoIdAndIdentificador($proceso_id,$tarea_identificador);
        $proceso = Doctrine::getTable('Proceso')->find($proceso_id);
        if($tarea->Proceso->cuenta_id!=UsuarioBackendSesion::usuario()->cuenta_id){
            echo 'Usuario no tiene permisos para editar esta tarea.';
            exit;
        }
        $data['proceso_id'] = $proceso_id;
        $data['tarea'] = $tarea;
        $data['formularios']=Doctrine::getTable('Formulario')->findByProcesoId($proceso_id);
        $data['acciones']=Doctrine::getTable('Accion')->findByProcesoId($proceso_id);
        $data['proceso'] = $proceso;
        $data['variablesFormularios']=Doctrine::getTable('Proceso')->findVariblesFormularios($proceso_id,$tarea['id']);
        $data['variablesProcesos']=Doctrine::getTable('Proceso')->findVariblesProcesos($proceso_id);

        $cuentas = Doctrine::getTable('Cuenta')->findAll();

        $index = 0;
        foreach ($cuentas as $cuenta) {
            if($tarea->Proceso->cuenta_id == $cuenta->id){
                unset($cuentas[$index]);
                break;
            }
            $index++;
        }

        $data['cuentas'] = $cuentas;

        $proceso_cuenta = new ProcesoCuenta();
        $data['cuentas_con_permiso'] = $proceso_cuenta->findCuentasProcesos($proceso_id);

        $this->load->view('backend/procesos/ajax_editar_tarea',$data);
    }

    public function editar_tarea_form($tarea_id) {
        $tarea = Doctrine::getTable('Tarea')->find($tarea_id);

        if ($tarea->Proceso->cuenta_id != UsuarioBackendSesion::usuario()->cuenta_id) {
            echo 'Usuario no tiene permisos para editar esta tarea.';
            exit;
        }

        $this->form_validation->set_rules('nombre', 'Nombre', 'required');
        if ($this->input->post('vencimiento')) {
            $this->form_validation->set_rules('vencimiento_valor','Valor de Vencimiento', 'required|is_natural_no_zero');
            if ($this->input->post('vencimiento_notificar')) {
                $this->form_validation->set_rules('vencimiento_notificar_dias', 'Días para notificar vencimiento', 'required|is_natural_no_zero');
                $this->form_validation->set_rules('vencimiento_notificar_email', 'Correo electronico para notificar vencimiento', 'required');
            }
        }
        Doctrine::getTable('Proceso')->updateVaribleExposed($this->input->post('varForm'),$this->input->post('varPro'),$tarea->Proceso->id,$tarea_id);

        $proceso_cuenta = new ProcesoCuenta();
        $proceso_cuenta->deleteCuentasConPermiso($tarea->Proceso->id);
        $cuentas_con_permiso = $this->input->post('cuentas_con_permiso');
        if(isset($cuentas_con_permiso) && count($cuentas_con_permiso) > 0){
            foreach ($cuentas_con_permiso as $id_cuenta){
                $proceso_cuenta = new ProcesoCuenta();
                $proceso_cuenta->id_proceso = $tarea->Proceso->id;
                $proceso_cuenta->id_cuenta_origen = $tarea->Proceso->cuenta_id;
                $proceso_cuenta->id_cuenta_destino = $id_cuenta;
                $proceso_cuenta->save();
            }
        }

        $respuesta=new stdClass();
        if ($this->form_validation->run() == TRUE) {
            $tarea->nombre=$this->input->post('nombre');
            $tarea->inicial=$this->input->post('inicial');
            $tarea->final=$this->input->post('final');
            $tarea->asignacion=$this->input->post('asignacion');
            $tarea->asignacion_usuario=$this->input->post('asignacion_usuario');
            $tarea->asignacion_notificar=$this->input->post('asignacion_notificar');
            $tarea->setGruposUsuariosFromArray($this->input->post('grupos_usuarios'));
            $tarea->setPasosFromArray($this->input->post('pasos',false));
            $tarea->setEventosExternosFromArray($this->input->post('eventos_externos',false));
            $tarea->setEventosFromArray($this->input->post('eventos',false));
            $tarea->paso_confirmacion=$this->input->post('paso_confirmacion');
            $tarea->almacenar_usuario=$this->input->post('almacenar_usuario');
            $tarea->almacenar_usuario_variable=$this->input->post('almacenar_usuario_variable');
            $tarea->acceso_modo=$this->input->post('acceso_modo');
            $tarea->activacion=$this->input->post('activacion');
            $tarea->activacion_inicio=strtotime($this->input->post('activacion_inicio'));
            $tarea->activacion_fin=strtotime($this->input->post('activacion_fin'));
            $tarea->vencimiento=$this->input->post('vencimiento');
            $tarea->vencimiento_valor=$this->input->post('vencimiento_valor');
            $tarea->vencimiento_unidad=$this->input->post('vencimiento_unidad');
            $tarea->vencimiento_habiles=$this->input->post('vencimiento_habiles');
            $tarea->vencimiento_notificar=$this->input->post('vencimiento_notificar');
            $tarea->vencimiento_notificar_dias=$this->input->post('vencimiento_notificar_dias');
            $tarea->vencimiento_notificar_email=$this->input->post('vencimiento_notificar_email');
            $tarea->previsualizacion=$this->input->post('previsualizacion');
            $tarea->externa=$this->input->post('externa');
            $tarea->exponer_tramite=$this->input->post('exponer_tramite');
            $tarea->save();

            $respuesta->validacion = TRUE;
            $respuesta->redirect = site_url('backend/procesos/editar/' . $tarea->Proceso->id);

        } else {
            $respuesta->validacion = FALSE;
            $respuesta->errores = validation_errors();
        }

        echo json_encode($respuesta);
    }

    public function eliminar_tarea($tarea_id) {
        $tarea = Doctrine::getTable('Tarea')->find($tarea_id);

        if ($tarea->Proceso->cuenta_id!=UsuarioBackendSesion::usuario()->cuenta_id) {
            echo 'Usuario no tiene permisos para eliminar esta tarea.';
            exit;
        }

        $proceso=$tarea->Proceso;

        $fecha = new DateTime();

        // Auditar
        $registro_auditoria = new AuditoriaOperaciones ();
        $registro_auditoria->fecha = $fecha->format ( "Y-m-d H:i:s" );
        $registro_auditoria->operacion = 'Eliminación de Tarea';
        $usuario = UsuarioBackendSesion::usuario ();
        $registro_auditoria->usuario = $usuario->nombre . ' ' . $usuario->apellidos . ' <' . $usuario->email . '>';
        $registro_auditoria->proceso = $proceso->nombre;
        $registro_auditoria->cuenta_id = UsuarioBackendSesion::usuario()->cuenta_id;

        // Detalles
        $tarea_array['proceso'] = $proceso->toArray(false);

        $tarea_array['tarea'] = $tarea->toArray(false);
        unset($tarea_array['tarea']['posx']);
        unset($tarea_array['tarea']['posy']);
        unset($tarea_array['tarea']['proceso_id']);

        $registro_auditoria->detalles = json_encode($tarea_array);
        $registro_auditoria->save();

        $tarea->delete();

        redirect('backend/procesos/editar/' . $proceso->id);
    }
    
    public function ajax_crear_conexion($proceso_id) {
        $proceso = Doctrine::getTable('Proceso')->find($proceso_id);
        $tarea_origen = Doctrine::getTable('Tarea')->findOneByProcesoIdAndIdentificador($proceso_id,$this->input->post('tarea_id_origen'));
        $tarea_destino = Doctrine::getTable('Tarea')->findOneByProcesoIdAndIdentificador($proceso_id,$this->input->post('tarea_id_destino'));

        if ($proceso->cuenta_id != UsuarioBackendSesion::usuario()->cuenta_id) {
            echo 'Usuario no tiene permisos para crear esta conexion.';
            exit;
        }
        if ($tarea_origen->Proceso->cuenta_id!=UsuarioBackendSesion::usuario()->cuenta_id) {
            echo 'Usuario no tiene permisos para crear esta conexion.';
            exit;
        }
        if ($tarea_destino->Proceso->cuenta_id!=UsuarioBackendSesion::usuario()->cuenta_id) {
            echo 'Usuario no tiene permisos para crear esta conexion.';
            exit;
        }

        // El tipo solamente se setea en la primera conexion creada para esa tarea.
        $tipo = $this->input->post('tipo');
        if ($tarea_origen->ConexionesOrigen->count())
            $tipo = $tarea_origen->ConexionesOrigen[0]->tipo;

        $conexion = new Conexion();
        $conexion->tarea_id_origen = $tarea_origen->id;
        $conexion->tarea_id_destino = $tarea_destino->id;
        $conexion->tipo=$tipo;
        $conexion->save();
    }
    
    public function ajax_editar_conexiones($proceso_id,$tarea_origen_identificador,$union = null){

        if(!is_null($union)){
            $conexiones=  Doctrine_Query::create()
                ->from('Conexion c, c.TareaDestino t')
                ->where('t.proceso_id=? AND t.identificador=?',array($proceso_id,$tarea_origen_identificador))
                ->execute();
        }else{
            $conexiones=  Doctrine_Query::create()
                ->from('Conexion c, c.TareaOrigen t')
                ->where('t.proceso_id=? AND t.identificador=?',array($proceso_id,$tarea_origen_identificador))
                ->execute();
        }

        if ($conexiones[0]->TareaOrigen->Proceso->cuenta_id!=UsuarioBackendSesion::usuario()->cuenta_id) {
            echo 'Usuario no tiene permisos para editar estas conexiones.';
            exit;
        }

        $data['proceso_id'] = $proceso_id;
        $data['conexiones'] = $conexiones;

        $this->load->view('backend/procesos/ajax_editar_conexiones',$data);
    }

    public function editar_conexiones_form($tarea_id) {

        log_message('debug', 'method: editar_conexiones_form(' . $tarea_id . ')');

        $tarea = Doctrine::getTable('Tarea')->find($tarea_id);

        if ($tarea->Proceso->cuenta_id!=UsuarioBackendSesion::usuario()->cuenta_id) {
            echo 'Usuario no tiene permisos para editar estas conexiones.';
            exit;
        }

        $this->form_validation->set_rules('conexiones', 'Conexiones','required');

        $respuesta = new stdClass();
        if ($this->form_validation->run() == TRUE) {

            $tarea->setConexionesFromArray($this->input->post('conexiones', false));
            $tarea->save();

            $respuesta->validacion=TRUE;
            $respuesta->redirect=site_url('backend/procesos/editar/'.$tarea->Proceso->id);

        } else {
            $respuesta->validacion = FALSE;
            $respuesta->errores = validation_errors();
        }

        echo json_encode($respuesta);
    }

    public function eliminar_conexiones($tarea_id){
        $tarea=Doctrine::getTable('Tarea')->find($tarea_id);

        if ($tarea->Proceso->cuenta_id!=UsuarioBackendSesion::usuario()->cuenta_id) {
            echo 'Usuario no tiene permisos para eliminar esta conexion.';
            exit;
        }

        $proceso = $tarea->Proceso;
        $tarea->ConexionesOrigen->delete();

        redirect('backend/procesos/editar/' . $proceso->id);
    }

    public function ajax_editar_modelo($proceso_id) {
        $proceso = Doctrine::getTable('Proceso')->find($proceso_id);

        if ($proceso->cuenta_id!=UsuarioBackendSesion::usuario()->cuenta_id) {
            echo 'Usuario no tiene permisos para editar este proceso';
            exit;
        }

        $modelo = $this->input->post('modelo');

        $proceso->updateModelFromJSON($modelo);

    }

    public function exportar($proceso_id) {

        $proceso=Doctrine::getTable('Proceso')->find($proceso_id);

        $json=$proceso->exportComplete();

        header("Content-Disposition: attachment; filename=\"".mb_convert_case(str_replace(' ','-',$proceso->nombre),MB_CASE_LOWER).".simple\"");
        header('Content-Type: application/json');
        echo $json;

    }

    public function importar() {

        $file_path = $_FILES['archivo']['tmp_name'];

        if ($file_path) {
            $input = file_get_contents($_FILES['archivo']['tmp_name']);
            $proceso = Proceso::importComplete($input, TRUE);
            $proceso->save();

            $this->migrarSeguridadAcciones($proceso);
            $this->migrarSuscriptores($proceso);

        }

        redirect($_SERVER['HTTP_REFERER']);
    }

    public function publicar($proceso_draft_id){

        log_message("INFO", "ID Draft: ".$proceso_draft_id, FALSE);

        $proceso_draft = Doctrine::getTable('Proceso')->find($proceso_draft_id);

        log_message("INFO", "Root Draft: " . $proceso_draft->root, FALSE);

        log_message("INFO", "UsuarioBackendSesion::usuario()->cuenta_id: " . UsuarioBackendSesion::usuario()->cuenta_id);
        

        $activo = $proceso_draft->findIdProcesoActivo($proceso_draft->root, UsuarioBackendSesion::usuario()->cuenta_id);

        log_message("INFO", "Recuperado activo: [" . $activo->id . "]", FALSE);

        if (strlen($activo->id) > 0) { // Existe proceso activo
            log_message("INFO", "Existe Proceso Activo", FALSE);

            $activo->estado = 'arch';
            $activo->save();
            log_message("INFO", "Save() Estado arch Proceso Activo", FALSE);
        } else {
            $proceso_draft->root = $proceso_draft->id;
        }

        $proceso_draft->estado = 'public';
        $proceso_draft->save();

        $cuenta = Doctrine::getTable('Cuenta')->find(UsuarioBackendSesion::usuario()->cuenta_id);

        if ($cuenta->ambiente == 'dev') {

            log_message("INFO", "Cuenta DEV", FALSE);

            $fecha = new DateTime();
            log_message("INFO", "Posterior a DateTime", FALSE);

            log_message("INFO", "Previo proceso_draft->findIdProcesoActivo(proceso_draft->root [" . $proceso_draft->root . "], cuenta->vinculo_produccion [" . $cuenta->vinculo_produccion . "])", FALSE);
            $proc_produccion = $proceso_draft->findIdProcesoActivo($proceso_draft->root, $cuenta->vinculo_produccion);
            log_message("INFO", "Posterior proceso_draft->findIdProcesoActivo", FALSE);

            log_message("INFO", "proc_produccion->id [" . $proc_produccion->id . "]");
            log_message("INFO", "strlen proc_produccion->id [" . strlen($proc_produccion->id) . "]");

            if (strlen($proc_produccion->id) > 0) { // Existe proceso productivo

                // Auditar
                $registro_auditoria = new AuditoriaOperaciones();
                $registro_auditoria->fecha = $fecha->format("Y-m-d H:i:s");
                $registro_auditoria->operacion = 'Eliminación de Proceso en cuenta productiva';
                $registro_auditoria->motivo = "Publicación de nueva versión";
                $usuario = UsuarioBackendSesion::usuario();
                $registro_auditoria->usuario = $usuario->nombre . ' ' . $usuario->apellidos . ' <' . $usuario->email . '>';
                $registro_auditoria->proceso = $proc_produccion->nombre;
                $registro_auditoria->cuenta_id = $cuenta->vinculo_produccion;
                // Detalles
                $proceso_array['proceso'] = $proc_produccion->toArray(false);
                $registro_auditoria->detalles = json_encode($proceso_array);
                $registro_auditoria->save();

                $proc_produccion->delete();
            }

            // Auditar
            log_message("INFO", "Inicio Auditoria", FALSE);
            
            $registro_auditoria = new AuditoriaOperaciones();
            $registro_auditoria->fecha = $fecha->format ("Y-m-d H:i:s");
            $registro_auditoria->operacion = 'Publicación de Proceso a cuenta productiva';
            $registro_auditoria->motivo = "Publicación de nueva versión";
            $usuario = UsuarioBackendSesion::usuario();
            $registro_auditoria->usuario = $usuario->nombre . ' ' . $usuario->apellidos . ' <' . $usuario->email . '>';
            $registro_auditoria->proceso = $proceso_draft->nombre;
            $registro_auditoria->cuenta_id = $cuenta->id;
            // Detalles

            $proceso_array['proceso'] = $proceso_draft->toArray(false);
            $registro_auditoria->detalles = json_encode($proceso_array);
            $registro_auditoria->save();
            log_message("INFO", "Fin Auditoria", FALSE);


            log_message('debug', 'previo a Proceso::importComplete($proceso_draft->exportComplete());');
            $proceso = Proceso::importComplete($proceso_draft->exportComplete());
            log_message('debug', '$cuenta->vinculo_produccion [' . $cuenta->vinculo_produccion);            
            $proceso->cuenta_id = $cuenta->vinculo_produccion;
            $proceso->save();
            log_message('debug', 'post a $proceso->save();');

            $this->migrarSeguridadAcciones($proceso);
            $this->migrarSuscriptores($proceso);

            $this->migrarGrupos($proceso, $cuenta);

        }

        log_message("INFO", "Proceso actualizado", FALSE);

        $respuesta = new stdClass ();
        $respuesta->validacion = TRUE;
        $respuesta->redirect = site_url('backend/procesos/index/');
        echo json_encode($respuesta);
    }

    private function migrarGrupos($proceso, $cuenta) {
        //asignar grupos de usuario de producción por cada tarea
        log_message("INFO", "Revisando grupos para proceso id ".$proceso->id, FALSE);
        $tareas = $proceso->getTareasProceso($proceso->id);
        foreach ($tareas as $tarea){
            $idUsuarios = $tarea->grupos_usuarios;
            if(strlen($idUsuarios) > 0){
                $ids = explode(",", $idUsuarios);
                if(count($ids) > 0){
                    $ids_prod = "";
                    foreach ($ids as $id){
                        $grupo = Doctrine::getTable("GrupoUsuarios")->find($id);
                        log_message("INFO", "Revisando grupo: ".$grupo->nombre, FALSE);
                        $grupo_prod = $grupo->existeGrupo($cuenta->vinculo_produccion);
                        if(isset($grupo_prod)){
                            log_message("INFO", "Existe en produccion", FALSE);
                            log_message("INFO", "Nombre: ".$grupo_prod->nombre, FALSE);
                            if(strlen($ids_prod) > 0){
                                $ids_prod.= ",".$grupo_prod->id;
                            }else{
                                $ids_prod = $grupo_prod->id;
                            }
                        }else{
                            log_message("INFO", "No existe en produccion", FALSE);
                            $grupo_usuarios = new GrupoUsuarios();
                            $grupo_usuarios->nombre = $grupo->nombre;
                            $grupo_usuarios->cuenta_id = $cuenta->vinculo_produccion;
                            $grupo_usuarios->save();
                            log_message("INFO", "Se crea grupo en produccion", FALSE);
                            log_message("INFO", "Grupo creado: ".$grupo_usuarios->id, FALSE);
                            if(strlen($ids_prod) > 0){
                                $ids_prod.= ",".$grupo_usuarios->id;
                            }else{
                                $ids_prod = $grupo_usuarios->id;
                            }
                        }
                    }
                    log_message("INFO", "id grupos prod: ".$ids_prod, FALSE);
                    if(strlen($ids_prod) > 0) {
                        $tarea->grupos_usuarios = $ids_prod;
                        $tarea->save();
                    }
                }
            }
        }
    }

    private function migrarSeguridadAcciones($proceso) {
        log_message("INFO", "Revisando seguridad para proceso id ".$proceso->id, FALSE);
        $acciones = $proceso->Acciones;
        foreach ($acciones as $accion){
            if($accion->tipo == 'rest' || $accion->tipo == 'soap' || $accion->tipo == 'callback'){
                if(isset($accion->extra->idSeguridad) && strlen($accion->extra->idSeguridad) > 0 ){
                    $extra_accion = $accion->extra;
                    $extra_accion->idSeguridad = $proceso->Admseguridad[$accion->extra->idSeguridad]->id;
                    $accion->extra = $extra_accion;
                    log_message("INFO", "Guardando accion id ".$accion->id, FALSE);
                    $accion->save();
                }
            }elseif ($accion->tipo == 'iniciar_tramite'){
                if(isset($accion->extra->tareaRetornoSel) && strlen($accion->extra->tareaRetornoSel) > 0 ){
                    $extra_accion = $accion->extra;
                    $extra_accion->tareaRetornoSel = $proceso->Tareas[$accion->extra->tareaRetornoSel]->id;
                    $accion->extra = $extra_accion;
                    log_message("INFO", "Guardando accion id ".$accion->id, FALSE);
                    $accion->save();
                }
            }
        }
    }

    private function migrarSuscriptores($proceso) {
        log_message("INFO", "Revisando suscriptores para proceso id ".$proceso->id, FALSE);

        $suscriptores = $proceso->Suscriptores;
        foreach ($suscriptores as $suscriptor){
            if(isset($suscriptor->extra->idSeguridad) && strlen($suscriptor->extra->idSeguridad) > 0 ){
                $extra_suscriptor = $suscriptor->extra;
                $extra_suscriptor->idSeguridad = $proceso->Admseguridad[$suscriptor->extra->idSeguridad]->id;//$new_seguridad->id;
                $suscriptor->extra = $extra_suscriptor;
                log_message("INFO", "Guardando suscriptor id ".$suscriptor->id, FALSE);
                $suscriptor->save();
            }
        }

        $acciones = $proceso->Acciones;
        foreach ($acciones as $accion){
            if($accion->tipo == 'webhook'){
                if(isset($accion->extra->suscriptorSel) && count($accion->extra->suscriptorSel) > 0 ){
                    $suscriptores_seleccionados = array();
                    foreach ($accion->extra->suscriptorSel as $suscriptor){
                        $suscriptores_seleccionados[] = $proceso->Suscriptores[$suscriptor]->id;//$new_suscriptor->id;
                    }
                    $extra_accion = $accion->extra;
                    $extra_accion->suscriptorSel = $suscriptores_seleccionados;
                    $accion->extra = $extra_accion;
                    log_message("INFO", "Guardando accion id ".$accion->id, FALSE);
                    $accion->save();
                }
            }
        }
    }

    private function crearDraft($proceso){

        $proceso_id = $proceso->id;

        log_message("INFO", "Buscando si proceso ya tiene draft creado", FALSE);

        $root = $proceso_id;
        if(isset($proceso->root) && strlen($proceso->root) > 0) {
            $root = $proceso->root;
        }

        log_message("INFO", "Buscando draft con root: ".$root, FALSE);

        $draft = $proceso->findDraftProceso($root, UsuarioBackendSesion::usuario()->cuenta_id);

        log_message("INFO", "Draft: *".$draft->id."*", FALSE);
        //log_message("INFO", "Draft2: ".$draft[0]->id, FALSE);

        if(strlen($draft->id) == 0){ //No existe draft
            log_message("INFO", "Draft no existe", FALSE);
            $proceso=Proceso::importComplete($proceso->exportComplete());

            log_message("INFO", "Buscando última version", FALSE);
            $max_version = $proceso->findMaxVersion($root, UsuarioBackendSesion::usuario()->cuenta_id);
            log_message("INFO", "Ultima version recuperada. ".$max_version, FALSE);

            $proceso->version = $max_version+1;
            $proceso->estado = 'draft';

            if(!isset($proceso->root) || strlen($proceso->root) == 0){
                $proceso->root = $proceso_id;
            }

            $proceso->save();

            $this->migrarSeguridadAcciones($proceso);
            $this->migrarSuscriptores($proceso);

        }else{
            log_message("INFO", "Redirigiendo a edición de Draft con id: ".$draft->id, FALSE);
            $proceso = $draft;//Doctrine::getTable('Proceso')->find($draft[0]["id"]);
        }

        return $proceso;

    }

    public function ajax_auditar_eliminar_proceso($proceso_id) {
    	if (! in_array ( 'super', explode ( ",", UsuarioBackendSesion::usuario ()->rol ) ))
    		show_error ( 'No tiene permisos', 401 );

    	$proceso = Doctrine::getTable("Proceso")->find($proceso_id);
    	$data['proceso'] = $proceso;
    	$this->load->view ( 'backend/procesos/ajax_auditar_eliminar_proceso', $data );
    }

    public function ajax_auditar_activar_proceso($proceso_id) {
        if (! in_array('super', explode (",", UsuarioBackendSesion::usuario ()->rol)))
            show_error('No tiene permisos', 401);

        $proceso = Doctrine::getTable("Proceso")->find($proceso_id);
        $data['proceso'] = $proceso;
        $this->load->view('backend/procesos/ajax_auditar_activar_proceso', $data);
    }

    public function getJSONFromModelDraw($proceso_id){
        $proceso = Doctrine::getTable("Proceso")->find($proceso_id);
        $modelo=new stdClass();
        $modelo->nombre=$proceso->nombre;
        $modelo->elements=array();
        $modelo->connections=array();

        $tareas=Doctrine::getTable('Tarea')->findByProcesoId($proceso_id);
        foreach($tareas as $t){
            $element=new stdClass();
            $element->id=$t->identificador;
            $element->name=$t->nombre;
            $element->left=$t->posx;
            $element->top=$t->posy;
            $element->start=$t->inicial;
            $element->stop=$t->final;
            $modelo->elements[]=clone $element;
        }
        //$conexiones1=  Doctrine_Query::create()->from('Conexion c, c.TareaOrigen.Proceso p')->where('p.id = ?',$proceso_id);
        $conexiones=  Doctrine_Query::create()
                ->from('Conexion c, c.TareaOrigen.Proceso p')
                ->where('p.id = ?',$proceso_id)
                ->execute();
        //echo $conexiones1->getSqlQuery();
        foreach($conexiones as $c){
            //$conexion->id=$c->identificador;
            $conexion=new stdClass();
            $conexion->source=$c->TareaOrigen->identificador;
            $conexion->target=$c->TareaDestino->identificador;
            $conexion->tipo=$c->tipo;
            $modelo->connections[]=clone $conexion;
        }
        //print_r(json_encode($modelo));
        //exit;
        echo json_encode($modelo);
    }
    public function seleccionar_icono() {
        $DS = DIRECTORY_SEPARATOR;
        $directory = FCPATH . 'assets' . $DS .'img'. $DS .'icons';
        $html = '';
        $error = '';
        $hideButton = true;
        $isImage = false;
        
        if (file_exists($directory)) {
            $icons = @scandir($directory);            
            if ($icons !== FALSE) {
                if (count($icons) > 0) {
                    $hideButton = false;
                    foreach ($icons as $icon) {                        
                        if ($this->isImage($directory . $DS . $icon)) {
                            $isImage = true;                            
                            $html .= '<div class="item"><a class="sel-icono" href="javascript:;" rel="' . $icon . '"><img src="' . base_url('assets/img/icons/' . $icon) . '" alt="' . $icon . '" title="' . $icon . '"></a></div>';
                        }
                    }
                    
                    if (!$isImage) {
                        $error .= '<div class="alert alert-error"><a class="close" data-dismiss="alert">×</a>No hay &iacuteconos en la carpeta "assets/img/icons"</div>';
                    }
                } else {
                    $error .= '<div class="alert alert-error"><a class="close" data-dismiss="alert">×</a>No hay &iacuteconos en la carpeta "assets/img/icons"</div>';
                }
            } else {
                $error .= '<div class="alert alert-error"><a class="close" data-dismiss="alert">×</a>No se pudo leer la carpeta "assets/img/icons"</div>';
            }
        } else {
            $error .= '<div class="alert alert-error"><a class="close" data-dismiss="alert">×</a>La carpeta "assets/img/icons" no existe</div>';
        }
        
        $data['hideButton'] = $hideButton;
        $data['iconos'] = $html;
        $data['error'] = $error;
        
        $this->load->view('backend/procesos/seleccionar_icono', $data);
    }
    
    private function isImage($image)
    {        
        return @is_array(getimagesize($image));
    }

    public function ajax_publicar_proceso($proceso_id) {
        if (! in_array ( 'super', explode ( ",", UsuarioBackendSesion::usuario ()->rol ) ))
            show_error ( 'No tiene permisos', 401 );

        $proceso = Doctrine::getTable("Proceso")->find($proceso_id);
        $data['proceso'] = $proceso;
        $this->load->view ( 'backend/procesos/ajax_publicar_proceso', $data );
    }

    function varDump($data){
        ob_start();
        //var_dump($data);
        print_r($data);
        $ret_val = ob_get_contents();
        ob_end_clean();
        return $ret_val;
    }

}



















