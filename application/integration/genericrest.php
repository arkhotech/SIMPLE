<?php

class GenericRest extends CI_Controller {

    public function call($URL, $metodo, $data=null) {

        // Load the rest client spark
        $this->load->spark('restclient/2.2.1');
        // Load the library
        $this->load->library('rest');

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

        // Pull the response
        if($metodo == "GET"){
            $result = $this->rest->get($URL);
            log_message('info', 'Ejecutar webservice GET: '.$result, FALSE);
        }

    }

}

?>