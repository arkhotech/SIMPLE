<?php

/**
 * Created by PhpStorm.
 * User: jperezdearce
 * Date: 12-07-17
 * Time: 4:13 PM
 */
class Nusoap{

    function Nusoap(){
        require_once('nusoap-0.9.5/lib/nusoap'.EXT);
    }

    function soaprequest($api_url, $service, $params){
        log_message('info', 'En NuSoap request', FALSE);
        if ($api_url != '' && $service != '' && count($params) > 0) {
            try{
                log_message('info', 'wsdl: '.$api_url, FALSE);
                log_message('info', 'service: '.$service, FALSE);
                print_r($params);
                $wsdl = $api_url;
                log_message('info', 'Generando cliente', FALSE);
                $client = new nusoap_client($wsdl, true);
                log_message('info', 'Ejecutando operacion', FALSE);
                $result = $client->call($service, $params);

                log_message('info', 'Validando si hay errores', FALSE);
                $err = $client->getError();
                if ($err) {
                    log_message('info', 'Con error: '.$err, FALSE);
                    throw new Exception($err);
                }else{
                    if ($client->fault) {
                        log_message('info', 'Con error: '.$client->fault, FALSE);
                        throw new Exception($client->fault);
                    }
                }

                log_message('info', 'Sin errores', FALSE);
                return $result;
            }catch (Exception $err){
                throw new Exception($err);
            }
        }
    }

}