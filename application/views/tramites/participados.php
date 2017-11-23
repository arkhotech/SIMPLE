<script>
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
    
    function descargarDocumentos(tramiteId) {
        $("#modal").load(site_url + "etapas/descargar/" + tramiteId);
        $("#modal").modal();

        return false;
    }

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
        $("#modal").load(site_url + "etapas/descargar/" + tramites);
        $("#modal").modal();
            return false;
        }
    }
</script>

<div class="col-xs-12 col-md-8">
    <h2>Solicitudes en que ha participado</h2>
</div>
<div class="col-xs-12 col-md-4">
    <!--buscador-->
    <form class="form-search" method="GET" action="<?= current_url() ?>">
        <div class="search-form">
            <input name="query" class="search-form_input" placeholder="Para buscar aqu&iacute;" type="text" value="<?= $query?>">
            <button class="search-form_button search-form_button--submit" type="submit"><i class="icon-search"></i></button>
        </div>
    </form>
</div>
<div class="col-xs-12 col-md-12">
<?php if (count($tramites) > 0): ?>
    <table id="mainTable" class="table">
        <thead>
            <tr>
                <th></th>
                <th>Nro</th>
                <th>Ref.</th>
                <th>Nombre</th>
                <th>Etapa</th>
                <th>Fecha Modificación</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php $registros = false; ?>
            <?php foreach ($tramites as $t): ?>
                <?php
                      $file = false;
                      if (Doctrine::getTable('File')->findByTramiteId($t->id)->count() > 0) {
                          $file = true;
                          $registros = true;
                      }
                ?>

                <tr>
                    <?php if (Cuenta::cuentaSegunDominio()->descarga_masiva): ?>
                      <?php if ($file): ?>
                      <td><div class="checkbox"><label><input type="checkbox" class="checkbox1" name="select[]" value="<?=$t->id?>"></label></div></td>
                      <?php else: ?>
                      <td></td>
                      <?php endif; ?>
                      <?php else: ?>
                      <td></td>
                    <?php endif; ?>
                    <td><?= $t->id ?></td>
                    <td class="name">
                        <?php
                            $tramite_nro = '';
                            foreach ($t->getValorDatoSeguimiento() as $tra_nro) {
                               if ($tra_nro->nombre == 'tramite_ref') {
                                    $tramite_nro = $tra_nro->valor;
                                }
                            }
                            echo $tramite_nro != '' ? $tramite_nro : $t->Proceso->nombre;
                        ?>
                    </td>
                    <td class="name">
                        <?php
                            $tramite_descripcion = '';
                            foreach ($t->getValorDatoSeguimiento() as $tra) {
                                if ($tra->nombre == 'tramite_descripcion') {
                                    $tramite_descripcion = $tra->valor;
                                }  
                            }
                            echo $tramite_descripcion != '' ? $tramite_descripcion : $t->Proceso->nombre;
                        ?>
                    </td>
                    <td>
                        <?php
                        $etapas_array = array();
                        foreach ($t->getEtapasActuales() as $e)
                            $etapas_array[] = $e->Tarea->nombre;
                        echo implode(', ', $etapas_array);
                        ?>
                    </td>
                    <td class="time"><?= strftime('%d.%b.%Y', mysql_to_unix($t->updated_at)) ?><br /><?= strftime('%H:%M:%S', mysql_to_unix($t->updated_at)) ?></td>
                    <td><?= $t->pendiente ? 'Pendiente' : 'Completado' ?></td>
                    <td class="actions">
                        <?php $etapas = $t->getEtapasParticipadas(UsuarioSesion::usuario()->id) ?>
                        <?php if (count($etapas) == 3e4354) : ?>
                            <a href="<?= site_url('etapas/ver/' . $etapas[0]->id) ?>">Historial</a>
                        <?php else: ?>
                            <div class="btn-group">
                                <a data-toggle="dropdown" href="#">Historial</a>
                                <ul class="dropdown-menu">
                                    <?php foreach ($etapas as $e): ?>
                                        <li><a href="<?= site_url('etapas/ver/' . $e->id) ?>"><?= $e->Tarea->nombre ?></a></li>
                                    <?php endforeach ?>
                                </ul>
                            </div>
                        <?php endif ?>
                        <?php if (Cuenta::cuentaSegunDominio()->descarga_masiva): ?>
                          <?php if ($file): ?>
                          <a href="#" onclick="return descargarDocumentos(<?=$t->id?>);">Descargar</a>
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
                        <a href="#" onclick="return descargarSeleccionados();" class="button preventDoubleRequest">Descargar seleccionados</a>
                    </label>
                </div>
            </div>
            <div class="modal hide fade" id="modal"></div>
        <?php endif; ?>
    <?php endif; ?>

    <p><?= $links ?></p>   
    <?php else: ?>
        <p>Ud no ha participado en tr&aacute;mites.</p>
    <?php endif; ?>
</div>