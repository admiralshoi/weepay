<?php
use classes\Methods;
use classes\enumerations\Links;

?>


<div id="sidebar" class="flex-col-between flex-align-start">
    <div class="flex-col-between h-100">
        <div class="flex-col-start h-100">
            <div class="flex-row-end flex-align-center px-2 pt-2 color-gray flex-nowrap" style="column-gap: .25rem" id="leftSidebarCloseBtn">
                <i class="font-16  fa-solid fa-xmark" id="" ></i>
                <span class="text-sm">Close</span>
            </div>

            <div id="sidebar-top-logo" class="position-relative px-2" style="height: 75px">
                <div class="flex-row-start flex-align-center cursor-pointer px-3" >
                    <div class="flex-col-start">
                        <p class="mb-0 w-75 font-22 noSelect text-nowrap">System Panel</p>
                        <a href="<?=__url(Links::$admin->dashboard)?>" class="font-14 hover-underline">
                            <i class="mdi mdi-arrow-left font-14"></i>
                            Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <div class="tabletOnlyFlex justify-content-center" id="sidebar-top-nav">
                <div class="flex-row-center">
                    <i class="font-25 text-gray fa-solid fa-bars " id="leftSidebarOpenBtn"></i>
                </div>
            </div>

            <div id="side-bar-menu-content" class="w-100 px-2">

                <!-- Overview -->
                <p class="mb-1 mt-2 px-3 font-11 text-uppercase color-gray font-weight-bold sidebar-section-title">Oversigt</p>

                <a class="sidebar-nav-link py-2 px-3 text-sm font-weight-medium m-0 flex-row-start flex-align-center"
                   data-page="panel-home" href="<?=__url(Links::$admin->panel)?>">
                    <i class="mdi mdi-home-outline"></i>
                    <p class="ml-3 sidebar-text">Panel Home</p>
                </a>

                <!-- Configuration -->
                <p class="mb-1 mt-3 px-3 font-11 text-uppercase color-gray font-weight-bold sidebar-section-title">Konfiguration</p>

                <a class="sidebar-nav-link py-2 px-3 text-sm font-weight-medium m-0 flex-row-start flex-align-center"
                   data-page="settings" href="<?=__url(Links::$admin->panelSettings)?>">
                    <i class="mdi mdi-cog-outline"></i>
                    <p class="ml-3 sidebar-text">App Indstillinger</p>
                </a>

                <a class="sidebar-nav-link py-2 px-3 text-sm font-weight-medium m-0 flex-row-start flex-align-center"
                   data-page="fees" href="<?=__url(Links::$admin->panelFees)?>">
                    <i class="mdi mdi-cash-multiple"></i>
                    <p class="ml-3 sidebar-text">Gebyrer</p>
                </a>

                <a class="sidebar-nav-link py-2 px-3 text-sm font-weight-medium m-0 flex-row-start flex-align-center"
                   data-page="payment-plans" href="<?=__url(Links::$admin->panelPaymentPlans)?>">
                    <i class="mdi mdi-credit-card-settings-outline"></i>
                    <p class="ml-3 sidebar-text">Betalingsplaner</p>
                </a>

                <!-- Content & Policies -->
                <p class="mb-1 mt-3 px-3 font-11 text-uppercase color-gray font-weight-bold sidebar-section-title">Indhold</p>

                <a class="sidebar-nav-link py-2 px-3 text-sm font-weight-medium m-0 flex-row-start flex-align-center"
                   data-page="policies" href="<?=__url(Links::$admin->panelPolicies)?>">
                    <i class="mdi mdi-file-document-edit-outline"></i>
                    <p class="ml-3 sidebar-text">Politikker</p>
                </a>

                <a class="sidebar-nav-link py-2 px-3 text-sm font-weight-medium m-0 flex-row-start flex-align-center"
                   data-page="faqs" href="<?=__url(Links::$admin->panelFaqs)?>">
                    <i class="mdi mdi-frequently-asked-questions"></i>
                    <p class="ml-3 sidebar-text">FAQ</p>
                </a>

                <a class="sidebar-nav-link py-2 px-3 text-sm font-weight-medium m-0 flex-row-start flex-align-center"
                   data-page="contact-forms" href="<?=__url(Links::$admin->panelContactForms)?>">
                    <i class="mdi mdi-email-outline"></i>
                    <p class="ml-3 sidebar-text">Kontaktformularer</p>
                </a>

                <a class="sidebar-nav-link py-2 px-3 text-sm font-weight-medium m-0 flex-row-start flex-align-center"
                   data-page="notifications" href="<?=__url(Links::$admin->panelNotifications)?>">
                    <i class="mdi mdi-bell-outline"></i>
                    <p class="ml-3 sidebar-text">Notifikationer</p>
                </a>

                <!-- Users & Access -->
                <p class="mb-1 mt-3 px-3 font-11 text-uppercase color-gray font-weight-bold sidebar-section-title">Bruger & Adgang</p>

                <a class="sidebar-nav-link py-2 px-3 text-sm font-weight-medium m-0 flex-row-start flex-align-center"
                   data-page="users" href="<?=__url(Links::$admin->panelUsers)?>">
                    <i class="mdi mdi-account-key-outline"></i>
                    <p class="ml-3 sidebar-text">Brugerroller</p>
                </a>

                <a class="sidebar-nav-link py-2 px-3 text-sm font-weight-medium m-0 flex-row-start flex-align-center"
                   data-page="api" href="<?=__url(Links::$admin->panelApi)?>">
                    <i class="mdi mdi-api"></i>
                    <p class="ml-3 sidebar-text">API</p>
                </a>

                <!-- System -->
                <p class="mb-1 mt-3 px-3 font-11 text-uppercase color-gray font-weight-bold sidebar-section-title">System</p>

                <a class="sidebar-nav-link py-2 px-3 text-sm font-weight-medium m-0 flex-row-start flex-align-center"
                   data-page="logs" href="<?=__url(Links::$admin->panelLogs)?>">
                    <i class="mdi mdi-text-box-outline"></i>
                    <p class="ml-3 sidebar-text">Logs</p>
                </a>

                <a class="sidebar-nav-link py-2 px-3 text-sm font-weight-medium m-0 flex-row-start flex-align-center"
                   data-page="webhooks" href="<?=__url(Links::$admin->panelWebhooks)?>">
                    <i class="mdi mdi-webhook"></i>
                    <p class="ml-3 sidebar-text">Webhooks</p>
                </a>

                <a class="sidebar-nav-link py-2 px-3 text-sm font-weight-medium m-0 flex-row-start flex-align-center"
                   data-page="jobs" href="<?=__url(Links::$admin->panelJobs)?>">
                    <i class="mdi mdi-clock-fast"></i>
                    <p class="ml-3 sidebar-text">Cron Jobs</p>
                </a>

                <a class="sidebar-nav-link py-2 px-3 text-sm font-weight-medium m-0 flex-row-start flex-align-center"
                   data-page="cache" href="<?=__url(Links::$admin->panelCache)?>">
                    <i class="mdi mdi-cached"></i>
                    <p class="ml-3 sidebar-text">Cache</p>
                </a>

                <a class="sidebar-nav-link py-2 px-3 text-sm font-weight-medium m-0 flex-row-start flex-align-center"
                   data-page="maintenance" href="<?=__url(Links::$admin->panelMaintenance)?>">
                    <i class="mdi mdi-wrench-outline"></i>
                    <p class="ml-3 sidebar-text">Maintenance</p>
                </a>

            </div>

        </div>

        <div class="my-2 px-4 flex-col-end" id="sidebar-brand-box">
            <p class="font-13 ">&copy; <?=BRAND_NAME?></p>
            <div class="flex-row-start flex-align-center flex-wrap" style="row-gap: 0; column-gap: .5rem">
                <a href="<?=__url('privacy-policy')?>" class="">Privacy policy</a>
                <a href="<?=__url('terms-of-use')?>" class="">Terms of Use</a>
            </div>
        </div>
    </div>
</div>
