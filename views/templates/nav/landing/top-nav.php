<?php
/**
 * @var string|null $pageHeaderTitle
 */
use classes\Methods;
use classes\enumerations\Links;

?>


<div id="top-nav" class="home">
    <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .25rem; max-width: var(--left-nav-width)">
        <a href="<?=__url(Links::$app->home)?>">
            <img src="<?=__asset(LOGO_WIDE_HEADER)?>" class="w-100px" />
        </a>
    </div>



    <div class="flex-row-end flex-align-center flex-nowrap" style="column-gap: .5rem">
        <?php if(isLoggedIn()): ?>
            <?php if(Methods::isMerchant()): ?>
                <p class="mb-0 color-gray font-14 font-weight-medium ellipsis-single-line hideOnMobileBlock" style="max-width: 200px;">
                    <?=\classes\utility\Titles::cleanUcAll(\features\Settings::$organisation?->organisation?->name)?>
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
            <a href="<?=__url(Links::$app->logout)?>" class="btn-v2 design-action-btn flex-row-start flex-align-center flex-nowrap font-14" style="gap: .5rem;">
                <i class="mdi mdi-logout"></i>
                <span>Log ud</span>
            </a>
        <?php else: ?>
            <a href="<?=__url(Links::$app->auth->merchantLogin)?>"
               class="btn-v2 trans-btn flex-row-start flex-align-center flex-nowrap font-14 border-radius-10px" style="gap: .5rem;">
                <span>Forhandler</span>
            </a>

            <a href="<?=__url(Links::$app->auth->consumerLogin)?>"
               class="btn-v2 trans-btn flex-row-start flex-align-center flex-nowrap font-14 border-radius-10px" style="gap: .5rem;">
                <span>Kunde</span>
            </a>
        <?php endif; ?>
    </div>
</div>



