<?php

/**
 * @var object $args
 */

$pageTitle = "Log list";


?>
<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "logs";
</script>
<div class="page-content position-relative" data-page="logs">

    <div class="flex-row-start flex-align-center font-22 font-weight-medium">
        <p>Log list</p>
    </div>


    <div class="row mt-5">
        <div class="col-sm-10 m-auto">
            <div class="card">
                <div class="card-body">
                    <div class="table">
                        <table>
                            <thead>
                            <th>Name</th>
                            <th>Link</th>
                            </thead>
                            <tbody>

                            <?php foreach ($args->list as $type => $display):
                                    ?>
                                <tr>
                                    <td><?=is_object($display) ? $display->name : $display?></td>
                                    <td>
                                        <a href="<?=__url((is_object($display) ? $display->endpoint : ADMIN_PANEL_PATH . '/logs/' . strtolower($type)))?>">Open</a>
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