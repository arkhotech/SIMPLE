<?php

class API extends MY_BackendController {
    
    private $userHeadersKeys = array('Rut','Nombres','Apellido-Paterno','Apellido-Materno','Email');
    
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

    /*
     * Llamadas de la API
     * Tramote id es el identificador del proceso
     */

    public function especificacion($operacion ,$id_tramite,$id_tarea = NULL,$id_paso = NULL){

        //Cheque que la URL se complete correctamente
        if($operacion!= "servicio" && $operacion!= "form"){
            echo "$operacion";die;
            show_error("404 No encontrado",404, "La operación no existe" );
            exit;
        }

        switch($this->input->server('REQUEST_METHOD')){
            case "GET":
                $this->generarEspecificacion($operacion,$id_tramite,$id_tarea,$id_paso);
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
     * Chequea los headers con los datos de identificación segun el tipo:
     * 1.- Para clave unica
     * 2.- Pâra usuario backend
     * 
     * @param type $tipo valor entre 1 y 2
     */
    private function checkIdentificationHeaders($id_etapa,$id_tarea){
        
        $etapa = Doctrine::getTable('Tarea')->findOneById($id_tarea);

        if($etapa == NULL){
            echo "Error";
        }
 
        $headers = $this->input->request_headers();
        $method =  $this->router->fetch_method();
        $restrict_ops = $this->config->item('restrictred_rest_ops');
        $cu_keys = $this->userHeadersKeys;
        
        switch($etapa->acceso_modo){
        case 'claveunica':
            //print_r($headers);
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
            if( $headers['User']==NULL || !UsuarioSesion::registrarusuario($headers['User'])){
                header("HTTP/1.1 403 Forbiden");
                exit;
            }
            
            if( $etapa->acceso_modo==='grupos_usuarios'){
                $usuarios = $etapa->getUsuariosFromGruposDeUsuarioDeCuenta();
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
            $user->nombres = $headers['Nombres'];
            $user->apellido_paterno = $headers['Apellido-Paterno'];
            $user->apellido_materno = $headers['Apellido-Materno'];
            $user->email = $headers['Email'];
            $user->open_id = TRUE;
            $user->save();
        }
        $CI = & get_instance();
        $CI->session->set_userdata('usuario_id', $user->id);
         
    }
   
    public function tramites($proceso_id=null, $etapa = null) {
       
        //Tomar los segmentos desde el 3 para adelante
        //$urlSegment = $this->uri->segments;
        //print_r($urlSegment);
        //$cuenta = Cuenta::cuentaSegunDominio();
        
        switch($method = $this->input->server('REQUEST_METHOD')){
            case "GET":
                $this->listarCatalogo();
                break;
            case "PUT":
                $this->checkJsonHeader();
                $this->checkIdentificationHeaders($proceso_id,$etapa);
                $this->continuarProceso($proceso_id,$this->obtenerRequestBody());
                break;
            case "POST":
                log_message("INFO", "inicio proceso", FALSE);
                $this->checkJsonHeader();
                $this->checkIdentificationHeaders($proceso_id,$etapa);
                $this->iniciarProceso($proceso_id,$etapa,$this->obtenerRequestBody());
                break;
            default:
                header("HTTP/1.1 405 Metodo no permitido");
                $this->iniciarProceso($proceso_id,$etapa,$this->obtenerRequestBody());
                break;
            default:
                header("HTTP/1.1 405 Metodo no permitido");
        }

    }


    public function inicioProcesoSimple($proceso_id, $etapa, $body) {

                log_message("INFO", "inicio proceso", FALSE);
                $this->iniciarProceso($proceso_id,$etapa,$body);

    }

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

            UsuarioSesion::login('admin@admin.com', '123456');

            log_message("DEBUG", "carga libreria", FALSE);
            $this->load->library('SaferEval');

            log_message("DEBUG", "inicia tramite", FALSE);
            
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

    private function continuarProceso($id_proceso, $body){

        log_message("INFO", "En continuar proceso, input data: ".$body);

        try{
            $input = json_decode($body,true);

            if(!isset($input["idEtapa"]) || !isset($input["secuencia"])){
                header("HTTP/1.1 400 Bad Request");
                return;
            }
            //Obtener el nombre del proceso
            //$this->crearRegistroAuditoria($nombre_proceso, $body);
            $id_etapa = $input["idEtapa"];
            $secuencia = $input["secuencia"];

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
        
        $respuesta = new stdClass();
        $validar_formulario = FALSE;
        // Almacenamos los campos

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

        $this->crearRegistroAuditoria($etapa->Tarea->Proceso->nombre,$body);

        try{
            //obtener el primer paso de la secuencia o el pasado por parámetro
            $paso = $etapa->getPasoEjecutable($secuencia);

            log_message("INFO", "Paso: ".$paso, FALSE);
            log_message("INFO", "Paso ejecutable nro secuencia[".$secuencia."]: ".$paso->id, FALSE);

            $next_step = null;
            if(isset($paso)){
                $formulario = $paso->Formulario;
                $modo = $paso->modo;

                //TODO validar campos de formulario
                $respuesta = new stdClass();
                $validar_formulario = FALSE;
                // Almacenamos los campos

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

    private function registrarAuditoria($proceso_nombre,$operacion, $motivo, $detalles){
         $fecha = new DateTime();
         $registro_auditoria = new AuditoriaOperaciones ();
         $registro_auditoria->fecha = $fecha->format ( "Y-m-d H:i:s" );
         $registro_auditoria->operacion = $operacion;
         $usuario = UsuarioBackendSesion::usuario ();
            // Se necesita cambiar el usuario al usuario público.
         $registro_auditoria->usuario = 'Admin Admin <admin@admin.com>';
         $registro_auditoria->proceso = $proceso_nombre;
         $registro_auditoria->cuenta_id = 1;
         $registro_auditoria->motivo = $motivo;

         //unset($accion_array['accion']['proceso_id']);
         $registro_auditoria->detalles= 'Detalles';
         $registro_auditoria->detalles=  $detalles;//json_encode($accion_array);
         $registro_auditoria->save();
    }

  
    private function crearRegistroAuditoria($nombre_proceso,$body,$tipo = "INFO"){

        $headers = $this->input->request_headers();
        $new_headers = array('host' => $headers['Host'],
              'Origin' => $headers['Origin'],
            'largo-mensaje' => $headers['Content-Length'],
            'Content-type' => $headers['Content-type']);

        $data['headers'] = $new_headers;
        $data['input'] = $body['data'];
        
        if(array_key_exists('callback', $body)){
            $data['response_data'] = 
                array("Callback url" => $body['callback'],
                     "Callback id" => $body['callback-id']);
        }

        $this->registrarAuditoria($nombre_proceso,"Iniciar Proceso" ,
                $tipo.': Auditoría de llamados API',  json_encode($data));
    }    
}
