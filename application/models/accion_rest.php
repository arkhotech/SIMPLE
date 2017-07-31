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
        //$CI->form_validation->set_rules('extra[header]', 'Header', 'required');
    }

    public function ejecutar(Etapa $etapa) {
        $data = Doctrine::getTable('Seguridad')->find($this->extra->idSeguridad);
        $tipoSeguridad=$data->extra->tipoSeguridad;
        $user = $data->extra->user;
        $pass = $data->extra->pass;
        $ApiKey = $data->extra->apikey;
        $NameKey='';
        
        if(strlen($data->extra->namekey)>3){
            $NameKey = $data->extra->namekey;
        }
        
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

        switch ($tipoSeguridad) {
            case "HTTP_BASIC":
                $config = array(
                    'server'          => $url,
                    'http_user'       => $user,
                    'http_pass'       => $pass,
                    'http_auth'       => 'basic'
                );
                break;
            case "API_KEY":
                $config = array(
                    'server'          => $url,
                    'api_key'         => $ApiKey,
                    'api_name'        => $NameKey
                );
                break;
            case "OAUTH2":
                //SEGURIDAD OAUTH2
                $config="Config de asuth 2";;
                break;
            default:
                //NO TIENE SEGURIDAD
                //print_r("No tiene seguridad");
                $config = array(
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

        $CI = & get_instance();

        if(isset($this->extra->header)){
            log_message('info', 'Ejecutar rest headers: '.$this->extra->header, FALSE);
            $r=new Regla($this->extra->header);
            $header=$r->getExpresionParaOutput($etapa->id);
            log_message('info', 'headers: '.$header, FALSE);
            $headers = json_decode($header);
            foreach ($headers as $name => $value) {
                $CI->rest->header($name.": ".$value);
            }
        }
        /*print_r("<pre>");
        print_r($config);
        print_r("</pre>");
        print_r("<pre>");
        print_r($request);
        print_r("</pre>");*/        
        try{
            if($this->extra->tipoMetodo == "GET"){
                log_message('info', 'Entre a una peticion get', FALSE);
                $CI->rest->initialize($config);
                $result = $CI->rest->get($uri, array() , 'json');
            }else if($this->extra->tipoMetodo == "POST"){
                log_message('info', 'Llamando POST', FALSE);
                $CI->rest->initialize($config);
                $result = $CI->rest->post($uri, $request, 'json');
            }else if($this->extra->tipoMetodo == "PUT"){
                log_message('info', 'Llamando PUT', FALSE);
                $CI->rest->initialize($config);
                $result = $CI->rest->put($uri, $request, 'json');
            }else if($this->extra->tipoMetodo == "DELETE"){
                log_message('info', 'Llamando DELETE', FALSE);
                //Falta capturar el codigo http de la cabecera.//  
                $CI->rest->initialize($config);
                $result = $CI->rest->delete($uri, $request, 'json');
            }
            $debug = $CI->rest->debug();
            if($debug['http_code']=='204'){
                log_message('info', 'Respuesta 204, No Content', FALSE);
            }
            $result2 = "{\"response_".$this->extra->tipoMetodo."\":".$result."}";
            log_message('info', 'IMPRIMIR Result: '.$result2, FALSE);

            $json=json_decode($result2);
            log_message('info', 'Result: '.$json, FALSE);
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
