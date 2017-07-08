<?php
require_once('accion.php');

class AccionRest extends Accion {

    public function displayForm() {
        $display = '
            <p>
                Esta accion consultara via REST la siguiente URL. Los resultados, seran almacenados como variables.
            </p>
            <p>
                Los resultados esperados deben venir en formato JSON siguiendo este formato:
            </p>
        ';

        $display.='<script src="'.base_url().'assets/js/CrearDivHeader.js" type="text/javascript"></script>'; 
        
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
            <div id="divObject" style="display:none;">
                <label>Request</label>
                <textarea name="extra[request]" rows="7" cols="70" placeholder="{ object }" class="input-xxlarge">' . ($this->extra ? $this->extra->request : '') . '</textarea>
            </div>';


        $display.='
            <div class="col-md-12">
                <label>Header</label>
                <div id="divDinamico" class="col-md-12">
                    <div class="col-md-12">
                        <input type="text" placeholder="Nombre" name="extra[nombre]" value="' . ($this->extra ? $this->extra->nombre : '') . '" />

                        <input type="text" placeholder="Valor" name="extra[valor]" value="' . ($this->extra ? $this->extra->valor : '') . '" />

                        <button type="button"  id="btn-add" class="btn btn-default">
                            <span class="icon-plus"></span>
                        </button>
                    </div>
                </div>
            </div>';

        $display.= '<label>URL</label>';
        $display.='<input type="text" class="input-xxlarge" name="extra[url]" value="' . ($this->extra ? $this->extra->url : '') . '" />';


        return $display;
    }

    public function validateForm() {
        $CI = & get_instance();
        $CI->form_validation->set_rules('extra[url]', 'URL', 'required');
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
        //$rest_client = new GenericRest();
        log_message('info', 'Lllamando potr metodo', FALSE);
        //$result = $rest_client->call($url, $this->extra->tipoMetodo, $this->extra->request);
        $this->load->spark('restclient/2.2.1');
        // Load the library
        $this->load->library('rest');

        // Set config options (only 'server' is required to work)
        //$config = array('server'=> 'https://example.com/',
        //'api_key'			=> 'Setec_Astronomy'
        //'api_name'		=> 'X-API-KEY'
        //'http_user' 		=> 'username',
        //'http_pass' 		=> 'password',
        //'http_auth' 		=> 'basic',
        //'ssl_verify_peer' => TRUE,
        //'ssl_cainfo' 		=> '/certs/cert.pem'
        //);

        // Run some setup
        //$this->rest->initialize($config);

        $restCliente = new REST();

        // Pull the response
        log_message('info', 'Lllamando post', FALSE);
        $result = $restCliente->post($url, json_decode($this->extra->request));

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
        
    }

}
