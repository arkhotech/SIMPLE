<ul class="breadcrumb">
    <li>
        <a href="<?= site_url('backend/reportes') ?>">Gestión</a> <span class="divider">/</span>
    </li>
    <li><a href="<?=site_url('backend/reportes/listar/'.$proceso->id)?>"><?= $proceso->nombre ?></a></li> <span class="divider">/</span>
    <li class="active"><?=$edit?$reporte->nombre:'Crear reporte'?></li>
</ul>

<script type="text/javascript">

	function seleccionarHeader(){

		$("#disponibles").find(":selected").each(function(i,el){

			$(el).detach().appendTo($("#seleccionados"));			
			
		});

	}

	function eliminarHeader(){
		$("#seleccionados").find(":selected").each(function(i,el){

			$(el).detach().appendTo($("#disponibles").find("[label='"+ $(el).attr("name")+"']"));
			
			
		});

		
	}

	function subirOrden(){

		$("#seleccionados").find(":selected").each(function(i,el){
			var anterior = $(el).prev();
			if( $(anterior).size()>0 && !($(anterior).prop("selected"))){

				$(el).detach().insertBefore($(anterior));

			}
		});
		
	}

	function bajarOrden(){

		jQuery.fn.reverse = [].reverse;
		
		$("#seleccionados").find(":selected").reverse().each(function(i,el){
			var anterior = $(el).next();
			if( $(anterior).size()>0 && !($(anterior).prop("selected"))){

				$(el).detach().insertAfter($(anterior));

			}
		});
	}

	function selectAll(){

		$("#seleccionados").find("*").prop("selected",true);
	}

</script>


<form class="ajaxForm" method="POST" action="<?=site_url('backend/reportes/editar_form/'.($edit?$reporte->id:''))?>">
    <fieldset>
        <legend>Crear Reporte</legend>
        <div class="validacion"></div>
        <?php if(!$edit):?>
        <input type="hidden" name="proceso_id" value="<?=$proceso->id?>" />
            <input type="hidden" name="proceso_root" value="<?=$proceso->root?>" />
        <?php endif; ?>
        <label>Nombre</label>
        <input type="text" name="nombre" value="<?=$edit?$reporte->nombre:''?>" />
        <label>Campos</label>
        <div class="form-inline">
	        <select id="disponibles" style="height: 240px;" multiple>
	        	<?php
	        		$tramiteHeaders = Tramite::getReporteHeaders();
	        		$camposHeaders = $proceso->getCamposReporteHeaders();
	        		$variablesHeaders = $proceso->getVariablesReporteHeaders();
	        	?>
	        	<optgroup label="Datos de Trámite">
	        	<?php foreach($tramiteHeaders as $rh):?>
	        		<?php if (!($edit && in_array($rh,$reporte->campos))):?>
		            	<option value="<?=$rh?>" name="Datos de Trámite"><?=$rh?></option>
		            <?php endif;?>
        		<?php endforeach;?>
	        	</optgroup>
	        	
	        	<optgroup label="Campos de Formularios">
					<?php foreach($camposHeaders as $c):?>
					<?php if (!($edit && in_array($c, $reporte->campos))):?>
		            	<option value="<?=$c?>" name="Campos de Formularios"><?=$c?></option>
		            <?php endif;?>
		            <?php endforeach; ?>
	        	</optgroup>
	        	
	        	<optgroup label="Variables">
	        		<?php foreach ($variablesHeaders as $v):?>
					<?php if (!($edit && in_array($v,$reporte->campos))):?>
		            	<option value="<?=$v?>" name="Variables"><?=$v?></option>
		            <?php endif;?>
					<?php endforeach;?>
	        	</optgroup>

	        </select>
            <div class="btn-group-vertical" role="group">
	        	<button class = "btn btn-primary" type="button" onclick="seleccionarHeader()"><i class="icon-white icon-chevron-right"></i></button>
	        	<button class = "btn btn-primary" type="button" onclick="eliminarHeader()"><i class="icon-white icon-chevron-left"></i></button>
			</div>
	        
	        <select id="seleccionados" name="campos[]" style="height: 240px;" multiple>
	        	<?php foreach($reporte->campos as $c):?>
	        	<option value="<?=$c?>" name = "<?php 
	        		if (in_array($c,$tramiteHeaders))
	        			echo "Datos de Trámite";
	        		else if (in_array($c,$camposHeaders))
	        			echo "Campos de Formularios";
	        		else if (in_array($c, $variablesHeaders))
	        			echo "Variables";
	        	
	        	?>"><?=$c?></option>
	        	<?php endforeach;?>
	        </select>
	        
	        <div class="btn-group-vertical" role="group">
            <button class = "btn btn-primary" type="button" onclick="subirOrden()"><i class="icon-white icon-chevron-up"></i></button>
	        <button class = "btn btn-primary" type="button" onclick="bajarOrden()"><i class="icon-white icon-chevron-down"></i></button>
	        </div>  
        <div/>
        
        
        <div class="form-actions">
            <a class="btn" href="<?=site_url('backend/reportes/listar/'.$proceso->id)?>">Cancelar</a>
            <input class="btn btn-primary" type="submit" onclick="selectAll();" value="Guardar" />
        </div>
    </fieldset>
</form>




</div>