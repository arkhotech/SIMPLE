<ul class="breadcrumb">
    <li><a href="<?= site_url('manager/categorias') ?>">Categorias</a></li> /
    <li class="active"><?= $title ?> categoria</li>
</ul>

<form class="ajaxForm" method="post" action="<?= site_url('manager/categorias/editar_form/' . $categoria->id) ?>">
    <fieldset>
        <legend><?= $title ?></legend>
        <div class="validacion"></div>
        
        <label>Nombre</label>
        <input type="text" name="nombre" value="<?= $categoria->nombre ?>"/>

        <label>Descripción</label>
        <input class="input-xxlarge" type="text" name="descripcion" value="<?= $categoria->descripcion ?>"/>

        <label>Icono</label>
        <div id="file-uploader"></div>
        <?php if($categoria->icon_ref):?>
            <input type="hidden" name="logo" value="<?= $categoria->icon_ref ?>" />
            <img class="logo" src="<?= base_url('uploads/logos/' . $categoria->icon_ref)?>" alt="logo" style="max-width: 10%"/>
        <?php else:?>
            <input type="hidden" name="logo" value="nologo.png" />
            <img class="logo" src="<?= base_url('assets/img/icons/nologo.png') ?>" alt="logo" style="max-width: 10%;"/>
        <?php endif ?>

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
        <a class="btn" href="<?= site_url('manager/categorias') ?>">Cancelar</a>
    </div>
</form>