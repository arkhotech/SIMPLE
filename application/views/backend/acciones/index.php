<script src="<?= base_url() ?>assets/js/modelador-acciones.js" type="text/javascript"></script>

<ul class="breadcrumb">
    <li>
        <a href="<?=site_url('backend/procesos')?>">Listado de Procesos</a> <span class="divider">/</span>
    </li>
    <li class="active"><?=$proceso->nombre?></li>
</ul>

<ul class="nav nav-tabs">
    <li><a href="<?=site_url('backend/procesos/editar/'.$proceso->id)?>">Diseñador</a></li>
    <li><a href="<?=site_url('backend/formularios/listar/'.$proceso->id)?>">Formularios</a></li>
    <li><a href="<?= site_url('backend/documentos/listar/' . $proceso->id) ?>">Documentos</a></li>
    <li class="active"><a href="<?=site_url('backend/acciones/listar/'.$proceso->id)?>">Acciones</a></li>
    <li><a href="<?= site_url('backend/Admseguridad/listar/' . $proceso->id) ?>">Seguridad</a></li>
    <li><a href="<?= site_url('backend/suscriptores/listar/' . $proceso->id) ?>">Suscriptores Externos</a></li>
</ul>

<a class="btn btn-success" href="#" onclick="return seleccionarAccion(<?=$proceso->id?>);"><i class="icon-white icon-file"></i> Nuevo</a>
<a class="btn btn-default" href="#modalImportarAccion" data-toggle="modal" ><i class="icon-upload icon"></i> Importar</a>

<table class="table">
    <thead>
        <tr>
            <th>Accion
                <a href="/assets/ayuda/simple/backend/modelamiento-del-proceso/acciones.html" target="_blank">
                    <span class="glyphicon glyphicon-info-sign"></span>
                </a>
            </th>
            <th>Tipo</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($acciones as $p): ?>
        <tr>
            <td><?=$p->nombre?></td>
            <td><?=$p->tipo?></td>
            <td>
                <a href="<?=site_url('backend/acciones/editar/'.$p->id)?>" class="btn btn-primary"><i class="icon-white icon-edit"></i> Editar</a>
                <a class="btn btn-default" href="<?=site_url('backend/acciones/exportar/'.$p->id)?>"><i class="icon icon-share"></i> Exportar</a>
                <a href="<?=site_url('backend/acciones/eliminar/'.$p->id)?>" class="btn btn-danger" onclick="return confirm('¿Esta seguro que desea eliminar?')"><i class="icon-white icon-trash"></i> Eliminar</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table> 

<div id="modalImportarAccion" class="modal hide fade">
    <form method="POST" enctype="multipart/form-data" action="<?=site_url('backend/acciones/importar')?>">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h3>Importar Accion</h3>
    </div>
    <div class="modal-body">
        <p>Cargue a continuación el archivo .simple donde exportó su acción.</p>
        <input type="file" name="archivo" />
        <input type="hidden" name="proceso_id" value="<?= $proceso->id ?>" />
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
        <button type="submit" class="btn btn-primary">Importar</button>
    </div>
    </form>
</div>
<div id="modal" class="modal hide fade"></div>