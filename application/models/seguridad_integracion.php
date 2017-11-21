<?php

/**
 * Created by PhpStorm.
 * User: jperezdearce
 * Date: 22-09-17
 * Time: 9:51 AM
 */
class SeguridadIntegracion{

    public function getConfigRest($id_seguridad, $server, $timeout){

        $tipo_seguridad = "none";
        if(isset($id_seguridad) && strlen($id_seguridad) > 0 && $id_seguridad > 0){
            $seguridad = Doctrine::getTable('Seguridad')->find($id_seguridad);
            $tipo_seguridad = $seguridad->extra->tipoSeguridad;
            $user = $seguridad->extra->user;
            $pass = $seguridad->extra->pass;
            $api_key = $seguridad->extra->apikey;
            $name_key = $seguridad->extra->namekey;
            $url_auth = $seguridad->extra->url_auth;
            $uri_auth = $seguridad->extra->uri_auth;
            $request_seg = $seguridad->extra->request_seg;
        }

        $CI = & get_instance();
        switch ($tipo_seguridad) {
            case "HTTP_BASIC":
                //Seguridad basic
                $config = array(
                    'timeout'         => $timeout,
                    'server'          => $server,
                    'http_user'       => $user,
                    'http_pass'       => $pass,
                    'http_auth'       => 'Basic'
                );
                break;
            case "API_KEY":
                //Seguriad api key
                $config = array(
                    'timeout'         => $timeout,
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
                        'timeout'         => $timeout,
                        'server'          => $server,
                        'api_key'         => $result->token_type.' '.$result->access_token,
                        'api_name'        => 'Authorization'
                    );
                }
                break;
            default:
                //SIN SEGURIDAD
                $config = array(
                    'timeout'         => $timeout,
                    'server'          => $server
                );
                break;
        }

        return $config;

    }

    public function setSecuritySoap($client, $idSeguridad){

        $data = Doctrine::getTable('Seguridad')->find($idSeguridad);
        $tipoSeguridad=$data->extra->tipoSeguridad;
        $user = $data->extra->user;
        $pass = $data->extra->pass;
        $ApiKey = $data->extra->apikey;

        //Se instancia el tipo de seguridad segun sea el caso
        switch ($tipoSeguridad) {
            case "HTTP_BASIC":
                //SEGURIDAD BASIC
                $client->setCredentials($user, $pass, 'basic');
                break;
            case "API_KEY":
                //SEGURIDAD API KEY
                $header =
                    "<SECINFO>
                  <KEY>".$this->extra->apikey."</KEY>
                </SECINFO>";
                $client->setHeaders($header);
                break;
            case "OAUTH2":
                //SEGURIDAD OAUTH2
                $client->setCredentials($user, $pass, 'basic');
                break;
            default:
                //NO TIENE SEGURIDAD
                break;
        }

        return $client;

    }
}