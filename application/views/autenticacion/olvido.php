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

                $("input[name=usuario]").keypress(function (e) {

                    if (e.which == 13) {
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
                  }
                });

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
                    <form id="login" method="post" class="ajaxForm" action="<?= site_url('autenticacion/olvido_form') ?>">        
                        <fieldset>
                            <legend>¿Olvidaste tu contrase&ntilde;a?</legend>
                            <?php $this->load->view('messages') ?>
                            <div class="validacion"></div>

                            <p>Al hacer click en Reestablecer se te enviara un email indicando las instrucciones para reestablecer tu contrase&ntilde;a.</p>

                            <label>Usuario o Correo electrónico</label>
                            <input name="usuario" type="text" class="input-xlarge">

                            <div class="form-actions">
                                <a class="button button--lightgray" href="#" onclick="javascript:history.back();">Volver</a>
                                <a class="button submit" href="#" >Reestablecer</a>
                            </div>
                        </fieldset>
                        <div class='ajaxLoader'>Cargando</div>
                    </form>
                </div>
            </div>
        </div>
    </body>
</html>
