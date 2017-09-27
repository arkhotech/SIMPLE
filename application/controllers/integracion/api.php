 <?php
require APPPATH.'/core/REST_Controller.php';
class API extends REST_Controller{
      
    public function tramites_post(){ 
        log_message("INFO", "inicio proceso", FALSE);
        
         if(!isset($this->get()['proceso']) 
                || !isset($this->get()['etapa'])){
            $this->response(array('message' => 'Parámetros insuficientes',"code"=> 400), 400);
        }
        try{
            $this->checkIdentificationHeaders($this->get()['etapa']);

            $mediator = new IntegracionMediator();

            $this->registrarAuditoria($this->get()['etapa'],"Iniciar Tramite","Tramites");

            $data = $mediator->iniciarProceso($this->get()['proceso'],$this->get()['etapa'],$this->request->body);
            $this->response($data);
        }catch(Exception $e){
            $this->response(
                array("message" => $e->getMessage(),
                "code" => $e->getCode()),$e->getCode());
        }
    }
    
    public function tramites_put(){
        
        if(!isset($this->get()['tramite']) 
                || !isset($this->get()['etapa']) 
                || !isset($this->get()['paso'])){
            $this->response(array( 'message' => 'Parametros insuficientes',"code" => 400), 400);
        }
        //Recuperar los valores
        $etapa_id = $this->get()['etapa'];
        $tramite_id = $this->get()['tramite'];
        $secuencia = $this->get()['paso'];

        try{

            $mediator = new IntegracionMediator();

            $etapa = Doctrine::getTable('Etapa')->findOneById($etapa_id);
            if($etapa == null ){
                $this->response(array("message"=> "Etapa no existe"),400);
            }

            $this->checkIdentificationHeaders($etapa->tarea_id);
            $this->registrarAuditoria($etapa->id,"Continuar Tramite","Tramites");
       
            $data = $mediator->continuarProceso($tramite_id,$etapa_id,$secuencia,$this->request->body);
        }catch(Exception $e){
            $this->response(array("message" => $e->getMessage(),"code" => $e->getCode() ),$e->getCode());
        }
        $this->response($data);        
    }
   
    /**
     *
     * @param type $tipo
     * @param type $id_tramite
     * @param type $id_paso
     */
    public function status_get(){

        log_message("INFO", "Status proceso", FALSE);

        try{
            if(!isset($this->get()['tramite']) && !isset($this->get()['rut']) && !isset($this->get()['user'])){
                $this->response(array('message' => 'Parámetros insuficientes',"code"=> 400), 400);
            }

            if(isset($this->get()['tramite'])) {
                $status = $this->obtenerStatusPorTramite($this->get()['tramite']);
            }else if(isset($this->get()['rut']) || isset($this->get()['user'])) {
                $status = $this->obtenerStatusPorUsuario($this->get()['rut'], $this->get()['user']);
            }

            $this->response($status);
        }catch(Exception $e){
            $this->response(
                array("message" => $e->getMessage(),
                "code" => $e->getCode()),$e->getCode());
        }

    }

    /**
     * Realiza un check de los headers para degerminar a quien están asignados
     * 
     * @param type $etapa
     * @param type $id_tarea
     * @return boolean
     */
    private function checkIdentificationHeaders($id_tarea){
        log_message('INFO','checkIdentificationHeaders',FALSE);
        $tarea = Doctrine::getTable('Tarea')->findOneById($id_tarea);
        
        if($tarea == NULL ){
            error_log("etapa debe ser una instancia de Etapa");
            throw new Exception("Etapa no existe",500);
        }
        $body = json_decode($this->request->body,false);
        
        log_message('DEBUG','Check modo',FALSE);

        switch($tarea->acceso_modo){
        case 'claveunica':
            if(!isset($body->identificacion)){
                throw new Exception('Headers Clave Unica no enviados',403);
            }
            $mediator = new IntegracionMediator();
            $mediator->registerUserFromHeadersClaveUnica($body->identificacion);
            if(UsuarioSesion::usuario()==NULL){
                log_message('ERROR','No se pudo registrar el usuario Open ID',FALSE);
                throw new Exception('No se pudo registrar el usuario Open ID',500);    
            };
            break;
        case 'registrados':
        case 'grupos_usuarios':
            log_message('DEBUG',"No existe el usuario o no viene el header ".$this->varDump($body->usuario_simple->user),TRUE);
            if( !isset($body->usuario_simple)|| !UsuarioSesion::registrarUsuario($body->usuario_simple->user)){
                log_message('DEBUG',"No existe el usuario o no viene el header ".$this->varDump($body),TRUE);
                throw new Exception('No se ha enviado el usuario',403); 
            }
            log_message('DEBUG','recuperando usuarios',FALSE);
            if( $tarea->acceso_modo==='grupos_usuarios'){
                log_message('DEBUG',$tarea->id);
                $usuarios = $tarea->getUsuariosFromGruposDeUsuarioDeCuenta($id_tarea);
                foreach($usuarios as $user){
                    
                    if($body->usuario_simple->user===$user->usuario){
                        log_message('DEBUG','Validando usuario clave unica: '.$user->usuario,FALSE);
                        return TRUE;
                    }
                }      
                //si no
               
            }else{
                return TRUE;
            }
            throw new Exception('Usuario no existe',403);
            break;
        case 'publico':
            if( !UsuarioSesion::usuario() ) {
                //crear un usuario para sesion anonima
                UsuarioSesion::createAnonymousSession();
            }
            break;
        }
          
    }
    /**
     * 
     * @param type $etapa_id
     * @param type $operacion
     * @param type $nombre_proceso
     */
    public function registrarAuditoria($etapa_id,$operacion,$nombre_proceso = NULL){
        $nombre_etapa = $nombre_proceso;
        $etapa = NULL;
        if($etapa_id != NULL){
            $etapa = Doctrine::getTable('Tarea')->findOneById($etapa_id);
            $nombre_etapa = ($etapa!= NULL) ? $etapa->nombre : "Catalogo";
            
        }
        $headers = $this->input->request_headers();
        $new_headers = array('host' => $headers['Host'],
            'Origin' => isset($headers['Origin'])? $headers['Origin'] : '',
            'largo-mensaje' => isset($headers['Content-Length']) ? $headers['Content-Length'] : '',
            'Content-type' => isset($headers['Content-type']) ? $headers['Content-type'] : '',
            'http-Method' =>  $this->input->server('REQUEST_METHOD')) ;

        $data['headers'] = $new_headers;
        
        if(isset($headers['User']) && $nombre_etapa != NULL ){ //Comprobar que exista el header y etapa
                   
            $data['Credenciales'] = 
                    array("Metodo de acceso" => $etapa->acceso_modo,
                          "Username" =>
                        ($etapa->acceso_modo == 'claveunica') 
                        ? $headers['Rut']:$headers['User']);
        }
        //Recuperar el nombre para el regisrto
        log_message('DEBUG',"Recuperando credencial de identificación para auditoría");

        AuditoriaOperaciones::registrarAuditoria($nombre_etapa,$operacion, 
                "Auditoria de llamados a API REST", json_encode($data));
    }

    private function obtenerStatusPorTramite($id_tramite){

        log_message('INFO','Obteniendo estado para trámite: '.$id_tramite,FALSE);

        try{
            $tramite = Doctrine::getTable('Tramite')->find($id_tramite);

            if(isset($tramite) && is_object($tramite)){

                log_message('INFO','Tramite recuperado',FALSE);

                $status = $this->obtenerInfoTramite($tramite);

            }else{
                throw new Exception("Trámite no encontrado", 412);
            }

            log_message('INFO','Status: '.$this->varDump($status),FALSE);
            return $status;
        }catch(Exception $e){
            throw new Exception($e->getMessage(), 500);
        }

    }

    private function obtenerStatusPorUsuario($rut=null, $nombre_usuario=null){

        log_message('INFO','Obteniendo estado trámites para rut: '.$rut,FALSE);

        try{

            $user = new Usuario();
            if($rut != null){
                $usuario = $user->findUsuarioPorRut($rut);
            }else{
                $usuario = $user->findUsuarioPorUser($nombre_usuario);
            }

            if(isset($usuario) && is_array($usuario) && count($usuario) > 0){

                log_message('INFO','Usuario recuperado: '.$usuario[0]["id"],FALSE);

                $tramites = Doctrine::getTable('Tramite')->tramitesPorUsuario($usuario[0]["id"]);

                log_message('INFO','Tramites recuperados: '.$tramites,FALSE);

                if(isset($tramites) && (is_object($tramites) || is_array($tramites))){
                    $statusTramites = array();
                    foreach ($tramites as $tramite){
                        $status = $this->obtenerInfoTramite($tramite);
                        array_push($statusTramites, $status);
                    }
                }

            }else{
                throw new Exception("Usuario no encontrado", 412);
            }

            log_message('INFO','Status: '.$this->varDump($statusTramites),FALSE);
            return $statusTramites;
        }catch(Exception $e){
            throw new Exception($e->getMessage(), 500);
        }
    }

    private function obtenerInfoTramite($tramite){


        try {

            $etapa = $tramite->getEtapasActuales()->get(0);

            $proceso = Doctrine::getTable('Proceso')->find($tramite->proceso_id);

            if (isset($etapa) && is_object($etapa)) {

                log_message('INFO', 'Etapa recuperada', FALSE);
                log_message('INFO', 'Id usuario: ' . $etapa->usuario_id, FALSE);

                $usuario = Doctrine::getTable('Usuario')->find($etapa->usuario_id);

                $rut = "No existe información";
                if (isset($usuario) && isset($usuario->rut) && strlen($usuario->rut) > 0) {
                    log_message('INFO', 'Usuario rut: ' . $usuario->rut, FALSE);
                    $rut = $usuario->rut;
                }

                log_message('INFO', 'Nombre proceso: ' . $proceso->nombre, FALSE);

                $tarea = Doctrine::getTable('Tarea')->find($etapa->tarea_id);

                $status = array("idTramite" => $tramite->id,
                    "nombreTramite" => $proceso->nombre,
                    "estado" => $tramite->pendiente == 1 ? "Pendiente" : "Completado",
                    "rutUsuario" => $rut,
                    "nombreEtapaActual" => $tarea->nombre);

            } else {
                $status = array("idTramite" => $tramite->id,
                    "nombreTramite" => $proceso->nombre,
                    "estado" => "Completado",
                    "rutUsuario" => "No existe información",
                    "nombreEtapaActual" => "No existe información");
            }
            return $status;
        }catch(Exception $e){
            throw new Exception("Problema al recuperar información del trámite");
        }
    }


    private function varDump($data){
        ob_start();
        //var_dump($data);
        print_r($data);
        $ret_val = ob_get_contents();
        ob_end_clean();
        return $ret_val;
    }
    
}
