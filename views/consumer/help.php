<?php
/**
 * Consumer Help Page
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "Hjælp";
?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "help";
</script>

<div class="page-content">

    <div class="flex-col-start mb-4">
        <p class="mb-0 font-30 font-weight-bold">Hjælp & Support</p>
        <p class="mb-0 font-16 font-weight-medium color-gray">Find svar på dine spørgsmål eller kontakt vores supportteam</p>
    </div>

    <div class="row" style="row-gap: 1.5rem;">
        <!-- FAQ Card -->
        <div class="col-12 col-md-6">
            <a href="<?=__url(Links::$faq->consumer)?>" class="card border-radius-10px h-100 text-decoration-none hover-shadow" style="transition: box-shadow 0.2s, transform 0.2s;">
                <div class="card-body p-4">
                    <div class="flex-col-start h-100">
                        <div class="square-60 bg-blue border-radius-12px flex-row-center-center mb-3">
                            <i class="mdi mdi-frequently-asked-questions color-white font-30"></i>
                        </div>
                        <p class="font-20 font-weight-bold color-dark mb-2">Ofte stillede spørgsmål</p>
                        <p class="font-14 color-gray mb-3 flex-grow-1">Find svar på de mest almindelige spørgsmål om WeePay, betalinger, ordrer og meget mere.</p>
                        <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                            <span class="font-14 font-weight-medium color-blue">Se FAQ</span>
                            <i class="mdi mdi-arrow-right color-blue"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Support Card -->
        <div class="col-12 col-md-6">
            <a href="<?=__url(Links::$consumer->support)?>" class="card border-radius-10px h-100 text-decoration-none hover-shadow" style="transition: box-shadow 0.2s, transform 0.2s;">
                <div class="card-body p-4">
                    <div class="flex-col-start h-100">
                        <div class="square-60 bg-green border-radius-12px flex-row-center-center mb-3">
                            <i class="mdi mdi-face-agent color-white font-30"></i>
                        </div>
                        <p class="font-20 font-weight-bold color-dark mb-2">Kontakt Support</p>
                        <p class="font-14 color-gray mb-3 flex-grow-1">Har du brug for personlig hjælp? Opret en supportsag, og vi vender tilbage hurtigst muligt.</p>
                        <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                            <span class="font-14 font-weight-medium color-blue">Kontakt os</span>
                            <i class="mdi mdi-arrow-right color-blue"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Quick Contact Info -->
    <div class="card border-radius-10px mt-4">
        <div class="card-body p-4">
            <p class="font-18 font-weight-bold mb-3">Hurtig kontakt</p>
            <div class="row" style="row-gap: 1rem;">
                <div class="col-12 col-md-6">
                    <div class="flex-row-start flex-align-center" style="gap: 1rem;">
                        <div class="square-40 bg-light-blue border-radius-10px flex-row-center-center flex-shrink-0">
                            <i class="mdi mdi-email-outline color-blue font-22"></i>
                        </div>
                        <div class="flex-col-start">
                            <p class="mb-0 font-13 color-gray">Email</p>
                            <a href="mailto:support@wee-pay.dk" class="font-14 font-weight-medium color-dark">support@wee-pay.dk</a>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="flex-row-start flex-align-center" style="gap: 1rem;">
                        <div class="square-40 bg-light-blue border-radius-10px flex-row-center-center flex-shrink-0">
                            <i class="mdi mdi-clock-outline color-blue font-22"></i>
                        </div>
                        <div class="flex-col-start">
                            <p class="mb-0 font-13 color-gray">Svartid</p>
                            <p class="font-14 font-weight-medium color-dark mb-0">Inden for 24 timer</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
    .hover-shadow:hover {
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1) !important;
        transform: translateY(-2px);
    }
    .bg-light-blue {
        background-color: rgba(59, 130, 246, 0.1);
    }
</style>
