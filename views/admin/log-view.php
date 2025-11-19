<?php

/**
 * @var object $args
 */

$pageTitle = "Log list";
?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "logs";
</script>
<div class="page-content position-relative" data-page="log-view">

    <div class="flex-row-start flex-align-center font-22 font-weight-medium">
        <p><?=\classes\utility\Titles::clean(strtolower($args->type))?></p>
    </div>


    <div class="row mt-5">
        <div class="col-12 ">
            <div class="card">
                <div class="card-body">


                    <div style="display:flex;justify-content: flex-start;flex-flow: column">
                        <div style="text-align: right; color: #727272; font-size: 18px;">
                            <?php if(!empty($args->timer)): ?>
                                <span style="font-weight: 600;">Time since last log initialization: </span><?=$args->timer?>
                            <?php endif; ?>
                        </div>
                        <div style='color:#727272; text-align: left;'>
                            <?php
                            if(!empty($args->content)) echo implode("<br/>", toArray($args->content));
                            ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>






</div>


