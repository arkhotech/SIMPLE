 <?php
require APPPATH.'/core/REST_Controller.php';
class API extends REST_Controller{//MY_BackendController {
    
    private $userHeadersKeys = array('Rut','Nombres','Email');
    
    public function tramites_post(){   //$proceso_id=null, $etapa_id = null
        log_message("INFO", "inicio proceso", FALSE);
        log_message("INFO", "Call check headers", FALSE);
        
         if(!isset($this->get()['proceso']) 
                || !isset($this->get()['etapa'])){
            $this->response(array('message' => 'Parametros insuficientes'), 400);
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
            $this->response(array( 'message' => 'Parametros insuficientes'), 400);
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
            $this->response(array("message" => $e->getMessage()),$e->getCode());
        }
        $this->response($data);        
    }
   
    /**
     *
     * @param type $tipo
     * @param type $id_tramite
     * @param type $id_paso
     */
    public function status($tipo,$id_tramite, $rut ){

        if($tipo!= "tramite" ){
            show_error("404 No encontrado",404, "No se encuentra la operacion" );
            exit;
        }

        if($rut == NULL || $id_tramite == NULL ){
            show_error("400 Bad Request",400, "Uno de los parametros de entrada no ha sido especificado" );
        }

        switch($this->input->server('REQUEST_METHOD')){
            case "GET":
                $this->obtenerStatus($id_tramite,$rut);
                break;
            default:
                header("HTTP/1.1 405 Metodo no permitido.");
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

        $cu_keys = $this->userHeadersKeys;
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

    private function obtenerStatus($id_tramite, $rut ){

        $response = array("idTramite" => $id_tramite,
            "nombreTramite" => "Hardcoded Dummy",
            "rutUsuario" => $rut,
            "nombreEtapaActual" => "Eetapa Cero");
        $this->responseJson($response);
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
