<!DOCTYPE html>
<html lang="es">
    <!-- ATENCIÓN -->
    <!-- En el código encontrara el TAG Modificable con un número, un inicio y un fin de donde puede retocar con seguridad -->
    <head>
        <meta http-equiv="content-type" content="text/html; charset=UTF-8">
        <?php $this->load->view('head') ?>
        <link href="<?= base_url() ?>assets/home/css/navbar.css" rel="stylesheet">
    </head>

    <body>
    <ul class="saltar">
    <li><a href="#main" tabindex="1">Ir al contenido</a>
    </li>
</ul>
        <header>
            <div class="container">
                <div class="row">
                    <div class="span2">
                        <h1 id="logo"><a href="<?= site_url() ?>"><img src="<?= Cuenta::cuentaSegunDominio()!='localhost' ? Cuenta::cuentaSegunDominio()->logoADesplegar : base_url('assets/img/logo.png') ?>" alt="<?= Cuenta::cuentaSegunDominio()!='localhost' ? Cuenta::cuentaSegunDominio()->nombre_largo : 'Simple' ?>" /></a></h1>
                    </div>
                    <div class="span4">
                        <h1><?= Cuenta::cuentaSegunDominio()!='localhost' ? Cuenta::cuentaSegunDominio()->nombre_largo : '' ?></h1>
                        <p><?= Cuenta::cuentaSegunDominio()!='localhost' ? Cuenta::cuentaSegunDominio()->mensaje : '' ?></p>
                    </div>
                    <div class="offset3 span3">
                        <ul id="userMenu" class="nav nav-pills pull-right">
                            <?php if (!UsuarioSesion::usuario()->registrado): ?>
                                <li class="dropdown">
                                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">Iniciar sesión<span class="caret"></span></a>
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
                                                    <div id="login_captcha"></div>
                                                    <p class="olvido">
                                                        <a href="<?= site_url('autenticacion/olvido') ?>">¿Olvidaste tu contraseña?</a>
                                                    </p>
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
                                    <ul class="dropdown-menu">
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
        </header>

        <div id="main">
            <div class="container">
                <div class="row">
                    <div class="span3">
                        <div class="menubar">
                        <div class="navbar-header">
                                <span class="navbar-brand">Menú</span>                            
                            </div>
                            <ul id="sideMenu2" class="nav nav-list">    
                                <li class="iniciar <?= isset($sidebar) && $sidebar == 'disponibles' ? 'active' : '' ?>"><a href="<?= site_url('home/index') ?>">Iniciar trámite</a></li>
                                <?php if (UsuarioSesion::usuario()->registrado): ?>
                                    <?php
                                    $npendientes=Doctrine::getTable('Etapa')->findPendientes(UsuarioSesion::usuario()->id, Cuenta::cuentaSegunDominio())->count();
                                    $nsinasignar=Doctrine::getTable('Etapa')->findSinAsignar(UsuarioSesion::usuario()->id, Cuenta::cuentaSegunDominio())->count();
                                    $nparticipados=Doctrine::getTable('Tramite')->findParticipadosALL(UsuarioSesion::usuario()->id, Cuenta::cuentaSegunDominio())->count();
                                    ?>
                                    <li class="<?= isset($sidebar) && $sidebar == 'inbox' ? 'active' : '' ?>"><a href="<?= site_url('etapas/inbox') ?>">Bandeja de Entrada (<?= $npendientes ?>)</a></li>
                                    <?php if($nsinasignar): ?><li class="<?= isset($sidebar) && $sidebar == 'sinasignar' ? 'active' : '' ?>"><a href="<?= site_url('etapas/sinasignar') ?>">Sin asignar  (<?=$nsinasignar  ?>)</a></li><?php endif ?>
                                    <li class="<?= isset($sidebar) && $sidebar == 'participados' ? 'active' : '' ?>"><a href="<?= site_url('tramites/participados') ?>">Historial de Trámites  (<?= $nparticipados ?>)</a></li>
                                    <li class="<?= isset($sidebar) && $sidebar == 'miagenda' ? 'active' : '' ?>"><a href="<?= site_url('agenda/miagenda') ?>">Mi Agenda</a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                    <div class="offset1 span8">
                        <?php $this->load->view('messages') ?>
                        <?php $this->load->view($content) ?>
                    </div>
                </div>
            </div>
        </div>

        <footer>
            <?php $this->load->view('foot') ?>
        </footer>
    </body>
</html>
