<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal"><i class="icon-close icon--top"></i></button>
    <h3 id="myModalLabel">Descarga de documentos</h3>
</div>
<div class="modal-body">
    <form enctype="multipart/form-data" id="formDescargarDocumentos" method='POST' action="<?= site_url('etapas/descargar_form/') ?>" >
        <label>Seleccione:</label>

        <div class="radio">
          <label>
            <input type="radio" name="opcionesDescarga" id="opcionesDescarga1" value="documento">
            Generados: Documentación que el sistema genera al usuario.
          </label>
        </div>
        <div class="radio">
          <label>
            <input type="radio" name="opcionesDescarga" id="opcionesDescarga2" value="dato">
            Subidos: Documentación que el usuario adjuntó en el trámite.
          </label>
        </div>
        <div class="radio">
          <label>
            <input type="radio" name="opcionesDescarga" id="opcionesDescarga3" value="all" checked>
            Todos: Recopila toda la documentación.
          </label>
        </div>
        <input type="hidden" id="tramites" name="tramites" value="<?= $tramites ?>">
    </form>
</div>

<div class="modal-footer">
    <a class="button button--lightgray closeModal" data-dismiss="modal">Cerrar</a>
    <a href="#" onclick="javascript:$('#formDescargarDocumentos').submit();$('#modal').modal('hide')" class="button btn-primary">Descargar</a>
</div>