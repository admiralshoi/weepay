<?php
/**
 * @var object $args
 */

$pageTitle = "My cookies";
?>
<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "my-cookies";
</script>
<div class="page-content position-relative" data-page="cookie_manager">

    <div class="row">
        <div class="col-12">


            <p class="font-22 font-weight-bold">My cookies</p>

            <div class="card border-radius-10px mt-4">
                <div class="card-body">
                    <p class="font-18 font-weight-bold color-primary-cta">Set new cookie</p>


                    <form method="post"  id="cookie_form" class="row">
                        <div class="col-12 col-md-8 mt-1">
                            <div class="flex-col-start flex-align-start" style="row-gap: .5rem">
                                <p class="font-16">Cookie</p>
                                <input type="text" name="cookie" placeholder="Paste the cookie here" class="form-control" required/>
                            </div>
                        </div>
                        <div class="col-12 col-md-4 mt-1">
                            <div class="flex-col-end flex-align-end">
                                <p style="visibility: hidden">Create</p>
                                <button class="btn btn-primary mnw-100px" name="set_new_cookie" value="1"
                                        style="height: calc(1.5em + 0.75rem + 2px)">Set</button>
                            </div>
                        </div>
                        <div class="col-12 mt-3 d-flex">
                            <div style="display: none" class="error-field px-3 py-2 mb-3 alert-danger"></div>
                        </div>

                    </form>

                </div>
            </div>


            <?php if(!isEmpty($args->cookies->valid)): ?>
            <div class="row mt-5">
                <div class="col-12">
                    <p class="font-18 font-weight-bold">Valid cookies</p>

                    <div class="w-100  position-relative custom-table ">

                        <div class="background-layer"></div>
                        <table class="table-white custom-sorting-table" id="valid-cookie-table">
                            <thead class="noSelect">
                            <tr>
                                <th class="text-nowrap ">
                                    Name
                                </th>
                                <th class="text-nowrap sort-active " data-sortDirection="desc">
                                    Contributions
                                </th>
                                <th class="text-nowrap hideOnMobileTableCell">
                                    Error streak
                                </th>
                                <th class="text-nowrap hideOnMobileTableCell">
                                    Last updated
                                </th>
                                <th class="unsortable hideOnMobileTableCell">Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            foreach ($args->cookies->valid->list() as $obj):
                                ?>

                                <tr>
                                    <td data-sort="<?=$obj->name?>"><?=$obj->name?></td>
                                    <td data-sort="<?=$obj->successes?>"><?=$obj->successes?></td>
                                    <td class="hideOnMobileTableCell" data-sort="<?=$obj->error_streak?>"><?=$obj->error_streak?></td>
                                    <td class="hideOnMobileTableCell" data-sort="<?=strtotime($obj->updated_at)?>"><?=date("M d, H:i", strtotime($obj->updated_at))?></td>
                                    <td class="mxw-150px hideOnMobileTableCell">
                                        <div class="font-16 flex-col-start">
                                            <select name="table_actions" class="form-control"  data-cookie-id="<?=$obj->id?>">
                                                <option value="">No action...</option>
                                                <option value="invalidateCookie">Invalidate</option>
                                            </select>
                                        </div>
                                    </td>
                                </tr>


                            <?php endforeach; ?>
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>
            <?php endif; ?>





            <?php if(!isEmpty($args->cookies->invalid)): ?>
            <div class="row mt-5">
                <div class="col-12">
                    <p class="font-18 font-weight-bold">Invalid cookies</p>

                    <div class="w-100  position-relative custom-table ">

                        <div class="background-layer"></div>
                        <table class="table-white custom-sorting-table" id="valid-cookie-table">
                            <thead class="noSelect">
                            <tr>
                                <th class="text-nowrap">
                                    Name
                                </th>
                                <th class="text-nowrap sort-active" data-sortDirection="desc">
                                    Contributions
                                </th>
                                <th class="text-nowrap hideOnMobileTableCell">
                                    Failures
                                </th>
                                <th class="text-nowrap hideOnMobileTableCell">
                                    Invalidated at
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            foreach ($args->cookies->invalid->list() as $obj):
                                ?>

                                <tr>
                                    <td data-sort="<?=$obj->name?>"><?=$obj->name?></td>
                                    <td data-sort="<?=$obj->successes?>"><?=$obj->successes?></td>
                                    <td class="hideOnMobileTableCell" data-sort="<?=$obj->failures?>"><?=$obj->failures?></td>
                                    <td class="hideOnMobileTableCell" data-sort="<?=strtotime($obj->updated_at)?>"><?=date("M d, H:i", strtotime($obj->updated_at))?></td>
                                </tr>


                            <?php endforeach; ?>
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>
            <?php endif; ?>








        </div>
    </div>

</div>