<?php use classes\enumerations\Links; ?>
<div class="footer">
    <div class="flex-row-between flex-wrap h-100" style="column-gap: 1rem; row-gap: 1.5rem;">
        <div class="flex-col-start" >
            <div class="flex-col-start pb-2 border-bottom-card" style="row-gap: .25rem;">
                <div class="flex-row-start flex-align-center flex-nowrap" style="gap: 0">
                    <img class="w-100px" src="<?=__asset(LOGO_WIDE_HEADER)?>" />
                    <sup>
                        <p class="mb-0 font-20 font-weight-800 color-blue" style="margin-top: -4px;">&copy;</p>
                    </sup>
                </div>
                <p class="mb-0 font-14 color-gray">Danmarks bedste køb nu, betal senere løsning</p>
            </div>

            <div class="flex-col-start mt-1" style="row-gap: .25rem;">
                <div class="flex-row-start flex-align-center " style="gap: .5rem;">
                    <img class="w-100px"  src="<?=__asset(PARTNER_BANK_LOGO)?>" />
                </div>
            </div>
        </div>

        <div class="flex-col-start " style="row-gap: .75rem;">
            <p class="mb-0 font-14 font-weight-bold">Hjælp</p>
            <div class="flex-col-start " style="row-gap: .25rem;">
                <a href="<?=__url(Links::$faq->consumer)?>" class="font-14 color-gray text-decoration-none hover-color-blue">FAQ for forbrugere</a>
                <a href="<?=__url(Links::$faq->merchant)?>" class="font-14 color-gray text-decoration-none hover-color-blue">FAQ for forhandlere</a>
                <a href="<?=__url(Links::$policies->consumer->privacy)?>" class="font-14 color-gray text-decoration-none hover-color-blue">Privatlivspolitik</a>
                <a href="<?=__url(Links::$policies->consumer->termsOfUse)?>" class="font-14 color-gray text-decoration-none hover-color-blue">Vilkår og betingelser</a>
            </div>
        </div>

        <div class="flex-col-start " style="row-gap: .75rem;">
            <p class="mb-0 font-14 font-weight-bold">Kontakt</p>
            <div class="flex-col-start " style="row-gap: .25rem;">
                <div class="flex-row-start flex-align-center flex-nowrap" style="gap: .5rem;">
                    <i class="mdi mdi-phone color-gray font-13"></i>
                    <p class="mb-0 font-14 color-gray"><?=CONTACT_PHONE?></p>
                </div>
                <div class="flex-row-start flex-align-center flex-nowrap" style="gap: .5rem;">
                    <i class="mdi mdi-email color-gray font-13"></i>
                    <p class="mb-0 font-14 color-gray"><?=CONTACT_EMAIL?></p>
                </div>
            </div>
        </div>
    </div>
</div>