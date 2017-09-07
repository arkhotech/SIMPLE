<?php

class API extends MY_BackendController {
    
    private $userHeadersKeys = array('Rut','Nombres','Email');
    
    public function __construct() {
        parent::__construct();
    }
    
    public function _auth(){
        UsuarioBackendSesion::force_login();

//        if(UsuarioBackendSesion::usuario()->rol!='super' && UsuarioBackendSesion::usuario()->rol!='desarrollo'){
        if( !in_array('super', explode(',',UsuarioBackendSesion::usuario()->rol) ) && !in_array( 'desarrollo',explode(',',UsuarioBackendSesion::usuario()->rol))){
            echo 'No tiene permisos para acceder a esta seccion.';
            exit;
        }
    }

    /*

    }
    */
    private function obtenerRequestBody(){
        return file_get_contents('php://input');
    }

    /**
     * Llamadas de la API
     * Tramote id es el identificador del proceso
     *
     * 
     * @param type $operacion Operación que se ejecutara. Corresponde al tercer segmebto de la URL
     * @param type $id_proceso
     * @param type $id_tarea
     * @param type $id_paso
     */

    public function especificacion($operacion ,$id_proceso,$id_tarea = NULL,$id_paso = NULL){

        //Cheque que la URL se complete correctamente
        if($operacion!= "servicio" && $operacion!= "form"){
            show_error("404 No encontrado",404, "La operación no existe" );
            exit;
        }

        switch($this->input->server('REQUEST_METHOD')){
            case "GET":
                $this->generarEspecificacion($operacion,$id_proceso,$id_tarea,$id_paso);
                break;
            default:
                show_error("405 Metodo no permitido",405, "El metodo no esta implementado" );
        }

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

    private function checkJsonHeader(){
        $headers = $this->input->request_headers();
        if($headers['Content-Type']==NULL || $headers['Content-Type']!="application/json"){
            //show_error("415 Unsupported Media Type",415, "Se espera application/json" );
            header("HTTP/1.1 415 Unsupported Media Type. Solo se permite application/json");
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
            header("HTTP/1.1 500 Internal servel error. Etapa no existe");
           exit;
        }
 
        $headers = $this->input->request_headers();
        $method =  $this->router->fetch_method();
        $restrict_ops = $this->config->item('restrictred_rest_ops');
        $cu_keys = $this->userHeadersKeys;
        log_message('DEBUG','Check modo',FALSE);
        switch($tarea->acceso_modo){
        case 'claveunica':
            foreach($cu_keys as $key){
                
                if(!key_exists($key,$headers)){
                    header("HTTP/1.1 403 Forbiden. Headers Clave Unica no enviados");
                    exit;
                }
            }
            
            $this->registerUserFromHeadersClaveUnica($headers);
            if(UsuarioSesion::usuario()==NULL){
                log_message('ERROR','No se pudo registrar el usuario Open ID',FALSE);
                header("HTTP/1.1 500 Internal Server Error");
                exit;     
            };
            break;
        case 'registrados':
        case 'grupos_usuarios':
            
            if( !key_exists('User', $headers) || !UsuarioSesion::registrarUsuario($headers['User'])){
                error_log("No existe el usuario o no viene el header");
                header("HTTP/1.1 403 Forbiden");
                exit;
            }
            log_message('DEBUG','recuperando usuarios',FALSE);
            if( $tarea->acceso_modo==='grupos_usuarios'){
                log_message('DEBUG',$tarea->id);
                $usuarios = $tarea->getUsuariosFromGruposDeUsuarioDeCuenta($id_tarea);
                
                foreach($usuarios as $user){
                    if($headers['User']===$user->usuario){
                        log_message('DEBUG','Validando usuario clave unica: '.$user->usuario,FALSE);
                        return TRUE;
                    }
                }      
                //si no 
            }else{
                return TRUE;
            }
            
            header("HTTP/1.1 403 Forbiden");
            exit;
        case 'publico':
            break;
        }
          
    }
    
    private function registerUserFromHeadersClaveUnica($headers){
        log_message('INFO','Registrando cuenta clave unica ',FALSE);
        $user =Doctrine::getTable('Usuario')->findOneByRut($headers['Rut']);
        
        if($user == NULL){  //Registrar el usuario
            log_message('INFO','Registrando usuario: '.$headers['Rut'],FALSE);
            $user = new Usuario();
            $user->usuario = random_string('unique');
            $user->setPasswordWithSalt(random_string('alnum', 32));
            $user->rut = $headers['Rut'];
            $nombres = explode(";",$headers['Nombres']);
            if(count($nombres)< 3 ){
                header("HTTP/1.1 403 Forbiden. Credenciales incompletas");
                exit;
            }
            $user->nombres = $nombres[0];//$headers['Nombres'];
            $user->apellido_paterno = $nombres[1]; //$headers['Apellido-Paterno'];
            $user->apellido_materno = $nombres[2];//$headers['Apellido-Materno'];
            $user->email = $headers['Email'];
            $user->open_id = TRUE;
            $user->save();
        }
        $CI = & get_instance();
        $CI->session->set_userdata('usuario_id', $user->id);
         
    }
    
    public function procesos(){
        switch($method = $this->input->server('REQUEST_METHOD')){
            case "GET":
                $this->registrarAuditoria(null, 'Obtener Catalogo',"Catalogo");
                $this->listarCatalogo();
                break;
            default:
                header("HTTP/1.1 405 Metodo no permitido");
                exit;
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
            'Origin' => $headers['Origin'],
            'largo-mensaje' => $headers['Content-Length'],
            'Content-type' => $headers['Content-type'],
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
    
    
    
    public function tramites($proceso_tramite_id=null, $etapa_tarea_id = null,$secuencia=null) {
        /*
         * Auditar entradas
         */
        //
        
        switch($method = $this->input->server('REQUEST_METHOD')){
            case "PUT":
                $this->checkJsonHeader();
                $etapa = Doctrine::getTable('Etapa')->findOneById($etapa_tarea_id);
                $this->checkIdentificationHeaders($etapa->tarea_id);
                $this->registrarAuditoria($etapa->id,"Continuar Tramite","Tramites");
                $this->continuarProceso($proceso_tramite_id,$etapa_tarea_id,$secuencia,$this->obtenerRequestBody());
                break;
            case "POST":
                log_message("INFO", "inicio proceso", FALSE);
                $this->checkJsonHeader();
                log_message("INFO", "Call check headers", FALSE);
                $this->checkIdentificationHeaders($etapa_tarea_id);
                $this->registrarAuditoria($etapa_tarea_id,"Iniciar Tramite","Tramites");
                $this->iniciarProceso($proceso_tramite_id,$etapa_tarea_id,$this->obtenerRequestBody());
                break;
            default:
                header("HTTP/1.1 405 Metodo no permitido");
                break;
        }

    }
    /**
     * Operación que despliega lista de servicios.
     */
    private function listarCatalogo(){
        $tarea=Doctrine::getTable('Proceso')->findProcesosExpuestos(UsuarioBackendSesion::usuario()->cuenta_id);
        $result = array();
        $nombre_host = gethostname();
        ($_SERVER['HTTPS'] ? $protocol = 'https://' : $protocol = 'http://');
        foreach($tarea as $res ){
            array_push($result, array(
                "id" => $res['id'],
                "nombre" => $res['nombre'],
                "tarea" => $res['tarea'],
                "version" => "1.0",
                "institucion" => "N/I",
                "descripcion" => $res['previsualizacion'],
                "URL" => $protocol.$nombre_host.'/integracion/api/especificacion/servicio/'.$res['id'].'/'.$res['id_tarea']
            ));
        }
       $retval["catalogo"] = $result;
       
       header('Content-type: application/json');
       echo json_indent(json_encode($retval));
       exit;
    }
    /**
     * Inicia un proceso simple
     * 
     * @param type $proceso_id
     * @param type $id_tarea
     * @param type $body
     * @return type
     */
    private function iniciarProceso($proceso_id, $id_tarea, $body){
        //validar la entrada
        
        if($proceso_id == NULL || $id_tarea == NULL){
            header("HTTP/1.1 400 Bad Request");
            return;
        }

        try{
            $input = json_decode($body,true);
            log_message("DEBUG", "Input: ".$this->varDump($input), FALSE);
            //Validar entrada
            if(array_key_exists('callback',$input) && !array_key_exists('callback-id',$input)){
                header("HTTP/1.1 400 Bad Request");
                return;
            }

            log_message("DEBUG", "inicio proceso", FALSE);
            
            $tramite = new Tramite();
            $tramite->iniciar($proceso_id);
            
            log_message("INFO", "Iniciando trámite: ".$proceso_id, FALSE);

            $etapa_id = $tramite->getEtapasActuales()->get(0)->id;
            $result = $this->ejecutarEntrada($etapa_id, $input, 0, $tramite->id);
            
            if(array_key_exists('callback',$input)){
                $this->registrarCallbackURL($input['callback'],$input['callback-id'],$etapa_id);
            }
            log_message("INFO", "Preparando respuesta: ".$proceso_id, FALSE);
            //validaciones etapa vencida, si existe o algo por el estilo

             $response = array(
                "idInstancia" => $tramite->id,
                "output" => $result ['result']['output'],
                 "idEtapa" => $result ['result']['idEtapa'],
                 "secuencia" => $result ['result']['secuencia'],
                "proximoFormulario" => $result['result']['proximoForlulario']
                );
             $this->responseJson($response);
        }catch(Exception $e){
           $e->getTrace();
        }

    }

    private function continuarProceso($id_proceso,$id_etapa,$secuencia, $body){

        log_message("INFO", "En continuar proceso, input data: ".$body);

        try{
            $input = json_decode($body,true);

            if($id_etapa == NULL || $id_secuencia=NULL ){
                header("HTTP/1.1 400 Bad Request");
                return;
            }
            //Obtener el nombre del proceso

            log_message("INFO", "id_etapa: ".$id_etapa);
            log_message("INFO", "secuencia: ".$secuencia);

            $result = $this->ejecutarEntrada($id_etapa, $input, $secuencia, $id_proceso);

            $response = array(
                "idInstancia" => $id_proceso,
                "output" => $result ['result']['output'],
                "idEtapa" => $result ['result']['idEtapa'],
                "secuencia" => $result ['result']['secuencia'],
                "proximoFormulario" => $result['result']['proximoForlulario']
            );
            $this->responseJson($response);
        }catch(Exception $e){
            $e->getTrace();
        }

    }

    private function generarEspecificacion($operacion,$id_tramite=NULL,$id_tarea=NULL,$id_paso = NULL){

        if($operacion === "form"){
            $integrador = new FormNormalizer();
            $response = $integrador->obtenerFormularios($id_tramite, $id_tarea, $id_paso);
            $this->responseJson($response);
        }else{
            $this->load->helper('download');

            $integrador = new FormNormalizer();
            /* Siempre obtengo el paso número 1 para generar el swagger de la opracion iniciar trámite */
            $formulario = $integrador->obtenerFormularios($id_tramite, $id_tarea, 0);

            if($id_tramite == NULL || $id_tarea == NULL ){
                header("HTTP/1.1 400 Bad Request");
                exit;
            }

            $swagger_file = $integrador->generar_swagger($formulario, $id_tramite, $id_tarea);

            force_download("start_simple.json", $swagger_file);
            exit;
        }
    }


    private function obtenerStatus($id_tramite, $rut ){

        $response = array("idTramite" => $id_tramite,
            "nombreTramite" => "Hardcoded Dummy",
            "rutUsuario" => $rut,
            "nombreEtapaActual" => "Eetapa Cero");
        $this->responseJson($response);
    }

     private function responseJson($response){
         header('Content-type: application/json');
       echo json_indent(json_encode($response));
    }

    private function varDump($data){
        ob_start();
        //var_dump($data);
        print_r($data);
        $ret_val = ob_get_contents();
        ob_end_clean();
        return $ret_val;
    }

    private function extractVariable($body,$campo,$tramite_id){
       
        if(isset($body['data'][$campo->nombre])){
            //Guardar el nombre único
            if($campo->tipo === 'file'){
                
                $parts = explode(".",$body['data'][$campo->nombre]['nombre']);
                $filename = random_string('alnum',10).".". random_string('alnum',2).".".
                    random_string('alnum',4).".".$parts[1];
                //$body['data'][$campo->nombre]['mime-type'];
                //$body['data'][$campo->nombre]['content'];
                File::saveFile($filename, 
                                $tramite_id, 
                                $body['data'][$campo->nombre]['content']);
                return $filename;//$body['data'][$campo->nombre]['nombre'];
            }else{
                return (is_array($body['data'][$campo->nombre])) ? json_encode($body['data'][$campo->nombre]) : $body['data'][$campo->nombre];
            }
          }
        return "NE";
    }
    /**
     *
     * @param type $etapa_id
     * @param type $body
     * @return type
     */
    public function ejecutarEntrada($etapa_id,$body, $secuencia = 0, $id_proceso){

        log_message("INFO", "Ejecutar Entrada", FALSE);

        $etapa = Doctrine::getTable('Etapa')->find($etapa_id);
        
        log_message("INFO", "Tramite id desde etapa: ".$etapa->tramite_id, FALSE);

        if (!$etapa) {
            header("HTTP/1.1 404 Etapa no fue encontrada");
            exit;
        }
        if ($etapa->tramite_id != $id_proceso) {
            header("HTTP/1.1 412 Etapa no pertenece al proceso ingresado");
            exit;
        }
        if (!$etapa->pendiente) {
            header("HTTP/1.1 412 Esta etapa ya fue completada");
            exit;
        }
        if (!$etapa->Tarea->activa()) {
            header("HTTP/1.1 412 Esta etapa no se encuentra activa");
            exit;
        }
        if ($etapa->vencida()) {
            header("HTTP/1.1 412 Esta etapa se encuentra vencida");
            exit;
        }

        try{
            //obtener el primer paso de la secuencia o el pasado por parámetro
            $paso = $etapa->getPasoEjecutable($secuencia);

            log_message("INFO", "Paso: ".$paso, FALSE);
            log_message("INFO", "Paso ejecutable nro secuencia[".$secuencia."]: ".$paso->id, FALSE);

            $next_step = null;
            if(isset($paso)){
                $formulario = $paso->Formulario;
                $modo = $paso->modo;

                log_message("INFO", "Validando campos del formulario", FALSE);
                $valida_formulario = TRUE;
                if ($modo == 'edicion') {
                    log_message("INFO", "Entra en modo edición", FALSE);
                    foreach ($formulario->Campos as $c) {
                        // Validamos los campos que no sean readonly y que esten disponibles (que su campo dependiente se cumpla)
                        log_message("INFO", "Campo nombre: ".$c->nombre, FALSE);
                        log_message("INFO", "Campo validacion: ".$this->varDump($c->validacion), FALSE);

                        if(count($c->validacion) > 0){
                            foreach ($c->validacion as $validacion) {
                                log_message("INFO", "Campo requerido en for: " . $validacion, FALSE);
                                if($validacion == "required"){
                                    $valor = $this->extractVariable($body,$c,$etapa->tramite_id);
                                    log_message("INFO", "Valor para campo: " . $valor, FALSE);
                                    if($valor == "NE"){
                                        $valida_formulario = FALSE;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }

                if (!$valida_formulario) {
                    header("HTTP/1.1 400 Favor verificar parametros requeridos.");
                    exit;
                }

                foreach ($formulario->Campos as $c) {
                    // Almacenamos los campos que no sean readonly y que esten disponibles (que su campo dependiente se cumpla)
                    
                    if ($c->isEditableWithCurrentPOST($etapa_id,$body)) {
                        $dato = Doctrine::getTable('DatoSeguimiento')->findOneByNombreAndEtapaId($c->nombre, $etapa->id);
                        if (!$dato)
                            $dato = new DatoSeguimiento();
                        $dato->nombre = $c->nombre;
                        
                        $dato->valor = $this->extractVariable($body,$c,$etapa->tramite_id)=== false?'' :  $this->extractVariable($body,$c,$etapa->tramite_id);
                        if (!is_object($dato->valor) && !is_array($dato->valor)){
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
                //Obtiene el siguiete paso
                $next_step = $etapa->getPasoEjecutable($secuencia+1);
            }

            $result = $this->procesar_proximo_paso($secuencia, $next_step, $etapa, $id_proceso);


        }catch(Exception $e){
            print_r($e->getMessage());die;
            echo $e->getMessage();
            return null;
        }
        return $result;

    }

    private function registrarCallbackURL($callback,$callback_id,$etapa){
        if($callback != NULL ){
            $dato = new DatoSeguimiento();
            $dato->nombre = "callback";
            $dato->valor = $callback; //"{ url:".$url."}";
            $dato->etapa_id = $etapa;
            $dato->save();

            $dato2 = new DatoSeguimiento();
            $dato2->nombre = "callback_id";
            $dato2->valor = $callback_id;
            $dato2->etapa_id = $etapa;
            $dato2->save();
        }
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

    /**
     * @param $secuencia
     * @param $next_step
     * @param $etapa
     * @param $id_proceso
     * @return mixed
     */
    private function procesar_proximo_paso($secuencia, $next_step, $etapa, $id_proceso) {

        $result['result']=array();
        $result['result']['proximoForlulario']=array();
        $form_norm=array();
        
        $etapa_id = $etapa->id;

        $integrador = new FormNormalizer();
        $secuencia = $secuencia+1;
        if($next_step == NULL){
            //Finlaizar etapa
            $etapa->avanzar();
            log_message("INFO", "Id etapa despues de avanzar: ".$etapa->id, FALSE);

            $etapa_prox = $this->obtenerProximaEtapa($etapa, $id_proceso);
            if(isset($etapa_prox) && count($etapa_prox) == 1){
                $next_step = $etapa_prox[0]->getPasoEjecutable(0);
                while($next_step == null){
                    //Finlaizar etapa
                    $etapa_prox[0]->avanzar();

                    $etapa_prox = $this->obtenerProximaEtapa($etapa_prox[0], $id_proceso);

                    if(!isset($etapa_prox))
                        break;

                    $next_step = $etapa_prox[0]->getPasoEjecutable(0);
                }

                $form_norm = $integrador->obtenerFormulario($next_step->formulario_id,$etapa->id);

                $etapa_id = $etapa_prox[0]->id;
                $secuencia = 1;

            }else if(isset($etapa_prox) && count($etapa_prox) > 1){
                //TODO tareas en paralelo
                $secuencia = null;
            }else{
                //No existen mas etapas
                //Pendiente definir comportamiento standby
                $secuencia = null;
            }

        }else{
            
            $paso = $etapa->getPasoEjecutable($secuencia);
            $form_norm = $integrador->obtenerFormulario($paso->formulario_id,$etapa->id);
        }
        
        $campos = new Campo();
        log_message("INFO", "Id etapa asignado: ".$etapa_id, FALSE);
        $result['result']['proximoForlulario'] = $form_norm;
        $result['result']['idEtapa'] = $etapa_id;
        $result['result']['secuencia'] = $secuencia;      
        $result['result']['output']= $campos->obtenerResultados($etapa,$this);

        
        return $result;
    }

    private function obtenerProximaEtapa($etapa, $id_proceso){
        //Obtener la siguiente tarea
        $next = $etapa->getTareasProximas();

        $etapas = array();

        if(isset($next)){
            if($next->estado != 'completado'){
                if ($next->tipo == 'paralelo' || $next->tipo == 'paralelo_evaluacion') {
                    //etapas en paralelo
                    foreach($next->tareas as $tarea ){
                        $etapa_prox = $etapa->getEtapaPorTareaId($tarea->id, $id_proceso);
                        $etapas[] = $etapa_prox;
                    }
                }else if ($next->tipo == 'union') {
                    if ($next->estado == 'standby') {
                        //Esperar, enviar respuesta informando que se debe esperar
                        $etapas = null;
                    }
                }else{

                    $tarea_id = $next->tareas[0]->id;
                    $etapa_prox = $etapa->getEtapaPorTareaId($tarea_id, $id_proceso);

                    $etapas[] = $etapa_prox;

                }
            }else{
                $etapas = null;
            }
        }else{
            $etapas = null;
        }
        return $etapas;
    }

  
//    private function crearRegistroAuditoria($nombre_proceso,$body,$tipo = "INFO"){
//
//        $headers = $this->input->request_headers();
//        $new_headers = array('host' => $headers['Host'],
//              'Origin' => $headers['Origin'],
//            'largo-mensaje' => $headers['Content-Length'],
//            'Content-type' => $headers['Content-type']);
//
//        $data['headers'] = $new_headers;
//        $data['input'] = $body['data'];
//        
//        if(array_key_exists('callback', $body)){
//            $data['response_data'] = 
//                array("Callback url" => $body['callback'],
//                     "Callback id" => $body['callback-id']);
//        }
//
//        AuditoriaOperaciones::registrarAuditoria($nombre_proceso,"Iniciar Proceso" ,
//                $tipo.': Auditoría de llamados API',  json_encode($data));
//    }    
}
