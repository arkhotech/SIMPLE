<?php
if (! defined ( 'BASEPATH' ))
	exit ( 'No direct script access allowed' );
class Seguimiento extends MY_BackendController {
	public function __construct() {
		parent::__construct ();
		
		UsuarioBackendSesion::force_login ();
		
		// if(UsuarioBackendSesion::usuario()->rol!='super' && UsuarioBackendSesion::usuario()->rol!='operacion' && UsuarioBackendSesion::usuario()->rol!='seguimiento'){
		if (! in_array ( 'super', explode ( ",", UsuarioBackendSesion::usuario ()->rol ) ) && ! in_array ( 'operacion', explode ( ",", UsuarioBackendSesion::usuario ()->rol ) ) && ! in_array ( 'seguimiento', explode ( ",", UsuarioBackendSesion::usuario ()->rol ) )) {
			echo 'No tiene permisos para acceder a esta seccion.';
			exit ();
		}
	}
	public function index() {
		// $data['procesos'] = Doctrine::getTable('Proceso')->findByCuentaId(UsuarioBackendSesion::usuario()->cuenta_id);
		$data ['procesos'] = Doctrine_Query::create ()->from ( 'Proceso p, p.Cuenta c' )->where ('p.activo=1 AND p.estado = "public" AND c.id = ?', UsuarioBackendSesion::usuario ()->cuenta_id )->orderBy ( 'p.nombre asc' )->execute ();
		
		$data ['title'] = 'Listado de Procesos';
		$data ['content'] = 'backend/seguimiento/index';
		$this->load->view ( 'backend/template', $data );
	}
	public function index_proceso($proceso_id) {

        log_message("INFO", "Detalle de seguimiento para proceso id: ".$proceso_id, FALSE);

		$proceso = Doctrine::getTable ( 'Proceso' )->find ( $proceso_id );

        $procesos_archivados = $proceso->findProcesosArchivados($proceso->root);
        $id_archivados = array();
        log_message("INFO", "Buscando procesos relacionados archivados", FALSE);
        foreach($procesos_archivados as $proc_arch){
            $id_archivados[] = $proc_arch['id'];
        }
        log_message("INFO", "Procesos relacionados archivados: ".$this->varDump($id_archivados), FALSE);
		
		if (UsuarioBackendSesion::usuario ()->cuenta_id != $proceso->cuenta_id) {
			echo 'Usuario no tiene permisos';
			exit;
		}

		if(!is_null(UsuarioBackendSesion::usuario()->procesos) && !in_array($proceso_id,explode(',',UsuarioBackendSesion::usuario()->procesos))){
			echo 'Usuario no tiene permisos para el seguimiento del tramite';
			exit ();
		}
		
		$query = $this->input->get ( 'query' );
		$offset = $this->input->get ( 'offset' );
		$order = $this->input->get ( 'order' ) ? $this->input->get ( 'order' ) : 'updated_at';
		$direction = $this->input->get ( 'direction' ) ? $this->input->get ( 'direction' ) : 'desc';
		$created_at_desde = $this->input->get ( 'created_at_desde' );
		$created_at_hasta = $this->input->get ( 'created_at_hasta' );
		$updated_at_desde = $this->input->get ( 'updated_at_desde' );
		$updated_at_hasta = $this->input->get ( 'updated_at_hasta' );
		$pendiente = $this->input->get ( 'pendiente' ) !== false ? $this->input->get ( 'pendiente' ) : - 1;
		$per_page = 50;
		$busqueda_avanzada = $this->input->get ( 'busqueda_avanzada' );

        log_message("INFO", "Creando query", FALSE);
		$doctrine_query = Doctrine_Query::create ()->from ( 'Tramite t, t.Proceso p, t.Etapas e, e.DatosSeguimiento d' )
            ->where ('p.activo=1')
            ->andWhereIn('p.root', $id_archivados)
            ->having ( 'COUNT(d.id) > 0 OR COUNT(e.id) > 1' )-> // Mostramos solo los que se han avanzado o tienen datos
groupBy ( 't.id' )->orderBy ( $order . ' ' . $direction )->limit ( $per_page )->offset ( $offset );
		
		if ($created_at_desde)
			$doctrine_query->andWhere ( 'created_at >= ?', array (
					date ( 'Y-m-d', strtotime ( $created_at_desde ) ) 
			) );
		if ($created_at_hasta)
			$doctrine_query->andWhere ( 'created_at <= ?', array (
					date ( 'Y-m-d', strtotime ( $created_at_hasta ) ) 
			) );
		if ($updated_at_desde)
			$doctrine_query->andWhere ( 'updated_at >= ?', array (
					date ( 'Y-m-d', strtotime ( $updated_at_desde ) ) 
			) );
		if ($updated_at_hasta)
			$doctrine_query->andWhere ( 'updated_at <= ?', array (
					date ( 'Y-m-d', strtotime ( $updated_at_hasta ) ) 
			) );
		if ($pendiente != - 1)
			$doctrine_query->andWhere ( 'pendiente = ?', array (
					$pendiente 
			) );
		
		if ($query) {
			$this->load->library ( 'sphinxclient' );
			$this->sphinxclient->setServer ( $this->config->item ( 'sphinx_host' ), $this->config->item ( 'sphinx_port' ) );
			$this->sphinxclient->setFilter ( 'proceso_id', array (
					$proceso_id 
			) );
			$result = $this->sphinxclient->query ( json_encode ( $query ), 'tramites' );
			if ($result ['total'] > 0) {
				$matches = array_keys ( $result ['matches'] );
				$doctrine_query->whereIn ( 't.id', $matches );
			} else {
				$doctrine_query->where ( '0' );
			}
		}
		
		$tramites = $doctrine_query->execute ();
		$ntramites = $doctrine_query->count ();
		
		$this->load->library ( 'pagination' );
		$this->pagination->initialize ( array (
				'base_url' => site_url ( 'backend/seguimiento/index_proceso/' . $proceso_id . '?order=' . $order . '&direction=' . $direction . '&pendiente=' . $pendiente . '&created_at_desde=' . $created_at_desde . '&created_at_hasta=' . $created_at_hasta . '&updated_at_desde=' . $updated_at_desde . '&updated_at_hasta=' . $updated_at_hasta ),
				'total_rows' => $ntramites,
				'per_page' => $per_page 
		) );
		
		$data ['query'] = $query;
		$data ['order'] = $order;
		$data ['direction'] = $direction;
		$data ['created_at_desde'] = $created_at_desde;
		$data ['created_at_hasta'] = $created_at_hasta;
		$data ['updated_at_desde'] = $updated_at_desde;
		$data ['updated_at_hasta'] = $updated_at_hasta;
		$data ['pendiente'] = $pendiente;
		$data ['busqueda_avanzada'] = $busqueda_avanzada;
		$data ['proceso'] = $proceso;
		$data ['tramites'] = $tramites;
		
		$data ['title'] = 'Seguimiento de ' . $proceso->nombre;
		$data ['content'] = 'backend/seguimiento/index_proceso';
		$this->load->view ( 'backend/template', $data );
	}
	public function ver($tramite_id) {
		$tramite = Doctrine::getTable ( 'Tramite' )->find ( $tramite_id );

		if(!is_null(UsuarioBackendSesion::usuario()->procesos) && !in_array($tramite->Proceso->id,explode(',',UsuarioBackendSesion::usuario()->procesos))){
			echo 'Usuario no tiene permisos para ver el tramite';
			exit;
		}
		
		if (UsuarioBackendSesion::usuario ()->cuenta_id != $tramite->Proceso->cuenta_id) {
			echo 'No tiene permisos para hacer seguimiento a este tramite.';
			exit ();
		}
		
		$data ['tramite'] = $tramite;
		$data ['etapas'] = Doctrine_Query::create ()->from ( 'Etapa e, e.Tramite t' )->where ( 't.id = ?', $tramite->id )->orderBy ( 'id desc' )->execute ();
		
		$data ['title'] = 'Seguimiento - ' . $tramite->Proceso->nombre;
		$data ['content'] = 'backend/seguimiento/ver';
		$this->load->view ( 'backend/template', $data );
	}
	public function ajax_ver_etapas($tramite_id, $tarea_identificador) {
		$tramite = Doctrine::getTable ( 'Tramite' )->find ( $tramite_id );
		
		if (UsuarioBackendSesion::usuario ()->cuenta_id != $tramite->Proceso->cuenta_id) {
			echo 'No tiene permisos para hacer seguimiento a este tramite.';
			exit ();
		}
		
		$etapas = Doctrine_Query::create ()->from ( 'Etapa e, e.Tarea tar, e.Tramite t' )->where ( 't.id = ? AND tar.identificador = ?', array (
				$tramite_id,
				$tarea_identificador 
		) )->execute ();
		
		$data ['etapas'] = $etapas;
		
		$this->load->view ( 'backend/seguimiento/ajax_ver_etapas', $data );
	}
	public function ver_etapa($etapa_id, $secuencia = 0) {
		$etapa = Doctrine::getTable ( 'Etapa' )->find ( $etapa_id );
		$paso = $etapa->getPasoEjecutable ( $secuencia );

		if(!is_null(UsuarioBackendSesion::usuario()->procesos) && !in_array($etapa->Tramite->Proceso->id,explode(',',UsuarioBackendSesion::usuario()->procesos))){
			echo 'Usuario no tiene permisos para ver el tramite';
			exit;
		}
		
		if (UsuarioBackendSesion::usuario ()->cuenta_id != $etapa->Tramite->Proceso->cuenta_id) {
			echo 'No tiene permisos para hacer seguimiento a este tramite.';
			exit ();
		}
		
		$data ['etapa'] = $etapa;
		$data ['paso'] = $paso;
		$data ['secuencia'] = $secuencia;
		
		$data ['title'] = 'Seguimiento - ' . $etapa->Tarea->nombre;
		$data ['content'] = 'backend/seguimiento/ver_etapa';
		$this->load->view ( 'backend/template', $data );
	}
	public function reasignar_form($etapa_id) {
		$this->form_validation->set_rules ( 'usuario_id', 'Usuario', 'required' );
		
		$respuesta = new stdClass ();
		if ($this->form_validation->run () == TRUE) {
			
			$etapa = Doctrine::getTable ( 'Etapa' )->find ( $etapa_id );
			$etapa->asignar ( $this->input->post ( 'usuario_id' ) );
			
			$respuesta->validacion = TRUE;
			$respuesta->redirect = site_url ( 'backend/seguimiento/ver_etapa/' . $etapa->id );
		} else {
			$respuesta->validacion = FALSE;
			$respuesta->errores = validation_errors ();
		}
		
		echo json_encode ( $respuesta );
	}
	public function borrar_tramite($tramite_id) {
		
		if (! in_array ( 'super', explode ( ",", UsuarioBackendSesion::usuario ()->rol ) ))
			show_error ( 'No tiene permisos', 401 );
			
		// if(UsuarioBackendSesion::usuario()->rol=='seguimiento')
    	$this->form_validation->set_rules ( 'descripcion', 'Razón', 'required' );
    	$respuesta = new stdClass ();
    	if ($this->form_validation->run () == TRUE) {
    			
			$tramite = Doctrine::getTable ( 'Tramite' )->find ( $tramite_id );

			if(!is_null(UsuarioBackendSesion::usuario()->procesos) && !in_array($tramite->Proceso->id,explode(',',UsuarioBackendSesion::usuario()->procesos))){
				echo 'Usuario no tiene permisos';
				exit;
			}
		
			if (UsuarioBackendSesion::usuario ()->cuenta_id != $tramite->Proceso->cuenta_id) {
				echo 'No tiene permisos para hacer seguimiento a este tramite.';
				exit ();
			}
			$fecha = new DateTime ();
			$proceso = $tramite->Proceso;
			// Auditar
			$registro_auditoria = new AuditoriaOperaciones ();
			$registro_auditoria->fecha = $fecha->format ( "Y-m-d H:i:s" );
			$registro_auditoria->operacion = 'Eliminación de Trámite';
			$registro_auditoria->motivo = $this->input->post('descripcion');
			$usuario = UsuarioBackendSesion::usuario ();
			$registro_auditoria->usuario = $usuario->nombre . ' ' . $usuario->apellidos . ' <' . $usuario->email . '>';
			$registro_auditoria->proceso = $proceso->nombre;
			$registro_auditoria->cuenta_id = UsuarioBackendSesion::usuario()->cuenta_id;
			
			
			// Detalles
			$tramite_array['proceso'] = $proceso->toArray(false);
			
			$tramite_array['tramite'] = $tramite->toArray(false);
			unset($tramite_array['tramite']['proceso_id']);
			
			 
			$registro_auditoria->detalles = json_encode($tramite_array);
			$registro_auditoria->save();
			
			$tramite->delete ();
				
			
			$respuesta->validacion = TRUE;
			$respuesta->redirect = site_url('backend/seguimiento/index_proceso/'.$proceso->id);
			
			
		
		} else {
		
			$respuesta->validacion = FALSE;
			$respuesta->errores = validation_errors();
		
		}
		 
		echo json_encode($respuesta);
	}
	public function borrar_proceso($proceso_id) {
		// if(UsuarioBackendSesion::usuario()->rol=='seguimiento')
		if (! in_array ( 'super', explode ( ",", UsuarioBackendSesion::usuario ()->rol ) ))
			show_error ( 'No tiene permisos', 401 );

		
		$this->form_validation->set_rules ( 'descripcion', 'Razón', 'required' );
		$respuesta = new stdClass ();
		if ($this->form_validation->run () == TRUE) {


			$proceso = Doctrine::getTable ( 'Proceso' )->find ( $proceso_id );

			if(!is_null(UsuarioBackendSesion::usuario()->procesos) && !in_array($proceso_id,explode(',',UsuarioBackendSesion::usuario()->procesos))){
				echo 'Usuario no tiene permisos para el seguimiento del tramite';
				exit;
			}
			
			if (UsuarioBackendSesion::usuario ()->cuenta_id != $proceso->cuenta_id) {
				echo 'No tiene permisos para hacer seguimiento a este tramite.';
				exit ();
			}
			$fecha = new DateTime ();
				
			// Auditar
			$registro_auditoria = new AuditoriaOperaciones ();
			$registro_auditoria->fecha = $fecha->format ( "Y-m-d H:i:s" );
			$registro_auditoria->operacion = 'Eliminación de Todos los Trámites';
			$registro_auditoria->motivo = $this->input->post('descripcion');
			$usuario = UsuarioBackendSesion::usuario ();
			$registro_auditoria->usuario = $usuario->nombre . ' ' . $usuario->apellidos . ' <' . $usuario->email . '>';
			$registro_auditoria->proceso = $proceso->nombre;
			$registro_auditoria->cuenta_id = UsuarioBackendSesion::usuario()->cuenta_id;
				
				
			// Detalles
			$proceso_array['proceso'] = $proceso->toArray(false);
				
			foreach ($proceso->Tramites as $tramite){
				
				$tramite_array = $tramite->toArray(false);
				unset($tramite_array['proceso_id']);
				$proceso_array['tramites'][] = $tramite_array;
				
			}
				
		
			$registro_auditoria->detalles = json_encode($proceso_array);
			$registro_auditoria->save();
				
			$proceso->Tramites->delete ();
		
				
			$respuesta->validacion = TRUE;
			$respuesta->redirect = site_url('backend/seguimiento/index_proceso/'.$proceso_id);
				
				
		
		} else {
		
			$respuesta->validacion = FALSE;
			$respuesta->errores = validation_errors();
		
		}
			
		echo json_encode($respuesta);
	}
	public function reset_proc_cont($proceso_id) {
		if (! in_array ( 'super', explode ( ",", UsuarioBackendSesion::usuario ()->rol ) ))
			show_error ( 'No tiene permisos', 401 );
		
		$proceso = Doctrine::getTable ( 'Proceso' )->find ( $proceso_id );
		
		$proceso->proc_cont = 0;
		$proceso->save ();
		
		redirect ( $this->input->server ( 'HTTP_REFERER' ) );
	}
	public function ajax_editar_vencimiento($etapa_id) {
		$etapa = Doctrine::getTable ( 'Etapa' )->find ( $etapa_id );
		$data ['etapa'] = $etapa;
		
		$this->load->view ( 'backend/seguimiento/ajax_editar_vencimiento', $data );
	}
	public function editar_vencimiento_form($etapa_id) {
		$this->form_validation->set_rules ( 'vencimiento_at', 'Fecha de vencimiento', 'required' );
		$this->form_validation->set_rules ( 'descripcion', 'Razón', 'required' );
		$respuesta = new stdClass ();
		if ($this->form_validation->run () == TRUE) {
			
			$fecha = new DateTime ();
			$etapa = Doctrine::getTable ( 'Etapa' )->find ( $etapa_id );
			
			// Auditar
			$registro_auditoria = new AuditoriaOperaciones ();
			$registro_auditoria->fecha = $fecha->format ( "Y-m-d H:i:s" );
			$registro_auditoria->motivo = $this->input->post ( 'descripcion' );
			$registro_auditoria->operacion = 'Cambio de Fecha de Vencimiento';
			$usuario = UsuarioBackendSesion::usuario ();
			$registro_auditoria->usuario = $usuario->nombre . ' ' . $usuario->apellidos . ' <' . $usuario->email . '>';
			$registro_auditoria->proceso = $etapa->Tramite->Proceso->nombre;
			$registro_auditoria->cuenta_id = UsuarioBackendSesion::usuario()->cuenta_id;
				
			
			/* Formatear detalles */
			$etapa_array ['proceso'] = $etapa->Tramite->Proceso->toArray(false);
			$etapa_array ['tramite'] = $etapa->Tramite->toArray ( false );
			$etapa_array['etapa'] = $etapa->toArray (false);
			unset ( $etapa_array ['tarea_id'] );
			unset ( $etapa_array ['tramite_id'] );
			unset ( $etapa_array ['usuario_id'] );
			unset ( $etapa_array ['etapa_ancestro_split_id'] );
			
			$etapa_array ['tarea'] = $etapa->Tarea->toArray ( false );
			
			$etapa_array ['usuario'] = $etapa->Usuario->toArray ( false );
			unset ( $etapa_array ['usuario'] ['password'] );
			unset ( $etapa_array ['usuario'] ['salt'] );
			$registro_auditoria->detalles = json_encode ( $etapa_array );
			$registro_auditoria->save ();
			
			$etapa->vencimiento_at = date ( 'Y-m-d', strtotime ( $this->input->post ( 'vencimiento_at' ) ) );
			$etapa->save ();
			
			$respuesta->validacion = TRUE;
			$respuesta->redirect = site_url ( 'backend/seguimiento/index_proceso/' . $etapa->Tarea->proceso_id );
		} else {
			$respuesta->validacion = FALSE;
			$respuesta->errores = validation_errors ();
		}
		
		echo json_encode ( $respuesta );
	}
	public function ajax_auditar_retroceder_etapa($etapa_id) {
		$etapa = Doctrine::getTable ( 'Etapa' )->find ( $etapa_id );
		$data ['etapa'] = $etapa;
		
		$this->load->view ( 'backend/seguimiento/ajax_auditar_retroceder_etapa', $data );
	}
	
	/**
	 *
	 * Vuelve a la/s etapa/s anterior/es
	 * En caso de ser la última etapa(ya finalizada), vuelve a dejar el trámite en curso
	 *
	 * @param unknown $etapa_id        	
	 */
	public function retroceder_etapa($etapa_id) {
		$this->form_validation->set_rules ( 'descripcion', 'Razón', 'required' );
		$respuesta = new stdClass ();
		if ($this->form_validation->run () == TRUE) {
			
			$fecha = new DateTime ();
			
			$etapa = Doctrine::getTable ( "Etapa" )->find ( $etapa_id );
			$tramite = Doctrine::getTable ( "Tramite" )->find ( $etapa->tramite_id );
			if ($etapa->pendiente == 1) {
				// Tarea anterior de la actual, ordenada por las etapas
				$tareas_anteriores = Doctrine_Query::create ()->select ( "c.tarea_id_origen as id, c.tipo, e.id as etapa_id, e.etapa_ancestro_split_id as origen_paralelo" )->from ( "Conexion c, c.TareaOrigen to, to.Etapas e" )->where ( "c.tarea_id_destino = ?", $etapa->tarea_id )->andWhere ( "e.tramite_id = ?", $tramite->id )->andWhere ( "e.id != ?", $etapa->id )->orderBy ( "e.id DESC" )->fetchOne ();

				if (count ( $tareas_anteriores ) > 0) {
					// Eliminamos la etapa actual
					$id_etapa_actual = $etapa->id;
					$id_tarea_actual = $etapa->tarea_id;
					$etapa->delete ();
					
					$tipo_conexion = $tareas_anteriores->tipo;
					
					// Si no es union, debe retroceder solo a la ultima etapa de la tarea anterior
					if ($tipo_conexion != 'union') {
						$tareas_anteriores = array (
								$tareas_anteriores 
						);
					} else {
						$tareas_anteriores = Doctrine_Query::create ()->select ( "c.tarea_id_origen as id, c.tipo, e.id as etapa_id" )->from ( "Conexion c, c.TareaOrigen to, to.Etapas e" )->where ( "c.tarea_id_destino = ?", $etapa->tarea_id )->andWhere ( "e.tramite_id = ?", $tramite->id )->andWhere ( "e.id != ?", $etapa->id )->andWhere ( "e.etapa_ancestro_split_id = ?", $tareas_anteriores->origen_paralelo )->orderBy ( "e.id DESC" )->execute ();
					}
					
					// Si es union va retroceder a todas las etapas de dicha union, sino tareas_anteriores tendra un solo elemento
					foreach ( $tareas_anteriores as $tarea_anterior ) {
						if ($etapa_anterior = Doctrine::getTable ( "Etapa" )->find ( $tarea_anterior->etapa_id )) {
							// Auditoría de la etapa a la cual se regresa
							$registro_auditoria = new AuditoriaOperaciones ();
							$registro_auditoria->fecha = $fecha->format ( "Y-m-d H:i:s" );
							$registro_auditoria->motivo = $this->input->post ( 'descripcion' );
							$registro_auditoria->operacion = 'Retroceso a Etapa';
							$registro_auditoria->proceso = $etapa_anterior->Tramite->Proceso->nombre;
							$registro_auditoria->cuenta_id = UsuarioBackendSesion::usuario()->cuenta_id;
						
							$usuario = UsuarioBackendSesion::usuario ();
							$registro_auditoria->usuario = $usuario->nombre . ' ' . $usuario->apellidos . ' <' . $usuario->email . '>';
						
							/* Formatear detalles */
							$etapa_array['proceso'] = $etapa_anterior->Tramite->Proceso->toArray(false);
							$etapa_array ['tramite'] = $etapa_anterior->Tramite->toArray ( false );
						
							$etapa_array['etapa'] = $etapa_anterior->toArray (false);
							unset ( $etapa_array ['etapa']['tarea_id'] );
							unset ( $etapa_array ['etapa']['tramite_id'] );
							unset ( $etapa_array ['etapa']['usuario_id'] );
							unset ( $etapa_array ['etapa']['etapa_ancestro_split_id'] );
						
							$etapa_array ['tarea'] = $etapa_anterior->Tarea->toArray ( false );
							$etapa_array ['usuario'] = $etapa_anterior->Usuario->toArray ( false );
							unset ( $etapa_array ['usuario'] ['password'] );
							unset ($etapa_array['usuario']['salt']);
						
							$etapa_array ['datos_seguimiento'] = Doctrine_Query::create ()
								->from ( "DatoSeguimiento d" )
								->where ( "d.etapa_id = ?", $etapa_anterior->id )
								->execute ( array (), Doctrine_Core::HYDRATE_ARRAY );
						
							$registro_auditoria->detalles = json_encode ( $etapa_array );
							$registro_auditoria->save ();
						}
						
						$etapas_otra_rama = array ();
						if ($tipo_conexion == 'paralelo' || $tipo_conexion == 'paralelo_evaluacion') {
							// Select de otras ramas para evitar inconsistencias
							$etapas_otra_rama = Doctrine_Query::create ()->select ( "c.tarea_id_destino as id" )->from ( "Conexion c, c.TareaDestino to, to.Etapas e" )->where ( "c.tarea_id_origen = ?", $tarea_anterior->id )->andWhere ( "c.tarea_id_destino != ?", $id_tarea_actual )->andWhere ( "c.tarea_id_destino != c.tarea_id_origen" )->andWhere ( "e.etapa_ancestro_split_id = ?", $tarea_anterior->etapa_id )->execute ();
						}
						// Si es en paralelo, y hay etapas en otras ramas, no se pone en pendiente aun
						if (count ( $etapas_otra_rama ) == 0) {
							
							Doctrine_Query::create ()->update ( "Etapa" )->set ( array (
									'pendiente' => 1,
									'ended_at' => null 
							) )->where ( "id = ?", $tarea_anterior->etapa_id )->execute ();
							

						}
					}
				}
			} else {
				
				// Auditoría
				$registro_auditoria = new AuditoriaOperaciones ();
				$registro_auditoria->fecha = $fecha->format ( "Y-m-d H:i:s" );
				$registro_auditoria->motivo = $this->input->post ( 'descripcion' );
				$registro_auditoria->operacion = 'Retroceso a Etapa';
				$registro_auditoria->proceso = $etapa->Tramite->Proceso->nombre;
				$registro_auditoria->cuenta_id = UsuarioBackendSesion::usuario()->cuenta_id;
				
				$usuario = UsuarioBackendSesion::usuario ();
				$registro_auditoria->usuario = $usuario->nombre . ' ' . $usuario->apellidos . ' <' . $usuario->email . '>';
				
				/* Formatear detalles */
				
				$etapa_array ['proceso'] = $etapa->Tramite->Proceso->toArray ( false );
				$etapa_array ['tramite'] = $etapa->Tramite->toArray ( false );
				
				$etapa_array['etapa'] = $etapa->toArray ( false );
				unset ( $etapa_array ['etapa']['tarea_id'] );
				unset ( $etapa_array ['etapa']['tramite_id'] );
				unset ( $etapa_array ['etapa']['usuario_id'] );
				unset ( $etapa_array ['etapa']['etapa_ancestro_split_id'] );
				
				
				$etapa_array ['tarea'] = $etapa->Tarea->toArray ( false );
				
				$etapa_array ['usuario'] = $etapa->Usuario->toArray ( false );
				unset ( $etapa_array ['usuario'] ['password'] );
				
				$etapa_array ['datos_seguimiento'] = Doctrine_Query::create ()->from ( "DatoSeguimiento d" )->where ( "d.etapa_id = ?", $etapa->id )->execute ( array (), Doctrine_Core::HYDRATE_ARRAY );
				
				$registro_auditoria->detalles = json_encode ( $etapa_array );
				$registro_auditoria->save ();
				
				$etapa->pendiente = 1;
				$etapa->ended_at = null;
				$etapa->save ();
				if ($tramite->pendiente == 0) {
					$tramite->pendiente = 1;
					$tramite->ended_at = null;
					$tramite->save ();
				}
			}
			
			$respuesta->validacion = TRUE;
			$respuesta->redirect = site_url ( 'backend/seguimiento/ver/' . $tramite->id );
		} else {
			$respuesta->validacion = FALSE;
			$respuesta->errores = validation_errors ();
		}
		
		echo json_encode ( $respuesta );
	}
	public function ajax_actualizar_id_tramite() {
		$max = Doctrine_Query::create ()->select ( 'MAX(id) as max' )->from ( "Tramite" )->fetchOne ();
		
		$data ['max'] = $max->max;
		$this->load->view ( 'backend/seguimiento/ajax_actualizar_id_tramite', $data );
	}
	public function actualizar_id_tramites_form() {
		
		$max = Doctrine_Query::create ()->select ( 'MAX(id) as max' )->from ( "Tramite" )->fetchOne ();
		
		$this->form_validation->set_rules ( 'id', 'Nuevo Id', 'greater_than['.$max->max.']' );
		$respuesta = new stdClass ();
		if ($this->form_validation->run () == TRUE) {
			
			$id = $this->input->post('id');
			
			$stmt = Doctrine_Manager::getInstance()->connection();
			$sql = "ALTER TABLE tramite AUTO_INCREMENT = ".$id.";";
			$stmt->execute($sql);
			
			$respuesta->validacion=TRUE;
			$respuesta->redirect= site_url("backend/seguimiento/index");
			
		} else {
			$respuesta->validacion = FALSE;
			$respuesta->errores = validation_errors ();
		}
		
		echo json_encode($respuesta);
	}
	
	public function ajax_auditar_eliminar_tramite($tramite_id){

		$tramite = Doctrine::getTable("Tramite")->find($tramite_id);
		$data['tramite'] = $tramite;
		$this->load->view ( 'backend/seguimiento/ajax_auditar_eliminar_tramite', $data );
		 
		 
	}
	

	public function ajax_auditar_limpiar_proceso($proceso_id){
	
		$proceso = Doctrine::getTable("Proceso")->find($proceso_id);
		$data['proceso'] = $proceso;
		$this->load->view ( 'backend/seguimiento/ajax_auditar_limpiar_proceso', $data );
			
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