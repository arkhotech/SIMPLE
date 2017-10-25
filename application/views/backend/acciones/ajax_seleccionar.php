<div class="modal-header">
    <a class="close" data-dismiss="modal">×</a>
    <h3>Seleccione el tipo de acción
        <a href="/assets/ayuda/simple/backend/modelamiento-del-proceso/acciones.html#acciones_tipo" target="_blank">
            <span class="glyphicon glyphicon-info-sign" style="font-size: 15px;"></span>
        </a>
    </h3>
</div>
<div class="modal-body">
    <form id="formAgregarAccion" class="ajaxForm" method="POST" action="<?= site_url('backend/acciones/seleccionar_form/'.$proceso_id) ?>">
        <div class="validacion"></div>
        <label>Tipo de acción</label>
        <select name="tipo">
            <option value="enviar_correo">Enviar Correo</option>
            <option value="variable">Generar Variable</option>
            <option value="rest">Consultar Rest</option>
            <option value="soap">Consultar Soap</option>
            <option value="callback">Generar Callback</option>
            <option value="iniciar_tramite">Iniciar Trámite</option>
            <option value="continuar_tramite">Continuar Trámite</option>
            <option value="webhook">Notificaciones</option>
            <option value="descarga_documento">Descargar Documento</option>
        </select>
    </form>
</div>
<div class="modal-footer">
    <a href="#" data-dismiss="modal" class="btn">Cerrar</a>
    <a href="#" onclick="javascript:$('#formAgregarAccion').submit();return false;" class="btn btn-primary">Continuar</a>
</div>
