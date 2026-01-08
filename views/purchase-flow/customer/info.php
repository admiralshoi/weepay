<?php
/**
 * @var object $args
 */

$customer = $args->customer;
$terminalSession = $args->terminalSession;
$terminal = $terminalSession->terminal;
$basket = $args->basket;
$page = $args->page ?? null;
$logoUrl = $page ? __url($page->logo) : null;

$pageTitle = "{$terminal->location->name} - Købsdetaljer";

?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
</script>


<div class="page-content mt-3">
    <div class="page-inner-content">

        <div class="stepper-progress">
            <div class="stepper-item">
                <div class="stepper-circle">1</div>
                <div class="stepper-label">Login</div>
            </div>

            <div class="stepper-line"></div>

            <div class="stepper-item stepper-item--active">
                <div class="stepper-circle">2</div>
                <div class="stepper-label">Info</div>
            </div>

            <div class="stepper-line"></div>

            <div class="stepper-item">
                <div class="stepper-circle">3</div>
                <div class="stepper-label">Vælg og Bekræft</div>
            </div>

            <div class="stepper-line"></div>

            <div class="stepper-item">
                <div class="stepper-circle">4</div>
                <div class="stepper-label">Betal</div>
            </div>
        </div>


        <div class="flex-col-start flex-align-center mt-4" style="row-gap: 1rem;">

            <!-- Mobile hidden: "Du handler hos" header -->
            <div class="checkout-header-mobile-hide flex-col-start flex-align-center" style="row-gap: .25rem;">
                <p class="design-box mb-0 px-2">
                    <i class="mdi mdi-store"></i>
                    <span class="font-weight-bold">Du Handler hos</span>
                </p>
                <p class="mb-0 font-25 font-weight-bold"><?=$terminal?->location->name?></p>
                <p class="mb-0 font-14 font-weight-medium color-gray"><?=$args->page->caption?></p>
            </div>

            <?php if(!isEmpty($args->paymentError)): ?>
            <div class="alert alert-danger w-100 flex-row-start flex-align-center" style="gap: .5rem;">
                <i class="mdi mdi-alert-circle-outline font-20"></i>
                <span><?=htmlspecialchars($args->paymentError)?></span>
            </div>
            <?php endif; ?>

            <?php if(!isEmpty($args->bnplLimit)): ?>
            <!-- Dark Credit Box -->
            <?=\features\DomMethods::bnplCreditCard($args->bnplLimit, $args->hasPastDue ?? false, null, 'w-100')?>
            <?php endif; ?>

            <!-- Store & Basket Info -->
            <div class="checkout-store-info w-100" id="store-basket-info" style="<?=isEmpty($basket) ? 'display: none;' : ''?>">
                <div class="checkout-store-info__left">
                    <?php if($logoUrl): ?>
                        <img src="<?=$logoUrl?>" alt="<?=$terminal->location->name?>" class="checkout-store-info__logo">
                    <?php else: ?>
                        <div class="checkout-store-info__logo-placeholder">
                            <?=strtoupper(substr($terminal->location->name, 0, 2))?>
                        </div>
                    <?php endif; ?>
                    <div class="checkout-store-info__text">
                        <p class="checkout-store-info__name"><?=$terminal->location->name?></p>
                        <p class="checkout-store-info__basket" data-show="basket_name"><?=$basket->name ?? ''?></p>
                    </div>
                </div>
                <p class="checkout-store-info__price">
                    <span data-show="basket_price"><?=isset($basket->price) ? number_format($basket->price, 0, ',', '.') : ''?></span> kr.
                </p>
            </div>

            <!-- Customer Details Card -->
            <div class="card border-radius-10px w-100 p-4 position-relative">
                <!-- Session ID badge - flows on mobile, absolute on desktop -->
                <div class="d-md-none w-100 flex-row-end mb-3">
                    <p class="design-box mb-0 font-13 font-weight-bold px-2 py-1">
                        ID: <?=$terminalSession->session?>
                    </p>
                </div>
                <div class="d-none d-md-block position-absolute" style="top: 10px; right: 10px;">
                    <p class="design-box mb-0 font-13 font-weight-bold px-2 py-1">
                        ID: <?=$terminalSession->session?>
                    </p>
                </div>

                <div class="flex-row-start flex-align-center flex-wrap cg-15 rg-1 row-centered-xs">
                    <div class="mx-auto-xs flex-row-center flex-align-center square-75 bg-wrapper-hover border-radius-50">
                        <i class="font-40 color-design-blue mdi mdi-account"></i>
                    </div>
                    <div class="flex-col-start mt-2" style="row-gap: .25rem;">
                        <div class="flex-row-start flex-align-center flex-wrap" style="gap: .5rem">
                            <p class="mb-0 font-14">Navn</p>
                            <p class="mb-0 font-14 font-weight-bold"><?=$customer->user->full_name?></p>
                        </div>
                        <div class="flex-row-start flex-align-center flex-nowrap" style="gap: .5rem">
                            <p class="mb-0 font-14">Fødselsdag</p>
                            <p class="mb-0 font-14 font-weight-bold"><?=$customer->user->birthdate?></p>
                        </div>
                        <div class="flex-row-start flex-align-center flex-nowrap" style="gap: .5rem">
                            <p class="mb-0 font-14">Nationalitet</p>
                            <p class="mb-0 font-14 font-weight-bold"><?=\classes\Methods::countries()->name($customer->nin_country)?></p>
                        </div>
                    </div>
                </div>

                <a href="<?=$args->nextStepLink?>" id="next-step" style="gap: .55rem; <?=isEmpty($basket) ? 'display: none;' : ''?>"
                   class="mt-4 btn-v2 design-action-btn-lg flex-row-center flex-align-center flex-nowrap">
                    <i class="mdi mdi-contactless-payment font-18"></i>
                    <span class="font-18">Vælg Betalingsplan</span>
                </a>

                <div class="flex-row-center flex-align-center flex-wrap mt-4 cg-1 rg-05" id="loader-container" style="<?=!isEmpty($basket) ? 'display: none;' : ''?>">
                    <div class="ml-3 flex-align-center flex-row-start" id="paymentButtonLoader">
                        <span class="spinner-border color-dark square-30" role="status" style="border-width: 3px;">
                          <span class="sr-only">Loading...</span>
                        </span>
                    </div>
                    <p class="mb-0 font-18 font-weight-medium text-center">Afventer butikshandling...</p>
                </div>
            </div>

            <!-- Cancel Button (outside card) -->
            <button id="cancel-checkout" style="gap: .55rem;"
                    class="mt-2 btn-v2 danger-btn-outline-lg flex-row-center flex-align-center flex-nowrap w-100">
                <i class="mdi mdi-close font-18"></i>
                <span class="font-18">Afbryd køb</span>
            </button>



        </div>
    </div>
</div>


<?php scriptStart(); ?>
<script>
    $(document).ready(function () {
        CustomerCheckoutInfo.init(
            <?=json_encode($terminalSession->uid)?>
        );
    })
</script>
<?php scriptEnd(); ?>

