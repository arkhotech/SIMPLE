<script src="<?= base_url() ?>assets/js/jquery.chosen/chosen.jquery.min.js"></script> <?php //Soporte para selects con multiple choices     ?>
<script src="<?= base_url() ?>assets/js/jquery.select2/dist/js/select2.min.js"></script> <?php //Soporte para selects con multiple choices     ?>
<script src="<?= base_url() ?>assets/js/jquery.select2/dist/js/i18n/es.js"></script> <?php //Soporte para selects con multiple choices     ?>
<script src= "<?= base_url('/assets/js/jquery-ui/js/jquery-ui.js') ?>"></script>
<script src="<?= base_url() ?>assets/js/collapse.js"></script>
<script src="<?= base_url() ?>assets/js/transition.js"></script>
<script src="<?= base_url() ?>assets/js/bootstrap-datetimepicker.min.js"></script>
<ul class="breadcrumb">
    <li class="active"><?=$title?></li>
</ul>
<div>
    <form method="POST" action="<?=site_url('manager/tramites_expuestos/buscar_cuenta')?>">
        <div>
            <label>Cuenta</label>
            <select id="cuenta_id" name="cuenta_id" class="AlignText">
                <?php foreach($cuentas as $c):?>
                <option value="">Seleccione...</option>
                <option value="<?=$c->id?>" <?=$c->id==$usuario->cuenta_id?'selected':''?>><?=$c->nombre?></option>
                <?php endforeach ?>
            </select>
            <button type="submit" class="btn btn-primary"><i class="icon-search icon"></i> Consultar</button>
        </div>
        <div>
            <table class="table">
                <tr>
                    <th>Cuenta</th>
                    <th>Nombre del Proceso</th>
                    <th>Tarea</th>
                    <th>Descripción</th>
                    <th>Url</th>
                </tr>       
                <?
                    $nombre_host = gethostname();
                    ($_SERVER['HTTPS'] ? $protocol = 'https://' : $protocol = 'http://');
                    foreach ($json as $res){ 
                ?>
                    <tr>
                        <td><? echo $res['nombre_cuenta'] ?></td>
                        <td><? echo $res['nombre'] ?></td>
                        <td><? echo $res['tarea'] ?></td>
                        <td><? echo $res['previsualizacion'] ?></td>
                        <td>
                            <a class="btn btn-default" target="_blank" href="<? echo $protocol.$nombre_host.'/integracion/api/especificacion/servicio/'.$res['id'].'/'.$res['id_tarea']; ?> ">
                                <i class="icon-upload icon"></i>Swagger
                            </a>
                        </td>
                    </tr>
                <? } ?> 
            </table>
        </div>
    </form>
</div>
<script>
/*
function ConsultarFunciones(){
    var cuenta_id = $("#cuenta_id").val();
    $.post("/manager/tramites_expuestos/buscar_cuenta", {cuenta_id: cuenta_id}, function(d,e){
        //manejorespuesta(d);
        console.log(d);
    });
 }
$(document).ready(function(){
    $.get("/manager/tramites_expuestos/index", function(data) {
        console.log(data);
        $( ".result" ).html( data );
        alert( "Load was performed." );
    });

    console.log("hola desde aqui");
    $(document).on('click','#btn-consultar',ConsultarFunciones);
});
*/

</script>