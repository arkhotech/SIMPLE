<form method="POST" class="ajaxForm" action="<?=site_url('cuentas/editar_form')?>">
    <fieldset>
        <legend>Completa la información de tu cuenta</legend>
        <div class="validacion"></div>
        <div class="input-group">
        <label>Nombres</label>
        <input type="text" name="nombres" value="<?=$usuario->nombres?>" />
        </div>
        <div class="input-group">
        <label>Apellido Paterno</label>
        <input type="text" name="apellido_paterno" value="<?=$usuario->apellido_paterno?>" />
        </div>
        <div class="input-group">
        <label>Apellido Materno</label>
        <input type="text" name="apellido_materno" value="<?=$usuario->apellido_materno?>" />
        </div>
        <div class="input-group">
        <label>Correo electrónico</label>
        <input type="text" name="email" value="<?=$usuario->email?>" />
        </div>
        <div class="input-group">
        <?php if($usuario->cuenta_id): ?>
        <label class="checkbox"><input type="checkbox" name="vacaciones" value="1" <?=$usuario->vacaciones?'checked':''?> /> ¿Fuera de oficina?</label>
        <?php endif ?>
        </div>
        <input type="hidden" name="redirect" value="<?=$redirect?>" />
        <div class="form-actions input-group">
        <button class="button button--lightgray" type="button" onclick="javascript:history.back()">Cancelar</button>
        <button class="button" type="submit">Guardar</button>
    </div>
    </fieldset>
</form>