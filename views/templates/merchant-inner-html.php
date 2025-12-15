<?php
/**
 * @var array|null $scriptList
 * @var array|null $styleList
 * @var string|null $customScripts
 * @var string|null $head
 * @var string|null $pageContent
 * @var string|null $pageHeaderTitle
 * @var array $viewList
 */

use classes\Methods;
?>



<div class="page-wrapper home">
    <?php
    if(Methods::isMerchant()) include_once __view("templates.nav.merchant.top-nav");
    else include_once __view("templates.nav.nav");
    ?>




    <?php printView($viewList); ?>




</div>
