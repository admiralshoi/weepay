<?php
/**
 * @var string|null $pageHeaderTitle
 */
use classes\Methods;
use classes\enumerations\Links;

?>


<div id="top-nav" class="home">
    <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .25rem; max-width: var(--left-nav-width)">
        <img src="<?=__asset(LOGO_WIDE_HEADER)?>" class="w-100px" />
    </div>



    <div class="flex-row-end flex-align-center flex-nowrap" style="column-gap: .5rem">

        <a href="<?=__url(Links::$app->auth->merchantLogin)?>"
           class="btn-v2 trans-btn flex-row-start flex-align-center flex-nowrap font-14 border-radius-10px" style="gap: .5rem;">
            <span>Forhandler</span>
        </a>

        <a href="<?=__url(Links::$app->auth->consumerLogin)?>"
           class="btn-v2 trans-btn flex-row-start flex-align-center flex-nowrap font-14 border-radius-10px" style="gap: .5rem;">
            <span>Kunde</span>
        </a>

    </div>
</div>



