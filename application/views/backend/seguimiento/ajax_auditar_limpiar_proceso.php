<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">×</button>
    <h3 id="myModalLabel">Eliminación de todos los trámites del proceso</h3>
</div>
<div class="modal-body">
    <form id="formAuditarRetrocesoEtapa" method='POST' class='ajaxForm' action="<?= site_url('backend/seguimiento/borrar_proceso/' . $proceso->id) ?>">
        <div class='validacion'></div>
        <label>Indique la razón por la cual elimina todos los trámites de este proceso:</label>
        <textarea class="span6" name='descripcion' type='text' required/>
    </form>

</div>
<div class="modal-footer">
    <button class="btn" data-dismiss="modal">Cerrar</button>
    <a href="#" onclick="javascript:$('#formAuditarRetrocesoEtapa').submit();
        return false;" class="btn btn-primary">Guardar</a>
</div>
