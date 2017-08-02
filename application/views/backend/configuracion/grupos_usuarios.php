<div class="row-fluid">
    
    <div class="span3">
        <?php $this->load->view('backend/configuracion/sidebar') ?>
    </div>
    <div class="span9">
        <ul class="breadcrumb">
            <li>
                <a href="<?=site_url('backend/configuracion')?>">Configuración</a> <span class="divider">/</span>
            </li>
            <li class="active">Grupos de Usuarios
                <a href="/assets/ayuda/simple/backend/configuracion/grupos.html" target="_blank">
                    <span class="glyphicon glyphicon-info-sign"></span>
                </a>
            </li>
        </ul>
        
        <p><a class="btn btn-success" href="<?=site_url('backend/configuracion/grupo_usuarios_editar')?>"><i class="icon-file icon-white"></i> Nuevo</a></p>
        
        <table class="table">
            <tr>
                <th>Id</th>
                <th>Nombre</th>
                <th>Usuarios</th>
                <th>Acciones</th>
            </tr>
            <?php foreach($grupos_usuarios as $u): ?>
            <tr>
                <td><?=$u->id?></td>
                <td><?=$u->nombre?></td>
                <td>
                    <?php
                    $tmp=array();
                    foreach($u->Usuarios as $g)
                        $tmp[]=$g->usuario;
                    echo implode(', ', $tmp);
                    ?>
                </td>
                <td>
                    <a class="btn btn-primary" href="<?=site_url('backend/configuracion/grupo_usuarios_editar/'.$u->id)?>"><i class="icon-edit icon-white"></i> Editar</a>
                    <a class="btn btn-danger" href="<?=site_url('backend/configuracion/grupo_usuarios_eliminar/'.$u->id)?>" onclick="return confirm('¿Está seguro que desea eliminar?')"><i class="icon-remove icon-white"></i> Eliminar</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>