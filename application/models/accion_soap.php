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
        $user = $data->extra->user;
        $pass = $data->extra->pass;


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


                /*
                            $request='{
                  "sobre": {
                    "encabezado": {
                      "idSobre": "161001000120100914100000099",
                      "fechaHora": "2013-01-23T09:30:47Z",
                      "proveedor": {
                        "nombre": "ISP",
                        "servicios": {
                          "servicio": "LISTA DE ESPERA CORAZON",
                          "respuestaServicio": {
                            "estado": "SI",
                            "glosa": "RESPUEåSTA EXITOSA"
                          }
                        }
                      },
                      "consumidor": {
                        "nombre": "MINSAL",
                        "tramite": "LISTA DE ESPERA",
                        "certificado": {
                          "X509Data": {
                            "X509IssuerSerial": {
                              "X509IssuerName": "IN",
                              "X509SerialNumber": "0"
                            }
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
                      
                    }
                  }
                }';


                $request2='{
                    "Sobre": {
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
                    }
                }';

                $prueba='{
                    "Sobre": {
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
                    }

                }';*/
            //$request3 = json_encode($request2, true);
            $request = json_decode($request, true);
            log_message('info', 'Reemplazando soap request: '.$request, FALSE);
            print_r($request4);
            $soapclient = new nusoap_client($wsdl,'wsdl');
            //$soapclient = new nusoap_client('http://localhost:8088/mockListaDeEsperaSoap?wsdl','wsdl');
            $soapclient->setCredentials($user, $pass, 'basic');
            $result = $soapclient->call($this->extra->operacion, $request4);
            echo '<pre>'; print_r($soapclient); echo '</pre>';
            echo '<pre>'; print_r($result); echo '</pre>';
            //exit;

            log_message('info', 'Se obtiene respuesta', FALSE);

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
