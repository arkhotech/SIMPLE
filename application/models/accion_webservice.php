<?php
require_once('accion.php');

class AccionWebservice extends Accion {

    public function displayForm() {
        $display = '<p>Esta accion consultara via REST la siguiente URL. Los resultados, seran almacenados como variables.</p>';
        $display.='<p>Los resultados esperados deben venir en formato JSON siguiendo este formato:</p>';
        $display.='<pre>
{
    "variable1": "valor1",
    "variable2": "valor2",
    ...
}</pre>';
        $display.= '<label>URL</label>';
        $display.='<input type="text" class="input-xxlarge" name="extra[url]" value="' . ($this->extra ? $this->extra->url : '') . '" />';


        return $display;
    }

    public function validateForm() {
        $CI = & get_instance();
        $CI->form_validation->set_rules('extra[url]', 'URL', 'required');
    }

    public function ejecutar(Etapa $etapa) {
        log_message('info', 'Ejecutar webservice', FALSE);
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
        log_message('info', 'URL: '.$url, FALSE);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
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
