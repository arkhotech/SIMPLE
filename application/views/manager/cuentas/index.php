<ul class="breadcrumb">
    <li class="active"><?=$title?></li>
</ul>

<p><a class="btn btn-primary" href="<?=site_url('manager/cuentas/editar')?>">Crear Cuenta</a></p>

<table class="table">
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Nombre largo</th>
            <th><center>Ambiente</center></th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($cuentas as $c):?>
        <tr>
            <td><?=$c->nombre?></td>
            <td><?=$c->nombre_largo?></td>
            <td><center><span class="badge"><?=strtoupper($c->ambiente)?></span></center></td>
            <td>
                <a class="btn btn-primary" href="<?=site_url('manager/cuentas/editar/'.$c->id)?>"><i class="icon-edit icon-white"></i> Editar</a>
                <a class="btn btn-danger" href="<?=site_url('manager/cuentas/eliminar/'.$c->id)?>" onclick="return confirm('¿Está seguro que desea eliminar esta cuenta?')"><i class="icon-edit icon-white"></i> Eliminar</a>
            </td>
        </tr>
        <?php endforeach ?>
    </tbody>
</table>