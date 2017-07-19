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
                        <input type="text" class="input-xxlarge AlignText" id="urlsoap" name="extra[wsdl]" value="' . ($this->extra ? $this->extra->wsdl : '') . '" />
                        <a class="btn btn-default" id="btn-consultar" ><i class="icon-search icon"></i> Consultar</a>
                        <a class="btn btn-default" href="#modalImportarWsdl" data-toggle="modal" ><i class="icon-upload icon"></i> Importar</a>
                </div>'; 
        
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
        $CI->form_validation->set_rules('extra[request]', 'Request', 'required');
        $CI->form_validation->set_rules('extra[operacion]', 'Métodos', 'required');
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
