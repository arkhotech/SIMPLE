<script src="<?= base_url() ?>assets/js/modelador-seguridad.js" type="text/javascript"></script>

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
    <li><a href="<?=site_url('backend/acciones/listar/'.$proceso->id)?>">acciones</a></li>
    <li class="active"><a href="<?= site_url('backend/Admseguridad/listar/' . $proceso->id) ?>">Seguridad</a></li>
</ul>

<a class="btn btn-success" href="<?=site_url('backend/Admseguridad/crear/'.$proceso->id)?>"><i class="icon-white icon-file"></i> Nuevo</a>
<a href="/assets/ayuda/simple/backend/modelamiento-del-proceso/acciones.html#webservice-seguridad" target="_blank">
    <span class="glyphicon glyphicon-info-sign"></span>
</a>
<table class="table">
    <thead>
        <tr>
            <th>Institución</th>
            <th>Servicio</th>
            <th>Tipo de seguridad</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($seguridad as $p): ?>

        <tr>
            <td><?=$p->institucion?></td>
            <td><?=$p->servicio?></td>
            <td><?=$p->extra->tipoSeguridad?></td>
            <td>
                <a href="<?=site_url('backend/Admseguridad/editar/'.$p->id)?>" class="btn btn-primary"><i class="icon-white icon-edit"></i> Editar</a>
                <a href="<?=site_url('backend/Admseguridad/eliminar/'.$p->id)?>" class="btn btn-danger" onclick="return confirm('¿Esta seguro que desea eliminar?')"><i class="icon-white icon-trash"></i> Eliminar</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table> 