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
                <label>WSDL</label>
               <input type="text" class="input-xxlarge" id="urlsoap" name="extra[wsdl]" value="' . ($this->extra ? $this->extra->wsdl : 'https://webpay3gint.transbank.cl/WSWebpayTransaction/cxf/WSWebpayService?wsdl') . '" />
                <button id="btn-consultar" type="button" class="btn btn-default">Consultar</button>

            </div>'; 
        
        $display.='
                <div id="divMetodos" style="display:none;" class="col-md-12">
                    <label>Métodos</label>
                    <div id="divOptions" class="col-md-12"></div>
                    <br /><br />
                    <span id="SpanResponse"></span>
                    <br /><br />
                </div>                
                <div id="divMetodosE" style="display:none;" class="col-md-12">
                    <span id="warningSpan" class="spanError"></span>
                    <br /><br />
                </div>';
                    
        $display.='            
            <div class="col-md-12">
                <label>Request</label>
                <textarea id="request" name="extra[request]" rows="7" cols="70" placeholder="{ object }" class="input-xxlarge">' . ($this->extra ? $this->extra->request : '') . '</textarea>
                <br />
                <span id="resultRequest" class="spanError"></span>
                <br /><br />
            </div>
           <div class="col-md-12">
                <label>Response</label>
                <textarea id="response" name="extra[response]" rows="7" cols="70" placeholder="{ object }" class="input-xxlarge" readonly>' . ($this->extra ? $this->extra->response : '') . '</textarea>
                <br /><br />
            </div>

            ';


        // $display.='
        //     <div class="col-md-12">
        //         <label>Header</label>
        //         <textarea id="header" name="extra[header]" rows="7" cols="70" placeholder="{ Header }" class="input-xxlarge">' . ($this->extra ? $this->extra->header : '') . '</textarea>
        //         <br />
        //         <span id="resultHeader" class="spanError"></span>
        //         <br /><br />
        //     </div>';
        return $display;
    }

    public function validateForm() {
        $CI = & get_instance();
        $CI->form_validation->set_rules('extra[wsdl]', 'WSDL', 'required');
        $CI->form_validation->set_rules('extra[request]', 'Request', 'required');
    }

    public function ejecutar(Etapa $etapa) {

        $r=new Regla($this->extra->url);
        $url=$r->getExpresionParaOutput($etapa->id);

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
