<!doctype html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang=""> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8" lang=""> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9" lang=""> <![endif]-->
<!--[if gt IE 8]><!-->

<html class="no-js" lang=""> <!--<![endif]-->
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title><?=Cuenta::cuentaSegunDominio()!='localhost'?Cuenta::cuentaSegunDominio()->nombre_largo:'SIMPLE'?> - <?= $title ?></title>
    <!--[if IE]><link rel="shortcut icon" href="/favicon.ico"><![endif]-->
    <link rel="icon" href="/favicon.png">

    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="<?= base_url() ?>assets/home/css/apple-touch-icon.png" rel="apple-touch-icon">
    <link href="<?= base_url() ?>assets/home/css/gobstrap.min.css" rel="stylesheet">
    <link href="<?= base_url() ?>assets/home/css/gobstrap-theme.min.css" rel="stylesheet">
    <link href="<?= base_url() ?>assets/home/css/main.css" rel="stylesheet">
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css" rel="stylesheet">

    <!-- <script src="js/vendor/modernizr-2.8.3-respond-1.4.2.min.js"></script> -->
  </head>
  <body>
    <!--[if lt IE 8]>
      <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
    <![endif]-->
    <header class="navbar" id="sticker">
      <div class="container">
        <div class="navbar-header">
          <div class="navbar-brand">
            <a href="<?= site_url() ?>">
              <img class="logo" src="<?= Cuenta::cuentaSegunDominio()!='localhost' ? Cuenta::cuentaSegunDominio()->logoADesplegar : base_url('assets/img/logo.png') ?>" alt="<?= Cuenta::cuentaSegunDominio()!='localhost' ? Cuenta::cuentaSegunDominio()->nombre_largo : 'Simple' ?>" />
            </a>
            <div class="titleComuna">
              <h1><?= Cuenta::cuentaSegunDominio()!='localhost' ? Cuenta::cuentaSegunDominio()->nombre_largo : '' ?></h1>
              <p><?= Cuenta::cuentaSegunDominio()!='localhost' ? Cuenta::cuentaSegunDominio()->mensaje : '' ?></p>
            </div>
          </div>
          <div class="navbar-right">
            <div class="btn-group" role="group">
              <ul id="userMenu" class="nav nav-pills pull-right">
                <?php if (!UsuarioSesion::usuario()->registrado): ?>
                    <li class="dropdown">
                        <a class="dropdown-toggle btn" data-toggle="dropdown" href="#">Iniciar sesi&oacute;n<span class="caret"></span></a>
                        <ul class="dropdown-menu pull-right">
                            <li id="loginView">
                                <div class="simple">
                                    <div class="wrapper">
                                <form method="post" class="ajaxForm" action="<?= site_url('autenticacion/login_form') ?>">        
                                    <fieldset>
                                        <div class="validacion"></div>
                                        <input type="hidden" name="redirect" value="<?= current_url() ?>" />
                                        <label for="usuario">Usuario o Correo electrónico</label>
                                        <input name="usuario" id="usuario" type="text" class="input-xlarge">
                                        <label for="password">Contraseña</label>
                                        <input name="password" id="password" type="password" class="input-xlarge">
                                        <p class="olvido"><a href="<?= site_url('autenticacion/olvido') ?>">¿Olvidaste tu contraseña?</a> - <a href="<?= site_url('autenticacion/registrar') ?>">Registrate aquí</a></p>
                                        <button class="btn btn-primary pull-right" type="submit">Ingresar</button>
                                    </fieldset>
                                </form>
                                </div>
                                </div>
                                <!-- Modificable 1: Acceso de Identificación electronica -->
                                <div class="claveunica">
                                    <div class="wrapper">
                                    <?php //if(!$claveunicaOnly):?><p>O utilice ClaveÚnica</p><?php //endif ?>
                                    <a href="<?= site_url('autenticacion/login_openid?redirect=' . current_url()) ?>"><img src="<?= base_url() ?>assets/img/claveunica-medium.png" alt="OpenID"/></a>
                                    </div>
                                </div>
                                <!-- Fin Modificable 1 -->  
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" href="#">Bienvenido/a <?= UsuarioSesion::usuario()->displayName() ?><span class="caret"></span></a>
                        <ul class="dropdown-menu btn" style="padding: 0px !important;">
                            <?php if (!UsuarioSesion::usuario()->open_id): ?> 
                                <li><a href="<?= site_url('cuentas/editar') ?>"><i class="icon-user"></i> Mi cuenta</a></li>
                            <?php endif; ?>
                            <?php if (!UsuarioSesion::usuario()->open_id): ?><li><a href="<?= site_url('cuentas/editar_password') ?>"><i class="icon-lock"></i> Cambiar contraseña</a></li><?php endif; ?>
                            <li><a href="<?= site_url('autenticacion/logout') ?>"><i class="icon-off"></i> Cerrar sesión</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </header>
    
    <main style="min-height:480px">
      <div class="container">
        <div class="row">
          <div class="col-sm-12">
            <?php if ($num_destacados > 0 || $sidebar == 'categorias'): ?>
              <section id="simple-destacados">
                  <div class="section-header">
                    <?php if($sidebar == 'disponibles'):?>
                      <h2>Trámites destacados</h2>
                    <?php else: ?>
                      <h2>Trámites - <?= $categoria->nombre ?></h2>
                      <a href="<?=site_url('home/index/')?>" class="btn btn-primary preventDoubleRequest" style="float:right">
                        <i class="icon-file icon-white"></i> Volver
                        </a>
                    <?php endif ?>
                    <div class="line"></div>
                  </div>
                  <div class="row">
                      <?php foreach ($procesos as $p): ?>
                        <?php if($p->destacado == 1 || $sidebar == 'categorias'):?>
                          <div class="col-md-4 item">
                            <div class="tarjeta">
                                <?php if($p->icon_ref):?>
                                  <div class="text-left">
                                    <img src="<?= base_url('assets/img/icons/' . $p->icon_ref) ?>" class="img-service">
                                  </div>
                                <?php else:?>
                                  <div class="text-left">
                                    <img src="<?= base_url('assets/img/icons/nologo.png') ?>" class="img-service">
                                  </div>
                                <?php endif ?>
                              <h4><?= $p->nombre ?></h4>
                              <div class="btn_derecha">
                                <?php if($p->canUsuarioIniciarlo(UsuarioSesion::usuario()->id)):?>
                                <a href="<?=site_url('tramites/iniciar/'.$p->id)?>" class="btn btn-primary preventDoubleRequest"><i class="icon-file icon-white"></i> Iniciar</a>
                                <?php else: ?>
                                    <?php if($p->getTareaInicial()->acceso_modo=='claveunica'):?>
                                    <a href="<?=site_url('autenticacion/login_openid')?>?redirect=<?=site_url('tramites/iniciar/'.$p->id)?>"><img style="max-width: none;" src="<?=base_url('assets/img/claveunica-medium.png')?>" alt="ClaveUnica" /></a>
                                    <?php else:?>
                                    <a href="<?=site_url('autenticacion/login')?>?redirect=<?=site_url('tramites/iniciar/'.$p->id)?>" class="btn btn-primary"><i class="icon-white icon-off"></i> Autenticarse</a>
                                    <?php endif ?>
                                <?php endif ?>
                              </div>
                            </div>
                          </div>
                        <?php $count++ ?>
                        <?php endif ?>
                      <?php endforeach; ?>
                  </div>
              </section>
            <?php endif ?>

            <?php if (count($categorias) > 0): ?>
              <section id="simple-categorias">
                  <div class="section-header">
                    <h2>Categor&iacute;as</h2>
                    <div class="line"></div>
                  </div>
                  <div class="row">
                    <?php foreach ($categorias as $c): ?>
                      <div class="col-md-3 item">
                        <a href="<?=site_url('home/procesos/'.$c->id)?>">
                          <div class="tarjeta">
                            <div class="text-left">
                              <?php if($c->icon_ref):?>
                                <img src="<?= base_url('uploads/logos/' . $c->icon_ref) ?>" class="img-service">
                              <?php else:?>
                                  <img src="<?= base_url('assets/img/icons/nologo.png') ?>" class="img-service">
                              <?php endif ?>
                            </div>
                            <span class="title"><?= $c->nombre ?></span>
                            <p><?= $c->descripcion ?></p>
                          </div>
                        </a>
                      </div>
                    <?php endforeach; ?>
                  </div>
              </section>
            <?php endif; ?>

            <?php if ($num_otros > 0 && $sidebar != 'categorias'): ?>
            <section id="simple-destacados">
                <div class="section-header">
                  <h2 style="width:40%">Otros trámites</h2>
                  <div class="line"></div>
                </div>
                <div class="row">
                  <?php foreach ($procesos as $p): ?>
                    <?php if($p->destacado == 0 || $p->categoria_id == 0):?>
                      <div class="col-md-4 item">
                        <div class="tarjeta">
                            <?php if($p->icon_ref):?>
                              <div class="text-left">
                                <img src="<?= base_url('assets/img/icons/' . $p->icon_ref) ?>" class="img-service">
                              </div>
                            <?php else:?>
                              <div class="text-left">
                                <img src="<?= base_url('assets/img/icons/nologo.png') ?>" class="img-service">
                              </div>
                            <?php endif ?>
                          <h4><?= $p->nombre ?></h4>
                          <div class="btn_derecha">
                            <?php if($p->canUsuarioIniciarlo(UsuarioSesion::usuario()->id)):?>
                            <a href="<?=site_url('tramites/iniciar/'.$p->id)?>" class="btn btn-primary preventDoubleRequest"><i class="icon-file icon-white"></i> Iniciar</a>
                            <?php else: ?>
                                <?php if($p->getTareaInicial()->acceso_modo=='claveunica'):?>
                                <a href="<?=site_url('autenticacion/login_openid')?>?redirect=<?=site_url('tramites/iniciar/'.$p->id)?>"><img style="max-width: none;" src="<?=base_url('assets/img/claveunica-medium.png')?>" alt="ClaveUnica" /></a>
                                <?php else:?>
                                <a href="<?=site_url('autenticacion/login')?>?redirect=<?=site_url('tramites/iniciar/'.$p->id)?>" class="btn btn-primary"><i class="icon-white icon-off"></i> Autenticarse</a>
                                <?php endif ?>
                            <?php endif ?>
                          </div>
                        </div>
                      </div>
                    <?php endif ?>
                  <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
          </div>
        </div>
    </main>

    <div class="container" style="margin-top:100px;">
      <footer class="footer-gobcl">
        <div class="banda-bicolor">
          <span class="azul"></span>
          <span class="rojo"></span>
        </div>

        <div class="top">
          <div class="listas">
            <div class="lista">
              <ul>
                <li><a href="http://www.modernizacion.gob.cl/" target="_blank">Iniciativa de la Unidad de Modernizaci&oacute;n y Gobierno Digital</a></li>
                <li><a href="http://www.minsegpres.gob.cl/" target="_blank">Ministerio Secretaría General de la Presidencia</a></li>
              </ul>
            </div>
          </div>
        </div>

        <div class="bottom">
          <div class="clearfix"></div>
          <div class="banda-bicolor">
            <span class="azul"></span>
            <span class="rojo"></span>
          </div>
        </div>
      </footer>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
    <script src="<?= base_url() ?>assets/home/js/gobstrap.min.js"></script>
    <script src="<?= base_url() ?>assets/home/js/jquery.sticky.js"></script>
    <script src="<?= base_url() ?>assets/home/js/main.js"></script>
    <script src="<?= base_url() ?>assets/home/js/home.js"></script>

    <!-- Google Analytics: change UA-XXXXX-X to be your site's ID. 
    <script>
      (function(b,o,i,l,e,r){b.GoogleAnalyticsObject=l;b[l]||(b[l]=
      function(){(b[l].q=b[l].q||[]).push(arguments)});b[l].l=+new Date;
      e=o.createElement(i);r=o.getElementsByTagName(i)[0];
      e.src='//www.google-analytics.com/analytics.js';
      r.parentNode.insertBefore(e,r)}(window,document,'script','ga'));
      ga('create','UA-XXXXX-X','auto');ga('send','pageview');
    </script>
    -->
  </body>
</html>
