<?php
/**
 * Admin Panel - FAQ Management
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "FAQ Administration";
$consumerFaqs = $args->consumerFaqs ?? [];
$merchantFaqs = $args->merchantFaqs ?? [];

// Flatten FAQs into single arrays
$allConsumerFaqs = [];
$allMerchantFaqs = [];
foreach ($consumerFaqs as $category => $faqs) {
    foreach ($faqs as $faq) {
        $allConsumerFaqs[] = $faq;
    }
}
foreach ($merchantFaqs as $category => $faqs) {
    foreach ($faqs as $faq) {
        $allMerchantFaqs[] = $faq;
    }
}
// Sort by updated_at (most recent first)
usort($allConsumerFaqs, fn($a, $b) => strtotime($b->updated_at ?? $b->created_at) - strtotime($a->updated_at ?? $a->created_at));
usort($allMerchantFaqs, fn($a, $b) => strtotime($b->updated_at ?? $b->created_at) - strtotime($a->updated_at ?? $a->created_at));
?>
<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "faqs";
</script>


<div class="page-content py-3">
    <div class="page-inner-content">
        <div class="flex-col-start rg-20">

            <!-- Page Header -->
            <div class="flex-row-between flex-align-center w-100 flex-wrap cg-10 rg-10">
                <div class="flex-col-start">
                    <h1 class="mb-0 font-24 font-weight-bold">FAQ Administration</h1>
                    <p class="mb-0 font-14 color-gray">Administrer ofte stillede spørgsmål</p>
                </div>
            </div>

            <!-- Toggle -->
            <div class="flex-row-between flex-align-center w-100 mb-3">
                <div class="faq-toggle">
                    <button type="button" class="faq-toggle-btn active" onclick="switchTab('consumer')" id="toggleConsumer">
                        <i class="mdi mdi-account-outline"></i>
                        Forbrugere
                        <span class="count" id="consumerCount"><?=count($allConsumerFaqs)?></span>
                    </button>
                    <button type="button" class="faq-toggle-btn" onclick="switchTab('merchant')" id="toggleMerchant">
                        <i class="mdi mdi-store-outline"></i>
                        Forhandlere
                        <span class="count" id="merchantCount"><?=count($allMerchantFaqs)?></span>
                    </button>
                </div>
                <button type="button" class="btn-v2 action-btn btn-sm" onclick="addNewFaq()" id="addFaqBtn">
                    <i class="mdi mdi-plus"></i> Tilføj ny
                </button>
            </div>

            <!-- Consumer FAQs -->
            <div class="faq-section active w-100" id="consumerSection">
                <div id="consumerFaqList">
                    <?php if (empty($allConsumerFaqs)): ?>
                    <div class="text-center py-5 color-gray">
                        <i class="mdi mdi-frequently-asked-questions font-48 d-block mb-2"></i>
                        <p class="mb-0">Ingen forbruger FAQ'er endnu</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($allConsumerFaqs as $faq): ?>
                    <?php include __DIR__ . '/_faq-item.php'; ?>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Merchant FAQs -->
            <div class="faq-section w-100" id="merchantSection">
                <div id="merchantFaqList">
                    <?php if (empty($allMerchantFaqs)): ?>
                    <div class="text-center py-5 color-gray">
                        <i class="mdi mdi-frequently-asked-questions font-48 d-block mb-2"></i>
                        <p class="mb-0">Ingen forhandler FAQ'er endnu</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($allMerchantFaqs as $faq): ?>
                    <?php include __DIR__ . '/_faq-item.php'; ?>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Template for new FAQ (hidden) -->
<template id="faq-template">
    <div class="faq-item" data-uid="">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group mb-2">
                    <label class="font-12 color-gray">Kategori</label>
                    <input type="text" class="form-field-v2 w-100 faq-category" placeholder="F.eks. Generelt om WeePay">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group mb-2">
                    <label class="font-12 color-gray">Sortering</label>
                    <input type="number" class="form-field-v2 w-100 faq-sort" value="0" min="0">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group mb-2">
                    <label class="font-12 color-gray">Status</label>
                    <select class="form-select-v2 w-100 h-45px faq-active">
                        <option value="0" selected>Inaktiv</option>
                        <option value="1">Aktiv</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="form-group mb-2">
            <label class="font-12 color-gray">Spørgsmål/Titel</label>
            <input type="text" class="form-field-v2 w-100 faq-title" placeholder="F.eks. Hvad er WeePay?">
        </div>
        <div class="form-group mb-3">
            <label class="font-12 color-gray">Svar</label>
            <div class="html-editor-container">
                <div class="html-editor-toolbar">
                    <button type="button" onclick="execCmd('bold')" title="Fed"><i class="mdi mdi-format-bold"></i></button>
                    <button type="button" onclick="execCmd('italic')" title="Kursiv"><i class="mdi mdi-format-italic"></i></button>
                    <button type="button" onclick="execCmd('underline')" title="Understreget"><i class="mdi mdi-format-underline"></i></button>
                    <button type="button" onclick="execLink(this)" title="Link"><i class="mdi mdi-link"></i></button>
                    <button type="button" onclick="execCmd('insertUnorderedList')" title="Liste"><i class="mdi mdi-format-list-bulleted"></i></button>
                </div>
                <div class="faq-content-editor" contenteditable="true" oninput="syncContent(this)" data-placeholder="Svar på spørgsmålet..."></div>
                <input type="hidden" class="faq-content" value="">
            </div>
        </div>
        <div class="flex-row-end flex-align-center cg-15">
            <button type="button" class="btn-v2 danger-btn btn-sm" onclick="deleteFaq(this)">
                <i class="mdi mdi-delete"></i> Slet
            </button>
            <button type="button" class="btn-v2 action-btn btn-sm" onclick="saveFaq(this)">
                <i class="mdi mdi-content-save"></i> Gem
            </button>
        </div>
        <input type="hidden" class="faq-type" value="">
    </div>
</template>
