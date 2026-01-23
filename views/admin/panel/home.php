<?php
/**
 * Admin Panel - Home
 * Quick links to all panel sections with system status overview
 */

use classes\enumerations\Links;
use classes\lang\Translate;
use classes\Methods;

$pageTitle = "Panel";

?>
<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "panel-home";
</script>

<div class="page-content py-3 ">
    <div class="page-inner-content">
        <div class="flex-col-start" style="row-gap: 1.5rem;">

            <!-- Page Header -->
            <div class="flex-row-between flex-align-center w-100 flex-wrap" style="gap: 1rem;">
                <div class="flex-col-start">
                    <h1 class="mb-0 font-24 font-weight-bold">Panel</h1>
                    <p class="mb-0 font-14 color-gray">System konfiguration og indstillinger</p>
                </div>
            </div>


            <!-- Quick Links Grid -->
            <div class="row rg-15">

                <!-- App Settings -->
                <div class="col-12 col-md-6 col-lg-4">
                    <a href="<?=__url(Links::$admin->panelSettings)?>" class="card border-radius-10px h-100 hover-shadow-card" style="text-decoration: none;">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center" style="gap: 1rem;">
                                <div class="square-50 bg-blue border-radius-10px flex-row-center-center">
                                    <i class="mdi mdi-cog-outline color-white font-24"></i>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-16 font-weight-bold color-dark">App Indstillinger</p>
                                    <p class="mb-0 font-12 color-gray">Globale indstillinger via AppMeta</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Fees -->
                <div class="col-12 col-md-6 col-lg-4">
                    <a href="<?=__url(Links::$admin->panelFees)?>" class="card border-radius-10px h-100 hover-shadow-card" style="text-decoration: none;">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center" style="gap: 1rem;">
                                <div class="square-50 bg-green border-radius-10px flex-row-center-center">
                                    <i class="mdi mdi-percent-outline color-white font-24"></i>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-16 font-weight-bold color-dark">Gebyrer</p>
                                    <p class="mb-0 font-12 color-gray">Transaktions- og abonnementsgebyrer</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Payment Plans -->
                <div class="col-12 col-md-6 col-lg-4">
                    <a href="<?=__url(Links::$admin->panelPaymentPlans)?>" class="card border-radius-10px h-100 hover-shadow-card" style="text-decoration: none;">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center" style="gap: 1rem;">
                                <div class="square-50 bg-pee-yellow border-radius-10px flex-row-center-center">
                                    <i class="mdi mdi-credit-card-clock-outline color-white font-24"></i>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-16 font-weight-bold color-dark">Betalingsplaner</p>
                                    <p class="mb-0 font-12 color-gray">BNPL, direkte og udskudt betaling</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Notifications -->
                <div class="col-12 col-md-6 col-lg-4">
                    <a href="<?=__url(Links::$admin->panelNotifications)?>" class="card border-radius-10px h-100 hover-shadow-card" style="text-decoration: none;">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center" style="gap: 1rem;">
                                <div class="square-50 bg-info border-radius-10px flex-row-center-center">
                                    <i class="mdi mdi-bell-outline color-white font-24"></i>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-16 font-weight-bold color-dark">Notifikationer</p>
                                    <p class="mb-0 font-12 color-gray">Email og SMS skabeloner</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- User Roles -->
                <div class="col-12 col-md-6 col-lg-4">
                    <a href="<?=__url(Links::$admin->panelUsers)?>" class="card border-radius-10px h-100 hover-shadow-card" style="text-decoration: none;">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center" style="gap: 1rem;">
                                <div class="square-50 bg-purple border-radius-10px flex-row-center-center">
                                    <i class="mdi mdi-shield-account-outline color-white font-24"></i>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-16 font-weight-bold color-dark">Brugerroller</p>
                                    <p class="mb-0 font-12 color-gray">Admin brugere og roller</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- API Settings -->
                <div class="col-12 col-md-6 col-lg-4">
                    <a href="<?=__url(Links::$admin->panelApi)?>" class="card border-radius-10px h-100 hover-shadow-card" style="text-decoration: none;">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center" style="gap: 1rem;">
                                <div class="square-50 bg-dark border-radius-10px flex-row-center-center">
                                    <i class="mdi mdi-api color-white font-24"></i>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-16 font-weight-bold color-dark">API</p>
                                    <p class="mb-0 font-12 color-gray">API nøgler og rate limits</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Logs -->
                <div class="col-12 col-md-6 col-lg-4">
                    <a href="<?=__url(Links::$admin->panelLogs)?>" class="card border-radius-10px h-100 hover-shadow-card" style="text-decoration: none;">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center" style="gap: 1rem;">
                                <div class="square-50 bg-gray border-radius-10px flex-row-center-center">
                                    <i class="mdi mdi-text-box-outline color-white font-24"></i>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-16 font-weight-bold color-dark">Logs</p>
                                    <p class="mb-0 font-12 color-gray">System og fejl logs</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Cron Jobs -->
                <div class="col-12 col-md-6 col-lg-4">
                    <a href="<?=__url(Links::$admin->panelJobs)?>" class="card border-radius-10px h-100 hover-shadow-card" style="text-decoration: none;">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center" style="gap: 1rem;">
                                <div class="square-50 bg-teal border-radius-10px flex-row-center-center">
                                    <i class="mdi mdi-clock-outline color-white font-24"></i>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-16 font-weight-bold color-dark">Cron Jobs</p>
                                    <p class="mb-0 font-12 color-gray">Planlagte opgaver</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Maintenance -->
                <div class="col-12 col-md-6 col-lg-4">
                    <a href="<?=__url(Links::$admin->panelMaintenance)?>" class="card border-radius-10px h-100 hover-shadow-card" style="text-decoration: none;">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center" style="gap: 1rem;">
                                <div class="square-50 bg-pee-yellow border-radius-10px flex-row-center-center">
                                    <i class="mdi mdi-tools color-white font-24"></i>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-16 font-weight-bold color-dark">Maintenance</p>
                                    <p class="mb-0 font-12 color-gray">Vedligeholdelsestilstand</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

            </div>

            <!-- Secondary Links Section -->
            <div class="flex-col-start" style="gap: 1rem;">
                <p class="mb-0 font-14 font-weight-bold color-gray text-uppercase">Indhold & Marketing</p>

                <div class="row rg-15">
                    <!-- Marketing Materials -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <a href="<?=__url(Links::$admin->panelMarketing)?>" class="card border-radius-10px h-100 hover-shadow-card" style="text-decoration: none;">
                            <div class="card-body">
                                <div class="flex-row-start flex-align-center" style="gap: 1rem;">
                                    <div class="square-50 bg-pink border-radius-10px flex-row-center-center">
                                        <i class="mdi mdi-bullhorn-outline color-white font-24"></i>
                                    </div>
                                    <div class="flex-col-start">
                                        <p class="mb-0 font-16 font-weight-bold color-dark">Marketing Materialer</p>
                                        <p class="mb-0 font-12 color-gray">Bannere, kampagner og reklamer</p>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Policies -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <a href="<?=__url(Links::$admin->panelPolicies)?>" class="card border-radius-10px h-100 hover-shadow-card" style="text-decoration: none;">
                            <div class="card-body">
                                <div class="flex-row-start flex-align-center" style="gap: 1rem;">
                                    <div class="square-50 bg-secondary border-radius-10px flex-row-center-center">
                                        <i class="mdi mdi-file-document-outline color-white font-24"></i>
                                    </div>
                                    <div class="flex-col-start">
                                        <p class="mb-0 font-16 font-weight-bold color-dark">Politikker</p>
                                        <p class="mb-0 font-12 color-gray">Privatliv, vilkår og cookies</p>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Contact Forms -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <a href="<?=__url(Links::$admin->panelContactForms)?>" class="card border-radius-10px h-100 hover-shadow-card" style="text-decoration: none;">
                            <div class="card-body">
                                <div class="flex-row-start flex-align-center" style="gap: 1rem;">
                                    <div class="square-50 bg-cyan border-radius-10px flex-row-center-center">
                                        <i class="mdi mdi-email-outline color-white font-24"></i>
                                    </div>
                                    <div class="flex-col-start">
                                        <p class="mb-0 font-16 font-weight-bold color-dark">Kontaktformularer</p>
                                        <p class="mb-0 font-12 color-gray">Modtag og administrer henvendelser</p>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- FAQs -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <a href="<?=__url(Links::$admin->panelFaqs)?>" class="card border-radius-10px h-100 hover-shadow-card" style="text-decoration: none;">
                            <div class="card-body">
                                <div class="flex-row-start flex-align-center" style="gap: 1rem;">
                                    <div class="square-50 bg-info border-radius-10px flex-row-center-center">
                                        <i class="mdi mdi-frequently-asked-questions color-white font-24"></i>
                                    </div>
                                    <div class="flex-col-start">
                                        <p class="mb-0 font-16 font-weight-bold color-dark">FAQ'er</p>
                                        <p class="mb-0 font-12 color-gray">Ofte stillede spørgsmål</p>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Technical Links Section -->
            <div class="flex-col-start" style="gap: 1rem;">
                <p class="mb-0 font-14 font-weight-bold color-gray text-uppercase">Teknisk</p>

                <div class="row rg-15">
                    <!-- Webhooks -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <a href="<?=__url(Links::$admin->panelWebhooks)?>" class="card border-radius-10px h-100 hover-shadow-card" style="text-decoration: none;">
                            <div class="card-body">
                                <div class="flex-row-start flex-align-center" style="gap: 1rem;">
                                    <div class="square-50 bg-indigo border-radius-10px flex-row-center-center">
                                        <i class="mdi mdi-webhook color-white font-24"></i>
                                    </div>
                                    <div class="flex-col-start">
                                        <p class="mb-0 font-16 font-weight-bold color-dark">Webhooks</p>
                                        <p class="mb-0 font-12 color-gray">Udgående webhook konfiguration</p>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Cache -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <a href="<?=__url(Links::$admin->panelCache)?>" class="card border-radius-10px h-100 hover-shadow-card" style="text-decoration: none;">
                            <div class="card-body">
                                <div class="flex-row-start flex-align-center" style="gap: 1rem;">
                                    <div class="square-50 bg-orange border-radius-10px flex-row-center-center">
                                        <i class="mdi mdi-cached color-white font-24"></i>
                                    </div>
                                    <div class="flex-col-start">
                                        <p class="mb-0 font-16 font-weight-bold color-dark">Cache</p>
                                        <p class="mb-0 font-12 color-gray">Cache administration</p>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
