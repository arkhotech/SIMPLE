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
        $CI->form_validation->set_rules('extra[tipoMetodoC]', 'Método', 'required');
    }

    public function ejecutar(Etapa $etapa) {
        $accion=Doctrine::getTable('Accion')->find($this->id);
        $data = Doctrine::getTable('Seguridad')->find($this->extra->idSeguridad);
        $proceso = Doctrine::getTable('Proceso')->findProceso($etapa['Tarea']['proceso_id']);
        $callback = Doctrine::getTable('Proceso')->findVaribleCallback($etapa->id);
        if ($callback['valor']>0){

            $tipoSeguridad=$data->extra->tipoSeguridad;
            $user = $data->extra->user;
            $pass = $data->extra->pass;
            $ApiKey = $data->extra->apikey;
            ($data->extra->namekey ? $NameKey = $data->extra->namekey : $NameKey = '');

            foreach ($callback['data'] as $res){
                if($res['nombre']=='callback'){
                    if(strlen($res['valor'])>5){
                        $callback_url = $res['valor'];
                    }
                }else if($res['nombre']=='callback_id'){
                    $callback_id = $res['valor'];
                }
            }

   
            $callback_url = str_replace('\/', '/', $callback_url);
            $base = explode("/", $callback_url);
            $server = $base[0].'//'.$base[2];
            $server = str_replace('"', '', $server);
            $uri ='';
            for ($i = 3; $i <= count($base); $i++){
                $uri .='/'.$base[$i];
            }

            $caracter='/';
            $l = substr($uri, 0, 1);
            if($caracter===$l){
                $uri = substr($uri, 1);
            }
            $uri = str_replace('"', '', $uri);
            
            $campo = new Campo();
            $data=$campo->obtenerResultados($etapa,$etapa['Tarea']['proceso_id']);
            $output['idInstancia']=$etapa['tramite_id'];
            $output['idTarea']=$etapa['Tarea']['id'];
            $output['callback-id']=$callback_id;
            $output['data']=$data;

            log_message('info',$this->varDump($output));

            $request=json_encode($output);
            log_message('info',$this->varDump($output));
            log_message('info',$this->varDump($request));

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
            // obtenemos el Headers si lo hay
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
                    $operacion = 'Error en llamada Callback';
               
                    AuditoriaOperaciones::registrarAuditoria($proceso->nombre, 
                            "Error en llamada Callback", $response, array());
           
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
                log_message('error',$e->getMessage());
                $dato=Doctrine::getTable('DatoSeguimiento')->findOneByNombreAndEtapaId("error_rest",$etapa->id);
                if(!$dato)
                    $dato=new DatoSeguimiento();
                $dato->nombre="error_rest";
                $dato->valor=$e->getMessage();
                $dato->etapa_id=$etapa->id;
                $dato->save();
                
                AuditoriaOperaciones::registrarAuditoria($proceso->nombre, "Ejecutar Callback", $e->getMessage(), array());
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
   
            AuditoriaOperaciones::registrarAuditoria($proceso->nombre, "Ejecutar Callback",$response);
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