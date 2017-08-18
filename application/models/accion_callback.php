<?php
require_once('accion.php');

class AccionCallback extends Accion {

    public function displaySecurityForm($proceso_id) {
        $data = Doctrine::getTable('Proceso')->find($proceso_id);
        $conf_seguridad = $data->Admseguridad;
        $display = '
            <p>
                Generar un acción de tipo Callback para responder a un agente externo que inicie un trámite en simple. (Debe existir la variable Callback creada. De lo contrario el proceso será interrumpido).
            </p>
        ';

        $display.='
                <label>Método</label>
                <select id="tipoMetodoC" name="extra[tipoMetodoC]">. 
                    <option value="">Seleccione...</option>';
                    if ($this->extra->tipoMetodoC && $this->extra->tipoMetodoC == "POST"){
                        $display.='<option value="POST" selected>POST</option>';
                    }else{
                        $display.='<option value="POST">POST</option>';
                    }
                    if ($this->extra->tipoMetodoC && $this->extra->tipoMetodoC == "PUT"){
                        $display.='<option value="PUT" selected>PUT</option>';
                    }else{
                        $display.='<option value="PUT">PUT</option>';
                    }
                    if ($this->extra->tipoMetodoC && $this->extra->tipoMetodoC == "DELETE"){
                        $display.='<option value="DELETE" selected>DELETE</option>';
                    }else{
                        $display.='<option value="DELETE">DELETE</option>';
                    }
        $display.='</select>';

        $display.='
            <div class="col-md-12">
                <label>Header</label>
                <textarea id="header" name="extra[header]" rows="7" cols="70" placeholder="{ Header }" class="input-xxlarge">' . ($this->extra ? $this->extra->header : '') . '</textarea>
                <br />
                <span id="resultHeader" class="spanError"></span>
                <br /><br />
            </div>';
        $display.='
            <label>Seguridad</label>
            <select id="tipoSeguridad" name="extra[idSeguridad]">';
            foreach($conf_seguridad as $seg){
                $display.='
                    <option value="">Sin seguridad</option>';
                    if ($this->extra->idSeguridad && $this->extra->idSeguridad == $seg->id){
                        $display.='<option value="'.$seg->id.'" selected>'.$seg->institucion.' - '.$seg->servicio.'</option>';
                    }else{
                        $display.='<option value="'.$seg->id.'">'.$seg->institucion.' - '.$seg->servicio.'</option>';
                    }
            }
        $display.='</select>';    
        return $display;
    }

    public function validateForm() {
        $CI = & get_instance();
        //$CI->form_validation->set_rules('extra[url]', 'Endpoint', 'required');
        //$CI->form_validation->set_rules('extra[uri]', 'Resource', 'required');
        $CI->form_validation->set_rules('extra[tipoMetodoC]', 'Método', 'required');
        //$CI->form_validation->set_rules('extra[request]', 'Request', 'required');
    }

    public function ejecutar(Etapa $etapa) {
        $required = Doctrine::getTable('Proceso')->findVaribleCallback($etapa['Tarea']['proceso_id']);
        $accion=Doctrine::getTable('Accion')->find($this->id);
        $data = Doctrine::getTable('Seguridad')->find($this->extra->idSeguridad);
        $proceso = Doctrine::getTable('Proceso')->findProceso($etapa['Tarea']['proceso_id']);
        $tipoSeguridad=$data->extra->tipoSeguridad;
        $user = $data->extra->user;
        $pass = $data->extra->pass;
        $ApiKey = $data->extra->apikey;
        ($data->extra->namekey ? $NameKey = $data->extra->namekey : $NameKey = '');
         
        $var=Doctrine::getTable('DatoSeguimiento')->findOneByNombreAndEtapaId("callback",$etapa->id);
        $callback_url=Doctrine::getTable('DatoSeguimiento')->findOneByNombreAndEtapaId("callback_url",$etapa->id);
        $callback_request=Doctrine::getTable('DatoSeguimiento')->findOneByNombreAndEtapaId("callback_request",$etapa->id);
        $callback_id=Doctrine::getTable('DatoSeguimiento')->findOneByNombreAndEtapaId("callback_id",$etapa->id);
        $request2['callback_response']=json_encode($callback_request->valor);
        $request2['callback_id']=$callback_id->valor;
        $request2['tramite_id']=$etapa['Tarea']['proceso_id'];
        $request2['etapa_id']=$etapa->id;
        $request=json_encode($request2);
        log_message('info',$this->varDump($request));

        if ( $callback_url || $required>0){
            // $r=new Regla($this->extra->url);
            // $url=$r->getExpresionParaOutput($etapa->id);
            // $caracter="/";
            // $f = substr($url, -1);
            // if($caracter===$f){
            //     $url = substr($url, 0, -1);
            // }

            //Hacemos encoding a la url
            // $url=preg_replace_callback('/([\?&][^=]+=)([^&]+)/', function($matches){
            //     $key=$matches[1];
            //     $value=$matches[2];
            //     return $key.urlencode($value);
            // },
            // $url);

            $nuevo = parse_url($callback_url->valor);
            $caracter="/";
            $server= $nuevo['scheme'].'://'.$nuevo['host'];
            $uri = $nuevo['path'];
            $l = substr($uri, 0, 1);
            if($caracter===$l){
                $uri = substr($uri, 1);
            }
   
            $CI = & get_instance();
            switch ($tipoSeguridad) {
                case "HTTP_BASIC":
                    //Seguridad basic
                    $config = array(
                        'server'          => $server,
                        'http_user'       => $user,
                        'http_pass'       => $pass,
                        'http_auth'       => 'basic'
                    );
                    break;
                case "API_KEY":
                    //Seguriad api key
                    $config = array(
                        'server'          => $server,
                        'api_key'         => $ApiKey,
                        'api_name'        => $NameKey
                    );
                    break;
                case "OAUTH2":
                    //SEGURIDAD OAUTH2
                    $config_seg = array(
                        'server'          => $url_auth
                    );
                    $request_seg= $data->extra->request_seg;
                    $CI->rest->initialize($config_seg);
                    $result = $CI->rest->post($uri_auth, $request_seg, 'json');
                    //Se obtiene la codigo de la cabecera HTTP
                    $debug_seg = $CI->rest->debug();
                    $response_seg= intval($debug_seg['info']['http_code']);
                    if($response_seg >= 200 && $response_seg < 300){
                        $config = array(
                            'server'          => $server,
                            'api_key'         => $result->token_type.' '.$result->access_token,
                            'api_name'        => 'Authorization'
                        );
                    }
                break;
                default:
                    //SIN SEGURIDAD
                    $config = array(
                        'server'          => $server
                    );
                break;
            }

            /*if(isset($this->extra->request)){
                $r=new Regla($this->extra->request);
                $request=$r->getExpresionParaOutput($etapa->id);
            }*/

            //obtenemos el Headers si lo hay
            if(isset($this->extra->header)){
                $r=new Regla($this->extra->header);
                $header=$r->getExpresionParaOutput($etapa->id);
                $headers = json_decode($header);
                foreach ($headers as $name => $value) {
                    $CI->rest->header($name.": ".$value);
                }
            }
            try{
                // Se ejecuta la llamada segun el metodo
                if($this->extra->tipoMetodoC == "POST"){
                    $CI->rest->initialize($config);
                    $result = $CI->rest->post($uri, $request, 'json');
                }else if($this->extra->tipoMetodoC == "PUT"){
                    $CI->rest->initialize($config);
                    $result = $CI->rest->put($uri, $request, 'json');
                }else if($this->extra->tipoMetodoC == "DELETE"){
                    $CI->rest->initialize($config);
                    $result = $CI->rest->delete($uri, $request, 'json');
                }

                //Se obtiene la codigo de la cabecera HTTP
                $debug = $CI->rest->debug();
                $parseInt=intval($debug['info']['http_code']);
                if ($parseInt<200 || $parseInt>204){
                    
                    // Ocurio un error en el server del Callback ## Error en el servidor externo ##
                    // Se guarda en Auditoria el error
                    $response['code']=$debug['info']['http_code'];
                    $response['des_code']=$debug['response_string'];
                    $response=json_encode($response);
                    $fecha = new DateTime();
                    $registro_auditoria = new AuditoriaOperaciones ();
                    $registro_auditoria->fecha = $fecha->format ( "Y-m-d H:i:s" );
                    $registro_auditoria->operacion = 'Error en respuesta de Callback';
                    $usuario = UsuarioBackendSesion::usuario ();
                    
                    // Se necesita cambiar el usuario al usuario público. 
                    $registro_auditoria->usuario = 'Admin Admin <admin@admin.com>';
                    $registro_auditoria->proceso = $proceso->nombre;
                    $registro_auditoria->cuenta_id = 1;
                    $registro_auditoria->motivo = $response;
                    
                    //Detalles de proceso
                    $accion_array['proceso'] = $proceso;
                    $accion_array['accion'] = $accion->toArray(false);
                    unset($accion_array['accion']['proceso_id']);
                    $registro_auditoria->detalles= 'Detalles';
                    $registro_auditoria->detalles=  json_encode($accion_array);
                    $registro_auditoria->save();
                   
                    // Se genera la variable callback_error y se le asigna el codigo y la descripcion del error.
                    $dato=Doctrine::getTable('DatoSeguimiento')->findOneByNombreAndEtapaId("callback_error",$etapa->id);
                    if(!$dato){
                        $dato=new DatoSeguimiento();
                        $dato->nombre="callback_error";
                        $dato->valor=$response;
                        $dato->etapa_id=$etapa->id;
                        $dato->save();
                    }else{
                        $dato->valor=$response;
                        $dato->save();
                    }
                }else{
                    // Caso OK, sin errores
                    $result2 = get_object_vars($result);
                    $response=$result2;
                        $dato=Doctrine::getTable('DatoSeguimiento')->findOneByNombreAndEtapaId('callback',$etapa->id);
                        if(!$dato){
                            $dato=new DatoSeguimiento();
                            $dato->nombre='callback';
                            $dato->valor=$response;
                            $dato->etapa_id=$etapa->id;
                            $dato->save();
                        }else{
                            $dato->valor=$response;
                            $dato->save();
                        }
                }
            }catch (Exception $e){
                $dato=Doctrine::getTable('DatoSeguimiento')->findOneByNombreAndEtapaId("error_rest",$etapa->id);
                if(!$dato)
                    $dato=new DatoSeguimiento();
                $dato->nombre="error_rest";
                $dato->valor=$e;
                $dato->etapa_id=$etapa->id;
                $dato->save();
            }
        }else{
            /////////////////////////////////////////////////////////////////////////////////////
            /// Caso donde no existe la variable callback y no se ejecuta la accion,
            //  Aqui falta agregar la auditoria.
            /////////////////////////////////////////////////////////////////////////////////////
            $response="No se pudo ejecutar el proceso de Callback debido a que no existe una variable para tal fin.";
            log_message('info','####################################################################################');
            log_message('info',$response);
            log_message('info','####################################################################################');
            // Auditoria
            $fecha = new DateTime();
            $registro_auditoria = new AuditoriaOperaciones ();
            $registro_auditoria->fecha = $fecha->format ( "Y-m-d H:i:s" );
            $registro_auditoria->operacion = 'Error en llamada Callback';
            $usuario = UsuarioBackendSesion::usuario ();
            // Se necesita cambiar el usuario al usuario público. 
            $registro_auditoria->usuario = 'Admin Admin <admin@admin.com>';
            $registro_auditoria->proceso = $proceso->nombre;
            $registro_auditoria->cuenta_id = 1;
            $registro_auditoria->motivo = $response;
            
            //Detalles
            $accion_array['proceso'] = $proceso;
            $accion_array['accion'] = $accion->toArray(false);
            unset($accion_array['accion']['proceso_id']);
            $registro_auditoria->detalles= 'Detalles';
            $registro_auditoria->detalles=  json_encode($accion_array);
            $registro_auditoria->save();  
        }
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