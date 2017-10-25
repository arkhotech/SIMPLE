<?php
require_once('accion.php');

class AccionIniciarTramite extends Accion {

    public function displaySecurityForm($proceso_id) {

        log_message("INFO", "En accion trámite", FALSE);

        $tareas_proceso = Doctrine::getTable('Proceso')->findTareasProceso($proceso_id);

        $proceso = Doctrine::getTable('Proceso')->find($proceso_id);

        if (isset($this->extra->cuentaSel)){
            $tramites_disponibles = Doctrine::getTable('Proceso')->findProcesosExpuestos($this->extra->cuentaSel);
            $cuenta = Doctrine::getTable('Cuenta')->find($this->extra->cuentaSel);
        }else{
            $tramites_disponibles = Doctrine::getTable('Proceso')->findProcesosExpuestos($proceso->cuenta_id);
            $cuenta = Doctrine::getTable('Cuenta')->find($proceso->cuenta_id);
        }

        $display ='
                <label>Cuentas</label>
                <select id="cuentaSel" name="extra[cuentaSel]">
                    <option value="'.$cuenta->id.'">'.$cuenta->nombre.'</option>';

        $proceso_cuenta = new ProcesoCuenta();
        $cuentas_con_permiso = $proceso_cuenta->findCuentasAcceso($cuenta->id);
        if(isset($cuentas_con_permiso) && count($cuentas_con_permiso) > 0){
            foreach ($cuentas_con_permiso as $cuentas_permiso) {
                if (isset($this->extra->cuentaSel) && $this->extra->cuentaSel == $cuentas_permiso["id"]){
                    $display.='<option value="'.$cuentas_permiso["id"].'" selected>'.$cuentas_permiso["nombre"].'</option>';
                }else{
                    $display.='<option value="'.$cuentas_permiso["id"].'">'.$cuentas_permiso["nombre"].'</option>';
                }
            }
        }

        $display.='</select>';

        $display.='<input type="hidden" name="cuenta_actual_id" id="cuenta_actual_id" value="'.$cuenta->id.'" />';
        $display.='<input type="hidden" name="cuenta_actual_nombre" id="cuenta_actual_nombre" value="'.$cuenta->nombre.'" />';

        $display.='
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
        $CI->form_validation->set_rules('extra[tareaRetornoSel]', 'Tarea retorno', 'required');
        $CI->form_validation->set_rules('extra[request]', 'Request', 'required');
    }

    public function ejecutar(Etapa $etapa) {

        log_message("INFO", "En ejecución accion iniciando trámite simple", FALSE);

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

            $integracion = new IntegracionMediator();

            //TODO al parecer falta indicar tarea de inicio
            $info_inicio = $integracion->iniciarProceso($this->extra->tramiteSel, $etapa->id, $request, $etapa->tramite_id, $this->extra->tareaRetornoSel);

            $response["respuesta_inicio"]=$info_inicio;

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

    private function registrarRetorno($tramite_id, $tramite_retorno, $retorno_id){

        $tramite = Doctrine::getTable('Tramite')->find($tramite_id);
        $etapa_id = $tramite->getEtapasActuales()->get(0)->id;

        $dato = new DatoSeguimiento();
        $dato->nombre = "tramite_retorno";
        $dato->valor = $tramite_retorno;
        $dato->etapa_id = $etapa_id;
        $dato->save();

        $dato = new DatoSeguimiento();
        $dato->nombre = "tarea_retorno";
        $dato->valor = $retorno_id;
        $dato->etapa_id = $etapa_id;
        $dato->save();
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