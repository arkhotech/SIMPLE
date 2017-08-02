<?php
require_once('campo.php');
class CampoMaps extends Campo {

    public $requiere_datos = false;

    protected function display($modo, $dato, $etapa_id) {

        if ($etapa_id) {
            $etapa = Doctrine::getTable('Etapa')->find($etapa_id);
            $regla = new Regla($this->valor_default);
            $valor_default = $regla->getExpresionParaOutput($etapa->id);
        } else {
            $valor_default = $this->valor_default;
        }

        $columns = $this->extra->columns;

        $display = '<label class="control-label" for="' . $this->id . '">' . $this->etiqueta . (!in_array('required', $this->validacion) ? ' (Opcional)' : '') . '</label>';
        $display .= '<div class="controls">';
        $display .= '<div class="map" id="dat_' . $this->id . '" data-id="' . $this->id . '" style="width=590px;"></div>';
        $display .= '<input type="hidden" name="' . $this->nombre . '" value=\'' . ($dato?json_encode($dato->valor) : $valor_default) . '\' />';

        if ($this->ayuda)
            $display .= '<span class="help-block">'.$this->ayuda.'</span>';
        $display .= '</div>';

        $display .= '
            <script>
                function initMap() {
                    var bounds = new google.maps.LatLngBounds();
                    var marker;

                    var map = new google.maps.Map(document.getElementById("dat_' . $this->id . '"), {
                    zoom: 4
                  });
        ';

        if ($columns) {
            $i = 0;
            foreach ($columns as $key => $c) {
                $display .= '

                    marker = new google.maps.Marker({
                        position: {lat: ' . $c->latitude . ', lng: ' . $c->longitude . '},
                        map: map,
                        title: "' . $c->title . '"
                    });

                    marker.setMap(map);
                    bounds.extend(marker.position);
                ';
                $i++;
            }
        }

        $display .= '
                    map.fitBounds(bounds);
                }
            </script>
        ';

        return $display;
    }

    public function backendExtraFields() {

        $columns = array();
        if (isset($this->extra->columns))
            $columns = $this->extra->columns;

        $output = '
            <div class="columnas">
                <script type="text/javascript">
                    $(document).ready(function() {
                        $("#formEditarCampo .columnas .nuevo").click(function() {
                            var pos=$("#formEditarCampo .columnas table tbody tr").size();
                            var html="<tr>";
                            html+="<td><input type=\'text\' name=\'extra[columns]["+pos+"][latitude]\' style=\'width:100px;\' /></td>";
                            html+="<td><input type=\'text\' name=\'extra[columns]["+pos+"][longitude]\' style=\'width:100px;\' /></td>";
                            html+="<td><input type=\'text\' name=\'extra[columns]["+pos+"][title]\' style=\'width:140px;\' /></td>";
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

    public function backendExtraValidate() {
        $CI =& get_instance();
        $CI->form_validation->set_rules('extra[columns]', 'Columnas', 'required');
    }

}