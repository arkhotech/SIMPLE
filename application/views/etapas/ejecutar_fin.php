<form method="POST" class="ajaxForm dynaForm" action="<?= site_url('etapas/ejecutar_fin_form/' . $etapa->id.($qs?'?'.$qs:'')) ?>">
    <fieldset>
        <div class="validacion"></div>
        <legend>Paso final</legend>
        <?php if ($tareas_proximas->estado == 'pendiente'): ?>
            <?php foreach ($tareas_proximas->tareas as $t): ?>
                <p>Para confirmar y enviar el formulario a la siguiente etapa (<?= $t->nombre ?>) haga click en Finalizar.</p>
                <?php if ($t->asignacion == 'manual'): ?>
                    <label>Asignar próxima etapa a</label>
                    <select name="usuarios_a_asignar[<?= $t->id ?>]">
                        <?php foreach ($t->getUsuarios($etapa->id) as $u): ?>
                            <option value="<?= $u->id ?>"><?= $u->usuario ?> <?=$u->nombres?'('.$u->nombres.' '.$u->apellido_paterno.')':''?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php elseif($tareas_proximas->estado=='standby'): ?>
            <p>Luego de hacer click en Finalizar esta etapa quedara detenida momentaneamente hasta que se completen el resto de etapas pendientes.</p>
        <?php elseif($tareas_proximas->estado=='completado'):?>
            <p>Luego de hacer click en Finalizar este trámite quedará completado.</p>
        <?php elseif($tareas_proximas->estado=='sincontinuacion'):?>
            <p>Este trámite no tiene una etapa donde continuar.</p>
        <?php endif; ?>

        <div class="form-actions">
            <button class="button button--gray" type="button"><a href="<?= site_url('etapas/ejecutar/' . $etapa->id . '/' . (count($etapa->getPasosEjecutables()) - 1).($qs?'?'.$qs:'')) ?>">Volver</a></button>
            <?php if($tareas_proximas->estado!='sincontinuacion'):?><button class="button" type="submit">Finalizar</button><?php endif?>
        </div>
    </fieldset>
</form>