<script type="text/javascript">
    $(document).ready(function() {
        function escapeHtml(text) {
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        $("#selectGruposUsuarios").select2({tags: true});
        $("[rel=tooltip]").tooltip();

        $(".datepicker")
        .datepicker({
            format: "dd-mm-yyyy",
            weekStart: 1,
            autoclose: true,
            language: "es"
        })

        $('#formEditarTarea .nav-tabs a').click(function (e) {
            e.preventDefault();
            $(this).tab('show');
        });

        // Permite borrar pasos
        $(".tab-pasos").on("click",".delete",function() {
            $(this).closest("tr").remove();
            return false;
        });

        // Permite agregar nuevos pasos
        $(".tab-pasos .form-agregar-paso button").click(function() {
            var $form=$(".tab-pasos .form-agregar-paso");

            var pos=1+$(".tab-pasos table tbody tr").size();
            var formularioId=$form.find(".pasoFormulario option:selected").val();
            var formularioNombre=$form.find(".pasoFormulario option:selected").text();
            var modo=$form.find(".pasoModo option:selected").val();
            var regla=$form.find(".pasoRegla").val();

            var html="<tr>";
            html+="<td>"+pos+"</td>";
            html+='<td><a title="Editar" target="_blank" href="'+site_url+'backend/formularios/editar/'+formularioId+'">'+formularioNombre+'</td>';
            html+="<td>"+regla+"</td>";
            html+="<td>"+modo+"</td>";
            html+='<td>';
            html+='<input type="hidden" name="pasos['+pos+'][id]" value="" />';
            html+='<input type="hidden" name="pasos['+pos+'][formulario_id]" value="'+formularioId+'" />';
            html+='<input type="hidden" name="pasos['+pos+'][regla]" value="'+escapeHtml(regla)+'" />';
            html+='<input type="hidden" name="pasos['+pos+'][modo]" value="'+modo+'" />';
            html+='<a class="delete" title="Eliminar" href="#"><i class="icon-remove"></i></a>';
            html+='</td>';
            html+="</tr>";

            $(".tab-pasos table tbody").append(html);

            return false;
        });

        //Permite que los pasos sean reordenables
        $(".tab-pasos table tbody").sortable({
            revert: true,
            stop: function() {
                //Reordenamos las posiciones
                $(this).find("tr").each(function(i, e) {
                    $(e).find("td:nth-child(1)").text(i+1);
                    $(e).find("input[name*=formulario_id]").attr("name","pasos["+(i+1)+"][formulario_id]");
                    $(e).find("input[name*=regla]").attr("name","pasos["+(i+1)+"][regla]");
                    $(e).find("input[name*=modo]").attr("name","pasos["+(i+1)+"][modo]");
                });
            }
        });

        //Permite borrar eventos
        $(".tab-eventos").on("click",".delete",function(){
            $(this).closest("tr").remove();
            return false;
        });

        //Permite agregar nuevos eventos
        $(".tab-eventos .form-agregar-evento button").click(function(){
            var $form=$(".tab-eventos .form-agregar-evento");

            var pos=1+$(".tab-eventos table tbody tr").size();
            var accionId=$form.find(".eventoAccion option:selected").val();
            var accionNombre=$form.find(".eventoAccion option:selected").text();
            var regla=$form.find(".eventoRegla").val();
            var instante=$form.find(".eventoInstante option:selected").val();
            var pasoId=$form.find(".eventoPasoId option:selected").val();
            var pasoNombre=$form.find(".eventoPasoId option:selected").text();
            var pasoTitle=$form.find(".eventoPasoId option:selected").attr("title");

            var html="<tr>";
            html+="<td>"+pos+"</td>";
            html+='<td><a title="Editar" target="_blank" href="'+site_url+'backend/acciones/editar/'+accionId+'">'+accionNombre+'</td>';
            html+="<td>"+regla+"</td>";
            html+="<td>"+instante+"</td>";
            html+="<td><abbr title='"+pasoTitle+"'>"+pasoNombre+"</abbr></td>";
            html+='<td>';
            html+='<input type="hidden" name="eventos['+pos+'][accion_id]" value="'+accionId+'" />';
            html+='<input type="hidden" name="eventos['+pos+'][regla]" value="'+escapeHtml(regla)+'" />';
            html+='<input type="hidden" name="eventos['+pos+'][instante]" value="'+instante+'" />';
            html+='<input type="hidden" name="eventos['+pos+'][paso_id]" value="'+pasoId+'" />';
            html+='<a class="delete" title="Eliminar" href="#"><i class="icon-remove"></i></a>';
            html+='</td>';
            html+="</tr>";

            $(".tab-eventos table tbody").append(html);

            return false;
        });

        //Permite agregar nuevos eventos externos
        $(".tab-eventos-externos .form-agregar-evento-externo button").click(function(){
            var $form=$(".tab-eventos-externos .form-agregar-evento-externo");

            var pos=1+$(".tab-eventos-externos table tbody tr").size();
            var nombre=$form.find("#nombre").val();
            var metodo=$form.find(".eventoSentido option:selected").val();
            var url=$form.find("#url").val();
            var mensaje=$form.find("#mensaje").val();
            var regla=$form.find("#regla").val();
            var opciones=$form.find("#opciones").val();

            var html="<tr>";
            html+="<td>"+pos+"</td>";
            html+="<td>"+nombre+"</td>";
            html+="<td>"+metodo+"</td>";
            html+="<td>"+url+"</td>";
            html+="<td>"+mensaje+"</td>";
            html+="<td>"+regla+"</td>";
            html+="<td>"+opciones+"</td>";
            html+='<td>';
            html+='<input type="hidden" name="eventos_externos['+pos+'][id]" value="" />';
            html+='<input type="hidden" name="eventos_externos['+pos+'][nombre]" value="'+nombre+'" />';
            html+='<input type="hidden" name="eventos_externos['+pos+'][metodo]" value="'+metodo+'" />';
            html+='<input type="hidden" name="eventos_externos['+pos+'][url]" value="'+url+'" />';
            html+='<input type="hidden" name="eventos_externos['+pos+'][mensaje]" value="'+escapeHtml(mensaje)+'" />';
            html+='<input type="hidden" name="eventos_externos['+pos+'][regla]" value="'+regla+'" />';
            html+='<input type="hidden" name="eventos_externos['+pos+'][opciones]" value="'+opciones+'" />';
            html+='<a class="delete" title="Eliminar" href="#"><i class="icon-remove"></i></a>';
            html+='</td>';
            html+="</tr>";

            $(".tab-eventos-externos table tbody").append(html);

            return false;
        });

        $(".tab-eventos-externos").on("click",".delete",function(){
            $(this).closest("tr").remove();
            return false;
        });

        //$("#modalEditarTarea form input[name=socket_id_emisor]").val(socketId);
        //$("#modalEditarTarea .botonEliminar").attr("href",function(i,href){return href+"?socket_id_emisor="+socketId;})
    });
</script>

<div class="modal-header">
    <a class="close" data-dismiss="modal">×</a>
    <h3>Editar Tarea</h3>
</div>
<div class="modal-body" >
    <form id="formEditarTarea" class="ajaxForm" method="POST" action="<?= site_url('backend/procesos/editar_tarea_form/' . $tarea->id) ?>">
        <div class="validacion"></div>

        <div class="tabbable">
            <ul class="nav nav-tabs">
                <li class="active"><a href="#tab1">Definición</a></li>
                <li><a href="#tab2">Asignación</a></li>
                <li><a href="#tab3">Usuarios</a></li>
                <li><a href="#tab4">Pasos</a></li>
                <li><a href="#tab5">Eventos</a></li>
                <li><a href="#tab6">Vencimiento</a></li>
                <li><a href="#tab7">Otros</a></li>
                <li><a href="#tab8">Datos</a></li>
                <li><a href="#tab9">Cuentas</a></li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane active" id="tab1">
                    <label>
                        <strong>Nombre</strong>
                        <a href="/assets/ayuda/simple/backend/modelamiento-del-proceso/disenador.html#pestana_definicion" target="_blank">
                            <span class="glyphicon glyphicon-info-sign"></span>
                        </a>
                    </label>
                    <div class="row-fluid">
                        <div class="span12">
                            <input class="span12" name="nombre" type="text" value="<?= $tarea->nombre ?>" />
                        </div>
                    </div>
                    <br />
                    <label><strong>Activación</strong></label>
                    <div class="row-fluid">
                        <div class="span6">
                            <label class="checkbox"><input name="inicial" id="inicial" value="1" type="checkbox" <?= $tarea->inicial ? 'checked' : '' ?>> Tarea Inicial</label>
                            <label class="checkbox"><input name="final" value="1" type="checkbox" <?= $tarea->final ? 'checked' : '' ?>> Tarea Final</label>
                        </div>
                        <div class="span6">
                            <script>
                                function MostrarExponer() {
                                    if($('#inicial').prop('checked')) {
                                        $("#DivExponer").show();
                                    }else{
                                        $("#DivExponer").hide();
                                        $("#exponer_tramite").prop('checked',false);
                                    }
                                }
                                $(document).ready(function(){
                                    MostrarExponer();
                                    $("input[name=activacion]").change(function(){
                                        if($("input[name=activacion]:checked").val()=='entre_fechas'){
                                            $("#activacionEntreFechas").show();
                                        } else {
                                            $("#activacionEntreFechas").hide();
                                        }
                                    }).change();
                                    $("#inicial").click(function() {
                                      MostrarExponer();
                                    });
                                });
                            </script>
                            <div id="DivExponer" style="display:none;">
                                <label class="checkbox"><input name="exponer_tramite" id="exponer_tramite" value="1" type="checkbox" <?= $tarea->exponer_tramite ? 'checked' : '' ?>> Exponer trámite</label>
                            </div>
                            <label class="radio"><input name="activacion" value="si" type="radio" <?= $tarea->activacion == 'si' ? 'checked' : '' ?>>Tarea activada</label>
                            <label class="radio"><input name="activacion" value="entre_fechas" type="radio" <?= $tarea->activacion == 'entre_fechas' ? 'checked' : '' ?>>Tarea activa entre fechas</label>
                            <div id="activacionEntreFechas" class="hide" style="margin-left: 20px;">
                                <label>Fecha inicial</label>
                                <input class="datepicker" rel="tooltip" title="Deje el campo en blanco para no considerar una fecha inicial" type="text" name="activacion_inicio" value="<?= $tarea->activacion_inicio ? date('d-m-Y', $tarea->activacion_inicio) : '' ?>" placeholder="DD-MM-AAAA" />
                                <label>Fecha final</label>
                                <input class="datepicker" rel="tooltip" title="Deje el campo en blanco para no considerar una fecha final" type="text" name="activacion_fin" value="<?= $tarea->activacion_fin ? date('d-m-Y', $tarea->activacion_fin) : '' ?>" placeholder="DD-MM-AAAA" />
                            </div>
                            <label class="radio"><input name="activacion" value="no" type="radio" <?= $tarea->activacion == 'no' ? 'checked' : '' ?>>Tarea desactivada</label>
                        </div>
                    </div>
                    <br />
                    <label><strong>Información para previsualización</strong></label>
                    <div class="row-fluid">
                        <div class="span12">
                            <textarea class="span12" rows="5" name="previsualizacion"><?=$tarea->previsualizacion?></textarea>
                            <div class="help-block">Información que aparecera en la bandeja de entrada al pasar el cursor por encima.</div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane" id="tab2">
                    <script type="text/javascript">
                        $(document).ready(function(){
                            $("input[name=asignacion]").click(function(){
                                if(this.value=="usuario")
                                    $("#optionalAsignacionUsuario").removeClass("hide");
                                else
                                    $("#optionalAsignacionUsuario").addClass("hide");
                            });
                        });
                    </script>
                    <label>Regla de asignación
                        <a href="/assets/ayuda/simple/backend/modelamiento-del-proceso/disenador.html#pestana_asignacion" target="_blank">
                            <span class="glyphicon glyphicon-info-sign"></span>
                        </a>
                    </label>
                    <label class="radio" rel="tooltip" title="Los usuarios se asignan en forma ciclica. Se van turnando dentro del grupo de usuarios en forma circular."><input type="radio" name="asignacion" value="ciclica" <?= $tarea->asignacion == 'ciclica' ? 'checked' : '' ?> /> Cíclica</label>
                    <label class="radio" rel="tooltip" title="Al finalizar la tarea anterior, se le pregunta al usuario a quien se le va a asignar esta tarea."><input type="radio" name="asignacion" value="manual" <?= $tarea->asignacion == 'manual' ? 'checked' : '' ?> /> Manual</label>
                    <label class="radio" rel="tooltip" title="La tarea queda sin asignar, y los usuarios mismos deciden asignarsela segun corresponda."><input type="radio" name="asignacion" value="autoservicio" <?= $tarea->asignacion == 'autoservicio' ? 'checked' : '' ?> /> Auto Servicio</label>
                    <label class="radio" rel="tooltip" title="Ingresar el id de usuario a quien se le va asignar. Se puede ingresar una variable que haya almacenado esta información. Ej: @@usuario_inical"><input type="radio" name="asignacion" value="usuario" <?= $tarea->asignacion == 'usuario' ? 'checked' : '' ?> /> Usuario</label>
                    <div id="optionalAsignacionUsuario" class="<?= $tarea->asignacion == 'usuario' ? '' : 'hide' ?>">
                        <input type="text" name="asignacion_usuario" value="<?= $tarea->asignacion_usuario ?>" placeholder='Ej: @@id' />
                    </div>
                    <br />
                    <label class="checkbox"><input type="checkbox" name="asignacion_notificar" value="1" <?= $tarea->asignacion_notificar ? 'checked' : '' ?> /> Notificar vía correo electrónico al usuario asignado.</label>
                </div>
                <div class="tab-pane" id="tab3">
                    <script type="text/javascript">
                        $(document).ready(function() {
                            $("input[name=acceso_modo]").change(function() {
                                if (this.value == "grupos_usuarios") {
                                    $("#optionalGruposUsuarios").removeClass("hide");
                                } else {
                                    $("#optionalGruposUsuarios").addClass("hide");
                                }
                            });

                            $(".reglas").blur(function() {
                                var input = this;
                                console.log("$rule: " + $(this).val());
                                $.ajax({
                                    url: '<?=site_url('backend/configuracion/ajax_get_validacion_reglas')?>',
                                    data: {
                                        rule:  $(input).val(),
                                        proceso_id: <?= $proceso_id ?>
                                    },
                                    dataType: "json",
                                    cache: false,
                                    success: function(data) {
                                        console.log("$data.mensaje: " + data.mensaje);
                                        if (data.code == 200) {
                                            $(input).parent().find(".message").html(data.mensaje);
                                            $(input).parent().find(".message").show();
                                        }
                                    }
                                });
                            }).focus(function() {
                                $(this).parent().find(".message").hide();
                            });
                        });
                    </script>
                    <label>Nivel de Acceso
                        <a href="/assets/ayuda/simple/backend/modelamiento-del-proceso/disenador.html#pestana_usuarios" target="_blank">
                            <span class="glyphicon glyphicon-info-sign"></span>
                        </a>
                    </label>
                    <label class='radio'><input type="radio" name="acceso_modo" value="publico" <?= $tarea->acceso_modo == 'publico' ? 'checked' : '' ?> /> Cualquier persona puede acceder.</label>
                    <label class='radio'><input type="radio" name="acceso_modo" value="registrados" <?= $tarea->acceso_modo == 'registrados' ? 'checked' : '' ?> /> Sólo los usuarios registrados.</label>
                    <label class='radio'><input type="radio" name="acceso_modo" value="claveunica" <?= $tarea->acceso_modo == 'claveunica' ? 'checked' : '' ?> /> Sólo los usuarios registrados con ClaveUnica.</label>
                    <label class='radio'><input type="radio" name="acceso_modo" value="grupos_usuarios" <?= $tarea->acceso_modo == 'grupos_usuarios' ? 'checked' : '' ?> /> Sólo los siguientes grupos de usuarios pueden acceder.</label>
                    <div id="optionalGruposUsuarios" style="height: 300px;" class="<?= $tarea->acceso_modo == 'grupos_usuarios' ? '' : 'hide' ?>">
                        <select id="selectGruposUsuarios" class="input-xlarge" name="grupos_usuarios[]" multiple>
                            <?php foreach($tarea->Proceso->Cuenta->GruposUsuarios as $g):?>
                                <option value="<?=$g->id?>" <?=in_array($g->id,explode(',',$tarea->grupos_usuarios))?'selected':''?>><?=$g->nombre?></option>
                            <?php endforeach ?>
                            <?php foreach(explode(',',$tarea->grupos_usuarios) as $g): ?>
                                <?php if(!is_numeric($g)): ?>
                                <option selected><?=$g?></option>
                                <?php endif ?>
                            <?php endforeach ?>
                        </select>
                        <div class='help-block'>Puede incluir variables usando @@. Las variables deben contener el numero id del grupo de usuarios.</div>
                    </div>
                </div>
                <div class="tab-pasos tab-pane" id="tab4">
                    <table class="table">
                        <thead>
                            <tr class="form-agregar-paso">
                                <td></td>
                                <td>
                                    <select class="pasoFormulario">
                                        <?php foreach ($formularios as $f): ?>
                                            <option value="<?= $f->id ?>"><?= $f->nombre ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input class="pasoRegla reglas" type="text" placeholder="Escribir regla condición aquí" />
                                    <a href="/assets/ayuda/simple/backend/modelamiento-del-proceso/reglas-de-negocio-y-reglas-de-validacion.html" target="_blank">
                                        <span class="glyphicon glyphicon-info-sign"></span>
                                    </a>
                                    <p class="message" style="color: red; display: block;"></p>
                                </td>
                                <td>
                                    <select class="pasoModo input-small">
                                        <option value="edicion">Edición</option>
                                        <option value="visualizacion">Visualización</option>
                                    </select>
                                </td>
                                <td>
                                    <button type="button" class="btn" title="Agregar"><i class="icon-plus"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <th>#</th>
                                <th>Formulario</th>
                                <th>Condición</th>
                                <th>Modo</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tarea->Pasos as $key => $p): ?>
                                <tr>
                                    <td><?= $key + 1 ?></td>
                                    <td><a title="Editar" target="_blank" href="<?= site_url('backend/formularios/editar/' . $p->Formulario->id) ?>"><?= $p->Formulario->nombre ?></a></td>
                                    <td><?= $p->regla ?></td>
                                    <td><?= $p->modo ?></td>
                                    <td>
                                        <input type="hidden" name="pasos[<?= $key + 1 ?>][id]" value="<?= $p->id ?>" />
                                        <input type="hidden" name="pasos[<?= $key + 1 ?>][formulario_id]" value="<?= $p->formulario_id ?>" />
                                        <input type="hidden" name="pasos[<?= $key + 1 ?>][regla]" value="<?= $p->regla ?>" />
                                        <input type="hidden" name="pasos[<?= $key + 1 ?>][modo]" value="<?= $p->modo ?>" />
                                        <a class="delete" title="Eliminar" href="#"><i class="icon-remove"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <label class="checkbox"><input type="checkbox" name="paso_confirmacion" value="1" <?=$tarea->paso_confirmacion?'checked':''?> > Incluir último paso de confirmación antes de avanzar la tarea.
                        <a href="/assets/ayuda/simple/backend/modelamiento-del-proceso/disenador.html#pestana_pasos" target="_blank">
                            <span class="glyphicon glyphicon-info-sign"></span>
                        </a>
                    </label>
                </div>
                <div class="tab-eventos tab-pane" id="tab5">
                    <table class="table">
                        <thead>
                            <tr class="form-agregar-evento">
                                <td></td>
                                <td>
                                    <select class="eventoAccion input-medium">
                                        <?php foreach ($acciones as $f): ?>
                                            <option value="<?= $f->id ?>"><?= $f->nombre ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input class="eventoRegla input-medium reglas" type="text" placeholder="Escribir regla condición" />
                                    <p class="message" style="color: red; display: block;"></p>
                                </td>
                                <td>
                                    <select class="eventoInstante input-small">
                                        <option value="antes">Antes</option>
                                        <option value="despues">Después</option>
                                    </select>
                                </td>
                                <td>
                                    <select class="eventoPasoId input-medium">
                                        <option value="">Ejecutar Tarea</option>
                                        <?php foreach ($tarea->Pasos as $p): ?>
                                        <option value="<?=$p->id?>" title="<?=$p->Formulario->nombre?>">Ejecutar Paso <?=$p->orden?></option>
                                        <?php endforeach ?>
                                        <?php foreach ($tarea->EventosExternos as $ee): ?>
                                        <option value="<?=$ee->id?>" title="<?=$ee->nombre?>">Evento Externo <?=$ee->nombre?></option>
                                        <?php endforeach ?>
                                    </select>
                                </td>
                                <td>
                                    <button type="button" class="btn" title="Agregar"><i class="icon-plus"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <th>#</th>
                                <th>Accion</th>
                                <th>Condición</th>
                                <th>Instante</th>
                                <th>Momento</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tarea->Eventos as $key => $p): ?>
                                <tr>
                                    <td><?= $key + 1 ?></td>
                                    <td><a title="Editar" target="_blank" href="<?= site_url('backend/acciones/editar/' . $p->Accion->id) ?>"><?= $p->Accion->nombre ?></a></td>
                                    <td><?= $p->regla ?></td>
                                    <td><?= $p->instante ?></td>
                                    <td><?=$p->paso_id?'<abbr title="'.$p->Paso->Formulario->nombre.'">Ejecutar Paso '.$p->Paso->orden.'</abbr>': ($p->evento_externo_id?'<abbr title="'.$p->EventoExterno->nombre.'">Evento Externo '.$p->EventoExterno->nombre.'</abbr>' : 'Ejecutar Tarea')?></td>
                                    <td>
                                        <input type="hidden" name="eventos[<?= $key + 1 ?>][accion_id]" value="<?= $p->accion_id ?>" />
                                        <input type="hidden" name="eventos[<?= $key + 1 ?>][regla]" value="<?= $p->regla ?>" />
                                        <input type="hidden" name="eventos[<?= $key + 1 ?>][instante]" value="<?= $p->instante ?>" />
                                        <?php
                                            $paso_ee_id = !is_null($p->paso_id) ? $p->paso_id : $p->evento_externo_id;
                                        ?>
                                        <input type="hidden" name="eventos[<?= $key + 1 ?>][paso_id]" value="<?= $paso_ee_id ?>" />
                                        <a class="delete" title="Eliminar" href="#"><i class="icon-remove"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <label class="checkbox">Para mayor información puedes consultar en el siguiente enlace.
                        <a href="/assets/ayuda/simple/backend/modelamiento-del-proceso/disenador.html#pestana_eventos" target="_blank">
                            <span class="glyphicon glyphicon-info-sign"></span>
                        </a>
                    </label>
                </div>
                <div class="tab-pane" id="tab6">
                    <script>
                        $(document).ready(function(){
                            $("input[name=vencimiento]").change(function(){
                                if(this.checked)
                                    $("#vencimientoConfig").show();
                                else
                                    $("#vencimientoConfig").hide();
                            }).change();

                            $("select[name=vencimiento_unidad]").change(function(){
                                if(this.value=="D")
                                    $("#habilesConfig").show();
                                else
                                    $("#habilesConfig").hide();
                            }).change();
                        });
                    </script>
                    <label class="checkbox">
                        <input type="checkbox" name="vencimiento" value="1" <?=$tarea->vencimiento?'checked':''?> /> ¿La etapa tiene vencimiento?
                        <a href="/assets/ayuda/simple/backend/modelamiento-del-proceso/disenador.html#pestana_vencimiento" target="_blank">
                            <span class="glyphicon glyphicon-info-sign"></span>
                        </a>
                    </label>


                    <div id="vencimientoConfig" class="hide" style="margin-left: 20px;">
                        La etapa se vencera
                        <input type="text" name="vencimiento_valor" class="input-mini" value="<?= $tarea->vencimiento_valor?$tarea->vencimiento_valor:5 ?>" />
                        <select name="vencimiento_unidad" class="input-small">
                            <option value="D" <?= $tarea->vencimiento_unidad == 'D' ? 'selected' : '' ?>>día/s</option>
                            <option value="W" <?= $tarea->vencimiento_unidad == 'W' ? 'selected' : '' ?>>semana/s</option>
                            <option value="M" <?= $tarea->vencimiento_unidad == 'M' ? 'selected' : '' ?>>mes/es</option>
                            <option value="Y" <?= $tarea->vencimiento_unidad == 'Y' ? 'selected' : '' ?>>año/s</option>
                        </select>
                        despues de completada la etapa anterior.
                        <br />
                        <label id='habilesConfig' class='checkbox'><input type='checkbox' name='vencimiento_habiles' value='1' <?=$tarea->vencimiento_habiles?'checked':''?> /> Considerar solo días habiles.</label>

                        <label class="checkbox"><input type="checkbox" name="vencimiento_notificar" value="1" <?=$tarea->vencimiento_notificar?'checked':''?> /> Notificar cuando quede <input class="input-mini" type="text" name="vencimiento_notificar_dias" value="<?=$tarea->vencimiento_notificar_dias?>" /> día al siguiente correo:</label>
                         <input style="margin-left: 20px;" type="text" name="vencimiento_notificar_email" placeholder="ejemplo@mail.com" value="<?=$tarea->vencimiento_notificar_email?>" />
                         <div style="margin-left: 20px;" class="help-block">Tambien se pueden usar variables. Ej: @@email</div>
                    </div>
                </div>
                <div class="tab-eventos-externos tab-pane" id="tab7">
                    <script type="text/javascript">
                        $(document).ready(function(){
                            $("input[name=almacenar_usuario]").click(function(){
                                if(this.checked)
                                    $("#optionalAlmacenarUsuario").removeClass("hide");
                                else
                                    $("#optionalAlmacenarUsuario").addClass("hide");
                            });

                            $("input[name=externa]").click(function(){
                                if(this.checked)
                                    $("#optionalTareaExterna").removeClass("hide");
                                else
                                    $("#optionalTareaExterna").addClass("hide");
                            });
                        });
                    </script>
                    <label><input type="checkbox" name="almacenar_usuario" value="1" <?= $tarea->almacenar_usuario ? 'checked' : '' ?> /> ¿Almacenar el identificador del usuario que lleva a cabo esta tarea?

                        <a href="/assets/ayuda/simple/backend/modelamiento-del-proceso/disenador.html#pestana_otros" target="_blank">
                            <span class="glyphicon glyphicon-info-sign"></span>
                        </a>

                    </label>
                    <div id="optionalAlmacenarUsuario" class="<?= $tarea->almacenar_usuario ? '' : 'hide' ?>">
                        <label>Variable</label>
                        <div class="input-prepend">
                            <span class="add-on">@@</span><input type="text" name="almacenar_usuario_variable" value="<?= $tarea->almacenar_usuario_variable ?>" />
                        </div>
                    </div>
                    <label><input type="checkbox" name="externa" value="1" <?= $tarea->externa ? 'checked' : '' ?> /> ¿Tarea externa?</label>
                    <div id="optionalTareaExterna" class="<?= $tarea->externa ? '' : 'hide' ?>">
                    <table class="table">
                        <thead>
                            <tr class="form-agregar-evento-externo">
                                <td>
                                    <input class="eventoExterno input-medium" id="nombre" type="text" placeholder="Nombre" />
                                </td>
                                <td>
                                    <select class="eventoSentido input-small">
                                        <option value="GET">GET</option>
                                        <option value="POST">POST</option>
                                        <option value="PUT">PUT</option>
                                    </select>
                                </td>
                                <td>
                                    <input class="input-medium" id="url" type="text" placeholder="URL" />
                                </td>
                                <td>
                                    <textarea class="input-big" name="mensaje" id="mensaje" placeholder="Mensaje"></textarea>
                                </td>
                                <td>
                                    <input class="input-medium" id="regla" name="regla" type="text" placeholder="Condición" />
                                </td>
                                <td>
                                    <input class="input-big" id="opciones" name="opciones" type="text" placeholder="Opciones" />
                                </td>
                                <td>
                                    <button type="button" class="btn" title="Agregar"><i class="icon-plus"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <th>#</th>
                                <th>Nombre</th>
                                <th>Metodo</th>
                                <th>URL</th>
                                <th>Mensaje</th>
                                <th>Condición</th>
                                <th>Opciones</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tarea->EventosExternos as $key => $p): ?>
                                <tr>
                                    <td><?= $key + 1 ?></td>
                                    <td><?= $p->nombre ?></td>
                                    <td><?= $p->metodo ?></td>
                                    <td><?= $p->url ?></td>
                                    <td><?= $p->mensaje ?></td>
                                    <td><?= $p->regla ?></td>
                                    <td><?= $p->opciones ?></td>
                                    <td>
                                        <input type="hidden" name="eventos_externos[<?= $key + 1 ?>][id]" value="<?= $p->id ?>" />
                                        <input type="hidden" name="eventos_externos[<?= $key + 1 ?>][nombre]" value="<?= $p->nombre ?>" />
                                        <input type="hidden" name="eventos_externos[<?= $key + 1 ?>][metodo]" value="<?= $p->metodo ?>" />
                                        <input type="hidden" name="eventos_externos[<?= $key + 1 ?>][url]" value="<?= $p->url ?>" />
                                        <input type="hidden" name="eventos_externos[<?= $key + 1 ?>][mensaje]" value="<?= htmlspecialchars($p->mensaje) ?>" />
                                        <input type="hidden" name="eventos_externos[<?= $key + 1 ?>][regla]" value="<?= $p->regla ?>" />
                                        <input type="hidden" name="eventos_externos[<?= $key + 1 ?>][opciones]" value="<?= $p->opciones ?>" />
                                        <a class="delete" title="Eliminar" href="#"><i class="icon-remove"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
                <div class="tab-datos-expuestos tab-pane" id="tab8">
                    <script>
                        function selectAll(){
                            $("#seleccionados").find("*").prop("selected",true);
                        }

                        function SelectAllFunction(){
                            if($("input[name=SelectAll]:checked").val()){
                                $(".SelectAll").prop('checked',true);
                            }else{
                                $(".SelectAll").prop('checked',false);
                            }
                        }

                        function seleccionarForm(id){
                            if($("input[name="+id+"]:checked").val()){
                                $("."+id).prop('checked',true);
                            }else{
                                $("."+id).prop('checked',false);
                            }
                        }
                    </script>

                    <div class="row-fluid">
                        <div class="span6"><h5>Variables de formulario
                            <a href="/assets/ayuda/simple/backend/modelamiento-del-proceso/disenador.html#pestana_datos" target="_blank">
                                <span class="glyphicon glyphicon-info-sign"></span>
                            </a></h5></div>
                        <div class="span6"><h5>Variables de proceso</h5></div>
                    </div>
                    <div class="row-fluid">
                        <div class="span6" style="overflow-y: auto; height:280px;width:48%;border:.5px solid;border-radius: 5px;border-color:#DDDDDD;">
                            <?
                                $formularios = array();
                                $nameform = array();
                                foreach($variablesFormularios as $key => $valuesAry){
                                   $var = $valuesAry[nombre_formulario];
                                   if(!in_array($var, $nameform)){
                                      $nameform[] = $var;
                                   }
                                   $formIndex = array_search($var, $nameform);
                                   $formularios[$formIndex][] = $valuesAry;
                                }

                                foreach ($formularios as $key => $res) {
                                    $id=$key;
                                    ?>&nbsp;<input type="checkbox" onclick="seleccionarForm(<? echo $id; ?>)" name="<? echo $id; ?>" id="<? echo $id; ?>" value="<? echo $id; ?>"/>&nbsp;<b><? echo $res[0]['nombre_formulario'];?></b><br><?
                                    foreach ($res as $d){
                                        ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" class="<? echo $id ?>" name="varForm[]" id="<? echo $d['variable_id'] ?>" value="<? echo $d['variable_id'] ?>">&nbsp;<? echo $d['nom_variables']; ?><br><?
                                        if ($d['exponer_campo']==1){?>
                                            <script type="text/javascript">
                                            $("#"+<? echo $d['variable_id'] ?>).prop('checked',true);
                                            </script>
                                        <?}
                                    }
                                }
                            ?>
                        </div>
                        <div class="span6" style="overflow-y: auto; height:280px;width:47%;border: 0.5px solid;border-radius: 5px;border-color:#DDDDDD;">
                            <?php
                                $count=count($variablesProcesos); 
                                if($count>0){
                                    ?>&nbsp;<input type="checkbox" onclick="SelectAllFunction();" name="SelectAll" value="0">&nbsp;<b>All</b><br><?
                                    foreach ($variablesProcesos as $res) {
                                        $variables = json_decode($res['extra']);
                                        $variables = get_object_vars($variables);
                                        ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" class="SelectAll" name="varPro[]" id="var<? echo $res['variable_id'] ?>" value="<? echo $res['variable_id'] ?>">&nbsp;<? echo $variables['variable'];echo " "; echo $res['exponer_varible'];  ?><br><?
                                        if ($res['exponer_variable']==1){?>
                                                <script type="text/javascript">
                                                    $("#var"+<? echo $res['variable_id'] ?>).prop('checked',true);
                                                </script>
                                            <?}
                                    }
                                }
                            ?>
                        </div>
                    </div>
                </div>



                <div class="tab-datos-expuestos tab-pane" id="tab9">
                    <div class="row-fluid">
                        <div class="span12"><h5>Cuentas a las que desea dar acceso para iniciar este proceso
                                <a href="/assets/ayuda/simple/backend/modelamiento-del-proceso/disenador.html#pestana_datos" target="_blank">
                                    <span class="glyphicon glyphicon-info-sign"></span>
                                </a></h5></div>
                    </div>
                    <div class="row-fluid">
                        <div class="span12" style="overflow-y: auto; height:280px;width:96%;border:.5px solid;border-radius: 5px;border-color:#DDDDDD;">
                            <div class="campo control-group">
                            <label class="control-label">Suscriptores:</label>
                            <div class="controls">
                                <?foreach ($cuentas as $cuenta) {?>
                                <label class="checkbox">
                                    <input type="checkbox" name="cuentas_con_permiso[]" id="cuenta_<? echo $cuenta->id; ?>" value="<? echo $cuenta->id; ?>">
                                    <? echo $cuenta->nombre;?>
                                </label><?
                                    foreach ($cuentas_con_permiso as $cuenta_permiso) {
                                        if($cuenta_permiso["id"] == $cuenta->id){?>
                                            <script type="text/javascript">
                                                $("#cuenta_"+<? echo $cuenta->id; ?>).prop('checked',true);
                                            </script>
                                        <?
                                        break;
                                        }
                                    }
                                }
                            ?>
                            </div>
                            </div>
                        </div>
                    </div>
                </div>


            </div>
        </div>
    </form>
</div>
<div class="modal-footer">
    <a href="<?= site_url('backend/procesos/eliminar_tarea/' . $tarea->id) ?>" class="btn btn-danger pull-left" onclick="return confirm('¿Esta seguro que desea eliminar esta tarea?')">Eliminar</a>
    <a href="#" data-dismiss="modal" class="btn">Cerrar</a>
    <a href="#" onclick="javascript:$('#formEditarTarea').submit();return false;" class="btn btn-primary">Guardar</a>
</div>
