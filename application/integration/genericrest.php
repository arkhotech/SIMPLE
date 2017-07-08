<?php

class GenericRest{

    function __construct(){

    }

    public function call($URL, $metodo, $data=null) {

        log_message('info', 'LLamando a servicio por: '.$metodo, FALSE);
        // Load the rest client spark
        //$this->load->spark('restclient/2.2.1');
        // Load the library
        //$this->load->library('rest');

        // Set config options (only 'server' is required to work)
        //$config = array('server'=> 'https://example.com/',
            //'api_key'			=> 'Setec_Astronomy'
            //'api_name'		=> 'X-API-KEY'
            //'http_user' 		=> 'username',
            //'http_pass' 		=> 'password',
            //'http_auth' 		=> 'basic',
            //'ssl_verify_peer' => TRUE,
            //'ssl_cainfo' 		=> '/certs/cert.pem'
        //);

        // Run some setup
        //$this->rest->initialize($config);

        $restCliente = new REST();

        // Pull the response
        if($metodo == "GET"){
            $result = $restCliente->get($URL);
            log_message('info', 'Ejecutar webservice GET: '.$result, FALSE);
        }else if($metodo == "POST"){
            $result = $restCliente->post($URL, $data);
            log_message('info', 'Ejecutar webservice GET: '.$result, FALSE);
        }

    }

}

?>