<ul class="breadcrumb">
    <li><a href="<?= site_url('manager/cuentas') ?>">Cuentas</a></li> /
    <li class="active"><?= $title ?></li>
</ul>
<form class="ajaxForm" method="post" action="<?= site_url('manager/cuentas/editar_form/' . $cuenta->id) ?>">
    <fieldset>
        <legend><?= $title ?></legend>
        <div class="validacion"></div>
        <label>Nombre</label>
        <input type="text" name="nombre" value="<?= $cuenta->nombre ?>"/>
        <div class="help-block">En minusculas y sin espacios.</div>
        <label>Nombre largo</label>
        <input class="input-xxlarge" type="text" name="nombre_largo" value="<?= $cuenta->nombre_largo ?>"/>
        <label>Mensaje de bienvenida (Puede contener HTML)</label>
        <textarea name="mensaje" class="input-xxlarge"><?= $cuenta->mensaje ?></textarea>
        <label>Logo</label>
        <div id="file-uploader"></div>
        <input type="hidden" name="logo" value="<?= $cuenta->logo ?>" />
        <img class="logo" src="<?= $cuenta->logo ? base_url('uploads/logos/' . $cuenta->logo) : base_url('assets/img/simple.png') ?>" alt="logo" />
    </fieldset>
    </br>
    <fieldset>
        <legend><?= $title ?> configuración ambiente de desarrollo</legend>
        <label>¿Es ambiente Desarrollo?</label>
        <input name="desarrollo" id="toggle_ambiente_dev" type="checkbox" <?= ($cuenta->ambiente == 'dev') ? 'checked' : '' ?> data-toggle="toggle" data-size="normal" data-on="Si" data-off="No">
        <div id="vinculo_prod" name="ambiente" class="<?= ($cuenta->ambiente != 'dev') ? 'hide' : '' ?>">
            <label>Vincular Cuenta Productiva</label>
            <select id="ambiente-prod" name="vinculo_produccion">
                <option value="">Seleccionar ...</option>
                <?php foreach($cuentas_productivas as $cp):?>
                    <option value="<?=$cp[id]?>" <?= ($cp[id] == $cuenta->vinculo_produccion) ? 'selected' : '' ?>><?=$cp[nombre]?></option>
                <?php endforeach ?>
            </select>
        </div>
    </fieldset>
    </br>
    <fieldset>
        <legend><?= $title ?> configuraci&oacute;n de agenda</legend>
        <label>Clave App</label>
        <input type="text" name="appkey" readonly="true" disabled="true" value="<?= $calendar->getAppkey() ?>"/>
        <label>Dominio</label>
        <input type="text" name="domain" value="<?= $calendar->getDomain() ?>"/>
    </fieldset>
    <script>
        var uploader = new qq.FileUploader({
            element: document.getElementById('file-uploader'),
            action: site_url + 'manager/uploader/logo',
            onComplete: function(id, filename, respuesta) {
                $("input[name=logo]").val(respuesta.file_name);
                $("img.logo").attr("src", base_url + "uploads/logos/" + respuesta.file_name);
            }
        });
    </script>
    </br></br>
    <div class="form-actions">
        <button class="btn btn-primary" type="submit">Guardar</button>
        <a class="btn" href="<?= site_url('manager/cuentas') ?>">Cancelar</a>
    </div>
</form>