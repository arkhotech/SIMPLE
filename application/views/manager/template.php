<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Tramitador</title>

        <!-- Le styles -->

        <link href="<?= base_url() ?>assets/css/bootstrap.css" rel="stylesheet">
        <link href="<?= base_url() ?>assets/css/responsive.css" rel="stylesheet">
        <link href="<?= base_url() ?>assets/js/file-uploader/fileuploader.css" rel="stylesheet">
        <link href="<?= base_url() ?>assets/css/bootstrap2-toggle.min.css" rel="stylesheet">
        <link href="<?= base_url() ?>assets/css/common.css?v0.1" rel="stylesheet">


        <script src="<?= base_url() ?>assets/js/jquery/jquery-1.8.3.min.js" type="text/javascript"></script>
        <script src="<?= base_url() ?>assets/js/bootstrap.min.js" type="text/javascript"></script> <?php //JS base       ?>
        <script src="<?= base_url() ?>assets/js/bootstrap-datepicker/js/bootstrap-datepicker.js"></script>
        <script src="<?= base_url() ?>assets/js/bootstrap-datepicker/js/locales/bootstrap-datepicker.es.js"></script>
        <script src="<?= base_url() ?>assets/js/bootstrap2-toggle.min.js"></script>
        <script src="<?= base_url() ?>assets/js/jquery.chosen/chosen.jquery.min.js"></script> <?php //Soporte para selects con multiple choices     ?>
        <script src="<?= base_url() ?>assets/js/file-uploader/fileuploader.js"></script> <?php //Soporte para subir archivos con ajax     ?>

        <script src="<?= base_url() ?>assets/js/common.js"></script>
        <script type="text/javascript">
            var site_url = "<?= site_url() ?>";
            var base_url = "<?= base_url() ?>";

            $(document).ready(function() {

                $('#toggle_ambiente_dev').change(function() {
                    if ($(this).prop('checked')) {
                        $('#vinculo_prod').show(300);
                    } else {
                        $('#vinculo_prod').hide(300);
                    }
                });
            });
        </script>
    </head>
    <body>
        <div class="container-fluid">

            <header class="row-fluid">
                <div class="span4">
                    <h1><a href="<?= site_url('backend/portada') ?>"><img src="<?= base_url() ?>assets/img/logo.png" alt="Tramitador" /></a></h1>
                </div>
                <div class="span8">
                    <div class="row-fluid">
                        <div class="span12">
                            <ul id="userMenu" class="nav nav-pills pull-right">
                                <li class="dropdown">
                                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">Bienvenido <?= UsuarioManagerSesion::usuario()->usuario ?><span class="caret"></span></a>
                                    <ul class="dropdown-menu">
                                        <li><a href="<?= site_url('manager/autenticacion/logout') ?>">Cerrar sesión</a></li>
                                    </ul>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </header>
            <div id="main">
                <div class="row-fluid">
                    <div class="span3">
                        <ul class="nav nav-list">
                            <li><a href="<?= site_url('manager') ?>">Portada</a></li>
                            <li class="nav-header">Administración</li>
                            <li><a href="<?= site_url('manager/cuentas') ?>">Cuentas</a></li>
                            <li><a href="<?= site_url('manager/usuarios') ?>">Usuarios Backend</a></li>
                            <li><a href="<?= site_url('manager/diaferiado') ?>">D&iacute;as Feriados</a></li>
                            <li><a href="<?= site_url('manager/categorias') ?>">Categorías</a></li>
                            <li class="nav-header">Consultas</li>
                            <li><a href="<?= site_url('manager/tramites_expuestos') ?>">Trámites expuestos como servicios</a></li>      
                            <li class="nav-header">Estadisticas</li>
                            <li><a href="<?= site_url('manager/estadisticas/cuentas') ?>">Trámites en curso</a></li>                  
                        </ul>
                    </div>
                    <div class="span9">
                        <?=$this->session->flashdata('message')?'<div class="alert alert-success">'.$this->session->flashdata('message').'</div>':''?>
                        <?php $this->load->view($content) ?>
                    </div>
                </div>
            </div>

            <footer class="row-fluid">
                 <div class="area2">
                <div class="container-fluid">
                    <div class="row-fluid">
                        <div class="span3 align-center">
                            <img src="<?= base_url() ?>assets/img/logo.png" alt=""/>
                            <br>
                            <br>
                            <div class="align-center">
                                <a href="<?= base_url() ?>assets/license.txt" target="_blank">
                                    <img class="media-object" src="<?= base_url() ?>assets/img/ico_cc.png" alt="CC" />
                                </a>
                            </div>
                            <br>
                            <div class="">
                                <p>
                                    <a href="http://instituciones.chilesinpapeleo.cl/page/view/simple" target="_blank">Powered by SIMPLE</a>
                                </p>
                            </div>
                        </div>
                        <div class="span6 align-center">

                        </div>
                        <div class="span3">
                            <a href="http://www.gob.cl" target="_blank"><img src="<?= base_url() ?>assets/img/gobierno_chile.png" alt="Gobierno de Chile" /></a>
                        </div>
                    </div>
                </div>
            </div>
            </footer>
        </div>
    </body>
</html>
