<?php
require_once('accion.php');
   
class AccionSoap extends Accion {

    public function displaySecurityForm($proceso_id) {
        $data = Doctrine::getTable('Proceso')->find($proceso_id);
        $conf_seguridad = $data->Admseguridad;
        $display = '<p>
            Esta accion consultara via SOAP la siguiente URL. Los resultados, seran almacenados en la variable de respuesta definida.
            </p>';
        $display.= '<label>Variable respuesta</label>';
        $display.='<input type="text" name="extra[var_response]" value="' . ($this->extra ? $this->extra->var_response : '') . '" />';
        $display.='
                <div class="col-md-12">
                    <label>WSDL</label>
                    <input type="text" class="input-xxlarge AlignText" id="urlsoap" name="extra[wsdl]" value="' . ($this->extra ? $this->extra->wsdl : '') . '" />
                    <a class="btn btn-default" id="btn-consultar" ><i class="icon-search icon"></i> Consultar</a>
                    <a class="btn btn-default" href="#modalImportarWsdl" data-toggle="modal" ><i class="icon-upload icon"></i> Importar</a>
                </div>';

        $display.= '<label>Timeout</label>';
        $display.='<input type="text" placeholder="Tiempo en segundos..." name="extra[timeout]" value="' . ($this->extra ? $this->extra->timeout : '') . '" />';

        $display.= '<label>N&uacute;mero reintentos</label>';
        $display.='<input type="text" name="extra[timeout_reintentos]" value="' . ($this->extra ? $this->extra->timeout_reintentos : '3') . '" />';

        $display.='
                <div id="divMetodos" class="col-md-12">
                    <label>Métodos</label>
                    <select id="operacion" name="extra[operacion]">';
        if ($this->extra->operacion){
            $display.='<option value="'.($this->extra->operacion).'" selected>'.($this->extra->operacion).'</option>';
        }
        $display.='</select>
                </div>                
                <div id="divMetodosE" style="display:none;" class="col-md-12">
                    <span id="warningSpan" class="spanError"></span>
                    <br /><br />
                </div>';
        $display.='            
            <div class="col-md-12">
                <label>Request</label>
                <textarea id="request" name="extra[request]" rows="7" cols="70" placeholder="<xml></xml>" class="input-xxlarge">' . ($this->extra ? $this->extra->request : '') . '</textarea>
                <br />
                <!-- <span id="resultRequest" class="spanError"></span> -->
                <br /><br />
            </div>';
           /*<div class="col-md-12">
                <label>Response</label>
                <textarea id="response" name="extra[response]" rows="7" cols="70" placeholder="{ object }" class="input-xxlarge" readonly>' . ($this->extra ? $this->extra->response : '') . '</textarea>
                <br /><br />
            </div>';*/
        $display.='<div id="modalImportarWsdl" class="modal hide fade">
                <form method="POST" enctype="multipart/form-data" action="backend/acciones/upload_file">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h3>Importar Archivo Soap</h3>
                </div>
                <div class="modal-body">
                    <p>Cargue a continuación el archivo .wsdl del Servio Soap.</p>
                    <input type="file" name="archivo" />
                </div>
                <div class="modal-footer">
                    <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
                    <button type="button" id="btn-load" class="btn btn-primary">Importar</button>
                </div>
                </form>
            </div>
            <div id="modal" class="modal hide fade"></div>';
        $display.='<label>Seguridad</label>
                <select id="tipoSeguridad" name="extra[idSeguridad]">';
        foreach($conf_seguridad as $seg){
            $display.='<option value="">Sin seguridad</option>';
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
        $CI->form_validation->set_rules('extra[request]', 'Request', 'required');
        $CI->form_validation->set_rules('extra[operacion]', 'Métodos', 'required');
        $CI->form_validation->set_rules('extra[var_response]', 'Variable de respuesta', 'required');
    }

    public function ejecutar(Etapa $etapa) {

        //Se declara el cliente soap
        $client = new nusoap_client($this->extra->wsdl, 'wsdl');

        if(isset($this->extra->idSeguridad) && strlen($this->extra->idSeguridad) > 0 && $this->extra->idSeguridad > 0){
            $seguridad = new SeguridadIntegracion();
            $client = $seguridad->setSecuritySoap($client);
        }

        // Se asigna valor de timeout
        $client->soap_defencoding = 'UTF-8';
        $client->decode_utf8 = true;
        $client->timeout = $this->extra->timeout;
        $client->response_timeout = $this->extra->timeout;
        
        try{
            $CI = & get_instance();
            $r=new Regla($this->extra->wsdl);
            $wsdl=$r->getExpresionParaOutput($etapa->id);
            if(isset($this->extra->request)){
                $r=new Regla($this->extra->request);
                $request=$r->getExpresionParaOutput($etapa->id);
            }

            $intentos = -1;
            //se verifica si existe numero de reintentos
            $reintentos = 0;
            if(isset($this->extra->timeout_reintentos)){
                $reintentos = $this->extra->timeout_reintentos;
            }
            do{
                //Se EJECUTA el llamado Soap
                $result = $client->call($this->extra->operacion, $request,null,'',false,null,'rpc','literal', true);

                $error = $client->getError();
                log_message("INFO", "Error SOAP ".$this->varDump($error), FALSE);

                //se verifica si existe numero de reintentos
                if(isset($error) && strpos($error, 'timed out') !== false){
                    log_message("INFO", "Intento Nro: ".$intentos, FALSE);
                    $intentos++;
                }
            }while($intentos < $reintentos && strpos($error, 'timed out') !== false);
            
            if ($error){
                if(strpos($error, 'timed out') !== false){
                    $result_soap['code']= '504';
                    $result_soap['desc']= $error;
                }else{
                    $result_soap['code']= '500';
                    $result_soap['desc']= $error;
                }
            }else{
                $result_soap = $this->utf8ize($result);
            }
        }catch (Exception $e){
            $result_soap['code']= $e->getCode();
            $result_soap['desc']= $e->getMessage();
        }

        $result[$this->extra->var_response]= $result_soap;

        foreach($result as $key=>$value){

            log_message('info', 'key '.$key.': '.$this->varDump($value), FALSE);

            $dato=Doctrine::getTable('DatoSeguimiento')->findOneByNombreAndEtapaId($key,$etapa->id);

            if(!$dato)
                $dato=new DatoSeguimiento();
            $dato->nombre=$key;
            $dato->valor=$value;
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

    private function utf8ize($d) {
        try{
            if (is_array($d))
                foreach ($d as $k => $v)
                    $d[$k] = $this->utf8ize($v);
            else if(is_object($d))
                foreach ($d as $k => $v)
                    $d->$k = $this->utf8ize($v);
            else
                return utf8_encode($d);
        }catch (Exception $e){
            log_message('info', 'Exception utf8ize: '.$this->varDump($e), FALSE);
        }
        return $d;
    }

}
