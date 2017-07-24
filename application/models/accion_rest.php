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
        $CI->form_validation->set_rules('extra[header]', 'Header', 'required');
    }

    public function ejecutar(Etapa $etapa) {

        log_message('info', 'ejecutar id idseguridad: '.$this->extra->idSeguridad, FALSE);

        $data = Doctrine::getTable('Seguridad')->find($this->extra->idSeguridad);

        log_message('info', 'Obtiene seguridad desde bd: '.$data->id, FALSE);

        $r=new Regla($this->extra->url);
        $url=$r->getExpresionParaOutput($etapa->id);

        log_message('info', 'Ejecutar rest url: '.$this->extra->url, FALSE);
        log_message('info', 'Ejecutar rest tipoMetodo: '.$this->extra->tipoMetodo, FALSE);
        if(isset($this->extra->request)){
            log_message('info', 'Ejecutar rest request: '.$this->extra->request, FALSE);
            $r=new Regla($this->extra->request);
            $request=$r->getExpresionParaOutput($etapa->id);
            log_message('info', 'Request: '.$request, FALSE);
        }


        //Hacemos encoding a la url
        $url=preg_replace_callback('/([\?&][^=]+=)([^&]+)/', function($matches){
            $key=$matches[1];
            $value=$matches[2];
            return $key.urlencode($value);
        },
        $url);

        log_message('info', 'Inicializando rest client', FALSE);
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

        try{
            if($this->extra->tipoMetodo == "GET"){
                log_message('info', 'Lllamando GET', FALSE);
                $result = $CI->rest->get($url, array(), 'json');
            }else if($this->extra->tipoMetodo == "POST"){
                log_message('info', 'Lllamando POST', FALSE);
                $result = $CI->rest->post($url, $request, 'json');
            }else if($this->extra->tipoMetodo == "PUT"){
                log_message('info', 'Lllamando PUT', FALSE);
                $result = $CI->rest->put($url, $request, 'json');
            }else if($this->extra->tipoMetodo == "DELETE"){
                log_message('info', 'Lllamando DELETE', FALSE);
                $result = $CI->rest->delete($url, array(), 'json');
            }

            $result = json_encode($result);
            $result = "{\"response_".$this->extra->tipoMetodo."\":".$result."}";
            log_message('info', 'Result: '.$result, FALSE);

            $json=json_decode($result);

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
