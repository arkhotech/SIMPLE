<?php
require_once('accion.php');

class AccionNotificaciones extends Accion {

    public function displaySuscriptorForm($proceso_id) {

        $proceso = Doctrine::getTable('Proceso')->find($proceso_id);
        $suscriptores = $proceso->Suscriptores;

        $display = '
            <p>
                Genera una accion de notificación a los suscriptores seleccionados que esten registrados en este proceso.
            </p>
        ';


        $display.= '<div class="campo control-group">';
        $display.= '<label class="control-label">Suscriptores:</label>';
        $display.= '<div class="controls">';

            foreach ($suscriptores as $suscriptor) {
                $nombre_checkbox = '<a target="_blank" href="'.site_url('backend/suscriptores/editar/'.$suscriptor->id).'">'.$suscriptor->institucion.'</a>';
                if(isset($this->extra->suscriptorSel) && count($this->extra->suscriptorSel) > 0){
                    if(in_array($suscriptor->id, $this->extra->suscriptorSel)){
                        $display .= '<label class="checkbox"><input type="checkbox" name="extra[suscriptorSel][]" value="'.$suscriptor->id.'" checked=true />'.$nombre_checkbox.'</label>';
                    }else{
                        $display .= '<label class="checkbox"><input type="checkbox" class="SelectAll" name="extra[suscriptorSel][]" value="'.$suscriptor->id.'"/>'.$nombre_checkbox.'</label>';
                    }
                }else{
                    $display .= '<label class="checkbox"><input type="checkbox" class="SelectAll" name="extra[suscriptorSel][]" value="'.$suscriptor->id.'"/>'.$nombre_checkbox.'</label>';
                }
            }
        $display.= '</div></div>';

        return $display;
    }

    public function validateForm() {
        $CI = & get_instance();
        $CI->form_validation->set_rules('extra[suscriptorSel]', 'Suscriptores', 'required');
    }

    public function ejecutar(Etapa $etapa) {

        log_message("INFO", "Notificando a suscriptores", FALSE);

        try{
            $proceso = Doctrine::getTable('Proceso')->find($etapa['Tarea']['proceso_id']);
            $suscriptores = $proceso->Suscriptores;

            if(isset($this->extra->suscriptorSel) && count($this->extra->suscriptorSel) > 0){
                foreach ($this->extra->suscriptorSel as $suscriptor_id) {
                    log_message("INFO", "Notificando a suscriptor id: " . $suscriptor_id, FALSE);
                    try {
                        $suscriptor = Doctrine::getTable('Suscriptor')->find($suscriptor_id);
                        log_message("INFO", "Suscriptor institucion: " . $suscriptor->institucion, FALSE);
                        log_message("INFO", "Suscriptor request: " . $suscriptor->extra->request, FALSE);

                        $idSeguridad = $suscriptor->extra->idSeguridad;

                        $webhook_url = str_replace('\/', '/', $suscriptor->extra->webhook);
                        $base = explode("/", $webhook_url);
                        $server = $base[0] . '//' . $base[2];
                        $server = str_replace('"', '', $server);
                        $uri = '';
                        for ($i = 3; $i < count($base); $i++) {
                            if ($i == 3)
                                $uri .= $base[$i];
                            else
                                $uri .= '/' . $base[$i];
                        }
                        $uri = str_replace('"', '', $uri);

                        $campo = new Campo();
                        $data = $campo->obtenerResultados($etapa, $etapa['Tarea']['proceso_id']);
                        $output['idInstancia'] = $etapa['tramite_id'];
                        $output['idTarea'] = $etapa['Tarea']['id'];
                        $output['data'] = $data;

                        $request = json_encode($output);

                        $request_suscriptor = $suscriptor->extra->request;
                        if (isset($request_suscriptor) && strlen($request_suscriptor) > 0) {
                            if (strpos($request_suscriptor, '@@output') !== false) {
                                $request = str_replace('"', '\"', $request);
                                $request = str_replace('@@output', $request, $request_suscriptor);
                            }
                        }


                        $seguridad = new SeguridadIntegracion();
                        $config = $seguridad->getConfigRest($idSeguridad, $server, 30);

                        log_message("INFO", "Llamando a suscriptor URL: " . $uri, FALSE);

                        $CI = &get_instance();

                        $CI->rest->initialize($config);
                        $result = $CI->rest->post($uri, $request, 'json');

                        //Se obtiene la codigo de la cabecera HTTP
                        $debug = $CI->rest->debug();
                        $parseInt = intval($debug['info']['http_code']);
                        if ($parseInt < 200 || $parseInt >= 300) {
                            // Ocurio un error en el server del Callback ## Error en el servidor externo ##
                            // Se guarda en Auditoria el error
                            $response['code'] = $debug['info']['http_code'];
                            $response['des_code'] = $debug['response_string'];
                            $response = json_encode($response);
                            $operacion = 'Error Notificando a suscriptor ' . $suscriptor->institucion;

                            AuditoriaOperaciones::registrarAuditoria($proceso->nombre,
                                "Error Notificando a suscriptor " . $suscriptor->institucion, $response, array());

                        } else {
                            // Caso con errores
                            if(isset($result)){
                                $result2 = get_object_vars($result);
                            }else{
                                $result2 = "SUCCESS";
                            }
                            $response = $result2;
                            AuditoriaOperaciones::registrarAuditoria($proceso->nombre,
                                "Suscriptor " . $suscriptor->institucion . " notificado exitosamente", $response, array());
                        }
                    } catch (Exception $e) {
                        log_message('Error Notificando a suscriptor ' . $suscriptor->institucion, $e->getMessage());
                    }
                }
            }

            log_message("INFO", "Suscriptores notificados", FALSE);

        }catch (Exception $e){
            log_message('Error general en notificaciones a suscriptores ',$e->getMessage());
            AuditoriaOperaciones::registrarAuditoria($proceso->nombre, "Ejecutar PUSH", $e->getMessage(), array());
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