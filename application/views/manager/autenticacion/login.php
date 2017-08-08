<!DOCTYPE html>
<html lang="es">
    <head>
        <base href="<?= base_url() ?>" />
        <meta charset="utf-8">
        <title>Tramitador - Autenticación</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="">
        <meta name="author" content="">

        <!-- Le styles -->
        <link href="assets/css/bootstrap.css" rel="stylesheet">
        <link href="assets/css/bootstrap-responsive.css" rel="stylesheet">
        <link href="assets/js/bootstrap-datepicker/css/datepicker.css" rel="stylesheet">
        <link href="assets/css/common.css" rel="stylesheet">

        <!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
        <!--[if lt IE 9]>
          <script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>
        <![endif]-->

        <!-- Le fav and touch icons -->
        <link rel="shortcut icon" href="assets/ico/favicon.ico">
        <link rel="apple-touch-icon-precomposed" sizes="114x114" href="assets/ico/apple-touch-icon-114-precomposed.png">
        <link rel="apple-touch-icon-precomposed" sizes="72x72" href="assets/ico/apple-touch-icon-72-precomposed.png">
        <link rel="apple-touch-icon-precomposed" href="assets/ico/apple-touch-icon-57-precomposed.png">

        <script type="text/javascript">
            var site_key = "<?= sitekey() ?>";

            var onloadCallback = function() {
                if ($('#login_captcha').length && '<?=$this->session->flashdata('login_erroneo')?>' == 'TRUE') {
                    grecaptcha.render('login_captcha', {
                        'sitekey' : site_key
                   });
                }
            };
        </script>

    </head>

    <body>

        <div class="container">
            <div class="row" style="margin-top: 100px;">
                <div class="span6 offset3">
                    <form method="post" class="ajaxForm" action="<?= site_url('manager/autenticacion/login_form') ?>">
                        <fieldset>
                            <legend>Ingrese al Manager</legend>
                            <div class="validacion"></div>
                            <input type="hidden" name="redirect" value="<?= $redirect ?>" />
                            <label>Usuario</label>
                            <input name="usuario" type="text" class="span3">
                            <label>Contraseña</label>
                            <input name="password" type="password" class="span3">
                            <div id="login_captcha"></div>
                            <div class="form-actions">
                                <button class="btn btn-primary" type="submit">Ingresar</button>
                            </div>
                        </fieldset>
                    </form>
                </div>
            </div>


        </div> <!-- /container -->



        <!-- Le javascript
        ================================================== -->
        <!-- Placed at the end of the document so the pages load faster -->
        <script src="assets/js/jquery/jquery-1.8.3.min.js"></script>
        <script src="assets/js/bootstrap.min.js"></script>
        <script src="assets/js/bootstrap-datepicker/js/bootstrap-datepicker.js"></script>
        <script src="assets/js/bootstrap-datepicker/js/locales/bootstrap-datepicker.es.js"></script>
        <script src="assets/js/jquery.chosen/chosen.jquery.min.js"></script> <?php //Soporte para selects con multiple choices   ?>
        <script src="assets/js/common.js"></script>
        <script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit&hl=es"></script>
    </body>
</html>
