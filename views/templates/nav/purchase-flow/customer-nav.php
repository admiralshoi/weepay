<?php
/**
 * @var string|null $pageHeaderTitle
 */

use classes\enumerations\Links;
use classes\Methods;
?>

<div id="top-nav">
        <div class="flex-row-between-center w-100" style="column-gap: 1rem">





        <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .25rem;">
            <a href="<?=__url(Links::$app->home)?>">
                <img src="<?=__asset(LOGO_WIDE_HEADER)?>" class="w-100px" />
            </a>

        </div>


        <div class="flex-row-end flex-align-center" style="column-gap: 1rem">


            <?php if(Methods::isMerchant()): ?>
                <p class="mb-0 color-gray font-14 font-weight-medium ellipsis-single-line hideOnMobileBlock" style="max-width: 200px;">
                    <?=\classes\utility\Titles::cleanUcAll(\features\Settings::$organisation?->organisation?->name ?? '')?>
                </p>
                <a href="<?=__url(Links::$merchant->dashboard)?>"
                   class="btn-v2 trans-btn flex-row-start flex-align-center flex-nowrap font-14 border-radius-5px" style="gap: .5rem;">
                    <span>Dashboard</span>
                </a>
            <?php elseif(Methods::isAdmin()): ?>
                <p class="mb-0 color-gray font-14 font-weight-medium ellipsis-single-line hideOnMobileBlock" style="max-width: 200px;">
                    <?=\classes\utility\Titles::cleanUcAll(__name())?>
                </p>
                <a href="<?=__url(Links::$admin->dashboard)?>"
                   class="btn-v2 trans-btn flex-row-start flex-align-center flex-nowrap font-14 border-radius-5px" style="gap: .5rem;">
                    <span>Dashboard</span>
                </a>
            <?php elseif(Methods::isConsumer()): ?>
                <p class="mb-0 color-gray font-14 font-weight-medium ellipsis-single-line hideOnMobileBlock" style="max-width: 200px;">
                    <?=\classes\utility\Titles::cleanUcAll(__name())?>
                </p>
                <a href="<?=__url(Links::$consumer->dashboard)?>"
                   class="btn-v2 trans-btn flex-row-start flex-align-center flex-nowrap font-14 border-radius-5px" style="gap: .5rem;">
                    <span>Dashboard</span>
                </a>
            <?php endif; ?>


            <?php if(isLoggedIn()): ?>
            <a href="<?=__url(Links::$app->logout)?>" class="btn-v2 trans-hover-design-action-btn flex-row-start flex-align-center flex-nowrap font-14" style="gap: .5rem;">
                <i class="mdi mdi-logout"></i>
                <span>Log ud</span>
            </a>
            <?php endif; ?>
        </div>



    </div>
</div>

