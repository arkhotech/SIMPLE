<?php
require_once('accion.php');

class AccionIniciarTramite extends Accion {

    public function displaySecurityForm($proceso_id) {

        log_message("INFO", "En accion trámite", FALSE);

        $tramites_disponibles = Doctrine::getTable('Proceso')->findProcesosExpuestos("");

        $tareas_proceso = Doctrine::getTable('Proceso')->findTareasProceso($proceso_id);

        $data = Doctrine::getTable('Proceso')->find($proceso_id);
        $conf_seguridad = $data->Admseguridad;
        $display ='
                <label>Trámites disponibles</label>
                <select id="tramiteSel" name="extra[tramiteSel]">
                    <option value="">Seleccione...</option>';

                foreach ($tramites_disponibles as $tramite) {
                    if ($this->extra->tramiteSel && $this->extra->tramiteSel == $tramite["id"]){
                        $display.='<option value="'.$tramite["id"].'" selected>'.$tramite["nombre"].'</option>';
                    }else{
                        $display.='<option value="'.$tramite["id"].'">'.$tramite["nombre"].'</option>';
                    }
                }

        $display.='</select>';

        /*$display.='
                <label>Tareas disponibles del trámite para retorno</label>
                <select id="tareaRetornoSel" name="extra[tareaRetornoSel]">';
        $display.='</select>';*/

        $display.='
                <label>Tarea desde la cual desea continuar el proceso</label>
                <select id="tareaRetornoSel" name="extra[tareaRetornoSel]">
                    <option value="">Seleccione...</option>';

                foreach ($tareas_proceso as $tarea) {
                    if ($this->extra->tareaRetornoSel && $this->extra->tareaRetornoSel == $tarea["id"]){
                        $display.='<option value="'.$tarea["id"].'" selected>'.$tarea["nombre"].'</option>';
                    }else{
                        $display.='<option value="'.$tarea["id"].'">'.$tarea["nombre"].'</option>';
                    }
                }
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

        log_message("INFO", "En ejecución trámite", FALSE);

        $CI = & get_instance();
        // Se declara el tipo de seguridad segun sea el caso
        if(isset($this->extra->request)){
            $r=new Regla($this->extra->request);
            $request=$r->getExpresionParaOutput($etapa->id);
        }

        log_message("INFO", "Request: ".$request, FALSE);
        log_message("INFO", "Id trámite: ".$this->extra->tramiteSel, FALSE);
        log_message("INFO", "Id tarea retorno: ".$this->extra->tareaRetornoSel, FALSE);
        log_message("INFO", "Id tramite desde etapa: ".$etapa->tramite_id, FALSE);

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
            $info_inicio = $integracion->iniciarProceso($this->extra->tramiteSel, $etapa->tramite_id, $this->extra->tareaRetornoSel, $request);

            $response_inicio = "{\"respuesta_inicio\": ".$info_inicio."}";

            log_message("INFO", "Response: ".$response_inicio, FALSE);

            $response["respuesta_inicio"]=$response_inicio;

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
            $dato=Doctrine::getTable('DatoSeguimiento')->findOneByNombreAndEtapaId("error_iniciar_simple",$etapa->id);
            if(!$dato)
                $dato=new DatoSeguimiento();
            $dato->nombre="error_iniciar_simple";
            $dato->valor=$e->getCode().": ".$e->getMessage();
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

}