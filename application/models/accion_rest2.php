<?php
require_once('accion.php');
  
class AccionRest2 extends Accion {

    public function displayForm() {
        $display = '<p>Esta accion consultara via REST la siguiente URL. Los resultados, seran almacenados como variables.</p>';
        $display.='<p>Los resultados esperados deben venir en formato JSON siguiendo este formato:</p>';
        
        $display.='
            <script type="text/javascript">
            function CambioSelect(value) {
                switch(parseInt(value)){                
                    case 1: case 3:
                        $("#divObject").show();
                        break;
                    case 2: case 4:
                    $("#divObject").hide();
                        break;
                    default:
                    break;
                }
                 
            }
            </script>
            <label>Método</label>
            <select name="tipo" onchange="CambioSelect(value)">
                <option value="">Selecione..</option>
                <option value="1">POST</option>
                <option value="2">GET</option>
                <option value="3">PUT</option>
                <option value="4">DELETE</option>
            </select>';

        $display.='
            <div id="divObject" style="display:none;">
                <label>Request</label>
                <textarea name="extra[object]" rows="7" cols="70" placeholder="{ Object }" class="input-xxlarge">' . ($this->extra ? $this->extra->object : '') . '</textarea>
            </div>';

        $display.='<label>Headers</label>';
        
        $display.='
            <div class="col-md-12">
                <input type="text" placeholder="Nombre" name="extra[nombre]" value="' . ($this->extra ? $this->extra->nombre : '') . '" />

                <input type="text" placeholder="Valor" name="extra[valor]" value="' . ($this->extra ? $this->extra->valor : '') . '" />

                <button type="button" class="btn btn-default">
                    <span class="icon-plus"></span>
                </button>
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
        
        //Hacemos encoding a la url
        $url=preg_replace_callback('/([\?&][^=]+=)([^&]+)/', function($matches){
            $key=$matches[1];
            $value=$matches[2];
            return $key.urlencode($value);
        },
        $url);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        
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
