<?php
require_once('accion.php');
   
class AccionSoap extends Accion {

    public function displaySecurityForm($proceso_id) {

        log_message('info', 'displaySecurityForm id proceso: '.$proceso_id, FALSE);

        $data = Doctrine::getTable('Proceso')->find($proceso_id);

        log_message('info', 'Obtiene proceso desde bd: '.$data->id, FALSE);

        $conf_seguridad = $data->Admseguridad;

        $display = '
            <p>
                Esta accion consultara via SOAP la siguiente URL. Los resultados, seran almacenados como variables.
            </p>
        ';

        $display.='
                <div class="col-md-12">
                        <label>WSDL</label>
                        <input type="text" class="input-xxlarge AlignText" id="urlsoap" name="extra[wsdl]" value="' . ($this->extra ? $this->extra->wsdl : '') . '" />
                        <a class="btn btn-default" id="btn-consultar" ><i class="icon-search icon"></i> Consultar</a>
                        <a class="btn btn-default" href="#modalImportarWsdl" data-toggle="modal" ><i class="icon-upload icon"></i> Importar</a>
                </div>';

        $display.='
                <div id="divMetodos" class="col-md-12">
                    <label>Métodos</label>
                    <select id="operacion" name="extra[operacion]">';
        if ($this->extra->operacion){
            $display.='<option value="'.($this->extra->operacion).'" selected>'.($this->extra->operacion).'</option>';
        }
        $display.='</select>
                </div>                
                <div id="divMetodosE" style="display:none;" class="col-md-12">
                    <span id="warningSpan" class="spanError"></span>
                    <br /><br />
                </div>';

        $display.='            
            <div class="col-md-12">
                <label>Request</label>
                <textarea id="request" name="extra[request]" rows="7" cols="70" placeholder="{ object }" class="input-xxlarge">' . ($this->extra ? $this->extra->request : '') . '</textarea>
                <br />
                <span id="resultRequest" class="spanError"></span>
                <br /><br />
            </div>
           <div class="col-md-12">
                <label>Response</label>
                <textarea id="response" name="extra[response]" rows="7" cols="70" placeholder="{ object }" class="input-xxlarge" readonly>' . ($this->extra ? $this->extra->response : '') . '</textarea>
                <br /><br />
            </div>

            ';

        $display.='<div id="modalImportarWsdl" class="modal hide fade">
            <form method="POST" enctype="multipart/form-data" action="backend/acciones/upload_file">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h3>Importar Archivo Soap</h3>
            </div>
            <div class="modal-body">
                <p>Cargue a continuación el archivo .wsdl del Servio Soap.</p>
                <input type="file" name="archivo" />
            </div>
            <div class="modal-footer">
                <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
                <button type="button" id="btn-load" class="btn btn-primary">Importar</button>
            </div>
            </form>
        </div>
        <div id="modal" class="modal hide fade"></div>';

        $display.='
                <label>Seguridad</label>
                <select id="tipoSeguridad" name="extra[idSeguridad]">';
        foreach($conf_seguridad as $seg){
            $display.='
                        <option value="">Sin seguridad</option>';
            if ($this->extra->idSeguridad && $this->extra->idSeguridad == $seg->id){
                $display.='<option value="'.$seg->id.'" selected>'.$seg->institucion.' - '.$seg->servicio.'</option>';
            }else{
                $display.='<option value="'.$seg->id.'">'.$seg->institucion.' - '.$seg->servicio.'</option>';
            }
        }
        $display.='</select>';

        return $display;
    }

    public function validateForm() {
        $CI = & get_instance();
        $CI->form_validation->set_rules('extra[request]', 'Request', 'required');
        $CI->form_validation->set_rules('extra[operacion]', 'Métodos', 'required');
    }

    public function ejecutar(Etapa $etapa) { 
        $data = Doctrine::getTable('Seguridad')->find($this->extra->idSeguridad);
        log_message('info', '##################################################################', FALSE);
        log_message('info', 'tipo de seguridad: '.$data->extra->tipoSeguridad, FALSE);
        log_message('info', 'usuario: '.$data->extra->user, FALSE);
        log_message('info', 'password: '.$data->extra->pass, FALSE);
        log_message('info', '##################################################################', FALSE);
        
        $tipoSeguridad=$data->extra->tipoSeguridad;
        $user = $data->extra->user;
        $pass = $data->extra->pass;
        $ApiKey = $data->extra->apikey;
        $NameKey='';
        $client = new nusoap_client($this->extra->wsdl, 'wsdl');
        switch ($tipoSeguridad) {
            case "HTTP_BASIC":
                $client->setCredentials($user, $pass, 'basic');
                break;
            case "API_KEY":
            $header = 
                "<SECINFO>
                  <USERNAME>XXXXX</USERNAME>
                  <PASSWORD>XXXXX</PASSWORD>
                  <KEY>XXXXXXXXXXXXXXXXX</KEY>
                </SECINFO>";
                $client->setHeaders($header);
                break;
            case "OAUTH2":
                //SEGURIDAD OAUTH2
                //$client->setCredentials($user, $pass, 'basic');
                break;
            default:
                //NO TIENE SEGURIDAD
                //print_r("No tiene seguridad");
            break;
        }
        

        try{

            $CI = & get_instance();

            $r=new Regla($this->extra->wsdl);
            $wsdl=$r->getExpresionParaOutput($etapa->id);

            if(isset($this->extra->request)){
                //log_message('info', 'Reemplazando soap request: '.$this->extra->request, FALSE);
                $r=new Regla($this->extra->request);
                $request=$r->getExpresionParaOutput($etapa->id);
                //log_message('info', 'Request: '.$request, FALSE);
            }
            //print_r("<pre>");
            //print_r($request);
            //print_r("</pre>");


/*$prueba2='<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:q0="http://www.ispch.cl/" xmlns:q1="http://valida.aem.gob.cl" xmlns:q2="http://www.w3.org/2000/09/xmldsig#" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
   <soapenv:Body>
      <q1:sobre xsi:schemaLocation="http://valida.aem.gob.cl http://valida.aem.gob.cl/documentales/AEM/sobre.xsd" xmlns:aem="http://valida.aem.gob.cl" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:n3="http://www.altova.com/samplexml/other-namespace">
         <q1:encabezado>
            <q1:idSobre>161001000120100914100000099</q1:idSobre>
            <q1:fechaHora>2013-01-23T09:30:47Z</q1:fechaHora>
            <q1:proveedor>
               <q1:nombre>ISP</q1:nombre>
               <q1:servicios>
                  <q1:servicio>LISTA DE ESPERA CORAZON</q1:servicio>
                  <q1:respuestaServicio>
                     <q1:estado>SI</q1:estado>
                     <q1:glosa>RESPUESTA EXITOSA</q1:glosa>
                  </q1:respuestaServicio>
               </q1:servicios>
            </q1:proveedor>
            <q1:consumidor>
               <q1:nombre>MINSAL</q1:nombre>
               <q1:tramite>LISTA DE ESPERA</q1:tramite>
               <q1:certificado>
                  <ds:X509Data>
                     <ds:X509IssuerSerial>
                        <ds:X509IssuerName>IN</ds:X509IssuerName>
                        <ds:X509SerialNumber>0</ds:X509SerialNumber>
                     </ds:X509IssuerSerial>
                  </ds:X509Data>
               </q1:certificado>
            </q1:consumidor>
            <q1:fechaHoraReq>2013-01-23T09:30:47Z</q1:fechaHoraReq>
            <q1:emisor>ISP</q1:emisor>
            <q1:metadataOperacional>
               <q1:estadoSobre>00</q1:estadoSobre>
               <q1:glosaSobre>TRANSACCION EXITOSA</q1:glosaSobre>
            </q1:metadataOperacional>
         </q1:encabezado>
         <q1:cuerpo>
            <q1:documento/>
         </q1:cuerpo>
      </q1:sobre>
   </soapenv:Body>
</soapenv:Envelope>';
$prueba='{              
    "encabezado": {
        "idSobre": "161001000120100914100000099",
        "fechaHora": "2013-01-23T09:30:47Z",
        "proveedor": {
            "nombre": "ISP",
            "servicios": {
                "respuestaServicio": {
                    "estado": "SI",
                    "glosa": "RESPUESTA EXITOSA"
                },
                "servicio": "LISTA DE ESPERA CORAZON"
            }
        },
        "consumidor": {
            "nombre": "MINSAL",
            "tramite": "LISTA DE ESPERA",
            "certificado": {
                "x509Data": {
                    "X509IssuerName": "IN",
                    "X509SerialNumber": "0"
                }
            }
        },
        "fechaHoraReq": "2013-01-23T09:30:47Z",
        "emisor": "ISP",
        "metadataOperacional": {
            "estadoSobre": "00",
            "glosaSobre": "TRANSACCION EXITOSA"
        }
    },
    "cuerpo": {
        "documento": {}
    }
}';*/
//$request = json_decode($prueba, true);

if ($err) {
        echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
}
$err = $client->getError();
$result = $client->call($this->extra->operacion, $request);
if ($client->fault) {
        //echo '<h2>Fault</h2><pre>'; print_r($result); echo '</pre>';
} else {
        $err = $client->getError();
        if ($err) {
                //echo '<h2>Error</h2><pre>' . $err . '</pre>';
        } else {
                //echo '<h2>Result</h2><pre>'; print_r($result); echo '</pre>';
        }
}

//echo 'V 1.2<br/>';
//echo '<h2>Request</h2><pre>' . htmlspecialchars($client->request, ENT_QUOTES) . '</pre>';
//echo '<h2>Response</h2><pre>' . htmlspecialchars($client->response, ENT_QUOTES) . '</pre>';
//echo '<h2>Debug</h2><pre>' . htmlspecialchars($client->debug_str, ENT_QUOTES) . '</pre>';
//exit;
       
            //$request = json_decode($array, true);
            
            //log_message('info', 'Reemplazando soap request: '.$request, FALSE);
            //print_r($this->extra->operacion);
            //$soapclient = new nusoap_client($wsdl,'wsdl');
            //$var="http://sicexsagqa.sag.gob.cl/VyV.InicioActividades/RecepcionInicioActividades.svc?wsdl";
            //$soapclient = new nusoap_client($var,'wsdl');
            //$soapclient->setCredentials($user, $pass, 'basic');
            //$result = $soapclient->call($this->extra->operacion, $request);
            //print_r("<pre>");
            //print_r($soapclient);
            //print_r("</pre>");
            //print_r("<pre>");
            //print_r($result);
            //print_r("</pre>");
            //exit;
            //$result = $soapclient->call("RecepcionInicioActividades", $array);
            //echo '<pre>'; print_r($soapclient); echo '</pre>';
            //var_dump($result);
            //print_r($result);
            log_message('info', 'Se obtiene respuesta de servicio'.$result, FALSE);

            $response_name = "";
            if(isset($this->extra->response)){
                $response = json_decode($this->extra->response, true);
                foreach($response as $key=>$value){
                    $response_name = $key;
                    break;
                }
            }


            log_message('info', 'response name: '.$response_name, FALSE);
            $response_xml = $result[$response_name];
            log_message('info', '$response_xml: '.$response_xml, FALSE);
            log_message('info', 'Cargando xml', FALSE);
            $response = simplexml_load_string($response_xml);
            log_message('info', 'Transformando a json', FALSE);
            $result = json_encode($response);
            $result = "{\"response_soap\":".$result."}";
            log_message('info', 'Result: '.$result, FALSE);

            

            $json=json_decode($result);
   

            foreach($json as $key=>$value){
                $dato=Doctrine::getTable('DatoSeguimiento')->findOneByNombreAndEtapaId($key,$etapa->id);
                if(!$dato)
                    $dato=new DatoSeguimiento();
                $dato->nombre=$key;
                $dato->valor=$value;
                $dato->etapa_id=$etapa->id;
                $dato->save();
            }
        }catch (Exception $e){
            $dato=Doctrine::getTable('DatoSeguimiento')->findOneByNombreAndEtapaId("error_soap",$etapa->id);
            if(!$dato)
                $dato=new DatoSeguimiento();
            $dato->nombre="error_soap";
            $dato->valor=$e;
            $dato->etapa_id=$etapa->id;
            $dato->save();
        }

    }

}
