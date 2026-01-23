<?php
/**
 * @var object $args
 */

use classes\app\OrganisationPermissions;
use classes\enumerations\Links;
use classes\Methods;
use features\Settings;

$pageTitle = "Butikslokationer";

?>




<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "locations";
    var worldCountries = <?=json_encode(toArray($args->worldCountries))?>;
    var locations = <?=json_encode($args->locations->toArray())?>;

</script>


<div class="page-content home">

    <div class="flex-row-between flex-align-center flex-nowrap" id="nav" style="column-gap: .5rem;">
        <?=\features\DomMethods::locationSelect($args->locationOptions);?>
        <div class="flex-row-end">

        </div>
    </div>


    <div class="flex-row-between flex-align-center flex-wrap" style="column-gap: .75rem; row-gap: .5rem;">
        <div class="flex-col-start">
            <p class="mb-0 font-30 font-weight-bold">Butikker</p>
            <p class="mb-0 font-16 font-weight-medium color-gray">Administrer dine butikslokationer</p>
        </div>
        <div class="flex-row-end">
            <?php OrganisationPermissions::__oModifyProtectedContent('organisation', 'locations'); ?>
            <button class="btn-v2 action-btn flex-row-center flex-align-center flex-nowrap"
                onclick="LocationActions.addNewLocation()" style="gap: .5rem;">
                <i class="mdi mdi-plus"></i>
                <span>Tilføj ny butik</span>
            </button>
            <?php OrganisationPermissions::__oEndContentSilent(); ?>
        </div>
    </div>


    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-radius-10px">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .5rem;">
                        <i class="mdi mdi-store-outline font-22 color-blue"></i>
                        <p class="mb-0 font-22 font-weight-bold">Butiksoversigt</p>
                    </div>

                    <div class="mt-2">
                        <table class="table table-hover">
                            <thead class="color-gray">
                                <th>Butiksnavn</th>
                                <th>Addresse</th>
                                <th>Status</th>
                                <th >Handlinger</th>
                            </thead>
                            <tbody>
                            <?php foreach ($args->locations->list() as $location): ?>
                                <tr>
                                    <td>
                                        <a href="<?=__url(Links::$merchant->locations->setSingleLocation($location->slug))?>"
                                           class="color-blue hover-underline">
                                            <?=$location->name?>
                                        </a>
                                    </td>
                                    <td>
                                        <?=Methods::misc()::extractCompanyAddressString($location->address, true)?>
                                    </td>
                                    <td><?=ucfirst(strtolower($location->status))?></td>
                                    <td>
                                        <?php OrganisationPermissions::__oModifyProtectedContent('organisation', 'locations'); ?>
                                        <div class="flex-col-start " style="row-gap: 0;">
                                            <div class="nav-button font-14" data-location-id="<?=$location->uid?>" onclick="LocationActions.open(this)">
                                                <i class="mdi mdi-dots-horizontal font-14"></i>
                                            </div>
                                        </div>
                                        <?php OrganisationPermissions::__oEndContentSilent(); ?>
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




