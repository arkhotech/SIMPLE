<!DOCTYPE html>
<html lang="es">
    <head>
        <?php $this->load->view('head_newhome') ?>
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
                            <h1><?= Cuenta::cuentaSegunDominio() != 'localhost' ? Cuenta::cuentaSegunDominio()->nombre_largo : '' ?></h1>
                            <p><?= Cuenta::cuentaSegunDominio() != 'localhost' ? Cuenta::cuentaSegunDominio()->mensaje : '' ?></p>
                        </div>
                    </div>
                    <div class="navbar-right">
                        <div class="btn-group" role="group">
                            <div class="vr"></div>
                            <ul id="userMenu" class="nav nav-pills pull-right">
                                <?php if (!UsuarioSesion::usuario()->registrado): ?>
                                    <li class="dropdown">
                                        <a class="dropdown-toggle" data-toggle="dropdown" href="#">Iniciar sesi&oacute;n<span class="caret"></span></a>
                                        <ul class="dropdown-menu pull-right">
                                            <li id="loginView">
                                                <div class="simple">
                                                    <div class="wrapper">
                                                        <form id="login" method="post" class="ajaxForm" action="<?= site_url('autenticacion/login_form') ?>">        
                                                            <div class="validacion_login"></div>
                                                            <input type="hidden" name="redirect" value="<?= current_url() ?>" />
                                                            <label for="usuario">Usuario o Correo electr&oacute;nico</label>
                                                            <input name="usuario" id="usuario" type="text" class="input-xlarge">
                                                            <label for="password">Contrase&ntilde;a</label>
                                                            <input name="password" id="password" type="password" class="input-xlarge">
                                                            <div id="login_captcha"></div>
                                                            <p><a href="<?=site_url('autenticacion/olvido')?>">¿Olvidaste tu contrase&ntilde;a?</a></p>
                                                            <p><span>O utilice</span> <a href="<?=site_url('autenticacion/login_openid?redirect=' . $redirect)?>"><img src="<?= base_url() ?>assets/newhome/images/logo.4583c3bc.png" alt="ClaveÚnica" width="96" height="32"/></a></p>
                                                            <a class="button button--red submit" href="#">Ingresar</a>
                                                        </form>
                                                    </div>
                                                </div>
                                            </li>
                                        </ul>
                                    </li>
                                <?php else: ?>
                                    <li id="loginViewUser" class="dropdown">
                                        <a class="dropdown-toggle" data-toggle="dropdown" href="#">Bienvenido/a <?= UsuarioSesion::usuario()->displayName() ?><span class="caret"></span></a>
                                        <ul class="dropdown-menu btn">
                                            <?php if (!UsuarioSesion::usuario()->open_id): ?> 
                                            <li><a href="<?= site_url('cuentas/editar') ?>"><i class="icon-user"></i>Mi cuenta</a></li>
                                            <?php endif; ?>
                                            <?php if (!UsuarioSesion::usuario()->open_id): ?><li><a href="<?= site_url('cuentas/editar_password') ?>"><i class="icon-lock"></i>Cambiar contrase&ntilde;a</a></li><?php endif; ?>
                                            <li><a href="<?= site_url('autenticacion/logout') ?>"><i class="icon-log-out"></i>Cerrar sesi&oacute;n</a></li>
                                        </ul>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div id="main">
            <div class="container">
                <div class="row">
                    <div class="col-xs-12 col-md-3">
                        <aside class="aside" id="fixed-menu">
                            <ul class="side-menu nav nav-list">
                                <li class="iniciar <?= isset($sidebar) && $sidebar == 'disponibles' ? 'active' : '' ?>">
                                    <a class="link link--medium goTo" href="<?= site_url('home/index') ?>">Iniciar trámite</a>
                                </li>
                                <?php if (UsuarioSesion::usuario()->registrado): ?>
                                    <?php
                                        $npendientes = Doctrine::getTable('Etapa')->findPendientes(UsuarioSesion::usuario()->id, Cuenta::cuentaSegunDominio())->count();
                                        $nsinasignar = Doctrine::getTable('Etapa')->findSinAsignar(UsuarioSesion::usuario()->id, Cuenta::cuentaSegunDominio())->count();
                                        $nparticipados = Doctrine::getTable('Tramite')->findParticipadosALL(UsuarioSesion::usuario()->id, Cuenta::cuentaSegunDominio())->count();
                                    ?>
                                    <li class="<?= isset($sidebar) && $sidebar == 'inbox' ? 'active' : '' ?>"><a class="link link--medium goTo" href="<?= site_url('etapas/inbox') ?>">Bandeja de Entrada (<?= $npendientes ?>)</a></li>
                                    <?php if ($nsinasignar): ?>
                                        <li class="<?= isset($sidebar) && $sidebar == 'sinasignar' ? 'active' : '' ?>">
                                            <a class="link link--medium goTo" href="<?= site_url('etapas/sinasignar') ?>">Sin asignar  (<?=$nsinasignar  ?>)</a>
                                        </li>
                                    <?php endif ?>
                                    <li class="<?= isset($sidebar) && $sidebar == 'participados' ? 'active' : '' ?>">
                                        <a class="link link--medium goTo" href="<?= site_url('tramites/participados') ?>">Historial de Trámites (<?= $nparticipados ?>)</a>
                                    </li>
                                    <li class="<?= isset($sidebar) && $sidebar == 'miagenda' ? 'active' : '' ?>">
                                        <a class="link link--medium goTo" href="<?= site_url('agenda/miagenda') ?>">Mi Agenda</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </aside>
                    </div>
                    <div class="col-xs-12 col-md-9">

                        <nav class="navbar navbar-inverse">
                            <div class="container-fluid">
                                <div class="navbar-header">
                                    <button type="button" id="sidebar_head">
                                        <i class="icon-burguer icon--top"></i>
                                    </button>
                                </div>
                                <div class="navbar-detail">
                                    <aside class="aside" id="sidebar_detail">
                                        <ul class="side-menu nav nav-list">
                                            <li class="iniciar <?= isset($sidebar) && $sidebar == 'disponibles' ? 'active' : '' ?>">
                                                <a class="link link--medium goTo" href="<?= site_url('home/index') ?>">Iniciar trámite</a>
                                            </li>
                                            <?php if (UsuarioSesion::usuario()->registrado): ?>
                                                <?php
                                                    $npendientes = Doctrine::getTable('Etapa')->findPendientes(UsuarioSesion::usuario()->id, Cuenta::cuentaSegunDominio())->count();
                                                    $nsinasignar = Doctrine::getTable('Etapa')->findSinAsignar(UsuarioSesion::usuario()->id, Cuenta::cuentaSegunDominio())->count();
                                                    $nparticipados = Doctrine::getTable('Tramite')->findParticipadosALL(UsuarioSesion::usuario()->id, Cuenta::cuentaSegunDominio())->count();
                                                ?>
                                                <li class="<?= isset($sidebar) && $sidebar == 'inbox' ? 'active' : '' ?>"><a class="link link--medium goTo" href="<?= site_url('etapas/inbox') ?>">Bandeja de Entrada (<?= $npendientes ?>)</a></li>
                                                <?php if ($nsinasignar): ?>
                                                    <li class="<?= isset($sidebar) && $sidebar == 'sinasignar' ? 'active' : '' ?>">
                                                        <a class="link link--medium goTo" href="<?= site_url('etapas/sinasignar') ?>">Sin asignar  (<?=$nsinasignar  ?>)</a>
                                                    </li>
                                                <?php endif ?>
                                                <li class="<?= isset($sidebar) && $sidebar == 'participados' ? 'active' : '' ?>"><a class="link link--medium goTo" href="<?= site_url('tramites/participados') ?>">Historial de Trámites (<?= $nparticipados ?>)</a></li>
                                                <li class="<?= isset($sidebar) && $sidebar == 'miagenda' ? 'active' : '' ?>"><a class="link link--medium goTo" href="<?= site_url('agenda/miagenda') ?>">Mi Agenda</a></li>
                                            <?php endif; ?>
                                        </ul>
                                    </aside>
                                </div>
                            </div>
                        </nav>

                        <?php $this->load->view('messages') ?>
                        <?php $this->load->view($content) ?>
                    </div>
                </div>
            </div>
        </div>

        <?php $this->load->view('foot_newhome') ?>
    </body>
</html>
