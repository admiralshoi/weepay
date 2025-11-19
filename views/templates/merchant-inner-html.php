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



    <?php if(Methods::isAdmin()):

        $col1Notice = [
            [
                "criteria" => testingLiveDb(),
                "text" => "Test env. currently showing LIVE data."
            ],
            [
                "criteria" => \features\Settings::$app->live_campaign_dev,
                "text" => "Webhook 'forwards' currently uses LIVE test campaigns."
            ],
        ];

        $showCol = false;
        foreach ($col1Notice as $item) {
            if($item["criteria"]) {
                $showCol = true;
                break;
            }
        }

        if($showCol): ?>
            <div class="row mb-3 mx-md-4" style="row-gap: .5rem;">
                <div class="col-12 col-lg-6 p">
                    <div class="p-3 alert-warning">
                        <p class="font-16 font-weight-bold">Admin notice</p>
                        <ul>
                            <?php foreach ($col1Notice as $item) :
                                if(!$item["criteria"]) continue; ?>
                                <li><?=$item["text"]?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>



    <?php printView($viewList); ?>




</div>
