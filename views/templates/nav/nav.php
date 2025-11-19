<?php
/**
 * @var string|null $pageHeaderTitle
 */
use classes\Methods;
?>

<div id="fixed-top-nav">
    <div class="flex-row-start flex-align-center">

        <div class="nav-container-v2 mobileOnlyFlex">
            <div class="nav-item-v2">
                <i class="nav-item-icon fa-solid fa-bars " id="leftSidebarOpenBtn"></i>
            </div>
        </div>
    </div>
    <div class="flex-row-end flex-align-center" style="column-gap: 1rem">

        <div class="nav-container-v2">
            <div class="dropdown nav-item-v2 nav-item-icon">
                <a class="dropdown-toggle dropdown-no-arrow" href="javascript:void(0);"  role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fa-regular fa-bell"></i>
                </a>
                <div class="dropdown-menu" id="notifications-dropdown">
                    <div class="item-header">Today</div>
                    <div class="list-item">
                        <div>
                            <div class="icon-item">
                                <i class="mdi mdi-message"></i>
                            </div>
                            <div class="flex-col-start">
                                <div class="msg-title">All Messages</div>
                                <div class="msg-content">Some message is better than no message for sure. Also, how are we doing today my good friend?</div>
                            </div>
                        </div>
                        <div class="time-ago">2 hours ago</div>
                    </div>
                </div>
            </div>
            <div class="nav-divider-v2"></div>
            <a class="nav-item-v2 hideOnMobileFlex" style="column-gap: .25rem" href="<?=__url('premium')?>">
                <i class="fa-regular fa-star" style="margin-top: -2px;"></i>
                <span>Explore Premium</span>
            </a>
            <div class="nav-divider-v2 hideOnMobileBlock"></div>



            <div class="dropdown nav-item-v2">
                <a class=" dropdown-toggle " href="javascript:void(0);" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <img src="<?=__asset(DEFAULT_USER_PICTURE)?>"  class="square-20 border-radius-50 noSelect" />
                    <span class="">My Account</span>
                </a>
                <div class="dropdown-menu section-dropdown" id="account-dropdown">
                    <div class="account-header">
                        <img src="<?=__asset(DEFAULT_USER_PICTURE)?>" class="square-50 border-radius-50">
                        <div class="flex-col-start">
                            <p class="mb-0 font-14 font-weight-bold"><?=$_SESSION["full_name"]?></p>
                            <p class="mb-0 font-13 color-gray"><?=$_SESSION["email"]?></p>
                        </div>
                    </div>

                    <div class="account-body">
                        <?php if(Methods::isAdmin()): ?>
                            <a href="<?=__url(ADMIN_PANEL_PATH)?>" class="list-item">
                                <i class="fa-solid fa-user-tie"></i>
                                <span>Admin Panel</span>
                            </a>
                        <?php elseif(Methods::isBrand()): ?>
                            <a href="<?=__url('premium')?>" class="list-item mobileOnlyFlex">
                                <i class="fa-solid fa-star"></i>
                                <span>Explore Premium</span>
                            </a>
                        <?php endif; ?>

                        <a href="<?=__url('help')?>" class="list-item">
                            <i class="fa-solid fa-circle-info"></i>
                            <span>Help</span>
                        </a>
                        <a href="<?=__url('account-settings')?>" class="list-item">
                            <i class="fa-solid fa-gear"></i>
                            <span>Account Settings</span>
                        </a>
                    </div>

                    <div class="account-footer">
                        <a href="<?=__url('logout')?>" class="list-item">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>








    </div>
</div>

