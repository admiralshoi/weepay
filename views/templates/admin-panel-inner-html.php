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

$isPanel = true;
?>



<div class="page-wrapper home" data-area="admin">
    <?php
    include_once __view("templates.nav.admin.top-nav");
    ?>

    <?php printView($viewList); ?>

</div>
