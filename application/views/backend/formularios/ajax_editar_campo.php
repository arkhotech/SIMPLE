<?php 
    $idagendaeditar = htmlspecialchars($campo->agenda_campo);
    if (!isset($idagendaeditar) || !is_numeric($idagendaeditar)) {
        $idagendaeditar = 0;
    }
?>
<style>
    .fa {
        float: left;
        position: relative;
        line-height: 20px;
    }
</style>
<script type="text/javascript">
    $(document).ready(function() {
        $('.validacion').typeahead({
            mode: "multiple",
            delimiter: "|",
            source: ["required", "rut", "min_length[num]", "max_length[num]", "exact_length[num]", "greater_than[num]", "less_than[num]", "alpha", "alpha_numeric", "alpha_dash", "alpha_space", "numeric", "integer", "decimal", "is_natural", "is_natural_no_zero", "valid_email", "valid_emails", "valid_ip", "valid_base64", "trim", "is_unique[exp]"]
        });

        // Funcionalidad del llenado de nombre usando el boton de asistencia
        $("#formEditarCampo .asistencia .dropdown-menu a").click(function() {
            var nombre = $(this).text();
            $("#formEditarCampo input[name=nombre]").val(nombre);
        });

        // Funcionalidad del llenado de dependiente usando el boton de asistencia
        $("#formEditarCampo .dependiente .dropdown-menu a").click(function() {
            var nombre = $(this).text();
            $("#formEditarCampo input[name=dependiente_campo]").val(nombre);
        });

        // Funcionalidad en campo dependientes para seleccionar entre tipo regex y string
        $buttonRegex = $("#formEditarCampo .campoDependientes .buttonRegex");
        $buttonString = $("#formEditarCampo .campoDependientes .buttonString");
        $inputDependienteTipo = $("#formEditarCampo input[name=dependiente_tipo]");
        $buttonString.attr("disabled", $inputDependienteTipo.val() == "string");
        $buttonRegex.attr("disabled", $inputDependienteTipo.val() == "regex");

        $buttonRegex.click(function() {
            $buttonString.prop("disabled", false);
            $buttonRegex.prop("disabled", true);
            $inputDependienteTipo.val("regex");
        });

        $buttonString.click(function() {
            $buttonString.prop("disabled", true);
            $buttonRegex.prop("disabled", false);
            $inputDependienteTipo.val("string");
        });

        // Funcionalidad en campo dependientes para seleccionar entre tipo igualdad y desigualdad
        $buttonDesigualdad = $("#formEditarCampo .campoDependientes .buttonDesigualdad");
        $buttonIgualdad = $("#formEditarCampo .campoDependientes .buttonIgualdad");
        $inputDependienteRelacion = $("#formEditarCampo input[name=dependiente_relacion]");
        $buttonIgualdad.attr("disabled", $inputDependienteRelacion.val() == "==");
        $buttonDesigualdad.attr("disabled", $inputDependienteRelacion.val() == "!=");

        $buttonDesigualdad.click(function() {
            $buttonIgualdad.prop("disabled", false);
            $buttonDesigualdad.prop("disabled", true);
            $inputDependienteRelacion.val("!=");
        });

        $buttonIgualdad.click(function() {
            $buttonIgualdad.prop("disabled", true);
            $buttonDesigualdad.prop("disabled", false);
            $inputDependienteRelacion.val("==");
        });

        // Llenado automatico del campo nombre
        $("#formEditarCampo input[name=etiqueta]").blur(function() {
            ellipsize($("#formEditarCampo input[name=etiqueta]"),$("#formEditarCampo input[name=nombre]"));
        });

        // Llenado automatico del campo valor
        $("#formEditarCampo").on("blur", "input[name$='[etiqueta]']",function() {
            var campoOrigen = $(this);
            var campoDestino = $(this).closest("tr").find("input[name$='[valor]']")
            ellipsize(campoOrigen, campoDestino);
        });

        function ellipsize(campoOrigen, campoDestino) {
            if ($(campoDestino).val() == "") {
                var string = $(campoOrigen).val().trim();
                string = string.toLowerCase();
                string = string.replace(/\s/g, "_");
                string = string.replace(/á/g, "a");
                string = string.replace(/é/g, "e");
                string = string.replace(/í/g, "i");
                string = string.replace(/ó/g, "o");
                string = string.replace(/ú/g, "u");
                string = string.replace(/\W/g, "");
                $(campoDestino).val(string);
            }
        }
        /* Prevenir espacios en campo nombre y que puedan pegar contenido en el mismo campo */
        $(document).on('keypress', '#nombre', function(e) {
             return !(e.keyCode == 32);
        });

        $('#nombre').bind('paste', function (e) {
            e.preventDefault();
        });
    });
</script>

<div class="modal-header">
    <button class="close" data-dismiss="modal">×</button>
    <h3>Edici&oacute;n de Campo
        <a href="/assets/ayuda/simple/backend/modelamiento-del-proceso/diseno-de-formularios.html#btn_<?= $campo->tipo ?>" target="_blank">
            <span class="glyphicon glyphicon-info-sign" style="font-size: 16px;"></span>
        </a>
    </h3>
</div>
<div class="modal-body">
    <form id="formEditarCampo" class="ajaxForm" method="POST" action="<?= site_url('backend/formularios/editar_campo_form/' . ($edit ? $campo->id : '')) ?>">
        <div class="validacion"></div>
        <?php if (!$edit): ?>
            <input type="hidden" name="formulario_id" value="<?= $formulario->id ?>" />
            <input type="hidden" name="tipo" value="<?= $campo->tipo ?>" />
        <?php endif; ?>
        <label>Etiqueta
        </label>
        <?php if ($campo->etiqueta_tamano == 'xxlarge'): ?>
            <textarea class="input-xxlarge" rows="5" name="etiqueta" ><?= htmlspecialchars($campo->etiqueta) ?></textarea>
        <?php else: ?>
            <input type="text" name="etiqueta" value="<?= htmlspecialchars($campo->etiqueta) ?>" />
        <?php endif ?>
        <?php if ($campo->requiere_nombre): ?>
            <label>Nombre</label>
            <input type="text" id="nombre" name="nombre" value="<?= $campo->nombre ?>" />
        <?php $campos_asistencia = $formulario->Proceso->getNombresDeCampos($campo->tipo, false) ?>
        <?php if (count($campos_asistencia)): ?>
            <div class="btn-group asistencia" style="display: inline-block; vertical-align: top;">
                <a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
                    <i class="icon-th-list"></i><span class="caret"></span>
                </a>
                <ul class="dropdown-menu">
                    <?php foreach ($campos_asistencia as $c): ?>
                        <li><a href="#"><?= $c ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif ?>
        <?php else: ?>
            <input type="hidden" name="nombre" value="<?=$campo->nombre?$campo->nombre:uniqid();?>" />
        <?php endif; ?>

        <?php if (!$campo->estatico):?>
            <label>Ayuda contextual (Opcional)</label>
            <input type="text" class="input-xxlarge" name="ayuda" value="<?=$campo->ayuda?>" />
        <?php endif ?>

        <?php if (!$campo->estatico): ?>
            <?php if (isset($campo->datos_agenda) && $campo->datos_agenda) { ?>
                <label style="display: none;" class="checkbox"><input type="checkbox" name="readonly" value="1" <?=$campo->readonly?'checked':''?> /> Solo lectura</label>    
            <?php } else { ?>
                <label class="checkbox"><input type="checkbox" name="readonly" value="1" <?=$campo->readonly?'checked':''?> /> Solo lectura</label>
            <?php } ?>
        <?php endif; ?>
        <?php if (!$campo->estatico): ?>
            <?php if (isset($campo->datos_agenda) && $campo->datos_agenda) { ?>
                <label style="display:none;">Reglas de validación
                  <a href="/assets/ayuda/simple/backend/modelamiento-del-proceso/reglas-de-negocio-y-reglas-de-validacion.html" target="_blank">
                        <span class="glyphicon glyphicon-info-sign"></span>
                    </a>
                </label>
                <input style="display: none;" class='validacion' type="text" name="validacion" value="<?= $edit ? implode('|', $campo->validacion) : 'required' ?>"/>
            <?php } else { ?>
                <label>Reglas de validación
                    <a href="/assets/ayuda/simple/backend/modelamiento-del-proceso/reglas-de-negocio-y-reglas-de-validacion.html#validacion_campos" target="_blank">
                        <span class="glyphicon glyphicon-info-sign"></span>
                    </a>
                </label>
                <input class='validacion' type="text" name="validacion" value="<?= $edit ? implode('|', $campo->validacion) : 'required' ?>"/>
            <?php } ?>
            
               <?php endif; ?>
            <?php if (!$campo->estatico): ?>
                <?php if ((isset($campo->datos_agenda) && $campo->datos_agenda) || (isset($campo->datos_mapa) && $campo->datos_mapa)) { ?>
                        <label style="display:none;">Valor por defecto</label>
                        <input style="display:none;" type="text" name="valor_default" value="<?=htmlspecialchars($campo->valor_default)?>" />
                <?php } else { ?>
                        <label>Valor por defecto</label>
                        <input type="text" name="valor_default" value="<?=htmlspecialchars($campo->valor_default)?>" />
                <?php } ?>
            <?php endif ?>
            <div class="campoDependientes">                
                <label>Visible solo si</label>
                <input type="text" name="dependiente_campo" value="<?=$campo->dependiente_campo?>"/>
		        <div class="btn-group dependiente" style="display: inline-block; vertical-align: top;">
		            <a class="btn dropdown-toggle" data-toggle="dropdown" href="#"><i class="icon-th-list"></i><span class="caret"></span></a>
		            <ul class="dropdown-menu">
		            	<li><b>Campos</b></li>
		                <?php foreach ($formulario->Proceso->getCampos() as $c): ?>
		                    <li><a href="#"><?= $c->nombre ?></a></li>
		                <?php endforeach; ?>
		                <li><b>Variables</b></li>
		                <?php foreach ($formulario->Proceso->getVariables() as $v): ?>
		                    <li><a href="#"><?= $v->extra->variable ?></a></li>
		                <?php endforeach; ?>
		            </ul>
		        </div>

                <!-- <select class="input-medium" name="dependiente_campo"> -->
                <!-- </select> -->

                <div class="btn-group" style="margin-bottom: 9px;">
                    <button type="button" class="buttonIgualdad btn">=</button><button type="button" class="buttonDesigualdad btn">!=</button>
                </div>
                <input type="hidden" name="dependiente_relacion" value="<?=isset($campo) && $campo->dependiente_relacion? $campo->dependiente_relacion:'==' ?>" />
                <span class="input-append">
                    <input type="text" name="dependiente_valor" value="<?= isset($campo) ? $campo->dependiente_valor : '' ?>" />
                    <button type="button" class="buttonString btn">String</button>
                    <button type="button" class="buttonRegex btn">Regex</button>
                </span>
                <input type="hidden" name="dependiente_tipo" value="<?=isset($campo) && $campo->dependiente_tipo? $campo->dependiente_tipo:'string' ?>" />

                <?php if (isset($campo->datos_mapa) && $campo->datos_mapa): ?>
                    <script type="text/javascript">
                        $(function() {
                            $("[name=readonly]").click(function() {
                                if ($(this).attr('checked')) {
                                    $('.columnas').show();
                                } else {
                                    $("#formEditarCampo .columnas table tbody tr").remove();
                                    $('.columnas').hide();
                                }
                            });
                        });
                    </script>
                <?php endif; ?>

                <?php if (isset($campo->datos_agenda) && $campo->datos_agenda): ?>
                    <label>Pertenece a: </label>
                    <select id="selectgrupo" class="input-xlarge" name="grupos_usuarios">
                    </select>
                    <button class="btn btn_filtrar_agenda vtop" type="button">Filtrar</button>
                    <label style="margin-top:8px;">Agenda:</label>
                    <select id="miagenda" class="input-xlarge" name="agenda_campo">
                        <option value="1">Seleccione(Opcional)</option>
                    </select>
                    <script type="text/javascript">
                        $(function() {
                            $("#selectgrupo").select2({
                                placeholder: "Seleccione(Opcional)",
                                allowClear: true,
                                multiple: false,
                                templateSelection: selection,
                                templateResult: format
                            });

                            $("#selectgrupo").change(function() {
                                $("#miagenda").html('');
                                var idseleccionado = $(this).val();
                                $.ajax({
                                    url:'<?= site_url('/backend/formularios/ajax_mi_calendario') ?>',
                                    dataType: "json",
                                    data: {
                                        pertenece: idseleccionado
                                    },
                                    success: function(data) {
                                        if (data.code == 200) {
                                            var items = data.calendars;
                                            $('#miagenda').html('');
                                            if (items.length > 0) {
                                                $("#miagenda").removeAttr('disabled');
                                                $.each(items, function(index, element) {
                                                    $("#miagenda").append('<option value="' + element.id + '">' + element.name + '</option>');
                                                });
                                                var swedit = <?php echo (isset($edit) && $edit) ? 1 : 0; ?>;
                                                if (swedit == 1) {
                                                    var idagenda = <?= $idagendaeditar ?>;
                                                    $('#miagenda').val(idagenda);
                                                }
                                            }
                                        }
                                    }
                                });
                            });

                            $.ajax({
                                url:'<?= site_url('/backend/formularios/listarPertenece') ?>',
                                dataType: "json",
                                success: function(data) {
                                    if (data.code == 200) {
                                        var items = data.resultado.items;
                                        $.each(items, function(index, element) {
                                            var icon = 'iconglyp-user';
                                            if (element.tipo == 1) {
                                                icon = 'iconglyp-group';
                                            }
                                            $("#selectgrupo").append('<option value="' + element.id + '" data-icon="' + icon + '" >' + element.nombre + '</option>');
                                        });
                                    }
                                }
                            });
                            var swedit = <?php echo (isset($edit) && $edit) ? 1 : 0; ?>;
                            if (swedit == 1) {
                                var idagenda = <?= $idagendaeditar ?>;
                                cargar_service(idagenda);
                            }
                        });

                        function format(icon) {
                            var originalOption = icon.element;
                            return '<i class="fa defaulticonglyp ' + $(originalOption).data('icon') + '" style="top: 1px;"></i>&nbsp;&nbsp;' + icon.text;
                        }

                        function selection(icon) {
                            var originalOption = icon.element;
                            return '<i class="fa defaulticonglyp ' + $(originalOption).data('icon') + ' " style="top: 7px;"></i>&nbsp;&nbsp;' + icon.text;
                        }

                        function cargar_service(idagenda) {
                            $.ajax({
                                url: '<?= site_url('/backend/formularios/obtener_agenda') ?>', 
                                dataType: "json",
                                data: {
                                    idagenda: idagenda
                                },
                                success: function( data ) {
                                    if (data.code == 200) {
                                        var options = $('#selectgrupo').find('option');
                                        var owner = data.calendario_owner;
                                        var indexpertenece = 0;
                                        var v = 0;
                                        $.each(options,function(index,value) {
                                            if ($(value).text().indexOf(owner) >= 0) {
                                                indexpertenece = index;
                                            }
                                        });
                                        $("#selectgrupo").select2("val", options[indexpertenece].value);
                                    }
                                }
                            });
                        }
                    </script>
                <?php endif; ?>
            </div>
            
            <?=$campo->extraForm() ? $campo->extraForm() : '' ?>
            
        <?php if ($campo->requiere_datos): ?>
            <div class="datos">
                <script type="text/javascript">
                    $(document).ready(function() {
                        $('#formEditarCampo .datos .nuevo').click(function() {
                            var pos = $('#formEditarCampo .datos table tbody tr').size();
                            var html = '<tr>';
                            html += '<td><input type="text" name="datos[' + pos + '][etiqueta]" /></td>';
                            html += '<td><input class="input-small" type="text" name="datos[' + pos + '][valor]" /></td>';
                            html += '<td><button type="button" class="btn eliminar"><i class="icon-remove"></i> Eliminar</button></td>';
                            html += '</tr>';

                            $('#formEditarCampo .datos table tbody').append(html);
                        });
                        $('#formEditarCampo .datos').on('click','.eliminar',function() {
                            $(this).closest('tr').remove();
                        });
                    });
                </script>
                <h4>Datos</h4>
                <button class="btn nuevo" type="button"><i class="icon-plus"></i> Nuevo</button>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Etiqueta</th>
                            <th>Valor</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($campo->datos): ?>
                            <?php $i = 0 ?>
                            <?php foreach ($campo->datos as $key => $d): ?>
                                <tr>
                                    <td><input type="text" name="datos[<?= $i ?>][etiqueta]" value="<?= $d->etiqueta ?>" /></td>
                                    <td><input class="input-small" type="text" name="datos[<?= $i ?>][valor]" value="<?= $d->valor ?>" /></td>
                                    <td><button type="button" class="btn eliminar"><i class="icon-remove"></i> Eliminar</button></td>
                                </tr>
                                <?php $i++ ?>
                            <?php endforeach; ?>
                        <?php endif ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <?=$campo->backendExtraFields()?>
    </form>
</div>
<div class="modal-footer">
    <a href="#" data-dismiss="modal" class="btn">Cerrar</a>
    <a href="#" onclick="javascript:$('#formEditarCampo').submit();return false;" class="btn btn-primary">Guardar</a>
</div>
