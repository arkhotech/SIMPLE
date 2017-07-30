<?php
require_once('accion.php');

class AccionRest extends Accion {

    public function displaySecurityForm($proceso_id) {

        log_message('info', 'displaySecurityForm id proceso: '.$proceso_id, FALSE);

        $data = Doctrine::getTable('Proceso')->find($proceso_id);

        log_message('info', 'Obtiene proceso desde bd: '.$data->id, FALSE);

        $conf_seguridad = $data->Admseguridad;

        $display = '
            <p>
                Esta accion consultara via REST la siguiente URL. Los resultados, seran almacenados como variables.
            </p>
        ';

        $display.= '<label>URL</label>';
        $display.='<input type="text" class="input-xxlarge" name="extra[url]" value="' . ($this->extra ? $this->extra->url : '') . '" />';

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
        $CI->form_validation->set_rules('extra[url]', 'URL', 'required');
        //$CI->form_validation->set_rules('extra[header]', 'Header', 'required');
    }

    public function ejecutar(Etapa $etapa) {
        $data = Doctrine::getTable('Seguridad')->find($this->extra->idSeguridad);
        $tipoSeguridad=$data->extra->tipoSeguridad;
        $user = $data->extra->user;
        $pass = $data->extra->pass;
        $ApiKey = $data->extra->apikey;
        $NameKey = $data->extra->namekey;
        $r=new Regla($this->extra->url);
        $url=$r->getExpresionParaOutput($etapa->id);

        var_dump(parse_url($url));
        exit;

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

        $CI = & get_instance();

        /*if(isset($this->extra->header)){
            log_message('info', 'Ejecutar rest headers: '.$this->extra->header, FALSE);
            $r=new Regla($this->extra->header);
            $header=$r->getExpresionParaOutput($etapa->id);
            log_message('info', 'headers: '.$header, FALSE);
            $headers = json_decode($header);
            foreach ($headers as $name => $value) {
                $CI->rest->header($name.": ".$value);
            }
            print_r($CI->rest);
        }*/
        log_message('info', '#######################################################################################################', FALSE);
        try{
            if($this->extra->tipoMetodo == "GET"){
                log_message('info', 'Entre a una peticion get', FALSE);
                switch ($tipoSeguridad) {
                    case "HTTP_BASIC":
                        //SEGURIDAD BASIC
                        $config = array(
                            'server'          => $url,
                            'http_user'       => $user,
                            'http_pass'       => $pass,
                            'http_auth'       => 'basic'
                        );
                        $CI->rest->initialize($config);
                        $result = $CI->rest->get('posts/1/comments', array(), 'json');
                        break;
                    case "API_KEY":
                        // Set config options (only 'server' is required to work)
                        log_message('info', 'Entre a una api key get', FALSE);
                        
                        $config = array(
                            'server'          => $url,
                            'api_key'         => $ApiKey,
                            'api_name'        => $NameKey
                        );
                        $CI->rest->initialize($config);
                        $result = $CI->rest->get('/v3/templates/b8372ca8-aa4d-47aa-a506-85047bc201f4', array(), 'json');
                        break;
                    case "OAUTH2":
                        //SEGURIDAD OAUTH2
                        break;
                    default:
                        //NO TIENE SEGURIDAD
                        $result = $CI->rest->put($url, array(), 'json');
                    break;
                }
                exit;
                echo "entre en el get";
                // Run some setup
                // Pull in an array of tweets
            }else if($this->extra->tipoMetodo == "POST"){
                log_message('info', 'Lllamando POST', FALSE);
                switch ($tipoSeguridad) {
                    case "HTTP_BASIC":
                        //SEGURIDAD BASIC
                        $config = array(
                            'server'          => $url,
                            'http_user'       => $user,
                            'http_pass'       => $pass,
                            'http_auth'       => 'basic'
                        );
                        $CI->rest->initialize($config);
                        $result = $CI->rest->post('posts/1/comments', array(), 'json');
                        break;
                    case "API_KEY":
                        // Set config options (only 'server' is required to work)
                        $config = array(
                            'server'          => $url,
                            'api_key'         => $ApiKey,
                            'api_name'        => $NameKey
                        );
                        $CI->rest->initialize($config);
                        $result = $CI->rest->post('posts/1/comments', array(), 'json');
                        break;
                    case "OAUTH2":
                        //SEGURIDAD OAUTH2
                        break;
                    default:
                        //NO TIENE SEGURIDAD
                        $result = $CI->rest->post($url, array(), 'json');
                    break;
                }
                //$result = $CI->rest->post($url, $request, 'json');
            }else if($this->extra->tipoMetodo == "PUT"){
                log_message('info', 'Lllamando PUT', FALSE);
                switch ($tipoSeguridad) {
                    case "HTTP_BASIC":
                        //SEGURIDAD BASIC
                        $config = array(
                            'server'          => $url,
                            'http_user'       => $user,
                            'http_pass'       => $pass,
                            'http_auth'       => 'basic'
                        );
                        $CI->rest->initialize($config);
                        $result = $CI->rest->put('posts/1/comments', array(), 'json');
                        break;
                    case "API_KEY":
                        // Set config options (only 'server' is required to work)
                        $config = array(
                            'server'          => $url,
                            'api_key'         => $ApiKey,
                            'api_name'        => $NameKey
                        );
                        $CI->rest->initialize($config);
                        $result = $CI->rest->put('posts/1/comments', array(), 'json');
                        break;
                    case "OAUTH2":
                        //SEGURIDAD OAUTH2
                        break;
                    default:
                        //NO TIENE SEGURIDAD
                        $result = $CI->rest->put($url, array(), 'json');
                    break;
                }
                //$result = $CI->rest->put($url, $request, 'json');
            }else if($this->extra->tipoMetodo == "DELETE"){
                log_message('info', 'Llamando DELETE', FALSE);
                switch ($tipoSeguridad) {
                    case "HTTP_BASIC":
                        //SEGURIDAD BASIC
                        $config = array(
                            'server'          => $url,
                            'http_user'       => $user,
                            'http_pass'       => $pass,
                            'http_auth'       => 'basic'
                        );
                        $CI->rest->initialize($config);
                        $result = $CI->rest->delete('posts/1/comments', array(), 'json');
                        break;
                    case "API_KEY":
                        // Set config options (only 'server' is required to work)
                        $config = array(
                            'server'          => $url,
                            'api_key'         => $ApiKey,
                            'api_name'        => $NameKey
                        );
                        $CI->rest->initialize($config);
                        $result = $CI->rest->delete('posts/1/comments', array(), 'json');
                        break;
                    case "OAUTH2":
                        //SEGURIDAD OAUTH2
                        break;
                    default:
                        //NO TIENE SEGURIDAD
                        $result = $CI->rest->delete($url, array(), 'json');
                    break;
                }
                //$result = $CI->rest->delete($url, array(), 'json');
            }


            $result = json_encode($result);
            print_r($result);
            echo "Ya imprimi el result";
            exit;
            $result = "{\"response_".$this->extra->tipoMetodo."\":".$result."}";
            log_message('info', 'IMPRIMIR Result: '.$result, FALSE);

            $json=json_decode($result);
            log_message('info', 'Result: '.$json, FALSE);
            print_r($json);

            foreach($json as $key=>$value){
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

}
