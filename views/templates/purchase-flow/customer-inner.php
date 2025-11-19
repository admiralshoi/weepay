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

?>



<div class="page-wrapper">
    <?php require_once __view("templates.nav.purchase-flow.customer-nav")?>

    <?php printView($viewList); ?>

</div>
