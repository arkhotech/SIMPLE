<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">×</button>
    <h3 id="myModalLabel">Publicación de proceso</h3>
</div>
<div class="modal-body">
    <form id="formPublicarProceso" method='POST' class='ajaxForm' action="<?= site_url('backend/procesos/publicar/' . $proceso->id) ?>">
        <label>Esta acción dejará la versión actual del proceso disponible para los usuarios, esta seguro de publicar esta versión del proceso?</label>
    </form>
</div>
<div class="modal-footer">
    <button class="btn" data-dismiss="modal">Cerrar</button>
    <a href="#" onclick="javascript:$('#formPublicarProceso').submit();
        return false;" class="btn btn-primary">Aceptar</a>
</div>
