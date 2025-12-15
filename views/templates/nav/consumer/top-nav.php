<?php
/**
 * @var string|null $pageHeaderTitle
 */
use classes\Methods;
use classes\enumerations\Links;

?>


<div id="top-nav" class="home">
    <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .75rem; max-width: var(--left-nav-width)">
        <button class="mobileOnlyInlineFlex btn-unstyled p-0 m-0 border-0 bg-transparent" id="topNavSidebarToggle" style="cursor: pointer;">
            <i class="mdi mdi-menu font-30 color-gray hover-color-blue"></i>
        </button>
        <p class="hideOnSmallScreen mb-0 font-18 font-weight-bold color-blue text-nowrap">WeePay Kunde</p>
    </div>


    <div class="flex-row-end flex-align-center" style="column-gap: 1rem">

        <p class="mb-0 color-gray font-14 font-weight-medium ellipsis-single-line" style="max-width: 200px;">
            <?=\classes\utility\Titles::cleanUcAll(__name())?>
        </p>

        <a href="<?=__url(Links::$app->logout)?>" class="btn-v2 trans-hover-design-action-btn flex-row-start flex-align-center flex-nowrap font-14" style="gap: .5rem;">
            <i class="mdi mdi-logout"></i>
            <span>Log ud</span>
        </a>

    </div>
</div>


