<link rel="stylesheet" href= "<?= base_url('assets/calendar/css/calendar.css') ?>" >
<!-- <script src= "<?= base_url('/assets/js/jquery-ui/js/jquery-ui.js') ?>"></script> -->
<script src= "<?= base_url('/assets/js/moment.min.js') ?>"></script>
<script type="text/javascript" src="<?= base_url('assets/calendar/components/underscore/underscore-min.js') ?>"></script>
<script type="text/javascript" src="<?= base_url('assets/calendar/components/jstimezonedetect/jstz.min.js') ?>"></script>
<script type="text/javascript" src="<?= base_url('assets/calendar/js/language/es-CO.js') ?>"></script>
<script src="<?= base_url() ?>assets/js/bootstrap-datetimepicker.min.js"></script>
<script src= "<?= base_url('/assets/calendar/js/moment-2.2.1.js') ?>"></script>
<script type="text/javascript" src="<?= base_url('assets/calendar/js/calendar.js?v=0.3') ?>"></script>
<script src="<?= base_url() ?>assets/js/collapse.js"></script>
<script src="<?= base_url() ?>assets/js/transition.js"></script>

<ul class="steps">
    <?php for ($i = 0; $i < $num_pasos; $i++): ?>
    <li style="width: <?=100/$num_pasos?>%;" class="<?=($i <= $secuencia) ? 'active' :'' ?>"><span>Paso <?=$i+1?></span></li>
	<?php endfor; ?>
</ul>
<div style="clear: both;"></div>
<?php if ($etapa->Tarea->vencimiento): ?>
<div class="alert alert-warning">Atención. Esta etapa <?=$etapa->getFechaVencimientoAsString()?>.</div>
<?php endif ?>
<form method="POST" class="ajaxForm dynaForm form-horizontal" action="<?=site_url('etapas/ejecutar_form/'.$etapa->id.'/'.$secuencia.($qs?'?'.$qs:''))?>">
    <input type="hidden" name="_method" value="post">
    <div class="validacion"></div>
    <fieldset>
        <legend><?=$paso->Formulario->nombre?></legend>
        <?php foreach($paso->Formulario->Campos as $c):?>
            <div class="campo control-group" data-id="<?=$c->id?>" <?= $c->dependiente_campo ? 'data-dependiente-campo="' . $c->dependiente_campo.'" data-dependiente-valor="' . $c->dependiente_valor .'" data-dependiente-tipo="' . $c->dependiente_tipo.'" data-dependiente-relacion="'.$c->dependiente_relacion.'"' : '' ?> style="display: <?= $c->isCurrentlyVisible($etapa->id)? 'block' : 'none'?>;" data-readonly="<?=$paso->modo=='visualizacion' || $c->readonly?>" >
                <?=$c->displayConDatoSeguimiento($etapa->id, $paso->modo)?>
            </div>
        <?php endforeach ?>
        <div class="form-actions">
            <?php if ($secuencia > 0): ?><button class="button button--gray" type="button"><a href="<?=site_url('etapas/ejecutar/'.$etapa->id.'/'.($secuencia-1).($qs?'?'.$qs:''))?>">Volver</a></button><?php endif; ?>
            <button class="button" type="submit">Siguiente</button>
        </div>
    </fieldset>
</form>
<div id="modalcalendar" class="modal hide fade modalconfg modcalejec"></div>
<input type="hidden" id="urlbase" value="<?= base_url() ?>" />
<script>
    $(function() {
        moment.lang('es');
        $.each($('.js-data-cita'), function() {
            if (jQuery.trim($(this).val()) != "") {
                var id = $(this).attr('id');
                var arrdat = $(this).val().split('_');
                $('#codcita' + id).val(arrdat[0]);
                var feho = arrdat[1].split(' ');
                var fe = feho[0].split('-');
                var d = new Date(fe[0] + '/' + fe[1] + '/' + fe[2] + ' ' + feho[1]);
                var h = '';
                if (d.getHours() <= 9) {
                    h = '0' + d.getHours();
                } else {
                    h = d.getHours();
                }
                var m = '';
                if (d.getMinutes() <= 9) {
                    m = '0' + d.getMinutes();
                } else {
                    m = d.getMinutes();
                }
                var fecha = d.getDate() + '/' + (d.getMonth() + 1) + '/' + d.getFullYear() + ' ' + h + ':' +  m;
                var lab = moment(d.getFullYear() + '/' + (d.getMonth() + 1) + '/' + d.getDate()).format("LL");
                $('#txtresult' + id).html(lab + ' a las ' + h + ':' + m + " horas");
            }
        });
    });
</script>
