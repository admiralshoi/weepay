<?php
use classes\Methods;



$roleName = Methods::roles()->name();
$sidebarHandler = Methods::sidebars();


?>

<div id="sidebar" class="flex-col-between flex-align-start">
    <div class="flex-col-between h-100">
        <div class="flex-col-start">
            <div class="flex-row-end flex-align-center px-2 pt-2 color-gray flex-nowrap" style="column-gap: .25rem" id="leftSidebarCloseBtn">
                <i class="font-16  fa-solid fa-xmark" id="" ></i>
                <span class="text-sm">Close</span>
            </div>
            <div id="sidebar-top-logo">
                <div class="flex-row-start flex-align-center w-100 cursor-pointer">
                    <img src="<?=__asset(LOGO_ICON_WHITE)?>" class="w-10 noSelect mr-2"/>
                    <p class="mb-0 font-22 color-white noSelect" style="width: fit-content" data-href="<?=__url('')?>"><?=BRAND_NAME?></p>
                </div>
            </div>

            <div class="tabletOnlyFlex justify-content-center" id="sidebar-top-nav">
                <div class="flex-row-center">
                    <i class="font-25 text-gray fa-solid fa-bars " id="leftSidebarOpenBtn"></i>
                </div>
            </div>

            <div id="side-bar-menu-content" style="max-height: calc(100vh - 225px); overflow-x: auto;" class="w-100 px-2">


                <?php
                $sections = $sidebarHandler::sideBarMenuAccess();
                $accessLevel = __accessLevel();
                foreach ($sections as $menuOpt):
                    if(empty($menuOpt)) continue;
                    $menuItems = $sidebarHandler::sideBarLinks($menuOpt["pathName"]);

                    foreach ($menuItems as $key => $items):
                        if(!Methods::access()->userCanAccess($accessLevel,$items["access_level"])) continue;
                        if($items["link"] === "applications" && !\features\Settings::$app->open_campaigns_enabled) continue;
                        ?>

                        <a class="sidebar-nav-link py-2 px-3 text-sm font-weight-medium m-0 flex-row-start flex-align-center"
                           data-page="<?=$items["data-value"]?>" href="<?=__url($items["link"])?>">

                            <i class="<?=$items['icon-class']?> "></i>
                            <p class="ml-3 sidebar-text"><?=$items["title"]?></p>

                            <span id="<?=$items["data-value"]?>_notify"
                                  class="border-radius-50 p-1 ml-1 bg-orange color-white flex-row-around flex-align-center h-20px mnw-20px font-10 no-vis sidebar-notify">9</span>
                        </a>

                    <?php endforeach;
                endforeach; ?>
            </div>

        </div>

        <div class="my-2 px-4 flex-col-end" id="sidebar-brand-box">
            <p class="font-13 ">&copy; <?=BRAND_NAME?></p>
            <div class="flex-row-start flex-align-center flex-wrap" style="row-gap: 0; column-gap: .5rem">
                <a href="<?=__url('privacy-policy')?>" class="">Privacy policy</a>
                <a href="<?=__url('terms-of-use')?>" class="">Terms of Use</a>
                <?php if(Methods::isAffiliate()): ?>
                    <a href="<?=__url('affiliate-terms-and-usage-policy')?>" class="">Affiliate Terms</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
















</div>

