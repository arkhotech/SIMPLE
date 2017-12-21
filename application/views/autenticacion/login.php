
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta http-equiv="content-type" content="text/html; charset=UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto+Slab">
        <link rel="stylesheet" href="<?= base_url() ?>assets/css/bootstrap.css">
        <link rel="stylesheet" href="<?= base_url() ?>assets/css/responsive.css">
        <link rel="stylesheet" href="<?= base_url() ?>assets/newhome/css/style.css">
        <link rel="stylesheet" href="<?= base_url() ?>assets/newhome/css/components.css">
        <link rel="stylesheet" href="<?= base_url() ?>assets/newhome/css/prism-min.css">
        <link rel="stylesheet" href="<?= base_url() ?>assets/newhome/css/login.css">
        <script src="<?= base_url() ?>assets/js/jquery/jquery-1.8.3.min.js"></script>
        <script src="<?= base_url() ?>assets/js/bootstrap.min.js"></script>

        <script type="text/javascript">
            var site_url = "<?= site_url() ?>";
            var base_url = "<?= base_url() ?>";
            var site_key = "<?= sitekey() ?>";

            var onloadCallback = function() {
                if ($('#login_captcha').length && '<?=$this->session->flashdata('login_erroneo')?>' == 'TRUE') {
                    grecaptcha.render('login_captcha', {
                        'sitekey' : site_key
                   });
                }

                if ($('#form_captcha').length) {
                    grecaptcha.render('form_captcha', {
                        sitekey : site_key
                    });
                }
            };

            $(document).ready(function() {

                $("#login .submit").click(function() {

                    var form = $("#login");
                    if (!$(form).prop("submitting")) {
                        $(form).prop("submitting", true);
                        $('#login .ajaxLoader').show();
                        $.ajax({
                            url: $(form).prop("action"),
                            data: $(form).serialize(),
                            type: $(form).prop("method"),
                            dataType: "json",
                            success: function(response) {
                                if (response.validacion) {
                                    if (response.redirect) {
                                        window.location = response.redirect;
                                    } else {
                                        var f = window[$(form).data("onsuccess")];
                                        f(form);
                                    }
                                } else {
                                    if ($('#login_captcha').length > 0) {
                                        if ($('#login_captcha').is(':empty')) {
                                            grecaptcha.render('login_captcha', {
                                                'sitekey' : site_key
                                            });
                                        } else {
                                            grecaptcha.reset();
                                        }
                                    }

                                    $(form).prop("submitting", false);
                                    $('#login .ajaxLoader').hide();

                                    $(".validacion").html(response.errores);
                                    $('html, body').animate({
                                        scrollTop: $(".validacion").offset().top - 10
                                    });
                                }
                            },
                            error: function() {
                                $(form).prop("submitting", false);
                                $('#login .ajaxLoader').hide();
                            }
                        });
                    }
                    return false;
                });
            });
        </script>
    </head>
    <body>
        <div class="container">
            <div class="row" style="margin-top: 100px;">
                <div class="span6 offset3">
                    <form id="login" method="post" class="ajaxForm" action="<?= site_url('autenticacion/login_form') ?>">        
                        <fieldset>
                            <legend>Autenticación</legend>
                            <?php $this->load->view('messages') ?>
                            <div class="validacion"></div>
                            <label for="name">Usuario o Correo electr&oacute;nico</label>
                            <input name="usuario" id="name" type="text" class="input-xlarge">
                            <label for="password">Contrase&ntilde;a</label>
                            <input name="password" id="password" type="password" class="input-xlarge">
                            <div id="login_captcha"></div>
                            <input type="hidden" name="redirect" value="<?=$redirect?>" />
                            <p>
                                <a href="<?=site_url('autenticacion/olvido')?>">¿Olvidaste tu contrase&ntilde;a?</a>
                            </p>
                            <p>
                                <span>O utilice</span> <a href="<?=site_url('autenticacion/login_openid?redirect='.$redirect)?>">
                                <img src="<?= base_url() ?>assets/newhome/images/logo.4583c3bc.png" alt="ClaveÚnica" width="96" height="32"/></a>
                            </p>
                            <div class="form-actions">
                                <a class="button button--lightgray" href="<?= base_url() ?>">Volver</a>
                                <a class="button submit" href="#">Ingresar</a>
                            </div>
                        </fieldset>
                        <div class='ajaxLoader'>Cargando</div>
                    </form>
                </div>
            </div>
        </div> <!-- /container -->
        <script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit&hl=es"></script>
    </body>
</html>
