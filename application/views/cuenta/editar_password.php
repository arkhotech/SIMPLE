<form method="POST" class="ajaxForm" action="<?=site_url('cuentas/editar_password_form')?>">
    <fieldset>
        <legend>Edita la información de tu cuenta</legend>
        <div class="validacion"></div>
        <div class="input-group">
            <label>Contraseña antigua</label>
            <input type="password" name="password_old" value="" />
        </div>
        <div class="input-group">
        <label>Contraseña nueva</label>
        <input type="password" name="password_new" value="" />
        </div>
        <div class="input-group">
        <label>Confirmar contraseña nueva</label>
        <input type="password" name="password_new_confirm" value="" />
        </div>
        <input type="hidden" name="redirect" value="<?=$redirect?>" />
        <div class="form-actions input-group">
            <button class="button button--lightgray" type="button" onclick="javascript:history.back()">Cancelar</button>
            <button class="button" type="submit">Guardar</button>
        </div>
    </fieldset>
</form>