<?php
require_once('accion.php');

class AccionDescargaDocumento extends Accion {

    public function displayForm() {


        $display = '<label>Variable en base64</label>';
        $display.='<div class="input-prepend">';
        $display.='<span class="add-on">@@</span>';
        $display.='<input type="text" name="extra[documento]" value="' . ($this->extra ? $this->extra->documento : '') . '" />';
        $display.='</div>';
        $display.='<label>URL de Descarga</label>';
        $display.='<input type="text" class="input-xxlarge" name="extra[url]" value="' . ($this->extra ? $this->extra->url : '') . '" />';

        return $display;
    }

    public function validateForm() {
        $CI = & get_instance();
        $CI->form_validation->set_rules('extra[documento]', 'Nombre documento', 'required');
        $CI->form_validation->set_rules('extra[url]', 'Url', 'required');
    }

    public function ejecutar(Etapa $etapa) {
        $regla=new Regla($this->extra->documento);
        $documento=$regla->getExpresionParaOutput($etapa->id);

        $regla=new Regla($this->extra->url);
        $url=$regla->getExpresionParaOutput($etapa->id);

        //print_r($url);
        //exit;
        
        $CI = & get_instance();
        /*
        //Generamos el file
        $file=new File();
        $file->tramite_id=$etapa->Tramite->id;
        $archivo=$documento;
        $archivo = trim($archivo);
        $archivo = str_replace(array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'), array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A'),$archivo);
        $archivo = str_replace(array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'), array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'),$archivo);
        $archivo = str_replace(array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'), array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'),$archivo);
        $archivo = str_replace(array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'), array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'), $archivo);
        $archivo = str_replace(array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'),array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'),$archivo);
        $archivo = str_replace(array('ñ', 'Ñ', 'ç', 'Ç'),array('n', 'N', 'c', 'C',), $archivo);
        $archivo = str_replace(array("\\","¨","º","-","~","#","@","|","!","\"","·","$","%","&","/","(", ")","?","'","¡","¿","[","^","`","]","+","}","{","¨","´",">","< ",";", ",",":"," "),'',$archivo);
        $valor = $archivo.'.pdf';
        $file->filename= $valor;
        $file->tipo='dato';
        $file->llave=strtolower(random_string('alnum', 12));
        $file->save();
        */

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 0); 
        curl_setopt($ch, CURLOPT_URL,$url); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        $file_content = curl_exec($ch);
        curl_close($ch);
        
        $filename = uniqid().rand(10, 1000).'.pdf';
        $ruta = 'uploads/tmp/'.$filename;
        $downloaded_file = fopen($ruta, 'w');
        fwrite($downloaded_file, $file_content);
        fclose($downloaded_file);

        $base64 = base64_encode(file_get_contents($ruta));
        if(file_exists($ruta))
            unlink($ruta);

        $dato = Doctrine::getTable('DatoSeguimiento')->findOneByNombreAndEtapaId($this->extra->documento,$etapa->id);
        if (!$dato)
            $dato = new DatoSeguimiento();
        $dato->nombre = $this->extra->documento;
        $dato->valor = $base64;
        $dato->etapa_id = $etapa->id;
        $dato->save();
        
    }

}
