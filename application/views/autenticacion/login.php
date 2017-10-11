
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
        </script>
    </head>
    <body>
        <div class="container">
            <div class="row" style="margin-top: 100px;">
                <div class="span6 offset3">
                    <form method="post" class="ajaxForm" action="<?= site_url('autenticacion/login_form') ?>">        
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
                            
                            <p><a href="<?=site_url('autenticacion/olvido')?>">¿Olvidaste tu contrase&ntilde;a?</a></p>
                            <p><span>O utilice</span> <a href="<?=site_url('autenticacion/login_openid?redirect='.$redirect)?>"><img src="https://claveunica.gob.cl/images/logo.4583c3bc.png" alt="ClaveÚnica" width="96" height="32"/></a></p>

                            <div class="form-actions">
                                <a class="button button--lightgray" href="#" onclick="javascript:history.back();">Volver</a>
                                <a class="button" type="submit" href="#"  onclick="javascript:this.form.submit();">Ingresar</a>
                            </div>
                        </fieldset>
                    </form>
                </div>
            </div>
        </div> <!-- /container -->
        <script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit&hl=es"></script>
    </body>
</html>
