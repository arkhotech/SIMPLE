<?php

class AuditoriaOperaciones extends Doctrine_Record {
	
	
	function setTableDefinition() {
		$this->hasColumn('id');
		$this->hasColumn('fecha');
		$this->hasColumn('motivo');
		$this->hasColumn('detalles');
		$this->hasColumn('operacion');
		$this->hasColumn('usuario');
		$this->hasColumn('proceso');
		$this->hasColumn('cuenta_id');
	}
	
         /**
     * 
     * @param type $proceso_nombre Nombre del proceso que registra
     * @param type $operacion Operación que ha sido llamada
     * @param type $motivo Detalles de la auditoría. Ej.  Registo de llmaados
     * @param type $detalles Detalles en JSON
     */
    static public function registrarAuditoria($proceso_nombre,$operacion, $motivo, $detalles){
         $fecha = new DateTime();
         $registro_auditoria = new AuditoriaOperaciones ();
         $registro_auditoria->fecha = $fecha->format ( "Y-m-d H:i:s" );
         $registro_auditoria->operacion = $operacion;
          $user = UsuarioSesion::usuario();
        $datauser = "anonymous@no-domain.com";
        if($user){
            $datauser = $user->nombres." ".$user->apellido_paterno." <".$user->email.">";
        }
        log_message('INFO','Usuario Registrado '.$datauser);
            // Se necesita cambiar el usuario al usuario público.
         $registro_auditoria->usuario = $datauser;
         $registro_auditoria->proceso = $proceso_nombre;
         $registro_auditoria->cuenta_id = 1;
         $registro_auditoria->motivo = $motivo;

         //unset($accion_array['accion']['proceso_id']);
         $registro_auditoria->detalles=  $detalles;//json_encode($accion_array);
         $registro_auditoria->save();

    }
	
}
