<ul class="breadcrumb">
    <li class="active"><?=$title?></li>
</ul>

<p><a class="btn btn-primary" href="<?=site_url('manager/categorias/editar')?>">Crear Categoría</a></p>

<table class="table">
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Descripción</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($categorias as $c):?>
        <tr>
            <td><?=$c->nombre?></td>
            <td><?=$c->descripcion?></td>
            <td>
                <a class="btn btn-primary" href="<?=site_url('manager/categorias/editar/'.$c->id)?>"><i class="icon-edit icon-white"></i> Editar</a>
                <a class="btn btn-danger" href="<?=site_url('manager/categorias/eliminar/'.$c->id)?>" onclick="return confirm('¿Está seguro que desea eliminar esta categoria?')"><i class="icon-edit icon-white"></i> Eliminar</a>
            </td>
        </tr>
        <?php endforeach ?>
    </tbody>
</table>