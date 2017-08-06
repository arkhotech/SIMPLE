<?php
require_once('accion.php');

class AccionRest extends Accion {

    public function displaySecurityForm($proceso_id) {
        $data = Doctrine::getTable('Proceso')->find($proceso_id);
        $conf_seguridad = $data->Admseguridad;
        $display = '
            <p>
                Esta accion consultara via REST la siguiente URL. Los resultados, seran almacenados como variables.
            </p>
        ';
        $display.= '<label>Endpoint</label>';
        $display.='<input type="text" class="input-xxlarge" placeholder="Server" name="extra[url]" value="' . ($this->extra ? $this->extra->url : '') . '" />';
        $display.= '<label>Resource</label>';
        $display.='<input type="text" class="input-xxlarge" placeholder="Uri" name="extra[uri]" value="' . ($this->extra ? $this->extra->uri : '') . '" />';
        $display.='
                <label>Método</label>
                <select id="tipoMetodo" name="extra[tipoMetodo]">
                    <option value="">Seleccione...</option>';
                    if ($this->extra->tipoMetodo && $this->extra->tipoMetodo == "POST"){
                        $display.='<option value="POST" selected>POST</option>';
                    }else{
                        $display.='<option value="POST">POST</option>';
                    }
                    if ($this->extra->tipoMetodo && $this->extra->tipoMetodo == "GET"){
                        $display.='<option value="GET" selected>GET</option>';
                    }else{
                        $display.='<option value="GET">GET</option>';
                    }
                    if ($this->extra->tipoMetodo && $this->extra->tipoMetodo == "PUT"){
                        $display.='<option value="PUT" selected>PUT</option>';
                    }else{
                        $display.='<option value="PUT">PUT</option>';
                    }
                    if ($this->extra->tipoMetodo && $this->extra->tipoMetodo == "DELETE"){
                        $display.='<option value="DELETE" selected>DELETE</option>';
                    }else{
                        $display.='<option value="DELETE">DELETE</option>';
                    }
        $display.='</select>';
        $display.= '<label>Timeout</label>';
        $display.='<input type="text" placeholder="Tiempo en segundos..." name="extra[timeout]" value="' . ($this->extra ? $this->extra->timeout : '') . '" />';

        $display.='
            <div class="col-md-12" id="divObject" style="display:none;">
                <label>Request</label>
                <textarea id="request" name="extra[request]" rows="7" cols="70" placeholder="{ object }" class="input-xxlarge">' . ($this->extra ? $this->extra->request : '') . '</textarea>
                <br />
                <span id="resultRequest" class="spanError"></span>
                <br /><br />
            </div>';
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
        $CI->form_validation->set_rules('extra[url]', 'Endpoint', 'required');
        $CI->form_validation->set_rules('extra[uri]', 'Resource', 'required');
    }

    public function ejecutar(Etapa $etapa) {
        $data = Doctrine::getTable('Seguridad')->find($this->extra->idSeguridad);
        $tipoSeguridad=$data->extra->tipoSeguridad;
        $user = $data->extra->user;
        $pass = $data->extra->pass;
        $ApiKey = $data->extra->apikey;
        ($data->extra->namekey ? $NameKey = $data->extra->namekey : $NameKey = '');
        ($this->extra->timeout ? $timeout = $this->extra->timeout : $timeout = 30);

        $r=new Regla($this->extra->url);
        $url=$r->getExpresionParaOutput($etapa->id);
        $caracter="/";
        $f = substr($url, -1);
        if($caracter===$f){
            $url = substr($url, 0, -1);
        }

        $r=new Regla($this->extra->uri);
        $uri=$r->getExpresionParaOutput($etapa->id);
        $l = substr($uri, 0, 1);
        if($caracter===$l){
            $uri = substr($uri, 1);
        }

        $r=new Regla($data->extra->url_auth);
        $url_auth=$r->getExpresionParaOutput($etapa->id);
        $l = substr($url_auth, 0, 1);
        if($caracter===$l){
            $url_auth = substr($url_auth, 1);
        }

        $r=new Regla($data->extra->uri_auth);
        $uri_auth=$r->getExpresionParaOutput($etapa->id);
        $l = substr($uri_auth, 0, 1);
        if($caracter===$l){
            $uri_auth = substr($uri_auth, 1);
        }

        $CI = & get_instance();
        // Se declara el tipo de seguridad segun sea el caso
        switch ($tipoSeguridad) {
            case "HTTP_BASIC":
                //Seguridad basic
                $config = array(
                    'timeout'         => $timeout,
                    'server'          => $url,
                    'http_user'       => $user,
                    'http_pass'       => $pass,
                    'http_auth'       => 'basic'
                );
                break;
            case "API_KEY":
                //Seguridad api key
                $config = array(
                    'timeout'         => $timeout,
                    'server'          => $url,
                    'api_key'         => $ApiKey,
                    'api_name'        => $NameKey
                );
                break;
            case "OAUTH2":
                //SEGURIDAD OAUTH2
                $config_seg = array(
                    'timeout'         => $timeout,
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
                        'timeout'         => $timeout,
                        'server'          => $url,
                        'api_key'         => $result->token_type.' '.$result->access_token,
                        //'api_key'         => $result->token_type.' kjfghiofut485fhgruiotjbfgrhjh4uiyru',
                        'api_name'        => 'Authorization'
                    );
                }
            break;
            default:
                //SIN SEGURIDAD
                $config = array(
                    'timeout'         => $timeout,
                    'server'          => $url
                );
            break;
        }
        if(isset($this->extra->request)){
            $r=new Regla($this->extra->request);
            $request=$r->getExpresionParaOutput($etapa->id);
        }
        //Hacemos encoding a la url
        $url=preg_replace_callback('/([\?&][^=]+=)([^&]+)/', function($matches){
            $key=$matches[1];
            $value=$matches[2];
            return $key.urlencode($value);
        },
        $url);
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
            if($this->extra->tipoMetodo == "GET"){
                $CI->rest->initialize($config);
                $result = $CI->rest->get($uri, array() , 'json');
            }else if($this->extra->tipoMetodo == "POST"){
                $CI->rest->initialize($config);
                $result = $CI->rest->post($uri, $request, 'json');
            }else if($this->extra->tipoMetodo == "PUT"){
                $CI->rest->initialize($config);
                $result = $CI->rest->put($uri, $request, 'json');
            }else if($this->extra->tipoMetodo == "DELETE"){
                $CI->rest->initialize($config);
                $result = $CI->rest->delete($uri, $request, 'json');
            }
            //Se obtiene la codigo de la cabecera HTTP
            $debug = $CI->rest->debug();
            if($debug['info']['http_code']=='204'){
                $result2['code']= '204';
                $result2['des_code']= 'No Content';
            }else if($debud['info']['http_code']=='0'){
                $result2['code']= $debug['error_code'];
                $result2['des_code']= $debug['response_string'];
            }else{
                if(!is_object($result)) {
                    $result2['code']= '2';
                    $result2['des_code']= $debug['response_string'];
                }else{
                    $result2 = get_object_vars($result);
                }
            }
            $response["response".$this->extra->tipoMetodo]=$result2;
            foreach($response as $key=>$value){
                $dato=Doctrine::getTable('DatoSeguimiento')->findOneByNombreAndEtapaId($key,$etapa->id);
                if(!$dato)
                    $dato=new DatoSeguimiento();
                $dato->nombre=$key;
                $dato->valor=$value;
                $dato->etapa_id=$etapa->id;
                $dato->save();
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