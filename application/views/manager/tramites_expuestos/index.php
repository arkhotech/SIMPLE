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
                <option value="">Todas</option>
                <?php foreach($cuentas as $c):?>
                <option value="<?=$c->id?>" <?=$c->id==$cuenta_sel?'selected':''?>><?=$c->nombre?></option>
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
                    //$nombre_host = gethostname();
                    $nombre_host = $this->input->server('HTTP_HOST');
                    ($_SERVER['HTTPS'] ? $protocol = 'https://' : $protocol = 'http://');
                    foreach ($json as $res){ 
                ?>
                    <tr>
                        <td><? echo $res['nombre_cuenta'] ?></td>
                        <td><? echo $res['nombre'] ?></td>
                        <td><? echo $res['tarea'] ?></td>
                        <td><? echo $res['previsualizacion'] ?></td>
                        <td>
                            <a class="btn btn-default" target="_blank" href="<? echo $protocol.$nombre_host.'/integracion/especificacion/servicio/proceso/'.$res['id'].'/tarea/'.$res['id_tarea']; ?> ">
                                <i class="icon-upload icon"></i>Swagger
                            </a>
                        </td>
                    </tr>
                <? } ?> 
            </table>
        </div>
    </form>
</div>