<div class="row-fluid">
    <div class="span3">
        <?php $this->load->view('backend/api/sidebar') ?>
    </div>
    <div class="span9">
        <h2><?=$title?></h2>
        <table class="table">
            <tr>
                <th>Nombre del Proceso</th>
                <th>Tarea</th>
                <th>Estado</th>
                <th>Versión</th>
                <th>Descripción</th>
                <th>Url</th>
            </tr>       
            <?
                //$nombre_host = gethostname();
                $host = $this->input->server('HTTP_HOST');
                ($_SERVER['HTTPS'] ? $protocol = 'https://' : $protocol = 'http://');
                foreach ($json as $res){ 
            ?>
                <tr>
                    <td><? echo $res['nombre'] ?></td>
                    <td><? echo $res['tarea'] ?></td>
                    <td><? echo $res['estado'] == 'draft' ? "Draft" : "Publicado" ?></td>
                    <td><? echo $res['version'] ?></td>
                    <td><? $res['previsualizacion'] ?></td>
                    <td>
                        <a class="btn btn-default" target="_blank" href="<? echo $protocol.$host.'/integracion/especificacion/servicio/proceso/'.$res['id'].'/tarea/'.$res['id_tarea']; ?> ">
                            <i class="icon-upload icon"></i>Swagger
                        </a>
                    </td>
                </tr>
            <? } ?> 
        </table>
    </div>
</div>