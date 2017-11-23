<!DOCTYPE html>
<html lang="es">
    <head>
        <meta  http-equiv="content-type" content="text/html; charset=UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?=Cuenta::cuentaSegunDominio()!='localhost'?Cuenta::cuentaSegunDominio()->nombre_largo:'SIMPLE'?> - <?= $title ?></title>

        <!-- Le styles -->
        
        <link href="<?= base_url() ?>assets/css/bootstrap.css" rel="stylesheet">
        <link href="<?= base_url() ?>assets/css/responsive.css" rel="stylesheet">
        <link href="<?= base_url() ?>assets/css/common.css?v=0.2" rel="stylesheet">

        <link href="<?= base_url() ?>assets/css/frontend.css" rel="stylesheet">

        <link href="<?= base_url() ?>assets/js/bootstrap-datepicker/css/datepicker.css" rel="stylesheet">
        <link href="<?= base_url() ?>assets/js/handsontable/dist/handsontable.full.min.css" rel="stylesheet">
        <link href="<?= base_url() ?>assets/js/jquery.chosen/chosen.css" rel="stylesheet">
        <link href="<?= base_url() ?>assets/js/jquery.select2/dist/css/select2.css" rel="stylesheet">
        <link href="<?= base_url() ?>assets/js/file-uploader/fileuploader.css" rel="stylesheet">
        
        <link href="<?= base_url() ?>assets/css/modelador-formularios.css" rel="stylesheet">
        <link href="<?= base_url() ?>assets/css/dashboard.css" rel="stylesheet">

        <!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
        <!--[if lt IE 9]>
          <script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>
        <![endif]-->

        <!-- Le fav and touch icons -->
        <link rel="shortcut icon" href="<?= base_url() ?>assets/img/favicon.png">

        <script src="<?= base_url() ?>assets/js/jquery/jquery-1.8.3.min.js" type="text/javascript"></script>

        <script src="<?= base_url() ?>assets/js/bootstrap.min.js" type="text/javascript"></script> <?php //JS base     ?>
        <script src="<?= base_url() ?>assets/js/bootstrap-datepicker/js/bootstrap-datepicker.js"></script>
        <script src="<?= base_url() ?>assets/js/handsontable/dist/handsontable.full.min.js" type="text/javascript"></script> <?php //JS para hacer grillas     ?>
        <script src="<?= base_url() ?>assets/js/bootstrap-datepicker/js/locales/bootstrap-datepicker.es.js"></script>
        <script src="<?= base_url() ?>assets/js/bootstrap-typeahead-multiple/bootstrap-typeahead-multiple.js" type="text/javascript"></script> <?php //JS typeahead modificado para multiples items     ?>
        <script src="<?= base_url() ?>assets/js/jquery.chosen/chosen.jquery.min.js"></script> <?php //Soporte para selects con multiple choices     ?>
        <script src="<?= base_url() ?>assets/js/jquery.select2/dist/js/select2.min.js"></script> <?php //Soporte para selects con multiple choices     ?>
        <script src="<?= base_url() ?>assets/js/jquery.select2/dist/js/i18n/es.js"></script> <?php //Soporte para selects con multiple choices     ?>
        <script src="<?= base_url() ?>assets/js/file-uploader/fileuploader.js"></script> <?php //Soporte para subir archivos con ajax     ?>
        <script src="<?= base_url() ?>assets/js/jquery-ui/js/jquery-ui-1.9.2.custom.min.js" type="text/javascript"></script>

        <script src="<?= base_url() ?>assets/js/jquery.ui.touch-punch/jquery.ui.touch-punch.min.js" type="text/javascript"></script> <?php //JS para soportar drag & drop en iPad     ?>
        <script src="<?= base_url() ?>assets/js/jquery.ui.livedraggable/jquery.ui.livedraggable.js" type="text/javascript"></script> <?php //JS para que evento draggable sea live     ?>
        <script src="<?= base_url() ?>assets/js/jquery.doubletap/jquery.doubletap.js" type="text/javascript"></script> <?php //JS para soportar dobleclick en iPad     ?>
        <script src="<?= base_url() ?>assets/js/json-js/json2.js" type="text/javascript"></script> <?php //JS para convertir objetos a notacion JSON en multiples browsers     ?>
        <script src="<?= base_url() ?>assets/js/highcharts/highcharts.js" type="text/javascript"></script> <?php //JS para hacer graficos     ?>

        <script src="https://maps.googleapis.com/maps/api/js?key=<?= mapskey() ?>&libraries=places&&language=ES"></script>

        <script type="text/javascript">
            var site_url = "<?= site_url() ?>";
            var base_url = "<?= base_url() ?>";

            var onloadCallback = function() {
                if ($('#form_captcha').length) {
                    grecaptcha.render("form_captcha", {
                        sitekey : "6Le7zycUAAAAAKrvp-ndTrKRni3yeuCZQyrkJRfH"
                    });
                }
            };
        </script>
        <script src="<?= base_url() ?>assets/js/common.js" type="text/javascript"></script>
        <script src="<?= base_url() ?>assets/js/backend.js" type="text/javascript"></script>
        <script src="<?= base_url() ?>assets/js/nikEditor/nikEditor.js" type="text/javascript"></script>

        <link href="<?= base_url() ?>assets/timepicker/css/timepicker.less" rel="stylesheet">
        <link href="<?= base_url() ?>assets/timepicker/css/bootstrap-timepicker.min.css" rel="stylesheet">
        <script src="<?= base_url() ?>assets/timepicker/js/bootstrap-timepicker.js" type="text/javascript"></script>
    </head>
    <body>

        <header>
            <div class="container-fluid">
                <div class="row-fluid">
                    <div class="span2">
                        <h1 id="logo">
                            <a href="<?= site_url('backend/portada') ?>">
                                <img src="<?= base_url() ?>assets/img/logo.png" alt="Tramitador" />
                            </a>
                        </h1>
                    </div>
                    <div class="span10">
                        <div class="row-fluid">
                            <div class="span12">
                                <ul id="userMenu" class="nav nav-pills pull-right">
                                    <li class="dropdown">
                                        <a class="dropdown-toggle" data-toggle="dropdown" href="#">Bienvenido <?= UsuarioBackendSesion::usuario()->email ?><span class="caret"></span></a>
                                        <ul class="dropdown-menu">
                                            <li><a href="<?= site_url('backend/cuentas') ?>">Mi Cuenta</a></li>
                                            <li><a href="<?= site_url('backend/autenticacion/logout') ?>">Cerrar sesión</a></li>
                                        </ul>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="row-fluid">
                            <div class="span12">
                                <ul id="menu" class="nav nav-pills pull-right">
                                    <?php if (in_array('super', explode(',',UsuarioBackendSesion::usuario()->rol)) || in_array('gestion', explode(',',UsuarioBackendSesion::usuario()->rol))
                                            || in_array('reportes', explode(',',UsuarioBackendSesion::usuario()->rol))): ?>
                                        <li <?= $this->uri->segment(2) == 'gestion' || !$this->uri->segment(2) ? 'class="active"' : '' ?>><a href="<?= site_url('backend/gestion') ?>">Inicio</a></li>
                                    <?php endif ?>
                                    <?php if (in_array('super', explode(',',UsuarioBackendSesion::usuario()->rol))  || in_array('agenda', explode(',',UsuarioBackendSesion::usuario()->rol))): ?>
                                        <li <?= $this->uri->segment(2) == 'agendas' ? 'class="active"' : '' ?>><a href="<?= site_url('backend/agendas') ?>">Agenda</a></li>
                                    <?php endif ?>
                                    <?php if (in_array('super', explode(',',UsuarioBackendSesion::usuario()->rol))  || in_array('modelamiento', explode(',',UsuarioBackendSesion::usuario()->rol))): ?>
                                        <li <?= $this->uri->segment(2) == 'procesos' || $this->uri->segment(2) == 'formularios' ? 'class="active"' : '' ?>><a href="<?= site_url('backend/procesos') ?>">Modelador de Procesos</a></li>
                                    <?php endif ?>
                                    <?php if (in_array('super', explode(',',UsuarioBackendSesion::usuario()->rol)) || in_array('operacion', explode(',',UsuarioBackendSesion::usuario()->rol)) 
                                    || in_array('seguimiento', explode(',',UsuarioBackendSesion::usuario()->rol))): ?>
                                        <li <?= $this->uri->segment(2) == 'seguimiento' ? 'class="active"' : '' ?>><a href="<?= site_url('backend/seguimiento') ?>">Seguimiento</a></li>
                                    <?php endif ?>
                                    <?php if (in_array('super', explode(',',UsuarioBackendSesion::usuario()->rol)) || in_array('gestion', explode(',',UsuarioBackendSesion::usuario()->rol))
                                              || in_array('reportes', explode(',',UsuarioBackendSesion::usuario()->rol))): ?>
                                        <li <?= $this->uri->segment(2) == 'reportes' || !$this->uri->segment(2) ? 'class="active"' : '' ?>><a href="<?= site_url('backend/reportes') ?>">Gestión</a></li>
                                    <?php endif ?>
									<?php if (in_array('super', explode(',',UsuarioBackendSesion::usuario()->rol))): ?>
                                        <li <?= $this->uri->segment(2) == 'auditoria' ? 'class="active"' : '' ?>><a href="<?= site_url('backend/auditoria') ?>">Auditoría</a></li>
                                    <?php endif ?>
                                    <?php if (in_array('super', explode(',',UsuarioBackendSesion::usuario()->rol)) || in_array('desarrollo', explode(',',UsuarioBackendSesion::usuario()->rol))): ?>
                                        <li <?= $this->uri->segment(2) == 'api' || !$this->uri->segment(2) ? 'class="active"' : '' ?>><a href="<?= site_url('backend/api') ?>">API</a></li>
                                    <?php endif ?>
                                    <?php if (in_array('super', explode(',',UsuarioBackendSesion::usuario()->rol))
                                    		|| in_array('configuracion', explode(',',UsuarioBackendSesion::usuario()->rol))): ?>
                                        <li <?= $this->uri->segment(2) == 'configuracion' ? 'class="active"' : '' ?>><a href="<?= site_url('backend/configuracion') ?>">Configuración</a></li>
                                    <?php endif ?>
                                    <li><a href="<?= site_url('assets/ayuda/simple')?>" target="_blank">Ayuda</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div id="main">
            <div class="container-fluid">
        <?php $this->load->view($content) ?> 
            </div>
        </div>

        <footer>
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
                                <p><a href="http://instituciones.chilesinpapeleo.cl/page/view/simple" target="_blank">Powered by SIMPLE</a></p>
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
        <script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit&hl=es"></script>
    </body>
</html>
