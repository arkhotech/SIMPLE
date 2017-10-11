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
    </head>
    <body>
        <div class="container">
            <div class="row" style="margin-top: 100px;">
                <div class="span6 offset3">
                    <form method="post" class="ajaxForm" action="<?= site_url('autenticacion/olvido_form') ?>">        
                        <fieldset>
                            <legend>¿Olvidaste tu contrase&ntilde;a?</legend>
                            <?php $this->load->view('messages') ?>
                            <div class="validacion"></div>

                            <p>Al hacer click en Reestablecer se te enviara un email indicando las instrucciones para reestablecer tu contrase&ntilde;a.</p>

                            <label>Usuario o Correo electrónico</label>
                            <input name="usuario" type="text" class="input-xlarge">

                            <div class="form-actions">
                                <a class="button button--lightgray" href="#" onclick="javascript:history.back();">Volver</a>
                                <a class="button" type="submit" href="#"  onclick="javascript:this.form.submit();">Reestablecer</a>
                            </div>
                        </fieldset>
                    </form>
                </div>
            </div>
        </div>
    </body>
</html>
