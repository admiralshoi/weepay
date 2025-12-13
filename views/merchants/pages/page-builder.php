<?php
/**
 * @var object $args
 */

use classes\app\LocationPermissions;
use classes\enumerations\Links;
use classes\Methods;
use features\Settings;


$location = $args->location;
$pageTitle = $location->name . " - Pagebuilder";


?>




<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "locations";
    var worldCountries = <?=json_encode(toArray($args->worldCountries))?>;
</script>


<div class="page-content home">

    <div class="flex-col-start" id="nav" style="gap: 0;">
        <a class="cursor-pointer transition-color font-14 flex-row-start flex-align-center flex-nowrap color-gray hover-color-dark"
           style="gap: .5rem;" href="<?=__url(Links::$merchant->locations->setSingleLocation($location->slug))?>">
            <i class="mdi mdi-arrow-left"></i>
            <span>Tilbage</span>
        </a>
        <div class="flex-row-between flex-align-start flex-nowrap">
            <?=\features\DomMethods::locationSelect($args->locationOptions, $location->slug);?>
            <div class="flex-row-end">
                <p class="mb-0 font-16 font-weight-medium color-gray"><?=$location->name?></p>
            </div>
        </div>
    </div>


    <div class="flex-row-between-start flex-wrap" style="column-gap: .75rem; row-gap: .5rem;">
        <div class="flex-col-start">
            <div class="flex-row-start-center flex-nowrap cg-05">
                <p class="mb-0 font-30 font-weight-bold text-wrap">Rediger butiksside</p>
            </div>
            <p class="mb-0 font-16 font-weight-medium color-gray text-wrap mxw-400px">
                Tilpas din butiksside – denne side fokuserer på din butik. WeePay faciliterer kun.
            </p>
        </div>
        <div class="flex-row-end-center flex-nowrap cg-075 flex-nowrap">
            <?php LocationPermissions::__oModifyProtectedContent($location,  'pages'); ?>
            <a href="<?=__url(Links::$merchant->locations->previewPage($args->slug, 'draft'))?>" class="btn-v2 mute-btn text-nowrap" >
                <i class="mdi mdi-eye-outline"></i>
                <span class="text-nowrap">Forhåndsvis side</span>
            </a>
            <?php LocationPermissions::__oEndContent(); ?>

            <?php LocationPermissions::__oModifyProtectedContent($location,  'pages'); ?>
            <a href="<?=__url(Links::$merchant->locations->previewCheckout($args->slug, 'draft'))?>" class="btn-v2 mute-btn text-nowrap" >
                <i class="mdi mdi-cart-outline"></i>
                <span class="text-nowrap">Forhåndsvis checkout</span>
            </a>
            <?php LocationPermissions::__oEndContent(); ?>

            <?php LocationPermissions::__oModifyProtectedContent($location,  'pages'); ?>
            <button name="save_page_changes" data-id="<?=$location->uid?>" onclick="" class="btn-v2 action-btn text-nowrap" >
                <i class="mdi mdi-content-save"></i>
                <span class="text-nowrap">Gem ændringer</span>
            </button>
            <?php LocationPermissions::__oEndContent(); ?>
        </div>
    </div>

    <!-- Page Builder Content -->
    <div class="row mt-4" style="row-gap: 1.5rem;">
        <!-- Left Panel: Editor -->
        <div class="col-12 col-lg-6">
            <div class="flex-col-start" style="row-gap: 2rem;">

                <!-- Logo Section -->
                <div  class="card border-radius-10px">
                    <div class="card-body">
                        <div class="flex-col-start " style="row-gap: 1rem;">
                            <h5 class="mb-0 font-18 font-weight-bold">Logo</h5>
                            <div class="border-dashed border-color-card flex-col-start flex-align-center flex-justify-center border-dashed-gray border-radius-05rem p-5 cursor-pointer hover-bg-light transition-all"
                                 style="min-height: 150px;">
                                <i class="mdi mdi-upload font-48 color-gray mb-2"></i>
                                <p class="mb-0 font-14 font-weight-medium">Upload logo</p>
                                <p class="mb-0 font-12 color-gray">PNG, JPG op til 5MB</p>
                            </div>
                            <div class="bg-light border-radius-10px w-100 p-2 h-150px">
                                <div class="flex-row-between-center flex-nowrap cg-1 h-100">
                                    <img src="<?=__asset(DEFAULT_USER_PICTURE)?>" class="mxw-200px h-100" />
                                    <i class="mdi mdi-close-circle-outline color-danger-text font-20"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Hero Image Section -->
                <div  class="card border-radius-10px">
                    <div class="card-body">
                        <div class="flex-col-start " style="row-gap: 1rem;">
                            <h5 class="mb-0 font-18 font-weight-bold">Hero-billede</h5>
                            <div class="border-dashed border-color-card flex-col-start flex-align-center flex-justify-center border-dashed-gray border-radius-05rem p-5 cursor-pointer hover-bg-light transition-all" style="min-height: 180px;">
                                <i class="mdi mdi-upload font-48 color-gray mb-2"></i>
                                <p class="mb-0 font-14 font-weight-medium">Upload hero-billede</p>
                                <p class="mb-0 font-12 color-gray">PNG, JPG op til 10MB (1920×600px anbefalet)</p>
                            </div>
                            <div class="bg-light border-radius-10px w-100 flex-col-start flex-align-center flex-justify-center"
                                 style="aspect-ratio: 19/6">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content Section -->
                <div  class="card border-radius-10px">
                    <div class="card-body">
                        <div class="flex-col-start " style="row-gap: 1.5rem;">
                            <h5 class="mb-0 font-18 font-weight-bold">Indhold</h5>

                            <div class="flex-col-start" style="row-gap: .5rem;">
                                <label class="font-13 font-weight-bold mb-0">Overskrift</label>
                                <input type="text" class="form-field-v2" placeholder="Velkommen til København Centrum" value="Velkommen til København Centrum">
                            </div>

                            <div class="flex-col-start" style="row-gap: .5rem;">
                                <label class="font-13 font-weight-bold mb-0">Beskrivelse</label>
                                <textarea class="form-field-v2 mnh-100px" placeholder="Vi tilbyder fleksible betalingsløsninger via WeePay - betal med det samme eller fordel betalingen over tid.">Vi tilbyder fleksible betalingsløsninger via WeePay - betal med det samme eller fordel betalingen over tid.</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Custom Sections -->
                <div  class="card border-radius-10px">
                    <div class="card-body">
                        <div class="flex-col-start " style="row-gap: 1.5rem;">
                            <div class="flex-row-between flex-align-center">
                                <h5 class="mb-0 font-18 font-weight-bold">Tilpassede sektioner</h5>
                                <button class="btn-v2 mute-btn flex-row-center-center flex-nowrap" style="gap: .25rem;">
                                    <i class="mdi mdi-plus font-16"></i>
                                    <span>Tilføj sektion</span>
                                </button>
                            </div>

                            <!-- Example Section: Om os -->
                            <div class="flex-col-start card-border border-radius-10px p-3" style="row-gap: 1rem;">
                                <div class="flex-row-between flex-align-center">
                                    <p class="mb-0 font-14 font-weight-medium">Om os</p>
                                    <button class="btn-v2 mute-danger-outline-btn transition-all">
                                        <i class="mdi mdi-delete-outline"></i>
                                    </button>
                                </div>
                                <textarea class="form-field-v2 mnh-80px" placeholder="Skriv om din butik...">Vi er en førende skønhedssalon i København med over 10 års erfaring.</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Credit Widget Section -->
                <div  class="card border-radius-10px">
                    <div class="card-body">
                        <div class="flex-col-start " style="row-gap: 1rem;">
                            <h5 class="mb-0 font-18 font-weight-bold">Kredit widget</h5>
                            <div class="flex-row-between flex-align-start" style="column-gap: 1rem;">
                                <div class="flex-col-start flex-1" style="row-gap: .25rem;">
                                    <p class="mb-0 font-14 font-weight-medium">Inkluder "Tjek din kredit" widget</p>
                                    <p class="mb-0 font-12 color-gray">Lad kunder tjekke deres tilgængelige kredit direkte på din side</p>
                                </div>
                                <label class="form-switch">
                                    <input type="checkbox" checked>
                                    <i></i>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Right Panel: Live Preview -->
        <div class="col-12 col-lg-6">
            <div class="position-sticky" style="top: 20px;">
                <div  class="card border-radius-10px">
                    <div class="card-body">
                        <div class="flex-col-start " style="row-gap: 1rem;">
                            <h5 class="mb-0 font-18 font-weight-bold">Live forhåndsvisning</h5>

                            <!-- Preview Container -->
                            <div class="card-border border-radius-10px p-2 overflow-hidden " style="min-height: 600px;">
                                <!-- Hero Section Preview -->
                                <div class="bg-dark text-white p-5 flex-col-start flex-justify-end" style="min-height: 250px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                    <div class="bg-white-10 border-radius-05rem px-3 py-1 mb-3 d-inline-block" style="width: fit-content;">
                                        <p class="mb-0 font-12 text-white"><?=$location->name?></p>
                                    </div>
                                    <h2 class="mb-0 font-24 font-weight-bold text-white">Velkommen til København Centrum</h2>
                                </div>

                                <!-- Content Preview -->
                                <div class="p-4 bg-white">
                                    <p class="mb-0 font-14 color-dark">Vi tilbyder fleksible betalingsløsninger via WeePay - betal med det samme eller fordel betalingen over tid.</p>
                                </div>

                                <!-- Om os Section Preview -->
                                <div class="p-4 bg-white border-top">
                                    <h6 class="mb-2 font-16 font-weight-bold">Om os</h6>
                                    <p class="mb-0 font-14 color-gray">Vi er en førende skønhedssalon i København med over 10 års erfaring.</p>
                                </div>

                                <!-- Credit Widget Preview -->
                                <div class="p-4 bg-success-light border-top">
                                    <div class="flex-row-start-center" style="gap: .5rem;">
                                        <i class="mdi mdi-check-circle color-success"></i>
                                        <p class="mb-0 font-13 font-weight-medium color-success">Kredit widget inkluderet</p>
                                    </div>
                                </div>

                                <!-- Info Message -->
                                <div class="p-4 bg-info-light border-top">
                                    <div class="flex-row-start-center" style="gap: .75rem;">
                                        <i class="mdi mdi-information-outline color-info font-20"></i>
                                        <p class="mb-0 font-13 color-info">Dette er en forenklet forhåndsvisning. Klik "Forhåndsvis side" for at se den fulde side.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


</div>




