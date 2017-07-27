<script src="<?= base_url() ?>assets/js/modelador-acciones.js" type="text/javascript"></script>

<ul class="breadcrumb">
    <li>
        <a href="<?=site_url('backend/reportes')?>">Gestión</a> <span class="divider">/</span>
    </li>
    <li class="active"><?=$proceso->nombre?></li>
</ul>
<? if(!in_array('gestion',explode(",",$rol)) ){ ?>
<a class="btn btn-success" href="<?=site_url('backend/reportes/crear/'.$proceso->id)?>"><i class="icon-file icon-white"></i> Nuevo</a>
<?}?>

<table class="table">
    <thead>
        <tr>
            <th>Reporte
            <a href="/assets/ayuda/simple/backend/modelamiento-del-proceso/reportes.html" target="_blank">
                <span class="glyphicon glyphicon-info-sign"></span>
            </a>
            </th>
            <th>Filtro</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($reportes as $p): ?>
        <tr>
            <td><?=$p->nombre?></td>
            <form method="GET" action="<?= site_url('backend/reportes/ver/'.$p->id.'?busqueda_avanzada=1&query=&pendiente=-1') ?>">
            <td>
                <input type='text' name='created_at_desde' placeholder='Desde' class='datepicker input-small' />
                <input type='text' name='created_at_hasta' placeholder='Hasta' class='datepicker input-small' />
            </td>
            <td>
                <button type="submit" class="btn btn-info"> <i class="icon-eye-open icon-white"></i> Ver </button>
                <button type="submit" name="formato" value="xls" class="btn btn-info"> <i class="icon-file icon-white"></i> XLS </button>
                <button type="submit" name="formato" value="pdf" class="btn btn-info"> <i class="icon-file icon-white"></i> PDF </button>
                <? if(!in_array('gestion',explode(",",$rol)) ){ ?>
                    <a href="<?=site_url('backend/reportes/editar/'.$p->id)?>" class="btn btn-primary"><i class="icon-edit icon-white"></i> Editar</a>
                    <a href="<?=site_url('backend/reportes/eliminar/'.$p->id)?>" class="btn btn-danger" onclick="return confirm('¿Esta seguro que desea eliminar?')"><i class="icon-remove icon-white"></i> Eliminar</a>
                <?}?>
            </td>
            </form>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="modal hide fade" id="modal"></div>