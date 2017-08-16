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

        $display.= '<label>identificador de cliente</label>';
        $display.='<input type="text" class="input-xxlarge" placeholder="Identificador del cliente para reconocer quien hace el callback" name="extra[cliente]" value="' . ($this->extra ? $this->extra->cliente : '') . '" />';

        $display.= '<label>URL de callback</label>';
        $display.='<input type="text" class="input-xxlarge" placeholder="https://midominio.cl" name="extra[url]" value="' . ($this->extra ? $this->extra->url : '') . '" />';

        $display.= '<label>URI de callback</label>';
        $display.='<input type="text" class="input-xxlarge" placeholder="/recibir/9" name="extra[uri]" value="' . ($this->extra ? $this->extra->uri : '') . '" />';

        $display.='
                <label>Método</label>
                <select id="tipoMetodo" name="extra[tipoMetodo]">';
                    if ($this->extra->tipoMetodo && $this->extra->tipoMetodo == "POST"){
                        $display.='<option value="POST" selected>POST</option>';
                    }else{
                        $display.='<option value="POST">POST</option>';
                    }
        $display.='</select>';

        $display.='
            <div class="col-md-12" id="divObject" style="display:none;">
                <label>Request</label>';
        $display.='<input type="text" placeholder="@@Callback" name="extra[request]" value="' . ($this->extra ? $this->extra->request : '') . '" />';

        $display.='
            <div class="col-md-12">
                <label>Header</label>
                <textarea name="extra[header]" rows="7" cols="70" placeholder="{ Header }" class="input-xxlarge">' . ($this->extra ? $this->extra->header : '') . '</textarea>
                <br />
                <span id="resultHeader" class="spanError"></span>
                <br /><br />
            </div>';
        return $display;
    }

    public function validateForm() {
        $CI = & get_instance();
        $CI->form_validation->set_rules('extra[url]', 'Endpoint', 'required');
        $CI->form_validation->set_rules('extra[uri]', 'Resource', 'required');
        $CI->form_validation->set_rules('extra[tipoMetodo]', 'Método', 'required');
        // $CI->form_validation->set_rules('extra[request]', 'Request', 'required');
    }

    public function ejecutar(Etapa $etapa) {
        $required = Doctrine::getTable('Proceso')->findVaribleCallback($etapa['Tarea']['proceso_id']);
        if ($required[0][0]>0){
            $r=new Regla($this->extra->cliente);
            $cliente=$r->getExpresionParaOutput($etapa->id);

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

            $config = array(
                'server' => $url
            );

            $CI = & get_instance();
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
                $CI->rest->initialize($config);
                $result = $CI->rest->post($uri, $request, 'json');
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
        }else{
            /////////////////////////////////////////////////////////////////////////////////////
            /// Caso donde no existe la variable callback y no se ejecuta la accion,
            //  Aqui falta agregar la auditoria.
            /////////////////////////////////////////////////////////////////////////////////////
            $response="No se ejecuto el proceso callback porque no hay una variable Callback definida en el proceso";
            log_message('info',$this->varDump($response));
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