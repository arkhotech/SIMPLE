<?php
require_once('accion.php');
   
class AccionSoap extends Accion {

    public function displayForm() {
        $display = '
            <p>
                Esta accion consultara via SOAP la siguiente URL. Los resultados, seran almacenados como variables.
            </p>
        ';

         $display.='
            <div class="col-md-12">
                <label>URL</label>
               <input type="text" class="input-xxlarge" id="urlsoap" name="extra[url]" value="' . ($this->extra ? $this->extra->url : '') . '" />
                <button id="btn-consultar" type="button" class="btn btn-default">Consultar</button>

            </div>'; 
        
        $display.='
                <label>Método</label>
                <select id="tipoMetodo" name="extra[tipoMetodo]">
                    <option value="">Seleccione...</option>
                    <option value="POST">POST</option>
                    <option value="GET">GET</option>
                    <option value="PUT">PUT</option>
                    <option value="DELETE">DELETE</option>';
                    if ($this->extra->tipoMetodo){
                        $display.='<option value="'.($this->extra->tipoMetodo).'" selected>'.($this->extra->tipoMetodo).'</option>';
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

        $display.= '<label>URL</label>';
        $display.='<input type="text" class="input-xxlarge" name="extra[url]" value="' . ($this->extra ? $this->extra->url : '') . '" />';


        return $display;
    }

    public function validateForm() {
        $CI = & get_instance();
        $CI->form_validation->set_rules('extra[url]', 'URL', 'required');
        $CI->form_validation->set_rules('extra[header]', 'Header', 'required');
    }

    public function ejecutar(Etapa $etapa) {

        $r=new Regla($this->extra->url);
        $url=$r->getExpresionParaOutput($etapa->id);

        log_message('info', 'Ejecutar rest url: '.$this->extra->url, FALSE);
        log_message('info', 'Ejecutar rest tipoMetodo: '.$this->extra->tipoMetodo, FALSE);
        log_message('info', 'Ejecutar rest request: '.$this->extra->request, FALSE);

        //Hacemos encoding a la url
        $url=preg_replace_callback('/([\?&][^=]+=)([^&]+)/', function($matches){
            $key=$matches[1];
            $value=$matches[2];
            return $key.urlencode($value);
        },
        $url);

        log_message('info', 'Inicializando rest client', FALSE);
        $CI = & get_instance();

        if($this->extra->tipoMetodo == "GET"){
            log_message('info', 'Lllamando GET', FALSE);
            $result = $CI->rest->get($url, array(), 'json');
        }else if($this->extra->tipoMetodo == "POST"){
            log_message('info', 'Lllamando POST', FALSE);
            $result = $CI->rest->post($url, $this->extra->request, 'json');
        }else if($this->extra->tipoMetodo == "PUT"){
            log_message('info', 'Lllamando PUT', FALSE);
            $result = $CI->rest->put($url, $this->extra->request, 'json');
        }else if($this->extra->tipoMetodo == "DELETE"){
            log_message('info', 'Lllamando DELETE', FALSE);
            $result = $CI->rest->delete($url, array(), 'json');
        }

        $result = json_encode($result);
        $result = "{\"metodo".$this->extra->tipoMetodo."\":".$result."}";
        log_message('info', 'Result: '.$result, FALSE);
        //$result = json_decode("metodo".$this->extra->tipoMetodo.":".$result);

        $json=json_decode($result);
        //$json=$result;
        
        foreach($json as $key=>$value){
            $dato=Doctrine::getTable('DatoSeguimiento')->findOneByNombreAndEtapaId($key,$etapa->id);
            if(!$dato)
                $dato=new DatoSeguimiento();
            $dato->nombre=$key;
            $dato->valor=$value;
            $dato->etapa_id=$etapa->id;
            $dato->save();
        }        
        
    }

}
