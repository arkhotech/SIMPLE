<div class="modal-header">
    <a class="close" data-dismiss="modal">×</a>
    <h3>Seleccione el tipo de acción</h3>
</div>
<div class="modal-body">
    <form id="formAgregarAccion" class="ajaxForm" method="POST" action="<?= site_url('backend/acciones/seleccionar_form/'.$proceso_id) ?>">
        <div class="validacion"></div>
        <label>Tipo de acción</label>
        <select name="tipo">
            <option value="enviar_correo">Enviar correo</option>
            <option value="webservice">Consultar Webservice</option>
            <option value="variable">Generar Variable</option>
            <option value="rest">Consultar Rest</option>
            <option value="soap">Consultar Soap</option>
        </select>
    </form>
</div>
<div class="modal-footer">
    <a href="#" data-dismiss="modal" class="btn">Cerrar</a>
    <a href="#" onclick="javascript:$('#formAgregarAccion').submit();return false;" class="btn btn-primary">Continuar</a>
</div>
