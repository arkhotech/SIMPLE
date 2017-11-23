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
    <li><a href="<?= site_url('backend/Admseguridad/listar/' . $proceso->id) ?>">Seguridad</a></li>
    <li><a href="<?= site_url('backend/Suscriptores/listar/' . $proceso->id) ?>">Suscriptores Externos</a></li>
</ul>
  


<form id="plantillaForm" class="ajaxForm" method="POST" onsubmit="return ()" action="<?=site_url('backend/acciones/editar_form/'.($edit?$accion->id:''))?>">
    <fieldset>
        <?php if(!$edit):?>
            <legend> Crear Acción
        <?php endif; ?>
        <?php if($edit){
        $tipo= $accion->tipo;?>
            <legend> Editar Acción
        <?php }?>
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
        <?php } else if ($tipo == "rest") { ?>
            <a href="/assets/ayuda/simple/backend/modelamiento-del-proceso/acciones.html#webservice-rest" target="_blank">
                <span class="glyphicon glyphicon-info-sign"></span>
            </a>
        <?php } else if ($tipo == "soap") { ?>
            <a href="/assets/ayuda/simple/backend/modelamiento-del-proceso/acciones.html#webservice-soap" target="_blank">
                <span class="glyphicon glyphicon-info-sign"></span>
            </a>
        <?php } else if ($tipo == "callback") { ?>
            <a href="/assets/ayuda/simple/backend/modelamiento-del-proceso/acciones.html#callback" target="_blank">
                <span class="glyphicon glyphicon-info-sign"></span>
            </a>
        <?php } else if ($tipo == "webhook") { ?>
            <a href="/assets/ayuda/simple/backend/modelamiento-del-proceso/acciones.html#webhook" target="_blank">
                <span class="glyphicon glyphicon-info-sign"></span>
            </a>
        <?php } else if ($tipo == "iniciar_tramite") { ?>
            <a href="/assets/ayuda/simple/backend/modelamiento-del-proceso/acciones.html#iniciar-tramite" target="_blank">
                <span class="glyphicon glyphicon-info-sign"></span>
            </a>
        <?php } else if ($tipo == "continuar_tramite") { ?>
            <a href="/assets/ayuda/simple/backend/modelamiento-del-proceso/acciones.html#continuar-tramite" target="_blank">
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

        <?php
        log_message("INFO", "En view editar, tipo: ".$tipo, FALSE);
        $key='';
        ($tipo ? $key = $tipo : $key = $accion->tipo);
        log_message("INFO", "En view editar, $key: ".$key, FALSE);
        if($tipo == "rest" || $tipo == "soap" || $tipo == "callback" || $accion->tipo == "rest" || $accion->tipo == "soap" || $accion->tipo == "callback" || $accion->tipo == "iniciar_tramite" || $tipo == "iniciar_tramite" || $accion->tipo == "continuar_tramite" || $tipo == "continuar_tramite"){
            echo $accion->displaySecurityForm($proceso->id);
        }else if($tipo == "webhook" || $accion->tipo == "webhook"){
            echo $accion->displaySuscriptorForm($proceso->id);
        }else{
            echo $accion->displayForm();
        }
        ?>

        <div class="form-actions">
            <a class="btn" href="<?=site_url('backend/acciones/listar/'.$proceso->id)?>">Cancelar</a>
            <button class="btn btn-primary" value="Guardar" type="button" onclick="validateForm();">Guardar</button>
        </div>
    </fieldset>
</form>
</div>
<?php
    switch ($key) {
        case "rest":
            ?><script src="<?= base_url() ?>assets/js/accion_rest.js"></script><?
            break;
        case "soap":
            ?><script src="<?= base_url() ?>assets/js/accion_soap.js"></script><?
            break;
        case "callback":
            ?><script src="<?= base_url() ?>assets/js/accion_callback.js"></script><?
            break;
        case "iniciar_tramite":
            ?><script src="<?= base_url() ?>assets/js/accion_tramite_simple.js"></script><?
            break;
        default:
            ?><script src="<?= base_url() ?>assets/js/accion_otras.js"></script><?
            break;
    }
?>
