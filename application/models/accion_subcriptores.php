<?php
require_once('accion.php');

class AccionSubcriptores extends Accion {

    public function displayForm() {

        $display = '
            <p>
                Genera una accion de notificación al listado de suscriptores que esten registrados en este proceso.
            </p>
        ';

        return $display;
    }

    public function validateForm() {
    }

    public function ejecutar(Etapa $etapa) {

        log_message("INFO", "Notificando a suscriptor", FALSE);

        try{
            $proceso = Doctrine::getTable('Proceso')->find($etapa['Tarea']['proceso_id']);
            $suscriptores = $proceso->Suscriptores;

            foreach ($suscriptores as $suscriptor){
                try{
                    log_message("INFO", "Suscriptor institucion: ".$suscriptor->institucion, FALSE);
                    log_message("INFO", "Suscriptor idSeguridad: ".$suscriptor->extra->idSeguridad, FALSE);
                    log_message("INFO", "Suscriptor request: ".$suscriptor->extra->request, FALSE);

                    $idSeguridad = $suscriptor->extra->idSeguridad;

                    $webhook_url = str_replace('\/', '/', $suscriptor->extra->webhook);
                    $base = explode("/", $webhook_url);
                    $server = $base[0].'//'.$base[2];
                    $server = str_replace('"', '', $server);
                    $uri ='';
                    for ($i = 3; $i < count($base); $i++){
                        $uri .='/'.$base[$i];
                    }
                    $caracter='/';
                    $l = substr($uri, 0, 1);
                    if($caracter===$l){
                        $uri = substr($uri, 1);
                    }
                    $uri = str_replace('"', '', $uri);

                    $campo = new Campo();
                    $data=$campo->obtenerResultados($etapa,$etapa['Tarea']['proceso_id']);
                    $output['idInstancia']=$etapa['tramite_id'];
                    $output['idTarea']=$etapa['Tarea']['id'];
                    $output['data']=$data;

                    log_message('info',$this->varDump($output));

                    $request=json_encode($output);

                    $request_suscriptor = $suscriptor->extra->request;
                    log_message("INFO", "Request desde suscriptor: ".$request_suscriptor, FALSE);
                    if(isset($request_suscriptor) && strlen($request_suscriptor) > 0){
                        if (strpos($request_suscriptor, '@@output') !== false) {
                            log_message("INFO", "Reemplazando output", FALSE);
                            $request = str_replace('"', '\"', $request);
                            $request = str_replace('@@output', $request, $request_suscriptor);
                            log_message("INFO", "Request con reemplazo: ".$request, FALSE);
                        }
                    }


                    log_message('info',$this->varDump($output));
                    log_message('info',"Request generado: ".$this->varDump($request));

                    $config = $this->getConfigRest($idSeguridad, $server);

                    log_message("INFO", "Llamando a suscriptor URL: ".$uri, FALSE);

                    $CI = & get_instance();

                    log_message('info',"Config: ".$this->varDump($config));

                    $CI->rest->initialize($config);
                    $result = $CI->rest->post($uri, $request, 'json');

                    //Se obtiene la codigo de la cabecera HTTP
                    $debug = $CI->rest->debug();
                    log_message("INFO", "Llamando a suscriptor debug: ".$this->varDump($debug), FALSE);
                    $parseInt=intval($debug['info']['http_code']);
                    if ($parseInt<200 || $parseInt>=300){
                        // Ocurio un error en el server del Callback ## Error en el servidor externo ##
                        // Se guarda en Auditoria el error
                        $response['code']=$debug['info']['http_code'];
                        $response['des_code']=$debug['response_string'];
                        $response=json_encode($response);
                        $operacion = 'Error Notificando a suscriptor '.$suscriptor->institucion;

                        AuditoriaOperaciones::registrarAuditoria($proceso->nombre,
                            "Error Notificando a suscriptor ".$suscriptor->institucion, $response, array());

                    }else{
                        // Caso OK, sin errores
                        $result2 = get_object_vars($result);
                        $response=$result2;
                        AuditoriaOperaciones::registrarAuditoria($proceso->nombre,
                            "Suscriptor ".$suscriptor->institucion." notificado exitosamente", $response, array());
                    }
                }catch (Exception $e){
                    log_message('Error Notificando a suscriptor '.$suscriptor->institucion,$e->getMessage());
                }
            }

        }catch (Exception $e){
            log_message('Error general en notificaciones a suscriptores ',$e->getMessage());
            AuditoriaOperaciones::registrarAuditoria($proceso->nombre, "Ejecutar PUSH", $e->getMessage(), array());
        }
    }

    private function getConfigRest($id_seguridad, $server){

        $seguridad = Doctrine::getTable('Seguridad')->find($id_seguridad);
        $tipo_seguridad = $seguridad->extra->tipoSeguridad;
        $user = $seguridad->extra->user;
        $pass = $seguridad->extra->pass;
        $api_key = $seguridad->extra->apikey;
        $name_key = $seguridad->extra->namekey;
        $url_auth = $seguridad->extra->url_auth;
        $uri_auth = $seguridad->extra->uri_auth;
        $request_seg = $seguridad->extra->request_seg;

        $CI = & get_instance();
        switch ($tipo_seguridad) {
            case "HTTP_BASIC":
                //Seguridad basic
                $config = array(
                    'server'          => $server,
                    'http_user'       => $user,
                    'http_pass'       => $pass,
                    'http_auth'       => 'basic'
                );
                break;
            case "API_KEY":
                //Seguriad api key
                $config = array(
                    'server'          => $server,
                    'api_key'         => $api_key,
                    'api_name'        => $name_key
                );
                break;
            case "OAUTH2":
                //SEGURIDAD OAUTH2
                $config_seg = array(
                    'server'          => $url_auth
                );
                $CI->rest->initialize($config_seg);
                $result = $CI->rest->post($uri_auth, $request_seg, 'json');
                //Se obtiene la codigo de la cabecera HTTP
                $debug_seg = $CI->rest->debug();
                $response_seg= intval($debug_seg['info']['http_code']);
                if($response_seg >= 200 && $response_seg < 300){
                    $config = array(
                        'server'          => $server,
                        'api_key'         => $result->token_type.' '.$result->access_token,
                        'api_name'        => 'Authorization'
                    );
                }
                break;
            default:
                //SIN SEGURIDAD
                $config = array(
                    'server'          => $server
                );
                break;
        }

        return $config;

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