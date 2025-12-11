<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;
use classes\Methods;
use features\Settings;


$pageTitle = "Organisation - Tilføj ny";
$countries = Methods::countries()->getByX(['enabled' => 1], ['uid', 'name', 'code']);

?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "organisation";
</script>


<div class="page-content home">


    <div class="flex-row-center organisation-container">
        <div class="card border-radius-10px w-100 organisation-join-card">
            <div class="card-body">
                <?php if(Methods::organisationMembers()->hasOrganisation()): ?>
                <a class="mb-2 cursor-pointer transition-color font-14 flex-row-start flex-align-center flex-nowrap color-gray hover-color-dark"
                     style="gap: .5rem;" href="<?=__url(Links::$merchant->organisation->home)?>">
                    <i class="mdi mdi-arrow-left"></i>
                    <span>Tilbage</span>
                </a>
                <?php endif; ?>
                <p class="font-25 font-weight-700 text-center">Organisation - <?=BRAND_NAME?> Forhandler</p>
                <p class="font-14 font-weight-medium text-gray text-center">Opret en ny- eller tilmeld dig en eksisterende- organisation for at komme i gang</p>

                <div class="mt-5" data-switchParent data-switch-id="organisation-join"
                     data-active-btn-class="color-dark bg-wrapper"
                     data-inactive-btn-class="color-cta-inactive bg-cta-container">

                    <div class="d-flex flex-align-center w-100 bg-cta-container border-radius-5px p-1 mb-4" >
                        <div class="switchViewBtn text-center color-dark bg-wrapper cursor-pointer font-14 p-2 w-50 noSelect"
                             data-toggle-switch-object="create" data-switch-id="organisation-join" style="border-radius: 5px 0 0 5px;">
                            Opret ny organisation
                        </div>
                        <div class="switchViewBtn text-center color-cta-inactive bg-cta-container p-2 w-50 cursor-pointer font-14 noSelect"
                             data-toggle-switch-object="join" data-switch-id="organisation-join" style="border-radius: 0 5px 5px 0;">
                            Tilmeld dig en eksisterende organisation
                        </div>
                    </div>


                    <div class="switchViewObject" data-switch-id="organisation-join" data-switch-object-name="create" data-is-shown="true">
                        <div class=" row align-items-stretch">
                            <div class="col-12 col-md-4 d-flex">
                                <div class="vision-card transition-all active">
                                    <div class="flex-col-start">
                                        <div class="mb-2">
                                            <i class="fa-solid fa-building color-blue font-30"></i>
                                        </div>
                                        <p class="mb-0 font-weight-bold font-16">Opret Organisation</p>
                                        <p class="mb-2 text-gray font-14">Opret en virksomhedsorganisation til at administrere lokationer og medarbejdere</p>
                                    </div>
                                    <p class="mb-0 mt-2 font-14 vision-button organisation-show-form">
                                        <span>Kom i gang</span>
                                        <i class="fa-solid fa-arrow-right-long"></i>
                                    </p>
                                </div>
                            </div>
                            <div class="col-12 col-md-4 d-flex">
                                <div class="vision-card transition-all">
                                    <div class="flex-col-start">
                                        <div class="mb-2">
                                            <i class="fa-solid fa-user-plus color-cta-inactive font-30"></i>
                                        </div>
                                        <p class="mb-0 font-weight-bold font-16">Inviter medarbejder</p>
                                        <p class="mb-2 text-gray font-14">Tilføj teammedlemmer og medarbejdere for at administrere flere eller enkelte lokationer</p>
                                    </div>
                                    <p class="mb-0 mt-2 font-14 vision-button">
                                        <span>Tilgængelig efter opsættelse</span>
                                        <i class="fa-solid fa-arrow-right-long"></i>
                                    </p>
                                </div>
                            </div>
                            <div class="col-12 col-md-4 d-flex">
                                <div class="vision-card transition-all">
                                    <div class="flex-col-start">
                                        <div class="mb-2">
                                            <i class="fa-solid fa-store color-cta-inactive font-30"></i>
                                        </div>
                                        <p class="mb-0 font-weight-bold font-16">Opret lokationer</p>
                                        <p class="mb-2 text-gray font-14">Opsæt lokationer for at kunne modtage <?=BRAND_NAME?> betalinger</p>
                                    </div>
                                    <p class="mb-0 mt-2 font-14 vision-button">
                                        <span>Tilgængelig efter opsættelse</span>
                                        <i class="fa-solid fa-arrow-right-long"></i>
                                    </p>
                                </div>
                            </div>

                            <div class="col-12 mt-3">
                                <button class="btn-v2 action-btn-lg organisation-show-form flex-row-center flex-align-center flex-nowrap"
                                    style="gap: .5rem;">
                                    <span>Opret organisation</span>

                                    <span class="ml-3 flex-align-center flex-row-start button-disabled-spinner">
                                        <span class="spinner-border color-white square-15" role="status" style="border-width: 2px;">
                                          <span class="sr-only">Loading...</span>
                                        </span>
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>




                    <div class="switchViewObject" data-switch-id="organisation-join" data-switch-object-name="join" data-is-shown="false" style="display: none;">
                        <div class=" row align-items-stretch">
                            <div class="col-12 d-flex">
                                <div class="vision-card transition-all w-100">
                                    <div class="flex-col-start">
                                        <p class="mb-0 font-weight-bold font-20">Tilmeld dig en eksisterende orgnisation</p>
                                        <p class="mb-2 text-gray font-14 font-weight-medium">
                                            Bed din organisations-administrator om at sende dig en invitation på <u><?=$_SESSION["email"]?></u>.
                                        </p>

                                        <div class="mt-3">
                                            <table class="table-v2 table-v2-hover">
                                                <thead>
                                                <tr>
                                                    <th>Organisation</th>
                                                    <th>Rolle</th>
                                                    <th>Dato</th>
                                                    <th class="text-right">Handling</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <?php foreach ($args->invitations->list() as $invitation): ?>
                                                <tr>
                                                    <td class="font-weight-bold"><?=$invitation->name?></td>
                                                    <td class=""><?=\classes\utility\Titles::cleanUcAll($invitation->role)?></td>
                                                    <td class=""><?=date('F d, Y', $invitation->timestamp)?></td>
                                                    <td class="text-right">
                                                        <div class="flex-row-end flex-align-center flex-nowrap" style="column-gap: .5rem">
                                                            <button class="btn-v2 danger-btn-outline flex-row-center flex-align-center flex-nowrap"
                                                                    onclick="invitationAction(this)" style="gap: .25rem;"
                                                                    data-organisation-id="<?=$invitation->organisation->uid?>" data-invitation-action="decline">
                                                                <i class="mdi mdi-close mr-2"></i>
                                                                <span>Afvis</span>

                                                                <span class="ml-3 flex-align-center flex-row-start button-disabled-spinner">
                                                                    <span class="spinner-border color-white square-15" role="status" style="border-width: 2px;">
                                                                      <span class="sr-only">Loading...</span>
                                                                    </span>
                                                                </span>
                                                            </button>
                                                            <button class="btn-v2 action-btn flex-row-center flex-align-center flex-nowrap"
                                                                    onclick="invitationAction(this)" style="gap: .25rem;"
                                                                    data-organisation-id="<?=$invitation->organisation->uid?>"  data-invitation-action="accept">
                                                                <i class="mdi mdi-check mr-2"></i>
                                                                <span>Accepter</span>

                                                                <span class="ml-3 flex-align-center flex-row-start button-disabled-spinner">
                                                                    <span class="spinner-border color-white square-15" role="status" style="border-width: 2px;">
                                                                      <span class="sr-only">Loading...</span>
                                                                    </span>
                                                                </span>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                                </tbody>
                                            </table>
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




    <div class="flex-col-start w-100 organisation-container"  style="display: none">
        <div class="flex-row-center">
            <div class="card border-radius-10px organisation-create-card">
                <div class="card-body">

                    <div class="flex-col-start w-100">
                        <div class="mb-2 organisation-hide-form cursor-pointer transition-color font-14 flex-row-start flex-align-center flex-nowrap color-gray hover-color-dark"
                             style="gap: .5rem;">
                            <i class="mdi mdi-arrow-left"></i>
                            <span>Tilbage</span>
                        </div>
                        <div class="flex-row-start flex-align-center flex-nowrap mb-4" style="column-gap: 1rem">
                            <i class="fa-regular fa-building color-blue font-40"></i>
                            <div class="flex-col-start">
                                <p class="font-25 font-weight-bold mb-0">Opret Organisation</p>
                                <p class="font-13 color-gray mb-0" style="margin-top: -.25rem">Opsæt din forretningsorganisation hos <?=BRAND_NAME?></p>
                            </div>
                        </div>

                        <form method="post" action="<?=Links::$api->forms->createOrganisation?>" class="flex-col-start" style="row-gap: 1.5rem;">
                            <div class="row" style="row-gap: .5rem;">
                                <div class="col-12">
                                    <div class="flex-col-start " style="row-gap: .5rem">
                                        <p class="font-13 font-weight-bold mb-0">Organisationsnavn</p>
                                        <input type="text" name="name" placeholder="Roses Frisør" class="form-field-v2" maxlength="30">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="flex-col-start " style="row-gap: .5rem">
                                        <div class="flex-col-start " style="row-gap: 0">
                                            <p class="font-13 font-weight-bold mb-0">Primær email</p>
                                            <p class="font-12 color-gray mb-0">
                                                Bruges til at associere organisationens indgående betalinger. Er ikke synlig for kunder.
                                            </p>
                                        </div>
                                        <input type="email" name="email" placeholder="admin@mitfirma.dk" class="form-field-v2" maxlength="30">
                                    </div>
                                </div>
                            </div>
                            <div class="flex-col-start " style="row-gap: .5rem">
                                <p class="font-13 font-weight-bold mb-0">Beskrivelse (Valgfri)</p>
                                <textarea name="description" placeholder="Tell us about your organisation" class="mnh-100px form-field-v2" maxlength="1000"></textarea>
                            </div>
                            <div class="row" style="row-gap: .5rem;">
                                <div class="col-12 col-md-6">
                                    <div class="flex-col-start" style="row-gap: .5rem">
                                        <p class="font-13 font-weight-bold mb-0">Hjemmeside (Valgfri)</p>
                                        <input type="text" name="website" placeholder="https://acme.com" class="form-field-v2" maxlength="50">
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="flex-col-start " style="row-gap: .5rem">
                                        <p class="font-13 font-weight-bold mb-0">Industri (Valgfri)</p>
                                        <input type="text" name="industry" placeholder="Teknologi, Fashion, mv." class="form-field-v2" maxlength="30">
                                    </div>
                                </div>
                            </div>
                            <div class="row" style="row-gap: .5rem;">
                                <div class="col-12 col-md-6">
                                    <div class="flex-col-start " style="row-gap: .5rem">
                                        <p class="font-13 font-weight-bold mb-0">Kontakt Email (Valgfri)</p>
                                        <input type="email" name="contact_email" placeholder="contact@acme.com" class="form-field-v2" maxlength="50">
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 ">
                                    <div class="flex-col-start " style="row-gap: .5rem">
                                        <p class="font-13 font-weight-bold mb-0">Kontakt telefonnummer (Valgfri)</p>
                                        <div class="flex-row-start flex-align-center flex-nowrap" style="gap: 2px;">
                                            <select class="form-select-v2 h-45px w-70px dropdown-no-arrow border-radius-tr-br-0-5rem"
                                                    data-search="true" name="contact_phone_country">
                                                <?php foreach ($args->worldCountries as $country): ?>
                                                    <option data-sort="<?=$country->countryNameEn?>_<?=$country->countryCode?>_<?=$country->countryNameLocal?>_<?=$country->countryCallingCode?>"
                                                            value="<?=$country->countryCode?>" <?=$country->countryCode === Settings::$app->default_country ? 'selected' : ''?>>
                                                        <div class="flex-row-center flex-align-center flex-nowrap" style="gap: .25rem;">
                                                            <span class=""><?=$country->flag?></span>
                                                            <span class="">+<?=$country->countryCallingCode?></span>
                                                        </div>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="text" name="contact_phone" placeholder="12 34 56 78" class="form-field-v2"
                                                style="border-radius: 0 .5rem .5rem 0;" maxlength="15">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row" style="row-gap: .5rem;">
                                <div class="col-12 col-md-6">
                                    <div class="flex-col-start " style="row-gap: .5rem">
                                        <p class="font-13 font-weight-bold mb-0">Virksomhedsnavn</p>
                                        <input type="text" name="company_name" placeholder="Din virksomheds navn" class="form-field-v2" maxlength="50">
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="flex-col-start " style="row-gap: .5rem">
                                        <p class="font-13 font-weight-bold mb-0">Virksomhed CVR</p>
                                        <input type="text" name="company_cvr" placeholder="Dit CVR nummer" class="form-field-v2" maxlength="20">
                                    </div>
                                </div>
                            </div>
                            <div class="row" style="row-gap: .5rem;">
                                <div class="col-12 col-md-6">
                                    <div class="flex-col-start " style="row-gap: .5rem">
                                        <p class="font-13 font-weight-bold mb-0">Vejnavn</p>
                                        <input type="text" name="company_line_1" placeholder="Din virksomheds vejnavn + nr." class="form-field-v2" maxlength="100">
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="flex-col-start " style="row-gap: .5rem">
                                        <p class="font-13 font-weight-bold mb-0">By</p>
                                        <input type="text" name="company_city" placeholder="Din virksomheds Bynavn" class="form-field-v2" maxlength="50">
                                    </div>
                                </div>
                            </div>
                            <div class="row" style="row-gap: .5rem;">
                                <div class="col-12 col-md-6">
                                    <div class="flex-col-start " style="row-gap: .5rem">
                                        <p class="font-13 font-weight-bold mb-0">Postnummer</p>
                                        <input type="text" name="company_postal_code" placeholder="Din virksomheds postnummer" class="form-field-v2" maxlength="30">
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="flex-col-start " style="row-gap: .5rem">
                                        <p class="font-13 font-weight-bold mb-0">Land</p>
                                        <select class="form-select-v2 h-45px w-100 " name="company_country">
                                            <?php foreach ($countries as $country): ?>
                                            <option value="<?=$country->code?>" <?=$country->code === Settings::$app->default_country ? 'selected' : ''?>>
                                                <?=$country->name?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-col-start " style="row-gap: .5rem">
                                <button class="btn-v2 action-btn-lg flex-row-center flex-align-center flex-nowrap"
                                        name="create_new_organisation" style="gap: .5rem;" onclick="createOrganisation(this)">
                                    <span>Opret organisation</span>

                                    <span class="ml-3 flex-align-center flex-row-start button-disabled-spinner">
                                        <span class="spinner-border color-white square-15" role="status" style="border-width: 2px;">
                                          <span class="sr-only">Loading...</span>
                                        </span>
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

</div>



