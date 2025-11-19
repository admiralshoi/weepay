<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;


?>






<div class="page-content">


    <section class="w-100 py-6 px-3">
        <div class="row align-items-stretch" style="row-gap: 1rem;">
            <div class="col-12 col-md-6">
                <div class="flex-col-start parent-lineHeight-1 parent-letter-space-1" >
                    <p class="mb-0  font-weight-700 color-dark text-nowrap hero-font">Danmarks bedste</p>
                    <p class="mb-0 font-weight-700  color-blue text-nowrap text-uppercase hero-font">Køb nu</p>
                    <p class="mb-0 font-weight-700  color-blue text-nowrap text-uppercase hero-font">Betal senere</p>
                </div>
                <p class="mb-0 font-weight-700 text-nowrap color-gray hero-sub-font">- løsning til fysiske butikker</p>

                <div class="flex-row-start flex-align-center mt-3 mt-md-4" style="gap: 1rem;">

                    <a href="<?=__url(Links::$app->auth->merchantLogin)?>" style="gap: .5rem; padding-bottom: .75rem; padding-top: .75rem;"
                       class="btn-v2 action-btn flex-row-start flex-align-center flex-nowrap font-14 border-radius-10px  px-4 font-weight-bold" >
                        <span>Bliv forhandler</span>
                        <i class="mdi mdi-arrow-right"></i>
                    </a>

                    <a href="<?=__url(Links::$app->auth->consumerLogin)?>" style="gap: .5rem; padding-bottom: .75rem; padding-top: .75rem;"
                       class="btn-v2 trans-hover-design-action-btn card-border flex-row-start flex-align-center flex-nowrap font-14 border-radius-10px px-4 font-weight-medium" >
                        <span>Kunde login</span>
                    </a>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="w-100 overflow-hidden position-relative">
                    <div
                            class="w-100 overflow-hidden bg-cover"
                            style="
                                    border-radius: 10px;
                                    aspect-ratio: 1.8;
                                    background-image: url('<?=__image("hero-payment.jpg")?>');
                                    "
                    ></div>

                    <div class="p-4 bg-blue color-white mnw-50px mnh-50px border-radius-10px" style="position: absolute; bottom:25px; right: 25px;">
                        <p class="mb-0 font-18 font-weight-bold color-white text-uppercase">Flere køb og</p>
                        <p class="mb-0 font-18 font-weight-bold color-white text-uppercase">nye kunder</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="w-100 py-6 px-3 bg-wrapper-hover">
        <div class="flex-row-center w-100">
            <div class="w-100 mxw-1000px mx-auto">
                <div class="row align-items-stretch" style="row-gap: 3rem;">
                    <div class="col-12 col-md-4">
                        <div class="flex-col-start flex-align-center" style="row-gap: .25rem;">
                            <div  class="flex-col-start flex-align-center position-relative w-100" style="row-gap: .25rem;">
                                <div class="stepper-circle square-60 font-25 bg-blue color-white">1</div>
                                <div class="d-none d-md-block bg-gray w-80" style="position: absolute; height: .125rem; top:  1.5rem; left: 60%;"></div>
                            </div>
                            <div class="font-weight-bold font-16 mb-0 text-center">Kunden scanner QR koden</div>
                            <div class="font-14 color-gray mb-0 text-center">Ingen app nødvendig - bare scan og betal</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="flex-col-start flex-align-center" style="row-gap: .25rem;">
                            <div  class="flex-col-start flex-align-center position-relative w-100" style="row-gap: .25rem;">
                                <div class="stepper-circle square-60 font-25 bg-blue color-white">2</div>
                                <div class="d-none d-md-block bg-gray w-80" style="position: absolute; height: .125rem; top:  1.5rem; left: 60%;"></div>
                            </div>
                            <div class="font-weight-bold font-16 mb-0 text-center">Forhandler indtaster beløbet i Weepay-terminalen</div>
                            <div class="font-14 color-gray mb-0 text-center">Simpel og hurtig proces</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="flex-col-start flex-align-center" style="row-gap: .25rem;">
                            <div  class="flex-col-start flex-align-center position-relative w-100" style="row-gap: .25rem;">
                                <div class="stepper-circle square-60 font-25 bg-blue color-white">3</div>
                            </div>
                            <div class="font-weight-bold font-16 mb-0 text-center">Kunden bekræfter og godkender købet</div>
                            <div class="font-14 color-gray mb-0 text-center">Øjeblikkelig godkendelse</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="w-100 py-6 px-3">
        <div class="flex-col-start flex-align-center w-100">
            <p class="mb-0 font-35 font-weight-bold">Tre fleksible betalingsmuligheder</p>
            <p class="mb-0 font-16 font-weight-medium color-gray">Giv dine kunder frihed til at vælge den betalingsform, der passer dem bedst</p>
        </div>
        <div class="flex-row-center w-100 mt-5">
            <div class="w-100 mxw-1000px mx-auto">
                <div class="row align-items-stretch" style="row-gap: 1rem;">
                    <div class="col-12 col-md-4 d-flex">
                        <div class="card border-radius-10px w-100 card-effect">
                            <div class="card-body">
                                <div class="flex-col-start flex-align-center" style="row-gap: .5rem;">
                                    <div class="square-60 bg-lightest-green border-radius-50 flex-row-center flex-align-center">
                                        <i class="font-25 color-blue mdi mdi-wallet"></i>
                                    </div>
                                    <p class="mb-0 font-18 font-weight-bold text-center">Betal med det samme</p>
                                    <p class="mb-0 font-16 font-weight-medium color-gray text-center">Fuld betaling direkte ved køb</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 d-flex">
                        <div class="card border-radius-10px w-100 card-effect">
                            <div class="card-body">
                                <div class="flex-col-start flex-align-center" style="row-gap: .5rem;">
                                    <div class="square-60 bg-lightest-green border-radius-50 flex-row-center flex-align-center">
                                        <i class="font-25 color-blue mdi mdi-calendar-clock"></i>
                                    </div>
                                    <p class="mb-0 font-18 font-weight-bold text-center">Del betalingen i 4 rater</p>
                                    <p class="mb-0 font-16 font-weight-medium color-gray text-center">Spred betalingen over 4 lige store dele</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 d-flex">
                        <div class="card border-radius-10px w-100 card-effect">
                            <div class="card-body">
                                <div class="flex-col-start flex-align-center" style="row-gap: .5rem;">
                                    <div class="square-60 bg-lightest-green border-radius-50 flex-row-center flex-align-center">
                                        <i class="font-25 color-blue mdi mdi-clock-outline"></i>
                                    </div>
                                    <p class="mb-0 font-18 font-weight-bold text-center">Udskyd til næste lønningsdag</p>
                                    <p class="mb-0 font-16 font-weight-medium color-gray text-center">Betal når det passer dig bedst</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="w-100 py-6 px-3 bg-wrapper-hover">
        <div class="flex-col-start flex-align-center w-100">
            <p class="mb-0 font-35 font-weight-bold">Vi er din nære samarbejdspartner</p>
        </div>
        <div class="flex-row-center w-100 mt-5">
            <div class="w-100 mxw-1200px mx-auto">
                <div class="row align-items-stretch" style="row-gap: 1rem;">
                    <div class="col-12 col-md-6">
                        <div class="w-100 overflow-hidden position-relative">
                            <div
                                    class="w-100 overflow-hidden bg-cover"
                                    style="
                                            border-radius: 10px;
                                            aspect-ratio: 1.8;
                                            background-image: url('<?=__image("banner-payment-square.jpeg")?>');
                                            "
                            ></div>

                            <div class="p-4 bg-blue color-white mnw-50px mnh-50px border-radius-10px" style="position: absolute; bottom:25px; left: 25px;">
                                <p class="mb-0 font-18 font-weight-bold color-white text-uppercase">Vi hjælper dig</p>
                                <p class="mb-0 font-18 font-weight-bold color-white text-uppercase">godt i gang</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="flex-col-start parent-lineHeight-1-5 pl-3 " style="row-gap: 1.25rem;">
                            <p class="mb-0 font-weight-medium color-gray font-18">
                                Med WeePay™ får du ikke kun en fleksibel betalingsløsning – du får en partner, der aktivt hjælper
                                dig med at skabe vækst i din forretning.
                            </p>
                            <p class="mb-0 font-weight-medium color-gray font-18">
                                Det gør vi blandt andet ved at hjælpe dig med det marketingmateriale, du har brug for - lige fra A-skilte
                                til So-Me budskaber med korte og stærke budskaber.
                            </p>
                            <p class="mb-0 font-weight-medium color-gray font-18">
                               Det betyder, at du får værdi fra dag ét – dine kunder opdager straks, at du tilbyder "køb nu, betal senere",
                                og det giver dig både mersalg og nye kunder.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="w-100 py-6 px-3 bg-blue color-white">
        <div class="flex-row-center w-100">
            <div class="w-100 mxw-1200px mx-auto">
                <div class="flex-col-start flex-align-center w-100" style="row-gap: 1rem;">
                    <div class="flex-row-center flex-align-center flex-nowrap border-radius-10px py-2 px-4" style="gap: .5rem; background: rgba(255,255,255,.1);">
                        <i class="mdi mdi-trending-up font-14"></i>
                        <span class="font-14">Vækst for din forretning</span>
                    </div>
                    <p class="mb-0 font-35 font-weight-800 text-center">Klar til at øge dit salg med WeePay?</p>


                    <div class="w-100 mxw-675px mx-auto">
                        <div class="flex-col-start flex-align-center w-100 pb-5 border-bottom-card" style="row-gap: 2rem;">
                            <div class="flex-col-start flex-align-center w-100">
                                <p class="mb-0 font-16 font-weight-medium">Tilbyd dine kunder fleksible betalingsmuligheder og se dit salg stige.</p>
                                <p class="mb-0 font-16 font-weight-medium">Kom i gang på få minutter – ingen skjulte gebyrer.</p>
                            </div>

                            <div class="flex-row-center flex-align-center" style="gap: 1rem;">

                                <a href="<?=__url(Links::$app->auth->merchantLogin)?>" style="gap: .5rem; padding-bottom: .75rem; padding-top: .75rem;"
                                   class="btn-v2 white-btn flex-row-start flex-align-center flex-nowrap font-14 border-radius-10px px-4 font-weight-bold" >
                                    <span>Bliv forhandler nu</span>
                                    <i class="mdi mdi-arrow-right"></i>
                                </a>

                                <a href="#contact-form" style="gap: .5rem; padding-bottom: .75rem; padding-top: .75rem;"
                                   class="btn-v2 white-outline-btn flex-row-start flex-align-center flex-nowrap font-14 border-radius-10px px-4 font-weight-medium" >
                                    <span>Kontakt os</span>
                                </a>
                            </div>
                        </div>

                        <div class="flex-row-between flex-align-center px-3 mt-5" style="row-gap: 1rem; column-gap: .5rem;">
                            <div class="flex-col-start flex-align-center" style="row-gap: 1rem;">
                                <p class="mb-0 font-25 font-weight-bold text-center text-nowrap">0%</p>
                                <p class="mb-0 font-16 font-weight-medium text-center text-nowrap">Skjulte gebyrer</p>
                            </div>
                            <div class="flex-col-start flex-align-center" style="row-gap: 1rem;">
                                <p class="mb-0 font-25 font-weight-bold text-center text-nowrap">24/7</p>
                                <p class="mb-0 font-16 font-weight-medium text-center text-nowrap">Support</p>
                            </div>
                            <div class="flex-col-start flex-align-center" style="row-gap: 1rem;">
                                <p class="mb-0 font-25 font-weight-bold text-center text-nowrap">5 min</p>
                                <p class="mb-0 font-16 font-weight-medium text-center text-nowrap">Setup tid</p>
                            </div>
                        </div>
                    </div>
                </div>


            </div>
        </div>
    </section>
    <section class="w-100 py-6 px-3">
        <div class="flex-col-start flex-align-center w-100">
            <p class="mb-0 font-35 font-weight-bold">Marketingmaterialer inkluderet</p>
        </div>
        <div class="flex-row-center w-100 mt-5">
            <div class="w-100 mxw-1000px mx-auto">
                <div class="row align-items-stretch" style="row-gap: 1rem;">
                    <div class="col-6 col-lg-3">
                        <div class="flex-col-start flex-align-center w-100" style="row-gap: 1rem;">
                            <div class="w-100 overflow-hidden position-relative">
                                <div
                                        class="w-100 overflow-hidden bg-cover"
                                        style="
                                                border-radius: 10px;
                                                aspect-ratio: 1;
                                                background-image: url('<?=__image("marketing/banner-poster-mat.jpg")?>');
                                                "
                                ></div>
                            </div>
                            <p class="mb-0 font-18 font-weight-bold">Måtter</p>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="flex-col-start flex-align-center w-100" style="row-gap: 1rem;">
                            <div class="w-100 overflow-hidden position-relative">
                                <div
                                        class="w-100 overflow-hidden bg-cover"
                                        style="
                                                border-radius: 10px;
                                                aspect-ratio: 1;
                                                background-image: url('<?=__image("marketing/banner-poster.jpg")?>');
                                                "
                                ></div>
                            </div>
                            <p class="mb-0 font-18 font-weight-bold">Sociale medier</p>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="flex-col-start flex-align-center w-100" style="row-gap: 1rem;">
                            <div class="w-100 overflow-hidden position-relative">
                                <div
                                        class="w-100 overflow-hidden bg-cover"
                                        style="
                                                border-radius: 10px;
                                                aspect-ratio: 1;
                                                background-image: url('<?=__image("marketing/banner-poster-sign.jpg")?>');
                                                "
                                ></div>
                            </div>
                            <p class="mb-0 font-18 font-weight-bold">Skilte</p>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="flex-col-start flex-align-center w-100" style="row-gap: 1rem;">
                            <div class="w-100 overflow-hidden position-relative">
                                <div
                                        class="w-100 overflow-hidden bg-cover"
                                        style="
                                                border-radius: 10px;
                                                aspect-ratio: 1;
                                                background-image: url('<?=__image("marketing/banner-poster-sms.jpg")?>');
                                                "
                                ></div>
                            </div>
                            <p class="mb-0 font-18 font-weight-bold">Sms markedsføring</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="w-100 py-6 px-3 bg-wrapper-hover">
        <div class="flex-col-start flex-align-center w-100">
            <p class="mb-0 font-35 font-weight-bold">Kontakt os for professionel rådgivning</p>
        </div>
        <div class="flex-row-center w-100 mt-5">
            <div class="w-100 mxw-1200px mx-auto">
                <div class="row align-items-stretch" style="row-gap: 1rem;">
                    <div class="d-none d-md-block col-md-6">

                        <div class="flex-col-start" style="row-gap: 1.25rem;">
                            <div class="flex-row-start flex-align-center flex-nowrap" style="gap: .75rem;">
                                <div class="square-40 bg-lighter-blue border-radius-50 flex-row-center flex-align-center">
                                    <i class="font-20 color-blue mdi mdi-phone"></i>
                                </div>
                                <p class="font-20 mb-0 font-weight-bold"><?=CONTACT_PHONE?></p>
                            </div>
                            <div class="flex-row-start flex-align-center flex-nowrap" style="gap: .75rem;">
                                <div class="square-40 bg-lighter-blue border-radius-50 flex-row-center flex-align-center">
                                    <i class="font-20 color-blue mdi mdi-email"></i>
                                </div>
                                <p class="font-20 mb-0 font-weight-bold">weepay@outlook.dk</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <form action="<?=Links::$api->forms->contactForm?>" id="contact-form" style="row-gap: .75rem;"
                              class="bg-blue p-4 border-radius-10px flex-col-start color-white recaptcha">
                            <div class="flex-col-start w-100" style="row-gap: .25rem;">
                                <p class="mb-0 font-14 font-weight-medium">Navn *</p>
                                <input type="text" class="form-field-v2 bg-white" name="contact_name" placeholder="Dit navn">
                            </div>
                            <div class="flex-col-start w-100" style="row-gap: .25rem;">
                                <p class="mb-0 font-14 font-weight-medium">E-mail *</p>
                                <input type="email" class="form-field-v2 bg-white" name="contact_email" placeholder="din@email.dk">
                            </div>
                            <div class="flex-col-start w-100" style="row-gap: .25rem;">
                                <p class="mb-0 font-14 font-weight-medium">Emne *</p>
                                <input type="text" class="form-field-v2 bg-white" name="msg_subject" placeholder="Hvad drejer det sig om?">
                            </div>
                            <div class="flex-col-start w-100" style="row-gap: .25rem;">
                                <p class="mb-0 font-14 font-weight-medium">Besked *</p>
                                <textarea type="text" class="form-field-v2 bg-white h-100px" name="msg_content" placeholder="Din besked..."></textarea>
                            </div>

                            <div class="flex-row-start flex-align-start flex-nowrap mt-2" style="gap: .75rem;">
                                <input type="checkbox" class="square-20" name="consent_newsletter" />
                                <p class="mb-0 font-14">Ja, jeg vil gerne modtage nyheder om kampagner.</p>
                            </div>

                            <button class="btn-v2 white-btn-lg px-4 py-2 font-16 font-weight-bold mt-2 flex-row-center flex-align-center flex-nowrap"
                                    onclick="contactForm(this)" name="send_contact_form" style="gap: .5rem;" type="button">
                                <span>Send</span>

                                <span class="ml-3 flex-align-center flex-row-start button-disabled-spinner">
                                    <span class="spinner-border color-blue square-15" role="status" style="border-width: 2px;">
                                      <span class="sr-only">Loading...</span>
                                    </span>
                                </span>

                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>


</div>