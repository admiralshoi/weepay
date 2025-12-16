<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "Kasseapparater";




?>




<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "terminals";

    var locations = <?=json_encode(toArray($args->locations))?>;
    var terminals = <?=json_encode(toArray($args->terminals->list()))?>;
    var selectedLocation = null;
</script>


<div class="page-content home">

    <div class="flex-row-between flex-align-center flex-nowrap" id="nav" style="column-gap: .5rem;">
        <?=\features\DomMethods::locationSelect($args->locationOptions);?>
        <div class="flex-row-end">

        </div>
    </div>



    <div class="flex-row-between flex-align-center flex-wrap" style="column-gap: .75rem; row-gap: .5rem;">
        <div class="flex-col-start">
            <p class="mb-0 font-30 font-weight-bold">Kasseapparater</p>
            <p class="mb-0 font-16 font-weight-medium color-gray">Administrer dine terminaler og QR-koder</p>
        </div>
        <div class="flex-row-end">
            <button class="btn-v2 action-btn flex-row-center flex-align-center flex-nowrap"
                    onclick="LocationActions.addNewTerminal()" style="gap: .5rem;">
                <i class="mdi mdi-plus"></i>
                <span>Tilføj ny terminal</span>
            </button>
        </div>
    </div>


    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-radius-10px">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .5rem;">
                        <i class="mdi mdi-monitor font-16 color-blue"></i>
                        <p class="mb-0 font-22 font-weight-bold">Alle kasseapparater</p>
                    </div>

                    <div class="mt-2">
                        <table class="table table-hover">
                            <thead class="color-gray">
                            <th class="desktopOnlyTableCell">ID</th>
                            <th class="hideOnDesktopTableCell">Navn</th>
                            <th class="desktopOnlyTableCell">Navn</th>
                            <th class="desktopOnlyTableCell">Butik</th>
                            <th class="hideOnMobileTableCell">QR-kode status</th>
                            <th class="hideOnDesktopTableCell">Handlinger</th>
                            <th class="desktopOnlyTableCell">Start</th>
                            <th class="desktopOnlyTableCell">Rediger</th>
                            </thead>
                            <tbody>
                            <?php foreach ($args->terminals->list() as $terminal): ?>
                                <tr>
                                    <td class="desktopOnlyTableCell"><?=$terminal->uid?></td>
                                    <td class="hideOnDesktopTableCell">
                                        <div class="flex-col-start">
                                            <?=$terminal->name?>
                                            <?=$terminal->location->name?>
                                        </div>
                                    </td>
                                    <td class="desktopOnlyTableCell"><?=$terminal->name?></td>
                                    <td class="desktopOnlyTableCell"><?=$terminal->location->name?></td>
                                    <td class="hideOnMobileTableCell">
                                        <?php if($terminal->status === 'ACTIVE'): ?>
                                            <button class="btn-v2 green-btn flex-row-center-center flex-nowrap g-05"
                                                onclick="LocationActions.qrAction('<?=$terminal->uid?>');">
                                                <i class="mdi mdi-qrcode"></i>
                                                <span class="">Vis QR</span>
                                            </button>
                                        <?php elseif($terminal->status === 'DRAFT'): ?>
                                            <div class="flex-row-start flex-align-center flex-nowrap cursor-pointer"
                                                 onclick="LocationActions.qrAction('<?=$terminal->uid?>');" style="gap: .25rem;">
                                                <i class="mdi mdi-qrcode color-gray"></i>
                                                <span class="color-gray">Udkast</span>
                                            </div>
                                        <?php elseif($terminal->status === 'INACTIVE'): ?>
                                            <div class="flex-row-start flex-align-center flex-nowrap cursor-pointer"
                                                 onclick="LocationActions.qrAction('<?=$terminal->uid?>');" style="gap: .25rem;">
                                                <i class="mdi mdi-qrcode color-dark"></i>
                                                <span class="color-dark">Inaktiv</span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="hideOnDesktopTableCell">
                                        <div class="flex-row-start flex-align-center flex-wrap w-100 mxw-200px" style="gap: .25rem;">
                                            <a href="<?=__url(Links::$merchant->terminals->posStart($terminal->location->slug,$terminal->uid))?>"
                                                target="_blank" class="btn-v2 action-btn flex-row-center flex-align-center flex-nowrap" style="gap: .5rem;">
                                                <i class="mdi mdi-play-outline font-18"></i>
                                                <span class="font-14">Start POS</span>
                                            </a>

                                            <?php \classes\app\LocationPermissions::__oModifyProtectedContent($terminal->location, 'terminals'); ?>
                                            <button class="btn-v2 mute-btn flex-row-center flex-align-center flex-nowrap" style="gap: .5rem;"
                                                    onclick="LocationActions.editTerminal('<?=$terminal->uid?>')">
                                                <i class="mdi mdi-pencil-outline font-18"></i>
                                                <span class="font-14">Rediger</span>
                                            </button>
                                            <?php \classes\app\LocationPermissions::__oEndContent(); ?>
                                        </div>
                                    </td>
                                    <td class="desktopOnlyTableCell">
                                        <a href="<?=__url(Links::$merchant->terminals->posStart($terminal->location->slug,$terminal->uid))?>"
                                            target="_blank" class="btn-v2 action-btn flex-row-center flex-align-center flex-nowrap" style="gap: .5rem;">
                                            <i class="mdi mdi-play-outline font-18"></i>
                                            <span class="font-14">Start POS</span>
                                        </a>
                                    </td>
                                    <td class="desktopOnlyTableCell">
                                        <?php \classes\app\LocationPermissions::__oModifyProtectedContent($terminal->location, 'terminals'); ?>
                                        <button class="btn-v2 mute-btn flex-row-center flex-align-center flex-nowrap" style="gap: .5rem;"
                                                onclick="LocationActions.editTerminal('<?=$terminal->uid?>')">
                                            <i class="mdi mdi-pencil-outline font-18"></i>
                                            <span class="font-14">Rediger</span>
                                        </button>
                                        <?php \classes\app\LocationPermissions::__oEndContent(); ?>
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




