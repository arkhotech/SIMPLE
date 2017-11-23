<script>
    function descargarDocumentos(tramiteId) {
        $("#modal").load(site_url + "etapas/descargar/" + tramiteId);
        $("#modal").modal();
        return false;
    }

    $(document).ready(function() {
        $('#select_all').click(function(event) {
            var checked = [];
            $('#tramites').val();
            if (this.checked) {
                $('.checkbox1').each(function() {
                    this.checked = true;
                });
            } else {
                $('.checkbox1').each(function() {
                    this.checked = false;
                });
            }
            $('#tramites').val(checked);
        });
    });

    function descargarSeleccionados() {
        var numberOfChecked = $('.checkbox1:checked').length;
        if (numberOfChecked == 0) {
            alert('Debe seleccionar al menos un trámite');
            return false;
        } else {
            var checked = [];
            $('.checkbox1').each(function() {
                if ($(this).is(':checked')) {
                    checked.push(parseInt($(this).val()));  
                }
            });
            $('#tramites').val(checked);
            var tramites = $('#tramites').val();
            $("#modal").load(site_url + "etapas/descargar/" +vtramites);
            $("#modal").modal();
            return false;  
        }
    }
</script>

<div class="col-xs-12 col-md-5">
    <h2>Etapas sin asignar</h2>
</div>
<div class="col-xs-12 col-md-7">
    <!--buscador-->
    <form class="form-search" method="GET" action="<?= current_url() ?>">
        <div class="search-form">
            <input name="query" class="search-form_input" placeholder="Escribe aquí lo que deseas buscar" type="text" value="<?= $query?>">
            <button class="search-form_button search-form_button--submit" type="submit"><i class="icon-search"></i></button>
        </div>
    </form>
</div>

<div class="col-xs-12 col-md-12">
    <?php if (count($etapas) > 0): ?>

    <table id="mainTable" class="table">
        <thead>
            <tr>
                <th></th>
                <th>Nro</th>
                <th>Ref.</th>
                <th>Nombre</th>
                <th>Etapa</th>
                <th>Modificación</th>
                <th>Vencimiento</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php $registros=false; ?>
            <?php foreach ($etapas as $e): ?>

                <?php
                    $file = false;
                    if (Doctrine::getTable('File')->findByTramiteId($e->Tramite->id)->count() > 0) {
                        $file = true;
                        $registros=true;
                    }
                ?>

                <tr <?=$e->getPrevisualizacion()?'data-toggle="popover" data-html="true" data-title="<h4>Previsualización</h4>" data-content="'.htmlspecialchars($e->getPrevisualizacion()).'" data-trigger="hover" data-placement="bottom"':''?>>
                    <?php if (Cuenta::cuentaSegunDominio()->descarga_masiva): ?>
                        <?php if ($file): ?>
                            <td><div class="checkbox"><label><input type="checkbox" class="checkbox1" name="select[]" value="<?=$e->Tramite->id?>"></label></div></td>
                        <?php else: ?>
                            <td></td>
                        <?php endif; ?>
                        <?php else: ?>
                            <td></td>
                        <?php endif; ?>
                    <td><?=$e->Tramite->id?></td>
                    <td class="name"><?//= $e->Tramite->Proceso->nombre ?>
                        <?php
                            $t = Doctrine::getTable('Tramite')->find($e->Tramite->id); 
                            $tramite_nro = '';
                            foreach ($t->getValorDatoSeguimiento() as $tra_nro) {
                            if ($tra_nro->nombre == 'tramite_ref') {
                                    $tramite_nro = $tra_nro->valor;
                                }                              
                            }                         
                            echo $tramite_nro != '' ? $tramite_nro : $e->Tramite->Proceso->nombre;
                        ?>
                    </td>
                    <td class="name">
                        <?php 
                            $tramite_descripcion ='';
                            foreach ($t->getValorDatoSeguimiento() as $tra) {
                                if ($tra->nombre == 'tramite_descripcion') {
                                    $tramite_descripcion = $tra->valor;
                                }  
                            }
                            echo $tramite_descripcion != '' ? $tramite_descripcion : $e->Tramite->Proceso->nombre;
                        ?>
                    </td>
                    <td><?=$e->Tarea->nombre ?></td>
                    <td class="time"><?= strftime('%d.%b.%Y',mysql_to_unix($e->updated_at))?><br /><?= strftime('%H:%M:%S',mysql_to_unix($e->updated_at))?></td>
                    <td><?=$e->vencimiento_at?strftime('%c',strtotime($e->vencimiento_at)):'N/A'?></td>
                    <td class="actions">
                        <a href="<?=site_url('etapas/asignar/'.$e->id)?>" class="btn btn-primary"><i class="icon-check icon-white"></i> Asignármelo</a>
                        <?php if (Cuenta::cuentaSegunDominio()->descarga_masiva): ?>
                        <?php if ($file): ?>
                        <a href="#" onclick="return descargarDocumentos(<?=$e->Tramite->id?>);" class="btn btn-success"><i class="icon-download icon-white"></i> Descargar</a>
                        <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (Cuenta::cuentaSegunDominio()->descarga_masiva): ?>
        <?php if ($registros): ?>
            <div class="pull-right">
                <div class="checkbox">
                    <input type="hidden" id="tramites" name="tramites" />
                    <label>
                        <input type="checkbox" id="select_all" name="select_all" /> Seleccionar todos
                        <a href="#" onclick="return descargarSeleccionados();" class="btn btn-success preventDoubleRequest">
                            <i class="icon-download icon-white"></i> Descargar seleccionados
                        </a>
                    </label>
                </div>
            </div>
            <div class="modal hide fade" id="modal"></div>
        <?php endif; ?>
    <?php endif; ?>
        <p><?= $links ?></p>
    <?php else: ?>
        <p>No hay trámites para ser asignados.</p>
    <?php endif; ?>
</div>