<style>
    .contenedor {
        text-align: center;
        width: 100%;
        min-height: 100px;
    }
    .contenedor .item {
        width: 64px;
        margin-right: 40px;
        float: left;
        text-align: center;
    }
    .contenedor .item a {
        padding: 5px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }
    .contenedor .item a.selected {
        background-color: #d9edf7;
    }
    .contenedor .item a:hover {
        background-color: #ddd;
        /*border: 1px solid #bbb;*/
    }
    .contenedor .item a img {
        max-width: 64px;
        max-height: 64px;
    }
</style>
    
<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">×</button>
    <h3 id="myModalLabel">&Iacute;conos</h3>
</div>
<div class="modal-body">
    <div class="validacion"><?= $error ?></div>
    <div class="contenedor"><div style="padding:10px;display:inline-block;"><?= $iconos ?></div></div>
</div>
<div class="modal-footer">
    <button data-dismiss="modal" class="btn">Cancelar</button>
    <?php if (!$hideButton) :?>
    <a href="#" id="btnSelectIcon" data-dismiss="modal" class="btn btn-primary">Seleccionar</a>
    <?php endif; ?>
</div>
<script>
    $(function() {
        $(".contenedor a.sel-icono").each(function() {
            if ($("#filenamelogo").val() == $(this).attr("rel")) {
                $(this).addClass("selected");
                return;
            }
        });
        
        $(document).on("click", "a.sel-icono", function() {
            $("a.sel-icono").removeClass("selected");
            $(this).addClass("selected");
        });
        $(document).on("click", "#btnSelectIcon", function() {            
            var icon = $("a.sel-icono.selected").attr("rel");            
            $("#filenamelogo").val(icon);            
            $("#icn-logo").attr("src", "<?= base_url('assets/img/icons') ?>" + "/" + icon);
            $("#modalSelectIcon").close;
        });
    });
</script>