<?php
require_once('accion.php');

class AccionContinuarTramite extends Accion {

    public function displaySecurityForm($proceso_id) {

        log_message("INFO", "En accion continuar trámite", FALSE);

        $tramites_disponibles = Doctrine::getTable('Proceso')->findProcesosExpuestos("");

        $tareas_proceso = Doctrine::getTable('Proceso')->findTareasProceso($proceso_id);

        $data = Doctrine::getTable('Proceso')->find($proceso_id);
        $conf_seguridad = $data->Admseguridad;
        /*$display ='
                <label>Trámites disponibles</label>
                <select id="tramiteSel" name="extra[tramiteSel]">
                    <option value="">Seleccione...</option>';

                foreach ($tramites_disponibles as $tramite) {
                    $display.='<option value="'.$tramite["id"].'">'.$tramite["nombre"].'</option>';
                }

        $display.='</select>';*/

        /*$display.='
                <label>Tareas disponibles del trámite para retorno</label>
                <select id="tareaRetornoSel" name="extra[tareaRetornoSel]">';
        $display.='</select>';*/

        /*$display.='
                <label>Tarea desde la cual desea continuar el proceso</label>
                <select id="tareaContinuarSel" name="extra[tareaContinuarSel]">
                    <option value="">Seleccione...</option>';

                foreach ($tareas_proceso as $tarea) {
                    $display.='<option value="'.$tarea["id"].'">'.$tarea["nombre"].'</option>';
                }
        $display.='</select>';*/

        $display ='
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
        //$CI->form_validation->set_rules('extra[tramiteSel]', 'Trámite', 'required');
    }

    public function ejecutar(Etapa $etapa) {

        log_message("INFO", "En ejecución continuar trámite", FALSE);

        $CI = & get_instance();
        // Se declara el tipo de seguridad segun sea el caso
        if(isset($this->extra->request)){
            $r=new Regla($this->extra->request);
            $request=$r->getExpresionParaOutput($etapa->id);
        }

        log_message("INFO", "Request: ".$request, FALSE);
        //log_message("INFO", "Id trámite: ".$this->extra->tramiteSel, FALSE);

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

            log_message("INFO", "Continuar desde etapa_id: ".$etapa->id, FALSE);

            $tramite_id=Doctrine::getTable('DatoSeguimiento')->findByNombreHastaEtapa("tramite_retorno",$etapa->id);
            $tarea_id=Doctrine::getTable('DatoSeguimiento')->findByNombreHastaEtapa("tarea_retorno",$etapa->id);

            log_message("INFO", "Continuar tramite_id: ".$tramite_id->valor, FALSE);
            log_message("INFO", "Continuar tarea_id: ".$tarea_id->valor, FALSE);

            $etapa_continuar = new Etapa();
            $etapa_continuar = $etapa_continuar->getEtapaPorTareaId($tarea_id->valor, $tramite_id->valor);
            log_message("INFO", "id_etapa a continuar: ".$etapa->id);
            if(strlen($etapa_continuar->id) != 0){ //Existe etapa para continuar el proceso
                $integracion = new IntegracionMediator();
                $info_continuar = $integracion->continuarProceso($tramite_id->valor, $etapa_continuar->id, "0", $request);

                $response_continuar = "{\"respuesta_continuar\": ".$info_continuar."}";

                log_message("INFO", "Response: ".$response_continuar, FALSE);

                $response["respuesta_continuar"]=$response_continuar;

            }else{
                //Se encola continuar proceso hasta que etapa se cree
                $cola = new ColaContinuarTramite();
                $cola->tramite_id = $tramite_id->valor;
                $cola->tarea_id = $tarea_id->valor;
                $cola->request = $request;
                $cola->procesado = 0;
                log_message("INFO", "Se encola, ya que aun no existe etapa cola: ".$cola, FALSE);
                $cola->save();
                $response["respuesta_continuar"]="Se encola continuación trámite id:".$tramite_id->valor." en tarea id: ".$tarea_id->valor;
            }

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
            $dato=Doctrine::getTable('DatoSeguimiento')->findOneByNombreAndEtapaId("error_continuar_simple",$etapa->id);
            if(!$dato)
                $dato=new DatoSeguimiento();
            $dato->nombre="error_continuar_simple";
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