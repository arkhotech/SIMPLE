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
    <li><a href="<?= site_url('backend/acciones/listar/' . $proceso->id) ?>">Acciones</a></li>
    <li class="active"><a href="<?= site_url('backend/Admseguridad/listar/' . $proceso->id) ?>">Seguridad</a></li>
    <li><a href="<?= site_url('backend/suscriptores/listar/' . $proceso->id) ?>">Suscriptores Externos</a></li>
</ul>
  

<form id="plantillaForm" class="ajaxForm" method="POST" action="<?=site_url('backend/Admseguridad/editar_form/'.($edit?$seguridad->id:''))?>">
    <fieldset>
        <?php if(!$edit):?>
            <legend> Regitrar métodos de seguridad
                <a href="/assets/ayuda/simple/backend/modelamiento-del-proceso/acciones.html#webservice-seguridad" target="_blank">
                    <span class="glyphicon glyphicon-info-sign"></span>
                </a>
            </legend>
        <?php endif; ?>
        <?php if($edit):?>
            <legend> Editar métodos de seguridad
                <a href="/assets/ayuda/simple/backend/modelamiento-del-proceso/acciones.html#webservice-seguridad" target="_blank">
                    <span class="glyphicon glyphicon-info-sign"></span>
                </a>
            </legend>
        <?php endif; ?>
        <div class="validacion"></div>
        <?php if(!$edit):?>
        <input type="hidden" name="proceso_id" value="<?=$proceso->id?>" /> 
        <?php endif; ?>
        <label>Nombre de la Institución</label>
        <input type="text" name="institucion" value="<?=$edit?$seguridad->institucion:''?>" />
        <label>Nombre del Servicio</label>
        <input type="text" name="servicio" value="<?=$edit?$seguridad->servicio:''?>" />
        <?= $seguridad->displayForm() ?>
        <div class="form-actions">
            <a class="btn" href="<?=site_url('backend/Admseguridad/listar/'.$proceso->id)?>">Cancelar</a>
            <button class="btn btn-primary" value="Guardar" type="submit">Guardar</button>
        </div>
    </fieldset>
</form>
</div>
<script src="<?= base_url() ?>assets/js/AdmSeguridad.js"></script>
