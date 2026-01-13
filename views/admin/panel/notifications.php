<?php
/**
 * Admin Panel - Notification System Hub
 * Central navigation for the notification system
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "Notifikationer";
?>
<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "notifications";
</script>

<div class="page-content py-3">
    <div class="page-inner-content">
        <div class="flex-col-start" style="row-gap: 1.5rem;">

            <!-- Breadcrumb -->
            <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                <a href="<?=__url(Links::$admin->panel)?>" class="font-13 color-gray hover-color-blue">Panel</a>
                <i class="mdi mdi-chevron-right font-14 color-gray"></i>
                <span class="font-13 color-dark">Notifikationer</span>
            </div>

            <!-- Page Header -->
            <div class="flex-row-between flex-align-center w-100 flex-wrap" style="gap: 1rem;">
                <div class="flex-col-start">
                    <h1 class="mb-0 font-24 font-weight-bold">Notifikationer</h1>
                    <p class="mb-0 font-14 color-gray">Konfigurer automatiske notifikationer og e-mail skabeloner</p>
                </div>
            </div>

            <!-- Navigation Cards -->
            <div class="row" style="row-gap: 1rem;">
                <!-- Templates -->
                <div class="col-12 col-md-6 col-lg-4">
                    <a href="<?=__url(Links::$admin->panelNotificationTemplates)?>" class="card border-radius-10px hover-shadow h-100 text-decoration-none">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center mb-3" style="gap: .75rem;">
                                <div class="square-50 bg-blue border-radius-10px flex-row-center-center">
                                    <i class="mdi mdi-file-document-outline color-white font-24"></i>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-16 font-weight-bold color-dark">Skabeloner</p>
                                    <p class="mb-0 font-12 color-gray">E-mail, SMS og push skabeloner</p>
                                </div>
                            </div>
                            <p class="mb-0 font-13 color-gray">Opret og rediger notifikationsskabeloner med dynamiske placeholders.</p>
                        </div>
                    </a>
                </div>

                <!-- Flows -->
                <div class="col-12 col-md-6 col-lg-4">
                    <a href="<?=__url(Links::$admin->panelNotificationFlows)?>" class="card border-radius-10px hover-shadow h-100 text-decoration-none">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center mb-3" style="gap: .75rem;">
                                <div class="square-50 bg-purple border-radius-10px flex-row-center-center">
                                    <i class="mdi mdi-source-branch color-white font-24"></i>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-16 font-weight-bold color-dark">Flows</p>
                                    <p class="mb-0 font-12 color-gray">Automatiserede notifikationsflows</p>
                                </div>
                            </div>
                            <p class="mb-0 font-13 color-gray">Opsæt automatiske notifikationer baseret på brugerhandlinger og begivenheder.</p>
                        </div>
                    </a>
                </div>

                <!-- Breakpoints -->
                <div class="col-12 col-md-6 col-lg-4">
                    <a href="<?=__url(Links::$admin->panelNotificationBreakpoints)?>" class="card border-radius-10px hover-shadow h-100 text-decoration-none">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center mb-3" style="gap: .75rem;">
                                <div class="square-50 bg-green border-radius-10px flex-row-center-center">
                                    <i class="mdi mdi-map-marker-path color-white font-24"></i>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-16 font-weight-bold color-dark">Breakpoints</p>
                                    <p class="mb-0 font-12 color-gray">Triggerpunkter i systemet</p>
                                </div>
                            </div>
                            <p class="mb-0 font-13 color-gray">Se alle tilgængelige breakpoints der kan trigge notifikationer.</p>
                        </div>
                    </a>
                </div>

                <!-- Queue -->
                <div class="col-12 col-md-6 col-lg-4">
                    <a href="<?=__url(Links::$admin->panelNotificationQueue)?>" class="card border-radius-10px hover-shadow h-100 text-decoration-none">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center mb-3" style="gap: .75rem;">
                                <div class="square-50 bg-pee-yellow border-radius-10px flex-row-center-center">
                                    <i class="mdi mdi-tray-full color-white font-24"></i>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-16 font-weight-bold color-dark">Kø</p>
                                    <p class="mb-0 font-12 color-gray">Afventende notifikationer</p>
                                </div>
                            </div>
                            <p class="mb-0 font-13 color-gray">Overvåg og administrer planlagte notifikationer i køen.</p>
                        </div>
                    </a>
                </div>

                <!-- Logs -->
                <div class="col-12 col-md-6 col-lg-4">
                    <a href="<?=__url(Links::$admin->panelNotificationLogs)?>" class="card border-radius-10px hover-shadow h-100 text-decoration-none">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center mb-3" style="gap: .75rem;">
                                <div class="square-50 bg-gray border-radius-10px flex-row-center-center">
                                    <i class="mdi mdi-history color-white font-24"></i>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-16 font-weight-bold color-dark">Logs</p>
                                    <p class="mb-0 font-12 color-gray">Sendte notifikationer</p>
                                </div>
                            </div>
                            <p class="mb-0 font-13 color-gray">Se historik over sendte notifikationer og deres status.</p>
                        </div>
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>
