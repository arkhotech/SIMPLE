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
        $CI->form_validation->set_rules('extra[operacion]', 'Operación', 'required');
    }

    public function ejecutar(Etapa $etapa) {

        log_message('info', 'Ejecutar rest url: '.$this->extra->wsdl, FALSE);
        log_message('info', 'Ejecutar rest request: '.$this->extra->operacion, FALSE);
        log_message('info', 'Ejecutar rest request: '.$this->extra->request, FALSE);
        //log_message('info', 'Ejecutar rest request: '.$this->extra->response, FALSE);
        //log_message('info', 'Ejecutar rest request: '.$this->extra->header, FALSE);

        try{

            $CI = & get_instance();

            $r=new Regla($this->extra->wsdl);
            $wsdl=$r->getExpresionParaOutput($etapa->id);

            if(isset($this->extra->request)){
                log_message('info', 'Reemplazando soap request: '.$this->extra->request, FALSE);
                $r=new Regla($this->extra->request);
                $request=$r->getExpresionParaOutput($etapa->id);
                log_message('info', 'Request: '.$request, FALSE);
            }

            $request = json_decode($request, true);
            //var_dump($request);

            //print_r($request);
            //dd($request);

            $result = $CI->nusoap->soaprequest($wsdl, $this->extra->operacion, $request);

            log_message('info', 'Se obtiene respuesta', FALSE);
            $response_xml = $result["GetCitiesByCountryResult"];

            log_message('info', 'Cargando xml', FALSE);
            $response = simplexml_load_string($response_xml);
            log_message('info', 'Transformando a json', FALSE);
            $result = json_encode($response);

            $result = "{\"response_soap\":".$result."}";
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
            $dato=Doctrine::getTable('DatoSeguimiento')->findOneByNombreAndEtapaId("error_soap",$etapa->id);
            if(!$dato)
                $dato=new DatoSeguimiento();
            $dato->nombre="error_soap";
            $dato->valor=$e;
            $dato->etapa_id=$etapa->id;
            $dato->save();
        }

    }

}
