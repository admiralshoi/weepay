<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;

$location = $args->location ?? null;
$reason = $args->reason ?? 'unknown';

$messages = [
    'location_inactive' => [
        'title' => 'Lokationen er ikke aktiv',
        'message' => 'Denne lokation accepterer ikke betalinger i øjeblikket.',
    ],
    'organisation_inactive' => [
        'title' => 'Forhandleren er ikke aktiv',
        'message' => 'Denne forhandler accepterer ikke betalinger i øjeblikket.',
    ],
    'no_payment_source' => [
        'title' => 'Betalingsopsætning ikke færdig',
        'message' => 'Forhandleren har ikke færdiggjort opsætningen af betalinger endnu.',
    ],
    'no_merchant_id' => [
        'title' => 'Betalingsopsætning ikke færdig',
        'message' => 'Forhandleren har ikke færdiggjort opsætningen af betalinger endnu.',
    ],
    'unknown' => [
        'title' => 'Betalinger ikke tilgængelige',
        'message' => 'Denne forhandler kan ikke acceptere betalinger i øjeblikket.',
    ],
    'no_published_page' => [
        'title' => 'Lokationen mangler en forside',
        'message' => 'Denne forhandler kan ikke acceptere betalinger i øjeblikket.',
    ],
];

$messageData = $messages[$reason] ?? $messages['unknown'];
$pageTitle = $messageData['title'];
?>


<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
</script>


<div class="page-content mt-3">
    <div class="page-inner-content">

        <div class="flex-row-center-center mx-auto w-100 mxw-600px mt-5">
            <div class="card border-radius-10px w-100">
                <div class="card-body p-5">
                    <div class="flex-col-start flex-align-center text-center" style="row-gap: 2rem;">
                        <!-- Icon -->
                        <div class="square-60 border-radius-50 flex-row-center-center bg-lighter-blue">
                            <i class="mdi mdi-alert-circle-outline font-40 color-blue"></i>
                        </div>

                        <!-- Title and Message -->
                        <div class="flex-col-start flex-align-center" style="row-gap: 1rem;">
                            <h1 class="font-weight-bold font-28 mb-0"><?=$messageData['title']?></h1>
                            <p class="font-16 color-gray mb-0"><?=$messageData['message']?></p>
                        </div>

                        <!-- Additional Info -->
                        <?php if(!isEmpty($location)): ?>
                        <div class="flex-col-start flex-align-center bg-lighter-blue border-radius-10px p-3 w-100" style="row-gap: 0.5rem;">
                            <p class="font-14 color-gray mb-0">Lokation</p>
                            <p class="font-18 font-weight-bold mb-0"><?=$location->name?></p>
                        </div>
                        <?php endif; ?>

                        <!-- Help Text -->
                        <div class="flex-col-start flex-align-center" style="row-gap: 0.5rem;">
                            <p class="font-14 color-gray mb-0">Kontakt forhandleren for at få mere information</p>
                            <?php if(!isEmpty($location) && !isEmpty($location->contact_email)): ?>
                            <a href="mailto:<?=$location->contact_email?>" class="font-14 font-weight-medium color-blue">
                                <?=$location->contact_email?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>


<?php scriptStart(); ?>
<script>
    $(document).ready(function () {
        // Page ready
    })
</script>
<?php scriptEnd(); ?>
