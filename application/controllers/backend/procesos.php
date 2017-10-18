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
        $data['procesos'] = Doctrine_Query::create()
                ->from('Proceso p, p.Cuenta c')
                ->where('p.activo=1 AND p.estado!="arch" AND c.id = ?',UsuarioBackendSesion::usuario()->cuenta_id)
                ->orderBy('p.nombre asc')
                ->execute();

        for ($i = 0; count($data['procesos']) > $i; $i++){
            $proceso = $data['procesos'][$i];
            log_message("INFO", "Proceso id: ".$proceso->id, FALSE);
            if($proceso->estado == 'public'){
                if(!isset($proceso->root) || strlen($proceso->root) == 0) {
                    $draft = $proceso->findDraftProceso($proceso->id);
                }else{
                    $draft = $proceso->findDraftProceso($proceso->root);
                }
                log_message("INFO", "Draft id: ".$this->varDump($draft), FALSE);
                if(isset($draft) && count($draft) > 0){
                    log_message("INFO", "Eliminando Proceso id: ".$data['procesos'][$i]->id, FALSE);
                    unset($data['procesos'][$i]);
                }
            }
        }

        $data['procesos_eliminados'] = Doctrine_Query::create()
                ->from('Proceso p, p.Cuenta c')
                ->where('p.activo=0 AND p.estado!="arch" AND c.id = ?',UsuarioBackendSesion::usuario()->cuenta_id)
                ->orderBy('p.nombre asc')
                ->execute();

        $data['title'] = 'Listado de Procesos';
        $data['content'] = 'backend/procesos/index';
        $this->load->view('backend/template', $data);
    }

    public function crear(){
        $proceso=new Proceso();
        $proceso->nombre='Proceso';
        $proceso->cuenta_id=UsuarioBackendSesion::usuario()->cuenta_id;

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

            if($proceso->estado != 'public'){
                $proceso->delete();
            }else{
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

        //Verificar si es draft o un proceso publicado
        if($proceso->estado != 'arch'){ //no es draft
            //Se crea Draft
            $proceso = $this->crearDraft($proceso);
        }else{
            $root = $proceso_id;
            if(isset($proceso->root) && strlen($proceso->root) > 0) {
                $root = $proceso->root;
            }
            $draft = $proceso->findDraftProceso($root);
            $proceso_draft = Doctrine::getTable('Proceso')->find($draft[0]["id"]);
            $proceso_draft->estado = 'arch';
            $proceso_draft->save();
            $proceso->estado = 'draft';
            $proceso->save();
        }

        log_message('debug', '$proceso->activo [' . $proceso->activo . '])');

        if($proceso->cuenta_id!=UsuarioBackendSesion::usuario()->cuenta_id || $proceso->activo != true) {
            echo 'Usuario no tiene permisos para editar este proceso';
            exit;
        }

        $procesosArchivados = $proceso->findProcesosArchivados($proceso->root);
        $data['procesos_arch'] = $procesosArchivados;

        $data['proceso'] = $proceso;

        $data['title'] = 'Modelador';
        $data['content'] = 'backend/procesos/editar';
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

        if($proceso->cuenta_id!=UsuarioBackendSesion::usuario()->cuenta_id){
            echo 'Usuario no tiene permisos para editar este proceso';
            exit;
        }

        $data['proceso']=$proceso;

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

    public function editar_tarea_form($tarea_id){
        $tarea=Doctrine::getTable('Tarea')->find($tarea_id);

        if($tarea->Proceso->cuenta_id!=UsuarioBackendSesion::usuario()->cuenta_id){
            echo 'Usuario no tiene permisos para editar esta tarea.';
            exit;
        }

        $this->form_validation->set_rules('nombre', 'Nombre', 'required');
        if($this->input->post('vencimiento')){
            $this->form_validation->set_rules('vencimiento_valor','Valor de Vencimiento','required|is_natural_no_zero');
            if($this->input->post('vencimiento_notificar')){
                $this->form_validation->set_rules('vencimiento_notificar_dias','Días para notificar vencimiento','required|is_natural_no_zero');
                $this->form_validation->set_rules('vencimiento_notificar_email','Correo electronico para notificar vencimiento','required');
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



            $respuesta->validacion=TRUE;
            $respuesta->redirect=site_url('backend/procesos/editar/'.$tarea->Proceso->id);

        }else{
            $respuesta->validacion=FALSE;
            $respuesta->errores=validation_errors();
        }

        echo json_encode($respuesta);
    }

    public function eliminar_tarea($tarea_id){
        $tarea=Doctrine::getTable('Tarea')->find($tarea_id);

        if($tarea->Proceso->cuenta_id!=UsuarioBackendSesion::usuario()->cuenta_id){
            echo 'Usuario no tiene permisos para eliminar esta tarea.';
            exit;
        }

        $proceso=$tarea->Proceso;

        $fecha = new DateTime ();

        // Auditar
        $registro_auditoria = new AuditoriaOperaciones ();
        $registro_auditoria->fecha = $fecha->format ( "Y-m-d H:i:s" );
        $registro_auditoria->operacion = 'Eliminación de Tarea';
        $usuario = UsuarioBackendSesion::usuario ();
        $registro_auditoria->usuario = $usuario->nombre . ' ' . $usuario->apellidos . ' <' . $usuario->email . '>';
        $registro_auditoria->proceso = $proceso->nombre;
        $registro_auditoria->cuenta_id = UsuarioBackendSesion::usuario()->cuenta_id;


        // Detalles
        $tarea_array['proceso'] = $proceso ->toArray(false);

        $tarea_array['tarea'] = $tarea->toArray(false);
        unset($tarea_array['tarea']['posx']);
        unset($tarea_array['tarea']['posy']);
        unset($tarea_array['tarea']['proceso_id']);


        $registro_auditoria->detalles = json_encode($tarea_array);
        $registro_auditoria->save();

        $tarea->delete();

        redirect('backend/procesos/editar/'.$proceso->id);
    }

    public function ajax_crear_conexion($proceso_id){
        $proceso=Doctrine::getTable('Proceso')->find($proceso_id);
        $tarea_origen=Doctrine::getTable('Tarea')->findOneByProcesoIdAndIdentificador($proceso_id,$this->input->post('tarea_id_origen'));
        $tarea_destino=Doctrine::getTable('Tarea')->findOneByProcesoIdAndIdentificador($proceso_id,$this->input->post('tarea_id_destino'));

        if($proceso->cuenta_id!=UsuarioBackendSesion::usuario()->cuenta_id){
            echo 'Usuario no tiene permisos para crear esta conexion.';
            exit;
        }
        if($tarea_origen->Proceso->cuenta_id!=UsuarioBackendSesion::usuario()->cuenta_id){
            echo 'Usuario no tiene permisos para crear esta conexion.';
            exit;
        }
        if($tarea_destino->Proceso->cuenta_id!=UsuarioBackendSesion::usuario()->cuenta_id){
            echo 'Usuario no tiene permisos para crear esta conexion.';
            exit;
        }

        //El tipo solamente se setea en la primera conexion creada para esa tarea.
        $tipo=$this->input->post('tipo');
        if($tarea_origen->ConexionesOrigen->count())
            $tipo=$tarea_origen->ConexionesOrigen[0]->tipo;

        $conexion=new Conexion();
        $conexion->tarea_id_origen=$tarea_origen->id;
        $conexion->tarea_id_destino=$tarea_destino->id;
        $conexion->tipo=$tipo;
        $conexion->save();
    }

    public function ajax_editar_conexiones($proceso_id,$tarea_origen_identificador){
        $conexiones=  Doctrine_Query::create()
                ->from('Conexion c, c.TareaOrigen t')
                ->where('t.proceso_id=? AND t.identificador=?',array($proceso_id,$tarea_origen_identificador))
                ->execute();

        if($conexiones[0]->TareaOrigen->Proceso->cuenta_id!=UsuarioBackendSesion::usuario()->cuenta_id){
            echo 'Usuario no tiene permisos para editar estas conexiones.';
            exit;
        }

        $data['conexiones'] = $conexiones;

        $this->load->view('backend/procesos/ajax_editar_conexiones',$data);
    }

    public function editar_conexiones_form($tarea_id){
        $tarea=Doctrine::getTable('Tarea')->find($tarea_id);

        if($tarea->Proceso->cuenta_id!=UsuarioBackendSesion::usuario()->cuenta_id){
            echo 'Usuario no tiene permisos para editar estas conexiones.';
            exit;
        }

        $this->form_validation->set_rules('conexiones', 'Conexiones','required');

        $respuesta=new stdClass();
        if ($this->form_validation->run() == TRUE) {
            $tarea->setConexionesFromArray($this->input->post('conexiones',false));
            $tarea->save();

            $respuesta->validacion=TRUE;
            $respuesta->redirect=site_url('backend/procesos/editar/'.$tarea->Proceso->id);

        }else{
            $respuesta->validacion=FALSE;
            $respuesta->errores=validation_errors();
        }

        echo json_encode($respuesta);
    }

    public function eliminar_conexiones($tarea_id){
        $tarea=Doctrine::getTable('Tarea')->find($tarea_id);

        if($tarea->Proceso->cuenta_id!=UsuarioBackendSesion::usuario()->cuenta_id){
            echo 'Usuario no tiene permisos para eliminar esta conexion.';
            exit;
        }

        $proceso=$tarea->Proceso;
        $tarea->ConexionesOrigen->delete();

        redirect('backend/procesos/editar/'.$proceso->id);
    }

    public function ajax_editar_modelo($proceso_id) {
        $proceso = Doctrine::getTable('Proceso')->find($proceso_id);

        if($proceso->cuenta_id!=UsuarioBackendSesion::usuario()->cuenta_id){
            echo 'Usuario no tiene permisos para editar este proceso';
            exit;
        }

        $modelo=$this->input->post('modelo');

        $proceso->updateModelFromJSON($modelo);

    }

    public function exportar($proceso_id){

        $proceso=Doctrine::getTable('Proceso')->find($proceso_id);

        $json=$proceso->exportComplete();

        header("Content-Disposition: attachment; filename=\"".mb_convert_case(str_replace(' ','-',$proceso->nombre),MB_CASE_LOWER).".simple\"");
        header('Content-Type: application/json');
        echo $json;

    }

    public function importar(){

        $file_path=$_FILES['archivo']['tmp_name'];

        if($file_path){
            $input=file_get_contents($_FILES['archivo']['tmp_name']);

            $proceso=Proceso::importComplete($input);

            $proceso->save();


        }

        redirect($_SERVER['HTTP_REFERER']);


    }

    public function publicar($proceso_draft_id){

        log_message("INFO", "ID Draft: ".$proceso_draft_id, FALSE);

        $proceso_draft = Doctrine::getTable('Proceso')->find($proceso_draft_id);

        log_message("INFO", "Root Draft: ".$proceso_draft->root, FALSE);

        $activo = $proceso_draft->findIdProcesoActivo($proceso_draft->root);

        log_message("INFO", "Recuperado activo: ".$this->varDump($activo), FALSE);

        $proceso = Doctrine::getTable('Proceso')->find($activo[0]['id']);
        $proceso->estado = 'arch';
        $proceso->save();

        $proceso_draft->estado = 'public';
        $proceso_draft->save();

        log_message("INFO", "Proceso actualizado", FALSE);

        redirect('backend/procesos/index');

    }

    private function crearDraft($proceso){

        $proceso_id = $proceso->id;

        log_message("INFO", "Buscando si proceso ya tiene draft creado", FALSE);

        $root = $proceso_id;
        if(isset($proceso->root) && strlen($proceso->root) > 0) {
            $root = $proceso->root;
        }
        $draft = $proceso->findDraftProceso($root);

        if(!isset($draft) || count($draft) == 0){ //No existe draft
            $proceso=Proceso::importComplete($proceso->exportComplete());

            $proceso->version = $proceso->version+1;
            $proceso->estado = 'draft';

            if(!isset($proceso->root) || strlen($proceso->root) == 0){
                $proceso->root = $proceso_id;
            }

            $proceso->save();

        }else{
            log_message("INFO", "Redirigiendo a edición de Draft con id: ".$draft[0]["id"], FALSE);
            $proceso=Doctrine::getTable('Proceso')->find($draft[0]["id"]);
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
            //$element->stop=$t->final;
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

    public function obtener_procesos_archivados($proceso_id) {
        $procesos = Doctrine_Query::create()
            ->from('Proceso p')
            ->where('(p.root = ? or p.id = ?) AND p.estado="arch" AND c.id = ?',
                $proceso_id, $proceso_id, UsuarioBackendSesion::usuario()->cuenta_id)
            ->orderBy('p.version desc')
            ->execute();

        $data = array();
        foreach ($procesos as $proceso){
            $data[] = array(
                "id" => $proceso->id,
                "nombre" => $proceso->nombre,
                "version" => $proceso->version
            );
        }
        echo json_encode($data);
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



















