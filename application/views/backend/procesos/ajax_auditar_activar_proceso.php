<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">×</button>
    <h3 id="myModalLabel">Activación de proceso</h3>
</div>
<div class="modal-body">
    <form id="formAuditarRetrocesoEtapa2" method='POST' class='ajaxForm' action="<?= site_url('backend/procesos/activar/' . $proceso->id) ?>">
        <div class='validacion'></div>
        <label>Indique la razón por la cual activa el proceso:</label>
        <textarea class="span6" name='descripcion' type='text' required/>
    </form>

</div>
<div class="modal-footer">
    <button class="btn" data-dismiss="modal">Cerrar</button>
    <a href="#" onclick="javascript:$('#formAuditarRetrocesoEtapa2').submit();
        return false;" class="btn btn-primary">Guardar</a>
</div>
