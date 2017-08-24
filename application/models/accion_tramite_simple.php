<?php
require_once('accion.php');

class AccionTramiteSimple extends Accion {

    public function displaySecurityForm($proceso_id) {

        log_message("INFO", "En accion trámite", FALSE);

        $tramites_disponibles = Doctrine::getTable('Proceso')->findProcesosExpuestos("");

        $data = Doctrine::getTable('Proceso')->find($proceso_id);
        $conf_seguridad = $data->Admseguridad;
        $display ='
                <label>Trámites disponibles</label>
                <select id="tramiteSel" name="extra[tramiteSel]">
                    <option value="">Seleccione...</option>';

                foreach ($tramites_disponibles as $tramite) {
                    $display.='<option value="'.$tramite["id"].'">'.$tramite["nombre"].'</option>';
                }

        $display.='</select>';

        $display.= '
            <p>
                Si desea esperar la respuesta en alguna tarea con Callback, favor seleccionela del siguiente listado.
            </p>
        ';

        $display.='
                <label>Tareas con Callback disponibles</label>
                <select id="callbackSel" name="extra[callbackSel]">';
        $display.='</select>';

        $display.='
            <div class="col-md-12" id="divObject">
                <label>Request</label>
                <textarea id="request" name="extra[request]" rows="7" cols="70" placeholder="{ form }" class="input-xxlarge">' . ($this->extra ? $this->extra->request : '') . '</textarea>
                <br />
                <span id="resultRequest" class="spanError"></span>
                <br /><br />
            </div>';


        return $display;
    }

    public function validateForm() {
        $CI = & get_instance();
        $CI->form_validation->set_rules('extra[tramiteSel]', 'Trámite', 'required');
    }

    public function ejecutar(Etapa $etapa) {

        $CI = & get_instance();
        // Se declara el tipo de seguridad segun sea el caso
        if(isset($this->extra->request)){
            $r=new Regla($this->extra->request);
            $request=$r->getExpresionParaOutput($etapa->id);
        }

        //obtenemos el Headers si lo hay
        /*if(isset($this->extra->header)){
            $r=new Regla($this->extra->header);
            $header=$r->getExpresionParaOutput($etapa->id);
            $headers = json_decode($header);
            foreach ($headers as $name => $value) {
                $CI->rest->header($name.": ".$value);
            }
        }*/
        try{

            $integracion = new FormNormalizer();
            $info_inicio = $integracion->iniciarProceso($this->extra->tramiteSel, 0, $request);

            $response = "{\"respuesta_inicio\": ".$info_inicio."}";

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
            log_message("ERROR", $e->getCode().": ".$e->getMessage(), true);
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