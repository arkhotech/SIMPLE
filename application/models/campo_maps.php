<?php
require_once('campo.php');
class CampoMaps extends Campo {

    public $requiere_datos = false;
    public $datos_mapa = true;

    protected function display($modo, $dato, $etapa_id) {

        if ($etapa_id) {
            $etapa = Doctrine::getTable('Etapa')->find($etapa_id);
            $regla = new Regla($this->valor_default);
            $valor_default = $regla->getExpresionParaOutput($etapa->id);
        } else {
            $valor_default = $this->valor_default;
        }

        $columns = $this->extra->columns;

        log_message('debug', 'columns: ' . json_encode($columns));
        log_message('debug', 'dato: ' . json_encode($dato));

        $display = '<label class="control-label" for="' . $this->id . '">' . $this->etiqueta . (!in_array('required', $this->validacion) ? ' (Opcional)' : '') . '</label>';
        $display .= '<div class="controls">';

        if ($modo == 'edicion') {
            $display.='<input id="' . $this->id . '" type="text" class="input-semi-large" name="' . $this->nombre . '" value="' . ($dato?htmlspecialchars($dato->valor):htmlspecialchars($valor_default)) . '" data-modo="' . $modo . '" style="position: absolute; z-index: 2; margin: 10px;" />';
        }

        $display .= '<div class="map" id="map_' . $this->id . '" data-id="' . $this->id . '" style="width=590px;"></div>';
        $display .= '<input type="hidden" id="maph_' . $this->id . '" name="' . $this->nombre . '" value=\'' . ($dato ? json_encode($dato->valor) : $valor_default) . '\' />';

        if ($this->ayuda)
            $display .= '<span class="help-block">'.$this->ayuda.'</span>';
        $display .= '</div>';

        $display .= '
            <script>
                function initMap_' . $this->id . '() {

                    var bounds = new google.maps.LatLngBounds();
                    var marker;

                    var map = new google.maps.Map(document.getElementById("map_' . $this->id . '"), {
                        zoom: 4,
                        center: {lat: -33.4429046, lng: -70.6560586},
                        mapTypeControl: false,
                        streetViewControl: false,
                    });

                   /* if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(function(objPosition) {
                            myLatLng.lat = objPosition.coords.longitude;
                            myLatLng.lng = objPosition.coords.latitude;
                            console.log("geolocate => lat: " + myLatLng.lat + " lng: " + myLatLng.lng);
                            map.setCenter(myLatLng);
                        }, function(objPositionError) {
                            console.log("objPositionError => lat: " + myLatLng.lat + "lng: " + myLatLng.lng);
                            myLatLng = {lat: -25.363, lng: 131.044};
                        }, {
                            maximumAge: 75000,
                            timeout: 15000
                        });
                    }*/

                    new AutocompleteDirectionsHandler(map);
        ';

        if ($columns) {
            log_message('debug', 'columns: ' . json_encode($columns));
            $i = 0;
            foreach ($columns as $key => $c) {

                if (strlen($c->latitude) > 0 && strlen($c->longitude) > 0) {
                    $display .= '

                        marker = new google.maps.Marker({
                            mapTypeControl: false,
                            anchorPoint: new google.maps.Point(0, -29),
                            animation: google.maps.Animation.DROP,
                            position: {lat: ' . $c->latitude . ', lng: ' . $c->longitude . '},
                            map: map,
                            title: "' . $c->title . '"
                        });

                        var infowindow = new google.maps.InfoWindow();
                        
                        infowindow.setContent("<div><strong>' . $c->title . '</strong><br>");
                        infowindow.open(map, marker);

                        // marker.setMap(map);
                        bounds.extend(marker.position);
                    ';
                    $i++;
                }
            }
        }

        $display .= '
                    map.fitBounds(bounds);
                }

                function AutocompleteDirectionsHandler(map) {
                    this.map = map;
                    var originInput = document.getElementById("' . $this->id . '");
                    this.directionsService = new google.maps.DirectionsService;
                    this.directionsDisplay = new google.maps.DirectionsRenderer;
                    this.directionsDisplay.setMap(map);

                    var autocomplete = new google.maps.places.Autocomplete(originInput);
                    autocomplete.bindTo("bounds", map);

                    this.map.controls[google.maps.ControlPosition.TOP_LEFT].push(originInput);

                    var infowindow = new google.maps.InfoWindow();

                    var marker = new google.maps.Marker({
                      map: map,
                      animation: google.maps.Animation.DROP,
                      anchorPoint: new google.maps.Point(0, -29)
                    });

                    autocomplete.addListener("place_changed", function() {
                        infowindow.close();
                        marker.setVisible(false);
                        var place = autocomplete.getPlace();
                        if (!place.geometry) {
                            // User entered the name of a Place that was not suggested and
                            // pressed the Enter key, or the Place Details request failed.
                            window.alert("No existe información para la dirección ingresada: \'" + place.name + "\'");
                            return;
                        }

                        // If the place has a geometry, then present it on a map.
                        if (place.geometry.viewport) {
                            console.log("lat: " + place.geometry.location.lat());
                            console.log("lng: " + place.geometry.location.lng());
                            var objLocation = {"columns":[{"latitude": place.geometry.location.lat() , "longitude": place.geometry.location.lng().toString() , "title":  $("#' . $this->id . '").val()}]};
                            $("#maph_' . $this->id . '").val(JSON.stringify(objLocation));
                            map.fitBounds(place.geometry.viewport);
                        } else {
                            map.setCenter(place.geometry.location);
                            map.setZoom(17);  // Why 17? Because it looks good.
                        }
                        marker.setPosition(place.geometry.location);
                        marker.setVisible(true);

                        var address = "";
                        if (place.address_components) {
                            address = [
                                (place.address_components[0] && place.address_components[0].short_name || ""),
                                (place.address_components[1] && place.address_components[1].short_name || ""),
                                (place.address_components[2] && place.address_components[2].short_name || "")
                            ].join(" ");
                        }

                        infowindow.setContent("<div><strong>" + place.name + "</strong><br>" + address);
                        infowindow.open(map, marker);
                    });
                }
            </script>
           <script 
                src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCDOQ2m4sss96dWd5sEs5levoURjrzMUYc&libraries=places&callback=initMap_' . $this->id . '"
                async defer></script>
        ';

        return $display;
    }

    public function backendExtraFields() {

        $columns = array();
        if (isset($this->extra->columns))
            $columns = $this->extra->columns;
        log_message('debug', '$this->readonly: ' .$this->readonly);
        $output = '
            <div class="columnas" ' . ($this->readonly == 0 ? 'style="display: none;"' : '') . '>
                <script type="text/javascript">
                    $(document).ready(function() {
                        $("#formEditarCampo .columnas .nuevo").click(function() {
                            var pos=$("#formEditarCampo .columnas table tbody tr").size();
                            var html="<tr>";
                            html+="<td><input type=\'text\' name=\'extra[columns][" + pos + "][latitude]\' style=\'width:100px;\' /></td>";
                            html+="<td><input type=\'text\' name=\'extra[columns][" + pos + "][longitude]\' style=\'width:100px;\' /></td>";
                            html+="<td><input type=\'text\' name=\'extra[columns][" + pos + "][title]\' style=\'width:140px;\' /></td>";
                            html+="<td><button type=\'button\' class=\'btn eliminar\'><i class=\'icon-remove\'></i> Eliminar</button></td>";
                            html+="</tr>";

                            $("#formEditarCampo .columnas table tbody").append(html);
                        });

                        $("#formEditarCampo .columnas").on("click", ".eliminar", function() {
                            $(this).closest("tr").remove();
                        });
                    });
                </script>
                <h4>Columnas</h4>
                <button class="btn nuevo" type="button"><i class="icon-plus"></i> Nuevo</button>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Latitud</th>
                            <th>Longitud</th>
                            <th>T&iacute;tulo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    ';

        if ($columns) {
            $i = 0;
            foreach ($columns as $key => $c) {
                $output .= '
                <tr>
                    <td><input type="text" name="extra[columns][' . $i . '][latitude]" style="width:100px;" value="' . $c->latitude . '" /></td>
                    <td><input type="text" name="extra[columns][' . $i . '][longitude]" style="width:100px;" value="' . $c->longitude . '" /></td>
                    <td><input type="text" name="extra[columns][' . $i . '][title]" style="width:140px;" value="' . $c->title . '" /></td>
                    <td><button type="button" class="btn eliminar"><i class="icon-remove"></i>Eliminar</button></td>
                </tr>
                ';
                $i++;
            }
        }

        $output .= '
        </tbody>
        </table>
        </div>
        ';

        return $output;
    }
}