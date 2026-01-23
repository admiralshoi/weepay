<?php
/**
 * Merchant FAQ Page
 * @var object $args
 * @var array $faqs - FAQs grouped by category from database
 */

use classes\enumerations\Links;

$pageTitle = "FAQ - Forhandlere";
$faqs = $args->faqs ?? [];
?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
</script>

<div class="page-content">
    <div class="faq-page">
        <div class="faq-header">
            <h1>Ofte stillede spørgsmål</h1>
            <p>Alt hvad du behøver at vide om WeePay for din butik</p>
        </div>

        <div class="faq-nav">
            <a href="<?=__url(Links::$faq->consumer)?>">For forbrugere</a>
            <a href="<?=__url(Links::$faq->merchant)?>" class="active">For forhandlere</a>
        </div>

        <?php if (empty($faqs)): ?>
            <div class="faq-empty-state">
                <i class="mdi mdi-frequently-asked-questions font-48 mb-3 d-block"></i>
                <p>Der er ingen FAQ'er tilgængelige i øjeblikket.</p>
                <p>Kontakt os venligst, hvis du har spørgsmål.</p>
            </div>
        <?php else: ?>
            <?php
            $accordionIndex = 0;
            foreach ($faqs as $category => $categoryFaqs):
                $accordionId = 'faqAccordion' . $accordionIndex;
            ?>
                <div class="faq-category">
                    <h4 class="faq-category-title"><?=htmlspecialchars($category)?></h4>
                    <div class="accordion faq-accordion" id="<?=$accordionId?>">
                        <?php foreach ($categoryFaqs as $faqIndex => $faq):
                            $headingId = 'heading' . $accordionIndex . '_' . $faqIndex;
                            $collapseId = 'collapse' . $accordionIndex . '_' . $faqIndex;
                        ?>
                            <div class="card">
                                <div class="card-header" id="<?=$headingId?>">
                                    <h5 class="mb-0">
                                        <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#<?=$collapseId?>" aria-expanded="false" aria-controls="<?=$collapseId?>">
                                            <?=htmlspecialchars($faq->title)?>
                                        </button>
                                    </h5>
                                </div>
                                <div id="<?=$collapseId?>" class="collapse" aria-labelledby="<?=$headingId?>" data-parent="#<?=$accordionId?>">
                                    <div class="card-body">
                                        <?=$faq->content?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php
                $accordionIndex++;
            endforeach;
            ?>
        <?php endif; ?>

        <div class="faq-cta">
            <h3><?=isLoggedIn() ? 'Har du flere spørgsmål?' : 'Klar til at komme i gang?'?></h3>
            <p><?=isLoggedIn() ? 'Vi er her for at hjælpe dig' : 'Tilmeld dig i dag og begynd at tilbyde fleksible betalinger til dine kunder'?></p>
            <div class="faq-cta-buttons">
                <?php if(!isLoggedIn()): ?>
                <a href="<?=__url(Links::$app->auth->merchantLogin)?>" class="btn-v2 action-btn px-4 py-2 border-radius-10px font-weight-bold">
                    Bliv forhandler
                </a>
                <?php endif; ?>
                <a href="<?=__url(Links::$app->home . '#contact')?>" class="btn-v2 trans-hover-design-action-btn card-border px-4 py-2 border-radius-10px font-weight-medium">
                    Kontakt os
                </a>
            </div>
        </div>
    </div>
</div>
