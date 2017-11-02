<script>
    function eliminarProceso(procesoId) {
        $("#modal").load(site_url + "backend/procesos/ajax_auditar_eliminar_proceso/" + procesoId);
        $("#modal").modal();
        return false;
    }

    function activarProceso(procesoId) {
        $("#modal").load(site_url + "backend/procesos/ajax_auditar_activar_proceso/" + procesoId);
        $("#modal").modal();
        return false;
    }

    function mostrarEliminados() {
        $(".procesos_eliminados").slideToggle('slow', callbackEliminadosFn);
        return false;
    }

    function callbackEliminadosFn() {
        var $link = $("#link_eliminados");
        $(this).is(":visible") ? $link.text("Ocultar Eliminados «") : $link.text("Mostrar Eliminados »");
    }

    function publicarProceso(procesoId) {
        $("#modal").load(site_url + "backend/procesos/ajax_publicar_proceso/" + procesoId);
        $("#modal").modal();
        return false;
    }
</script>

<ul class="breadcrumb">
    <li>
        Listado de Procesos
    </li>
</ul>

<a class="btn btn-success" href="<?=site_url('backend/procesos/crear/')?>"><i class="icon-file icon-white"></i> Nuevo</a>
<a class="btn btn-default" href="#modalImportar" data-toggle="modal" ><i class="icon-upload icon"></i> Importar</a>

<table class="table">
    <thead>
        <tr>
            <th>Proceso</th>
            <th>Estado</th>
            <th>Version</th>
            <th>Acciones
                <a href="/assets/ayuda/simple/backend/export-import.html" target="_blank">
                    <span class="glyphicon glyphicon-info-sign"></span>
                </a>
            </th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($procesos as $p): ?>
        <tr>
            <td><?=$p->nombre?></td>
            <td><?=$p->estado == 'public'?'Publicado':'Draft'?></td>
            <td><?=$p->version?></td>
            <td>
                <?php if($editar_proceso){ ?>
                    <a class="btn btn-primary" href="<?=site_url('backend/procesos/editar/'.$p->id)?>"><i class="icon-white icon-edit"></i> Editar</a>
                <?php } ?>
                <a class="btn btn-default" href="<?=site_url('backend/procesos/exportar/'.$p->id)?>"><i class="icon icon-share"></i> Exportar</a>
                <a class="btn btn-danger" href="#" onclick="return eliminarProceso(<?=$p->id?>);"><i class="icon-white icon-remove"></i> Eliminar</a>
                <?php if($p->estado == 'draft'){?>
                    <a class="btn btn-primary" href="#" onclick="return publicarProceso(<?=$p->id?>);"><i class="icon-white icon-edit"></i> Publicar</a>
                <?php }?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php if (sizeof($procesos_eliminados) > 0) { ?>
<a href="#" id="link_eliminados" onclick="return mostrarEliminados();">Mostrar Eliminados »</a>
<div class="procesos_eliminados">
    <table class="table">
        <thead>
            <tr>
                <th>Procesos Eliminados</th>
                <th>Version</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($procesos_eliminados as $pe): ?>
            <tr>
                <td><?=$pe->nombre?></td>
                <td><?=$pe->version?></td>
                <td>
                    <a class="btn btn-primary" href="#" onclick="return activarProceso(<?=$pe->id?>);"><i class="icon-white icon-share"></i> Activar</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php } ?>

<div id="modalImportar" class="modal hide fade">
    <form method="POST" enctype="multipart/form-data" action="<?=site_url('backend/procesos/importar')?>">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h3>Importar Proceso</h3>
    </div>
    <div class="modal-body">
        <p>Cargue a continuación el archivo .simple donde exportó su proceso.</p>
        <input type="file" name="archivo" />
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
        <button type="submit" class="btn btn-primary">Importar</button>
    </div>
    </form>
</div>

<div id="modal" class="modal hide fade"></div>
