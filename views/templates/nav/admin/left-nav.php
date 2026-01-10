<?php
use classes\Methods;
use classes\enumerations\Links;
use features\Settings;

?>

<div id="sidebar" class="flex-col-between flex-align-start">
    <div class="flex-col-between h-100">
        <div class="flex-col-start">
            <button class="btn-unstyled p-2 m-0 ml-auto border-0 bg-transparent" id="leftSidebarCloseBtn" style="cursor: pointer;" title="Luk menu">
                <i class="mdi mdi-close font-24 color-gray hover-color-red"></i>
            </button>

            <div class="tabletOnlyFlex justify-content-center" id="sidebar-top-nav">
                <div class="flex-row-center">
                    <i class="font-25 text-gray fa-solid fa-bars " id="leftSidebarOpenBtn"></i>
                </div>
            </div>

            <div id="side-bar-menu-content" style="max-height: calc(100vh - 50px); overflow-x: auto;" class="w-100 px-2 py-2">

                <a class="sidebar-nav-link py-2 px-3 font-18 font-weight-medium m-0 flex-row-start flex-align-center flex-nowrap"
                   data-page="dashboard" href="<?=__url(Links::$admin->dashboard)?>"
                   title="Dashboard" >
                    <span class="w-20px text-center">
                        <i class="mdi mdi-view-dashboard-outline"></i>
                    </span>
                    <p class="ml-3 sidebar-text">Dashboard</p>
                </a>

                <a class="sidebar-nav-link py-2 px-3 font-18 font-weight-medium m-0 flex-row-start flex-align-center flex-nowrap"
                   data-page="panel" href="<?=__url(ADMIN_PANEL_PATH)?>"
                   title="Admin Panel" >
                    <span class="w-20px text-center">
                        <i class="mdi mdi-cog-outline"></i>
                    </span>
                    <p class="ml-3 sidebar-text">Admin Panel</p>
                </a>

            </div>

        </div>
    </div>
</div>
