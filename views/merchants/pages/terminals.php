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
            <button class="btn-v2 action-btn flex-row-center flex-align-center flex-nowrap" style="gap: .5rem;">
                <i class="mdi mdi-plus"></i>
                <span>Tilføj nyt kasseapparat</span>
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
                            <th>Terinal ID</th>
                            <th>Navn</th>
                            <th>Butik</th>
                            <th >QR-kode status</th>
                            <th >Handlinger</th>
                            </thead>
                            <tbody>
                            <?php foreach ($args->terminals->list() as $terminal): ?>
                                <tr>
                                    <td><?=$terminal->uid?></td>
                                    <td><?=$terminal->name?></td>
                                    <td><?=$terminal->location->name?></td>
                                    <td>

                                        <div class="flex-row-start flex-align-center flex-nowrap" style="gap: .25rem;">
                                            <i class="mdi mdi-qrcode color-green"></i>
                                            <span class="color-green">Aktiv</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex-row-start flex-align-center flex-nowrap" style="gap: .25rem;">
                                            <a href="<?=__url("merchant/{$terminal->location->slug}/checkout?tid=$terminal->uid")?>"
                                               target="_blank" class="btn-v2 trans-btn flex-row-center flex-align-center flex-nowrap" style="gap: .5rem;">
                                                <i class="mdi mdi-play-outline font-18"></i>
                                                <span class="font-14">Start POS</span>
                                            </a>
                                            <button class="btn-v2 danger-btn flex-row-center flex-align-center flex-nowrap" style="gap: .5rem;"
                                                    data-terminal-id="<?=$terminal->uid?>" onclick="">
                                                <i class="mdi mdi-dots-horizontal font-14"></i>
                                                <span class="font-14">Se alle</span>
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




