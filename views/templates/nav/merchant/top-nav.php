<?php
/**
 * @var string|null $pageHeaderTitle
 */
use classes\Methods;
use classes\enumerations\Links;

$organisation = \features\Settings::$organisation?->organisation;
?>


<div id="top-nav" class="home">
    <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .25rem; max-width: var(--left-nav-width)">
        <sup>
        <img src="<?=__asset(LOGO_ICON)?>" class="w-30px" />
        </sup>
        <p class="hideOnMobileInlineBlock mb-0 font-18 font-weight-bold color-blue text-nowrap">WeePay Forhandler</p>
    </div>


    <div class="flex-row-end flex-align-center" style="column-gap: 1rem">

        <?php if(!isEmpty($organisation)): ?>
            <p class="mb-0 color-gray font-14 font-weight-medium ellipsis-single-line" style="max-width: 200px;"><?=\classes\utility\Titles::cleanUcAll($organisation->name)?></p>
        <?php endif; ?>

        <a href="<?=__url(Links::$app->logout)?>" class="btn-v2 trans-hover-design-action-btn flex-row-start flex-align-center flex-nowrap font-14" style="gap: .5rem;">
            <i class="mdi mdi-logout"></i>
            <span>Log ud</span>
        </a>

    </div>
</div>


