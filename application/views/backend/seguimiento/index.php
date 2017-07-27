<script>
    function actualizarIdTramites() {
        $("#modal").load(site_url + "backend/seguimiento/ajax_actualizar_id_tramite/");
        $("#modal").modal();
        return false;
    }

</script>

<ul class="breadcrumb">
	<li>Seguimiento de Procesos</li>
</ul>

<?php // if(UsuarioBackendSesion::usuario()->rol!='seguimiento'): 
        if(in_array( 'super',explode(',',UsuarioBackendSesion::usuario()->rol))):
    ?>
<div class="btn-group">
	<a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
		Operaciones <span class="caret"></span>
	</a>

	<ul class="dropdown-menu">
		<li><a href="#" onclick="return actualizarIdTramites();">Actualizar ID de Trámites</a></li>
	</ul>
</div>
<?php endif;?>

<table class="table">
	<thead>
		<tr>
			<th>Proceso
                <a href="/assets/ayuda/simple/backend/seguimiento-de-procesos.html" target="_blank">
                    <span class="glyphicon glyphicon-info-sign"></span>
                </a>
			</th>
			<th>Acciones</th>
		</tr>
	</thead>
	<tbody>
		
        <?php foreach($procesos as $p): ?>
        <?php  if(is_null((UsuarioBackendSesion::usuario()->procesos))): ?>
        <tr>
			<td><?=$p->nombre?></td>
			<td><a class="btn btn-primary" href="<?=site_url('backend/seguimiento/index_proceso/'.$p->id)?>"><i class="icon-eye-open icon-white"></i> Ver seguimiento</a></td>
		</tr>
        <?php elseif( in_array( $p->id,explode(',',UsuarioBackendSesion::usuario()->procesos))): ?>
        <tr>
			<td><?=$p->nombre?></td>
			<td><a class="btn btn-primary" href="<?=site_url('backend/seguimiento/index_proceso/'.$p->id)?>"><i class="icon-eye-open icon-white"></i> Ver seguimiento</a></td>
		</tr>	
		<?php endif; ?>
        <?php endforeach; ?>
    </tbody>
</table>

<div id="modal" class="modal hide fade" >

</div>
