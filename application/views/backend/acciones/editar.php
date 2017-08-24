<ul class="breadcrumb">
    <li>
        <a href="<?= site_url('backend/procesos') ?>">Listado de Procesos</a> <span class="divider">/</span>
    </li>
    <li class="active"><?= $proceso->nombre ?></li>
</ul>

<ul class="nav nav-tabs">
    <li><a href="<?= site_url('backend/procesos/editar/' . $proceso->id) ?>">Diseñador</a></li>
    <li><a href="<?= site_url('backend/formularios/listar/' . $proceso->id) ?>">Formularios</a></li>
    <li><a href="<?= site_url('backend/documentos/listar/' . $proceso->id) ?>">Documentos</a></li>
    <li class="active"><a href="<?= site_url('backend/acciones/listar/' . $proceso->id) ?>">Acciones</a></li>
</ul>

<form class="ajaxForm" method="POST" action="<?=site_url('backend/acciones/editar_form/'.($edit?$accion->id:''))?>">
    <fieldset>
        <legend>Crear Acción
        <?php if ($tipo == "enviar_correo") { ?>
            <a href="/assets/ayuda/simple/backend/modelamiento-del-proceso/acciones.html#enviar_correo" target="_blank">
                <span class="glyphicon glyphicon-info-sign"></span>
            </a>
        <?php } else if ($tipo == "webservice") { ?>
            <a href="/assets/ayuda/simple/backend/modelamiento-del-proceso/acciones.html#webservice" target="_blank">
                <span class="glyphicon glyphicon-info-sign"></span>
            </a>
        <?php } else if ($tipo == "variable") { ?>
            <a href="/assets/ayuda/simple/backend/modelamiento-del-proceso/acciones.html#variable" target="_blank">
                <span class="glyphicon glyphicon-info-sign"></span>
            </a>
        <?php } ?>
        </legend>
        <div class="validacion"></div>
        <?php if(!$edit):?>
        <input type="hidden" name="proceso_id" value="<?=$proceso->id?>" />
        <input type="hidden" name="tipo" value="<?=$tipo?>" />
        <?php endif; ?>
        <label>Nombre de la acción</label>
        <input type="text" name="nombre" value="<?=$edit?$accion->nombre:''?>" />
        <label>Tipo</label>
        <input type="text" readonly value="<?=$edit?$accion->tipo:$tipo?>" />
        
        <?=$accion->displayForm()?>
        
        <div class="form-actions">
            <a class="btn" href="<?=site_url('backend/acciones/listar/'.$proceso->id)?>">Cancelar</a>
            <input class="btn btn-primary" type="submit" value="Guardar" />
        </div>
    </fieldset>
</form>
</div>