<ul class="breadcrumb">
    <li>
        <a href="<?= site_url('backend/procesos') ?>">Listado de Procesos</a> <span class="divider">/</span>
    </li>
    <li class="active"><?= $proceso->nombre ?></li>
</ul>

<ul class="nav nav-tabs">
    <li><a href="<?= site_url('backend/procesos/editar/' . $proceso->id) ?>">Diseñador</a></li>
    <li><a href="<?= site_url('backend/formularios/listar/' . $proceso->id) ?>">Formularios</a></li>
    <li class="active"><a href="<?= site_url('backend/documentos/listar/' . $proceso->id) ?>">Documentos</a></li>
    <li><a href="<?= site_url('backend/acciones/listar/' . $proceso->id) ?>">Acciones</a></li>
    <li><a href="<?= site_url('backend/Admseguridad/listar/' . $proceso->id) ?>">Seguridad</a></li>
    <li><a href="<?= site_url('backend/suscriptores/listar/' . $proceso->id) ?>">Suscriptores Externos</a></li>
</ul>

<script>
    $(document).ready(function() {
        handleRadio();
        $("input[name=tipo]").change(handleRadio);

        function handleRadio() {
            var value = $("input[name=tipo]:checked").val();
            if (value == "blanco") {
                $("#certificadoArea").hide();
            } else {
                $("#certificadoArea").show();
            }
        }
    });
</script>

<form class="ajaxForm" method="POST" action="<?= site_url('backend/documentos/editar_form/' . ($edit ? $documento->id : '')) ?>">
    <fieldset>
        <legend>Crear Documento</legend>
        <div class="validacion"></div>
        <?php if (!$edit): ?>
            <input type="hidden" name="proceso_id" value="<?= $proceso->id ?>" />
        <?php endif; ?>
        <label>Nombre</label>
        <input type="text" name="nombre" value="<?= $edit ? $documento->nombre : '' ?>" />

        <label>Tipo de documento</label>
        <label class="radio"><input type="radio" name="tipo" value="blanco" <?= !$edit || ($edit && $documento->tipo) == 'blanco' ? 'checked' : '' ?> /> En blanco
            <a href="/assets/ayuda/simple/backend/modelamiento-del-proceso/generacion-de-documentos.html#documento_en_blanco" target="_blank">
                <span class="glyphicon glyphicon-info-sign"></span>
            </a>
        </label>
        <label class="radio"><input type="radio" name="tipo" value="certificado" <?= $edit && $documento->tipo == 'certificado' ? 'checked' : '' ?> /> Certificado
            <a href="/assets/ayuda/simple/backend/modelamiento-del-proceso/generacion-de-documentos.html#documento_certificado" target="_blank">
                <span class="glyphicon glyphicon-info-sign"></span>
            </a>
        </label>
        <div id="certificadoArea">
            <label>Título</label>
            <input class="input-xxlarge" type="text" name="titulo" value="<?= $edit ? $documento->titulo : '' ?>" placeholder="Ej: Certificado de Educación" />
            <label>Subtítulo</label>
            <input class="input-xxlarge" type="text" name="subtitulo" value="<?= $edit ? $documento->subtitulo : '' ?>" placeholder="Ej: Certificado Gratuito" />
            <label>Servicio que emite el documento</label>
            <input class="input-xxlarge" type="text" name="servicio" value="<?= $edit ? $documento->servicio : '' ?>" placeholder="Ej: Ministerio Secretaría General de la Presidencia" />
            <label>URL al sitio web del servicio</label>
            <input class="input-xxlarge" type="text" name="servicio_url" value="<?= $edit ? $documento->servicio_url : '' ?>" placeholder="Ej: http://www.minsegpres.gob.cl" />
            <label>Logo del Servicio (Opcional)</label>
            <div id="file-uploader-logo"></div>
            <input type="hidden" name="logo" value="<?= $edit ? $documento->logo : '' ?>" />
            <img class="logo" src="<?= $edit && $documento->logo ? site_url('backend/uploader/logo_certificado_get/' . $documento->logo) : '#' ?>" alt="" width="200" />
            <script>
                var uploader = new qq.FileUploader({
                    element: document.getElementById('file-uploader-logo'),
                    action: site_url + 'backend/uploader/logo_certificado',
                    onComplete: function(id, filename, respuesta) {
                        $("input[name=logo]").val(respuesta.file_name);
                        $("img.logo").attr("src", site_url + "backend/uploader/logo_certificado_get/" + respuesta.file_name);
                    }
                });
            </script>
            <label>Imagen del timbre (Opcional)</label>
            <div id="file-uploader-timbre"></div>
            <input type="hidden" name="timbre" value="<?= $edit ? $documento->timbre : '' ?>" />
            <img class="timbre" src="<?= $edit && $documento->timbre ? site_url('backend/uploader/timbre_get/' . $documento->timbre) : '#' ?>" alt="" width="200" />
            <script>
                var uploader = new qq.FileUploader({
                    element: document.getElementById('file-uploader-timbre'),
                    action: site_url + 'backend/uploader/timbre',
                    onComplete: function(id, filename, respuesta) {
                        $("input[name=timbre]").val(respuesta.file_name);
                        $("img.timbre").attr("src", site_url + "backend/uploader/timbre_get/" + respuesta.file_name);
                    }
                });
            </script>
            <label>Nombre de la persona que firma (Opcional)</label>
            <input class="input-xxlarge" type="text" name="firmador_nombre" value="<?= $edit ? $documento->firmador_nombre : '' ?>" placeholder="Ej: Juan Perez" />
            <label>Cargo de la persona que firma (Opcional)</label>
            <input class="input-xxlarge" type="text" name="firmador_cargo" value="<?= $edit ? $documento->firmador_cargo : '' ?>" placeholder="Ej: Jefe de Servicio" />
            <label>Servicio al que pertenece la persona que firma (Opcional)</label>
            <input class="input-xxlarge" type="text" name="firmador_servicio" value="<?= $edit ? $documento->firmador_servicio : '' ?>" placeholder="Ej: Ministerio Secretaría General de la Presidencia" />
            <label>Imagen de la firma</label>
            <div id="file-uploader"></div>
            <input type="hidden" name="firmador_imagen" value="<?= $edit ? $documento->firmador_imagen : '' ?>" />
            <div id="firmaPreview" class="<?=$edit && $documento->firmador_imagen?'':'hidden'?>">       
                <img src="<?= $edit && $documento->firmador_imagen?site_url('backend/uploader/firma_get/' . $documento->firmador_imagen):'#' ?>" alt="firma" width="200" />
                <a href="#">Quitar</a>
            </div>
            <script>
                $(document).ready(function(){
                    var uploader = new qq.FileUploader({
                        element: document.getElementById('file-uploader'),
                        action: site_url + 'backend/uploader/firma',
                        onComplete: function(id, filename, respuesta) {
                            $("input[name=firmador_imagen]").val(respuesta.file_name);
                            $("#firmaPreview").show();
                            $("#firmaPreview img").attr("src", site_url + "backend/uploader/firma_get/" + respuesta.file_name);
                        }
                    });
                    
                    $("#firmaPreview a").click(function(){
                        $("input[name=firmador_imagen]").val("");
                        $("#firmaPreview").hide();
                        $("#firmaPreview img").attr("src","#");
                        return false;
                    });
                });
                
            </script>          
            <label>Numero de dias de validez (Dejar en blanco para periodo ilimitado, 0 para no mostrar validez)</label>
            <input class="input-mini" type="text" name="validez" value="<?= $edit ? $documento->validez : '' ?>" placeholder="Ej: 90" />
            <label style="display: inline-block" class="checkbox"><input type="checkbox" name="validez_habiles" value="1" <?=$edit && $documento->validez_habiles ? 'checked':''?> /> Hábiles</label>
            <script>
                $(document).ready(function(){
                    $("input[name=validez]").keyup(function(){
                        if($(this).val().length>0){
                            $("input[name=validez_habiles]").prop("disabled",false);
                        }else{
                            $("input[name=validez_habiles]").prop("disabled",true);
                        }
                    }).keyup();
                });
            </script>

        </div>

        <label>Tamaño de la Página</label>
        <label class="radio"><input type="radio" name="tamano" value="letter" <?= !$edit || ($edit && $documento->tamano) == 'letter' ? 'checked' : '' ?> /> Carta</label>
        <label class="radio"><input type="radio" name="tamano" value="legal" <?= $edit && $documento->tamano == 'legal' ? 'checked' : '' ?> /> Oficio</label>

        <label>Contenido</label>
        <textarea name="contenido" class="input-xxlarge" rows="20"><?= $edit ? $documento->contenido : '' ?></textarea>
        <div class="help-block">
            <ul>
                <li>Para incluir un salto de página puede usar: <?=htmlspecialchars('<br pagebreak="true" />')?></li>
            </ul>
        </div>

        <?php if (isset($proceso->Cuenta->HsmConfiguraciones) && $proceso->Cuenta->HsmConfiguraciones->count()): ?>
            <label>Firma Electronica Avanzada (HSM)</label>
            <select name="hsm_configuracion_id">
                <option value="">No firmar con HSM</option>
                <?php foreach ($proceso->Cuenta->HsmConfiguraciones as $h): ?>
                    <option value="<?= $h->id ?>" <?= $edit && $documento->hsm_configuracion_id == $h->id ? 'selected' : '' ?>>Firmar con <?= $h->nombre ?></option>
                <?php endforeach ?>
            </select>
        <?php endif ?>

        <div class="form-actions">
            <a class="btn" href="<?= site_url('backend/documentos/listar/' . $proceso->id) ?>">Cancelar</a>
            <input class="btn btn-primary" type="submit" value="Guardar" />
        </div>
    </fieldset>
</form>




</div>