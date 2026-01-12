<?php
use classes\Methods;
use classes\enumerations\Links;
use classes\lang\Translate;
use features\Settings;

?>

<div id="sidebar" class="flex-col-between flex-align-start">
    <div class="flex-col-between h-100">
        <div class="flex-col-start h-100">
            <button class="btn-unstyled p-2 m-0 ml-auto border-0 bg-transparent" id="leftSidebarCloseBtn" style="cursor: pointer;" title="Luk menu">
                <i class="mdi mdi-close font-24 color-gray hover-color-red"></i>
            </button>

            <div class="tabletOnlyFlex justify-content-center" id="sidebar-top-nav">
                <div class="flex-row-center">
                    <i class="font-25 color-gray mdi mdi-menu cursor-pointer" id="leftSidebarOpenBtn"></i>
                </div>
            </div>

            <div id="side-bar-menu-content" class="w-100 px-2 py-2">

                <!-- Overview -->
                <p class="mb-1 mt-2 px-3 font-11 text-uppercase color-gray font-weight-bold sidebar-section-title"><?=ucfirst(Translate::word("Oversigt"))?></p>

                <a class="sidebar-nav-link py-2 px-3 font-15 font-weight-medium m-0 flex-row-start flex-align-center flex-nowrap"
                   data-page="dashboard" href="<?=__url(Links::$admin->dashboard)?>" title="Dashboard">
                    <span class="w-20px text-center"><i class="mdi mdi-view-dashboard-outline"></i></span>
                    <p class="ml-3 sidebar-text">Dashboard</p>
                </a>

                <a class="sidebar-nav-link py-2 px-3 font-15 font-weight-medium m-0 flex-row-start flex-align-center flex-nowrap"
                   data-page="reports" href="<?=__url(Links::$admin->reports)?>" title="Rapporter">
                    <span class="w-20px text-center"><i class="mdi mdi-file-document-outline"></i></span>
                    <p class="ml-3 sidebar-text"><?=ucfirst(Translate::word("Rapporter"))?></p>
                </a>

                <!-- Users -->
                <p class="mb-1 mt-3 px-3 font-11 text-uppercase color-gray font-weight-bold sidebar-section-title"><?=ucfirst(Translate::word("Brugere"))?></p>

                <a class="sidebar-nav-link py-2 px-3 font-15 font-weight-medium m-0 flex-row-start flex-align-center flex-nowrap"
                   data-page="users" href="<?=__url(Links::$admin->users)?>" title="Alle brugere">
                    <span class="w-20px text-center"><i class="mdi mdi-account-group-outline"></i></span>
                    <p class="ml-3 sidebar-text"><?=ucfirst(Translate::word("Alle brugere"))?></p>
                </a>

                <a class="sidebar-nav-link py-2 px-3 font-15 font-weight-medium m-0 flex-row-start flex-align-center flex-nowrap"
                   data-page="consumers" href="<?=__url(Links::$admin->consumers)?>" title="Forbrugere">
                    <span class="w-20px text-center"><i class="mdi mdi-account-outline"></i></span>
                    <p class="ml-3 sidebar-text"><?=ucfirst(Translate::word("Forbrugere"))?></p>
                </a>

                <a class="sidebar-nav-link py-2 px-3 font-15 font-weight-medium m-0 flex-row-start flex-align-center flex-nowrap"
                   data-page="merchants" href="<?=__url(Links::$admin->merchants)?>" title="Forhandlere">
                    <span class="w-20px text-center"><i class="mdi mdi-store-outline"></i></span>
                    <p class="ml-3 sidebar-text"><?=ucfirst(Translate::word("Forhandlere"))?></p>
                </a>

                <!-- Business -->
                <p class="mb-1 mt-3 px-3 font-11 text-uppercase color-gray font-weight-bold sidebar-section-title"><?=ucfirst(Translate::word("Virksomheder"))?></p>

                <a class="sidebar-nav-link py-2 px-3 font-15 font-weight-medium m-0 flex-row-start flex-align-center flex-nowrap"
                   data-page="organisations" href="<?=__url(Links::$admin->organisations)?>" title="<?=ucfirst(Translate::word("Organisationer"))?>">
                    <span class="w-20px text-center"><i class="mdi mdi-domain"></i></span>
                    <p class="ml-3 sidebar-text"><?=ucfirst(Translate::word("Organisationer"))?></p>
                </a>

                <a class="sidebar-nav-link py-2 px-3 font-15 font-weight-medium m-0 flex-row-start flex-align-center flex-nowrap"
                   data-page="locations" href="<?=__url(Links::$admin->locations)?>" title="Lokationer">
                    <span class="w-20px text-center"><i class="mdi mdi-map-marker-outline"></i></span>
                    <p class="ml-3 sidebar-text"><?=ucfirst(Translate::word("Lokationer"))?></p>
                </a>

                <!-- Transactions -->
                <p class="mb-1 mt-3 px-3 font-11 text-uppercase color-gray font-weight-bold sidebar-section-title"><?=ucfirst(Translate::word("Transaktioner"))?></p>

                <a class="sidebar-nav-link py-2 px-3 font-15 font-weight-medium m-0 flex-row-start flex-align-center flex-nowrap"
                   data-page="orders" href="<?=__url(Links::$admin->orders)?>" title="Ordrer">
                    <span class="w-20px text-center"><i class="mdi mdi-cart-outline"></i></span>
                    <p class="ml-3 sidebar-text"><?=ucfirst(Translate::word("Ordrer"))?></p>
                </a>

                <a class="sidebar-nav-link py-2 px-3 font-15 font-weight-medium m-0 flex-row-start flex-align-center flex-nowrap"
                   data-page="payments" href="<?=__url(Links::$admin->payments)?>" title="Betalinger">
                    <span class="w-20px text-center"><i class="mdi mdi-credit-card-outline"></i></span>
                    <p class="ml-3 sidebar-text"><?=ucfirst(Translate::word("Betalinger"))?></p>
                </a>

                <a class="sidebar-nav-link py-2 px-3 font-15 font-weight-medium m-0 flex-row-start flex-align-center flex-nowrap"
                   data-page="payments-pending" href="<?=__url(Links::$admin->paymentsPending)?>" title="Kommende">
                    <span class="w-20px text-center"><i class="mdi mdi-clock-outline"></i></span>
                    <p class="ml-3 sidebar-text"><?=ucfirst(Translate::word("Kommende"))?></p>
                </a>

                <a class="sidebar-nav-link py-2 px-3 font-15 font-weight-medium m-0 flex-row-start flex-align-center flex-nowrap"
                   data-page="payments-past-due" href="<?=__url(Links::$admin->paymentsPastDue)?>" title="Forfaldne">
                    <span class="w-20px text-center"><i class="mdi mdi-alert-circle-outline color-danger"></i></span>
                    <p class="ml-3 sidebar-text"><?=ucfirst(Translate::word("Forfaldne"))?></p>
                </a>

                <a class="sidebar-nav-link py-2 px-3 font-15 font-weight-medium m-0 flex-row-start flex-align-center flex-nowrap"
                   data-page="kpi" href="<?=__url(Links::$admin->kpi)?>" title="KPI">
                    <span class="w-20px text-center"><i class="mdi mdi-chart-line"></i></span>
                    <p class="ml-3 sidebar-text">KPI</p>
                </a>

                <!-- Support -->
                <p class="mb-1 mt-3 px-3 font-11 text-uppercase color-gray font-weight-bold sidebar-section-title"><?=ucfirst(Translate::word("Support"))?></p>

                <a class="sidebar-nav-link py-2 px-3 font-15 font-weight-medium m-0 flex-row-start flex-align-center flex-nowrap"
                   data-page="support" href="<?=__url(Links::$admin->support)?>" title="Support">
                    <span class="w-20px text-center"><i class="mdi mdi-lifebuoy"></i></span>
                    <p class="ml-3 sidebar-text"><?=ucfirst(Translate::word("Support"))?></p>
                </a>

                <!-- Panel / System -->
                <p class="mb-1 mt-3 px-3 font-11 text-uppercase color-gray font-weight-bold sidebar-section-title"><?=ucfirst(Translate::word("System"))?></p>

                <a class="sidebar-nav-link py-2 px-3 font-15 font-weight-medium m-0 flex-row-start flex-align-center flex-nowrap"
                   data-page="panel" href="<?=__url(Links::$admin->panel)?>" title="Panel">
                    <span class="w-20px text-center"><i class="mdi mdi-cog-outline"></i></span>
                    <p class="ml-3 sidebar-text">Panel</p>
                </a>

            </div>

        </div>
    </div>
</div>
