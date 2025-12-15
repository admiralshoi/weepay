<?php
use classes\Methods;
use features\Settings;



$roleName = Methods::roles()->name();
$sidebarHandler = Methods::sidebars();



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


                <?php
                $sections = $sidebarHandler::sideBarMenuAccess();
                $accessLevel = __accessLevel();
                foreach ($sections as $menuOpt):
                    if(empty($menuOpt)) continue;
                    $menuItems = $sidebarHandler::sideBarLinks($menuOpt["pathName"]);

                    foreach ($menuItems as $key => $items):
                        ?>

                        <a class="sidebar-nav-link py-2 px-3 font-18 font-weight-medium m-0 flex-row-start flex-align-center"
                           data-page="<?=$items["data-value"]?>" href="<?=__url($items["link"])?>"
                            title="<?=$items["title"]?>" >

                            <span class="w-20px text-center">
                                <i class="<?=$items['icon-class']?> "></i>
                            </span>
                            <p class="ml-3 sidebar-text"><?=$items["title"]?></p>

                            <span id="<?=$items["data-value"]?>_notify"
                                  class="border-radius-50 p-1 ml-1 bg-orange color-white flex-row-around flex-align-center h-20px mnw-20px font-10 no-vis sidebar-notify">9</span>
                        </a>

                    <?php endforeach;
                endforeach; ?>
            </div>

        </div>
    </div>
















</div>

