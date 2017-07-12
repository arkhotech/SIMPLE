<!DOCTYPE html>
<html lang="es">
    <head>
        <?php $this->load->view('head')?>
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
                            <label for="name">Usuario o Correo electrónico</label>
                            <input name="usuario" id="name" type="text" class="input-xlarge">
                            <label for="password">Contraseña</label>
                            <input name="password" id="password" type="password" class="input-xlarge">
                            <div id="login_captcha"></div>
                            <input type="hidden" name="redirect" value="<?=$redirect?>" />
                            
                            <p><a href="<?=site_url('autenticacion/olvido')?>">¿Olvidaste tu contraseña?</a> - <a href="<?= site_url('autenticacion/registrar') ?>">¿No estas registrado?</a></p>
                            <p>O utilice <a href="<?=site_url('autenticacion/login_openid?redirect='.$redirect)?>"><img src="<?= base_url() ?>assets/img/claveunica-medium.png" alt="ClaveÚnica"/></a></p>

                            <div class="form-actions">
                                <a class="btn" href="#" onclick="javascript:history.back();">Volver</a>
                                <button class="btn btn-primary" type="submit">Ingresar</button>
                            </div>
                        </fieldset>
                    </form>
                </div>
            </div>
        </div> <!-- /container -->
        <script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit&hl=es"></script>
    </body>
</html>
