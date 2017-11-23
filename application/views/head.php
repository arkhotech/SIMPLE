<meta charset="utf-8">
<title><?=Cuenta::cuentaSegunDominio()!='localhost'?Cuenta::cuentaSegunDominio()->nombre_largo:'SIMPLE'?> - <?= $title ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Le styles -->
<link href="<?= base_url() ?>assets/css/bootstrap.css" rel="stylesheet">
<link href="<?= base_url() ?>assets/css/responsive.css" rel="stylesheet">
<link href="<?= base_url() ?>assets/js/bootstrap-datepicker/css/datepicker.css" rel="stylesheet">
<link href="<?= base_url() ?>assets/js/handsontable/dist/handsontable.full.min.css" rel="stylesheet">
<link href="<?= base_url() ?>assets/js/jquery.chosen/chosen.css" rel="stylesheet">
<link href="<?= base_url() ?>assets/js/file-uploader/fileuploader.css" rel="stylesheet">
<link href="<?= base_url() ?>assets/css/common.css?v=0.11" rel="stylesheet">
<link href="<?= base_url() ?>assets/css/frontend.css" rel="stylesheet">

<!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
<!--[if lt IE 9]>
    <script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->

<!-- Le fav and touch icons -->
<link rel="shortcut icon" href="<?= base_url() ?>assets/img/favicon.png">

<script src="<?= base_url() ?>assets/js/jquery/jquery-1.8.3.min.js"></script>
<script src="<?= base_url() ?>assets/js/bootstrap.min.js"></script>
<script src="<?= base_url() ?>assets/js/bootstrap-datepicker/js/bootstrap-datepicker.js"></script>
<script src="<?= base_url() ?>assets/js/bootstrap-datepicker/js/locales/bootstrap-datepicker.es.js"></script>
<script src="<?= base_url() ?>assets/js/handsontable/dist/handsontable.full.min.js" type="text/javascript"></script> <?php //JS para hacer grillas     ?>
<script src="<?= base_url() ?>assets/js/jquery.chosen/chosen.jquery.min.js"></script> <?php //Soporte para selects con multiple choices    ?>
<script src="<?= base_url() ?>assets/js/file-uploader/fileuploader.js"></script> <?php //Soporte para subir archivos con ajax    ?>
<script src="https://maps.googleapis.com/maps/api/js?key=<?= mapskey() ?>&libraries=places&&language=ES"></script>
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
            grecaptcha.render("form_captcha", {
                sitekey : site_key
            });
        }
    };

</script>

<script src="<?= base_url() ?>assets/js/common.js"></script>
<script src="<?= base_url() ?>assets/js/nikEditor/nikEditor.js" type="text/javascript"></script>
