<script>
    function editarVencimiento(etapaId) {
        $("#modal").load(site_url + "backend/seguimiento/ajax_editar_vencimiento/" + etapaId);
        $("#modal").modal();
        return false;
    }

    function eliminarTramite(tramiteId){
        $("#modal").load(site_url + "backend/seguimiento/ajax_auditar_eliminar_tramite/" + tramiteId);
        $("#modal").modal();
        return false;

    }

    function borrarProceso(procesoId){
        $("#modal").load(site_url + "backend/seguimiento/ajax_auditar_limpiar_proceso/" + procesoId);
        $("#modal").modal();
        return false;
    }

    function toggleBusquedaAvanzada() {
        $("#busquedaAvanzada").slideToggle();
        return false;
    }
</script>

<ul class="breadcrumb">
    <li><a href="<?= site_url('backend/seguimiento/index') ?>">Seguimiento de Procesos</a></li> <span class="divider">/</span>
    <li class="active"><?= $proceso->nombre ?></li>
</ul>

<div class="row-fluid">
    <div class='pull-right'>
        <form class="form-search" method="GET" action="<?= current_url() ?>">
            <div class="input-append">
                <input name="query" value="<?= $query ?>" type="text" class="search-query" />
                <button type="submit" class="btn">Buscar</button>
            </div>
        </form>
        <div style='text-align: right;'><a href='#' onclick='toggleBusquedaAvanzada()'>Busqueda avanzada</a></div>
    </div>

    <?php // if(UsuarioBackendSesion::usuario()->rol!='seguimiento'):
        if(in_array( 'super',explode(',',UsuarioBackendSesion::usuario()->rol))):
    ?>
    <div class="btn-group pull-left">
        <a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
            Operaciones
            <span class="caret"></span>
        </a>

		<ul class="dropdown-menu">
            <li><a href="<?= site_url('backend/seguimiento/reset_proc_cont/' . $proceso->id) ?>" onclick="return confirm('¿Esta seguro que desea reiniciar el contador de Proceso?');">Reiniciar contador de Proceso</a></li>
            <?php if ($proceso->Cuenta->ambiente != 'prod'):?>
                <li><a href="<?= site_url('backend/seguimiento/borrar_proceso/' . $proceso->id) ?>" onclick="return borrarProceso(<?=$proceso->id?>);">Borrar todo</a></li>
            <?php endif ?>
        </ul>
    </div>
    <?php endif ?>
</div>

<div id='busquedaAvanzada' class='row-fluid' style='display: <?=$busqueda_avanzada?'block':'none'?>;'>
    <div class='span12'>
        <div class='well'>
            <form class='form-horizontal'>
                <input type='hidden' name='busqueda_avanzada' value='1' />
                <div class='row-fluid'>
                    <div class='span4'>
                        <div class='control-group'>
                            <label class='control-label'>Término a buscar</label>
                            <div class='controls'>
                                <input name="query" value="<?= $query ?>" type="text" class="search-query" />
                            </div>
                        </div>
                    </div>
                    <div class='span4'>
                        <div class='control-group'>
                            <label class='control-label'>Estado del trámite</label>
                            <div class='controls'>
                                <label class='radio'><input type='radio' name='pendiente' value='-1' <?= $pendiente == -1 ? 'checked' : '' ?>> Cualquiera</label>
                                <label class='radio'><input type='radio' name='pendiente' value='1' <?= $pendiente == 1 ? 'checked' : '' ?>> En curso</label>
                                <label class='radio'><input type='radio' name='pendiente' value='0' <?= $pendiente == 0 ? 'checked' : '' ?>> Completado</label>
                            </div>
                        </div>
                    </div>
                    <div class='span4'>
                        <div class='control-group'>
                            <label class='control-label'>Fecha de creación</label>
                            <div class='controls'>
                                <input type='text' name='created_at_desde' placeholder='Desde' class='datepicker input-small' value='<?= $created_at_desde ?>' />
                                <input type='text' name='created_at_hasta' placeholder='Hasta' class='datepicker input-small' value='<?= $created_at_hasta ?>' />
                            </div>
                        </div>
                        <div class='control-group'>
                            <label class='control-label'>Fecha de último cambio</label>
                            <div class='controls'>
                                <input type='text' name='updated_at_desde' placeholder='Desde' class='datepicker input-small' value='<?= $updated_at_desde ?>' />
                                <input type='text' name='updated_at_hasta' placeholder='Hasta' class='datepicker input-small' value='<?= $updated_at_hasta ?>' />
                            </div>
                        </div>
                    </div>
                </div>
                <hr />
                <div style='text-align: right;'><button type="submit" class="btn btn-primary">Buscar</button></div>

            </form>
        </div>
    </div>
</div>

<?= $this->pagination->create_links() ?>

<table class="table">
    <thead>
        <tr>
            <th><a href="<?= current_url() . '?query=' . $query . '&pendiente=' . $pendiente . '&created_at_desde=' . $created_at_desde . '&created_at_hasta=' . $created_at_hasta . '&updated_at_desde=' . $updated_at_desde . '&updated_at_hasta=' . $updated_at_hasta . '&order=id&direction=' . ($direction == 'asc' ? 'desc' : 'asc') ?>">Id <?= $order == 'id' ? $direction == 'asc' ? '<i class="icon-chevron-down"></i>' : '<i class="icon-chevron-up"></i>'  : '' ?></a></th>
            <th>Asignado a.</th>
            <th>Version</th>
            <th>Ref.</th>
            <th>Nombre</th>
            <th><a href="<?= current_url() . '?query=' . $query . '&pendiente=' . $pendiente . '&created_at_desde=' . $created_at_desde . '&created_at_hasta=' . $created_at_hasta . '&updated_at_desde=' . $updated_at_desde . '&updated_at_hasta=' . $updated_at_hasta . '&order=pendiente&direction=' . ($direction == 'asc' ? 'desc' : 'asc') ?>">Estado <?= $order == 'pendiente' ? $direction == 'asc' ? '<i class="icon-chevron-down"></i>' : '<i class="icon-chevron-up"></i>'  : '' ?></a></th>
            <th>Etapa actual</th>
            <th><a href="<?= current_url() . '?query=' . $query . '&pendiente=' . $pendiente . '&created_at_desde=' . $created_at_desde . '&created_at_hasta=' . $created_at_hasta . '&updated_at_desde=' . $updated_at_desde . '&updated_at_hasta=' . $updated_at_hasta . '&order=created_at&direction=' . ($direction == 'asc' ? 'desc' : 'asc') ?>">Fecha de creación <?= $order == 'created_at' ? $direction == 'asc' ? '<i class="icon-chevron-down"></i>' : '<i class="icon-chevron-up"></i>'  : '' ?></th>
            <th><a href="<?= current_url() . '?query=' . $query . '&pendiente=' . $pendiente . '&created_at_desde=' . $created_at_desde . '&created_at_hasta=' . $created_at_hasta . '&updated_at_desde=' . $updated_at_desde . '&updated_at_hasta=' . $updated_at_hasta . '&order=updated_at&direction=' . ($direction == 'asc' ? 'desc' : 'asc') ?>">Fecha de Último cambio <?= $order == 'updated_at' ? $direction == 'asc' ? '<i class="icon-chevron-down"></i>' : '<i class="icon-chevron-up"></i>'  : '' ?></a></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($tramites as $t): ?>
            <tr>
                <td><?= $t->id ?></td>
                <?php
                    $etapa_id = $t->getUltimaEtapa()->id;
                    $etapa = Doctrine::getTable ('Etapa')->find ($etapa_id);
                ?>
                <td><?= !$etapa->usuario_id ? 'Ninguno' : !$etapa->Usuario->registrado ? 'No registrado' : $etapa->Usuario->displayUsername() ?></td>
               <td><?=$t->Proceso->version?></td>
                <td class="name">
                 <?php 
                      $tramite_nro ='';
                      foreach ($t->getValorDatoSeguimiento() as $tra_nro){
                        if($tra_nro->nombre == 'tramite_ref'){
                              $tramite_nro = $tra_nro->valor;
                         }
                      }
                      echo $tramite_nro != '' ? $tramite_nro : 'N/A';
                ?>
                </td>
                <td class="name">
                 <?php
                      $tramite_descripcion ='';
                      foreach ($t->getValorDatoSeguimiento() as $tra){
                         if($tra->nombre == 'tramite_descripcion'){
                              $tramite_descripcion = $tra->valor;
                         }
                      }
                     echo $tramite_descripcion != '' ? $tramite_descripcion : 'N/A';
                ?>
                </td>

                <td><?= $t->pendiente ? 'En curso' : 'Completado' ?></td>
                <td>
                    <?php
                    $etapas_array = array();
                    foreach ($t->getEtapasActuales() as $e)
                        $etapas_array[] = $e->Tarea->nombre . ($e->vencimiento_at ? ' <a href="#" onclick="return editarVencimiento(' . $e->id . ')" title="Cambiar fecha de vencimiento">(' . $e->getFechaVencimientoAsString() . ')</a>' : '');
                    echo implode(', ', $etapas_array);
                    ?>
                </td>
                <td><?= strftime('%c', mysql_to_unix($t->created_at)) ?></td>
                <td><?= strftime('%c', mysql_to_unix($t->updated_at)) ?></td>
                <td style="text-align: right;">
                    <a class="btn btn-primary" href="<?= site_url('backend/seguimiento/ver/' . $t->id) ?>"><i class="icon-white icon-eye-open"></i> Seguimiento</a>
                    <?php // if(UsuarioBackendSesion::usuario()->rol!='seguimiento'):
                        if(in_array( 'super',explode(',',UsuarioBackendSesion::usuario()->rol))):
                    ?><a class="btn btn-danger" href="#" onclick="return eliminarTramite(<?=$t->id?>);"><i class="icon-white icon-trash"></i> Borrar</a><?php endif ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?= $this->pagination->create_links() ?>

<div id="modal" class="modal hide fade" >

</div>