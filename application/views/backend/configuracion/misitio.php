<div class="row-fluid">

    <div class="span3">
        <?php $this->load->view('backend/configuracion/sidebar') ?>
    </div>
    <div class="span9">
        <ul class="breadcrumb">
            <li>
                <a href="<?= site_url('backend/configuracion') ?>">Configuración</a> <span class="divider">/</span>
            </li>
            <li class="active">Mi Sitio
                <a href="/assets/ayuda/simple/backend/configuracion/inicial.html" target="_blank">
                    <span class="glyphicon glyphicon-info-sign"></span>
                </a>
            </li>
        </ul>

        <form class="ajaxForm" method="post" action="<?= site_url('backend/configuracion/misitio_form/') ?>">
            <fieldset>
                <legend>
                    Editar información de mi sitio
                </legend>
                <div class="validacion"></div>
                <label>Nombre</label>
                <input disabled type="text" name="nombre" class="input-xxlarge" value="<?=$cuenta->nombre?>"/>
                <label>Nombre largo</label>
                <input class="input-xxlarge" type="text" name="nombre_largo" value="<?=$cuenta->nombre_largo?>"/>
                <label>Mensaje de bienvenida (Puede contener HTML)</label>
                <textarea name="mensaje" class="input-xxlarge"><?=$cuenta->mensaje?></textarea>
                <label>Logo</label>
                <div id="file-uploader"></div>
                <input type="hidden" name="logo" value="<?=$cuenta->logo?>" />
                <img class="logo" src="<?=$cuenta->logo?base_url('uploads/logos/'.$cuenta->logo):base_url('assets/img/simple.png')?>" alt="logo" />
                <script>
                    var uploader = new qq.FileUploader({
                        element: document.getElementById('file-uploader'),
                        action: site_url+'backend/uploader/logo',
                        onComplete: function(id,filename,respuesta){
                            $("input[name=logo]").val(respuesta.file_name);
                            $("img.logo").attr("src",base_url+"uploads/logos/"+respuesta.file_name);
                        }
                    });
                </script>
                <br/><br/>
                <label class="checkbox"><input name="descarga_masiva" value="1" type="checkbox" <?= $cuenta->descarga_masiva ? 'checked' : '' ?>>Habilitar descarga masiva</label>
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">Guardar</button>
                    <a class="btn" href="<?=site_url('backend/configuracion/usuarios')?>">Cancelar</a>
                </div>
            </fieldset>
        </form>

    </div>
</div>