<?php

class HsmConfiguracion extends Doctrine_Record {

    function setTableDefinition() {
        $this->hasColumn('id');
        $this->hasColumn('nombre');
        $this->hasColumn('cuenta_id');
        $this->hasColumn('entidad');
        $this->hasColumn('proposito');
    }

    function setUp() {
        parent::setUp();
        
        $this->hasOne('Cuenta', array(
            'local' => 'cuenta_id',
            'foreign' => 'id'
        ));

        $this->hasMany('Documento as Documentos', array(
            'local' => 'id',
            'foreign' => 'hsm_configuracion_id'
        ));
    }

    public function firmar($file_path,$entity, $rut, $expiration, $purpose, $otp=NULL) {
        $CI = & get_instance();
        $dataf = array(
            "entity" => $entity,
            "run" => $rut,
            "expiration"=> $expiration,
            "purpose" => $purpose
            );
        $data['token'] = JWT::encode($dataf,$CI->config->item('secret'));
        $url = $CI->config->item('url_api_firma');
        $data['api_token_key'] = $CI->config->item('api_token_key');
        $data['files'] = array(array(
            "content-type" => "application/pdf",
            "content" => base64_encode(file_get_contents($file_path)),
            "description" => "prueba 1",
            "checksum" => hash_file("sha256",$file_path)
        ));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER,array('Content-Type: application/json; charset=UTF-8'));
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($data));
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        $dataresult = json_decode($result);
        $session_token = $dataresult->session_token;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url."/".$session_token);
        if(is_null($otp)){
            curl_setopt($ch, CURLOPT_HTTPHEADER,array('Content-Type: application/json; charset=UTF-8'));
        }else{
            $headers = [
                'Content-Type: application/json; charset=utf-8',
                'OTP: '.$otp
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
        }
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        $dataresult = json_decode($result);
        $fileresult = $dataresult->files;
        $metadata = $dataresult->metadata;
        if($metadata->files_signed == 1){
            foreach ($fileresult as $archivo) {
                file_put_contents($file_path, base64_decode($archivo->content));
            }
            return true;
        }else{
            return false;
        }
    }

}
