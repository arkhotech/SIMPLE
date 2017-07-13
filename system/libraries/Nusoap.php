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
                $wsdl = $api_url;
                $client = new nusoap_client($wsdl, true);
                $result = $client->call($service, $params);

                $err = $client->getError();
                if ($err) {
                    throw new Exception($err);
                }else{
                    if ($client->fault) {
                        throw new Exception($client->fault);
                    } else {
                        $err = $client->getError();
                        if ($err) {
                            throw new Exception($err);
                        }
                    }
                }

                return $result;
            }catch (Exception $err){
                throw new Exception($err);
            }
        }
    }

}