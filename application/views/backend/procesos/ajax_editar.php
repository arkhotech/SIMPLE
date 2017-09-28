<div class="modal-header">
    <button class="close" data-dismiss="modal">×</button>
    <h3>Editar Proceso</h3>
</div>
<div class="modal-body">
    <form id="formEditarProceso" class="ajaxForm" method="POST" action="<?=site_url('backend/procesos/editar_form/'.$proceso->id)?>">
        <div class="validacion" style="padding: 10px;"></div>

        <div style="width: 45%;display: inline-block;">
            <label>Nombre</label>
            <input type="text" name="nombre" value="<?=$proceso->nombre?>" />
            <label>Tamaño de la Grilla</label>
            <input type="text" name="width" value="<?=$proceso->width?>" class="input-small" /> X <input type="text" name="height" value="<?=$proceso->height?>" class="input-small" />
        </div>
        <div style="width: 45%;float: right">
            <label>Categoría</label>
            <select name="categoria" id="categoria">
            <option value="0">Todos los trámites</option>
            <?php foreach($categorias as $c):?>
                <?php if ($proceso->categoria_id == $c->id) { ?>
                    <option value="<?=$c->id?>" selected="true"><?=$c->nombre?></option>
                <?php } else { ?>
                    <option value="<?=$c->id?>"><?=$c->nombre?></option>
                <?php } ?>
            <?php endforeach ?>
            </select>
            <label>Destacado</label>
            <?php if ($proceso->destacado == 1) { ?>
                <input type="checkbox" name="destacado" id="destacado" checked>
            <?php } else { ?>
                <input type="checkbox" name="destacado" id="destacado">
            <?php } ?>
        </div>
        <div>
            <label>Icono</label>
            <input id="filenamelogo" type="hidden" name="logo" value="<?= $proceso->icon_ref ?>" />
            <a href="javascript:;" id="SelectIcon" class="btn">Seleccionar &iacute;cono</a>
            <?php if($proceso->icon_ref):?>
                <img id="icn-logo" class="logo" src="<?= base_url('assets/img/icons/' . $proceso->icon_ref)?>" alt="logo" style="max-width:64px;max-height:64px;"/>
            <?php else:?>
                <img id="icn-logo" class="logo" src="<?= base_url('assets/img/icons/nologo.png') ?>" alt="logo" style="max-width:64px;max-height:64px;"/>
            <?php endif ?>
        </div>
    </form>
</div>
<div class="modal-footer">
    <a href="#" data-dismiss="modal" class="btn">Cerrar</a>
    <a href="#" onclick="javascript:$('#formEditarProceso').submit();return false;" class="btn btn-primary">Guardar</a>
</div>
