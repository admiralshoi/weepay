<?php
/**
 * @var array|null $scriptList
 * @var array|null $styleList
 * @var string|null $customScripts
 * @var string|null $head
 * @var string|null $pageContent
 * @var string|null $pageHeaderTitles
 * @var array $viewList
 */

?>



<div class="page-wrapper">
    <?php require_once __view("templates.nav.landing.top-nav")?>

    <?php printView($viewList); ?>


    <?php require_once  __view("templates/landing/footer.php"); ?>
</div>
