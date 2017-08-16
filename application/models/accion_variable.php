<?php
require_once('accion.php');

class AccionVariable extends Accion {

    public function displayForm() {
        $display = '<label>Variable</label>';
        $display.='<div class="input-prepend">';
        $display.='<span class="add-on">@@</span>';
        $display.='<input type="text" name="extra[variable]" value="' . ($this->extra ? $this->extra->variable : '') . '" />';
        $display.='</div>';
        $display.='<label>Expresión a evaluar</label>';
        $display.='<textarea name="extra[expresion]" class="input-xxlarge">' . ($this->extra ? $this->extra->expresion : '') . '</textarea>';
        return $display;
    }

    public function validateForm() {
        $CI = & get_instance();
        $CI->form_validation->set_rules('extra[variable]', 'Variable', 'required');
        $CI->form_validation->set_rules('extra[expresion]', 'Expresión a evaluar', 'required');
    }

    public function ejecutar(Etapa $etapa) {
        $regla=new Regla($this->extra->expresion);
        $filewords = array("file_get_contents", "file_put_contents");
        $matchfound = preg_match_all("/\b(".implode($filewords,"|").")\b/i", $this->extra->expresion, $matches);
        $ev = $matchfound? TRUE : FALSE;
        $valor=$regla->evaluar($etapa->id,$ev);
        
        $dato = Doctrine::getTable('DatoSeguimiento')->findOneByNombreAndEtapaId($this->extra->variable,$etapa->id);
        if (!$dato)
            $dato = new DatoSeguimiento();
        $dato->nombre = $this->extra->variable;
        $dato->valor = $valor;
        $dato->etapa_id = $etapa->id;
        $dato->save();
    }

}
