<script src="<?= base_url() ?>assets/js/modelador-formularios.js" type="text/javascript"></script>

<script type="text/javascript">
    var formularioId=<?= $formulario->id ?>;
</script>

<ul class="breadcrumb">
    <li>
        <a href="<?= site_url('backend/procesos') ?>">Listado de Procesos</a> <span class="divider">/</span>
    </li>
    <li class="active"><?= $proceso->nombre ?></li>
</ul>

<ul class="nav nav-tabs">
    <li><a href="<?= site_url('backend/procesos/editar/' . $proceso->id) ?>">Diseñador</a></li>
    <li class="active"><a href="<?= site_url('backend/formularios/listar/' . $proceso->id) ?>">Formularios</a></li>
    <li><a href="<?= site_url('backend/documentos/listar/' . $proceso->id) ?>">Documentos</a></li>
    <li><a href="<?= site_url('backend/acciones/listar/' . $proceso->id) ?>">Acciones</a></li>
    <li><a href="<?= site_url('backend/Admseguridad/listar/' . $proceso->id) ?>">Seguridad</a></li>
    <li><a href="<?= site_url('backend/suscriptores/listar/' . $proceso->id) ?>">Suscriptores Externos</a></li>
</ul>

<div id="areaFormulario">

    <div class="btn-toolbar">
        <div class="btn-group">
            <button class="btn btn-inverse" onclick="return agregarCampo(<?= $formulario->id ?>,'title')">Título</button>
            <button class="btn btn-inverse" onclick="return agregarCampo(<?= $formulario->id ?>,'subtitle')">Subtítulo</button>
            <button class="btn btn-inverse" onclick="return agregarCampo(<?= $formulario->id ?>,'paragraph')">Parrafo</button>
            <button class="btn btn-inverse" onclick="return agregarCampo(<?= $formulario->id ?>,'documento')">Documento</button>
        </div>
        <div class="btn-group">
            <button class="btn btn-inverse" onclick="return agregarCampo(<?= $formulario->id ?>,'text')">Textbox</button>
            <button class="btn btn-inverse" onclick="return agregarCampo(<?= $formulario->id ?>,'textarea')">Textarea</button>
            <button class="btn btn-inverse" onclick="return agregarCampo(<?= $formulario->id ?>,'select')">Select</button>
            <button class="btn btn-inverse" onclick="return agregarCampo(<?= $formulario->id ?>,'radio')">Radio</button>
            <button class="btn btn-inverse" onclick="return agregarCampo(<?= $formulario->id ?>,'checkbox')">Checkbox</button>
            <button class="btn btn-inverse" onclick="return agregarCampo(<?= $formulario->id ?>,'file')">File</button>
            <button class="btn btn-inverse" onclick="return agregarCampo(<?= $formulario->id ?>,'date')">Date</button>
            <button class="btn btn-inverse" onclick="return agregarCampo(<?= $formulario->id ?>,'grid')">Grilla</button>
            <button class="btn btn-inverse" onclick="return agregarCampo(<?= $formulario->id ?>,'agenda')">Agenda</button>
            <button class="btn btn-inverse" onclick="return agregarCampo(<?= $formulario->id ?>,'recaptcha')">Recaptcha</button>
        </div>
        <div class="btn-group">
            <button class="btn btn-inverse" onclick="return agregarCampo(<?= $formulario->id ?>,'instituciones_gob')">Instituciones</button>
            <button class="btn btn-inverse" onclick="return agregarCampo(<?= $formulario->id ?>,'paises')">Paises</button>
            <button class="btn btn-inverse" onclick="return agregarCampo(<?= $formulario->id ?>,'comunas')">Comunas</button>
            <button class="btn btn-inverse" onclick="return agregarCampo(<?= $formulario->id ?>,'moneda')">Moneda</button>
        </div>
        <div class="btn-group">
            <button class="btn btn-inverse" onclick="return agregarCampo(<?= $formulario->id ?>,'javascript')">Javascript</button>
            <button class="btn btn-inverse" onclick="return agregarCampo(<?= $formulario->id ?>,'maps')">Mapa</button>
        </div>
    </div>

    <div class="row">
        <div class="span10">
    <form id="formEditarFormulario" class="form-horizontal dynaForm debugForm" onsubmit="return false">
        <div class="row">
            <div class="span10">
                <div class="pull-left">
                    <legend><?= $formulario->nombre ?>
                    <a href="/assets/ayuda/simple/backend/modelamiento-del-proceso/diseno-de-formularios.html" target="_blank">
                        <span class="glyphicon glyphicon-info-sign" style="font-size: 16px;"></span>
                    </a>
                    </legend>
                </div>
                <div class="pull-right">
                    <a href="#" class="btn btn-primary" onclick="return editarFormulario(<?= $formulario->id ?>)">Cambiar Nombre</a>&nbsp;
                </div>
            </div>
        </div>
        <div class="edicionFormulario">
            <?php foreach ($formulario->Campos as $c): 
            //print_r($c->datos_agenda);
            ?>
                <div class="row">
                    <div class="span10">
                    <div class="control-group campo" data-id="<?= $c->id ?>" <?= $c->dependiente_campo ? 'data-dependiente-campo="' . $c->dependiente_campo.'" data-dependiente-valor="' . $c->dependiente_valor .'" data-dependiente-tipo="' . $c->dependiente_tipo.'" data-dependiente-relacion="'.$c->dependiente_relacion.'"' : '' ?> >
                        <div class="pull-left"><?= $c->displaySinDato() ?></div>
                        <div class="buttons pull-right">
                            <a href="#" class="btn btn-primary" onclick="return editarCampo(<?= $c->id ?>)"><i class="icon-edit icon-white"></i></a>
                            <a href="<?= site_url('backend/formularios/eliminar_campo/' . $c->id) ?>" class="btn btn-danger" onclick="return confirm('¿Esta seguro que desea eliminar?')"><i class="icon-trash icon-white"></i></a>&nbsp;
                        </div>
                    </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </form>
        </div>
    </div>
</div>

<div class="modal hide fade" id="modal">

</div>