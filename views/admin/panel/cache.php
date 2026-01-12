<?php
/**
 * Admin Panel - Cache Management
 * Clear and manage application cache
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "Cache";
?>
<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "cache";
</script>

<div class="page-content py-3">
    <div class="page-inner-content">
        <div class="flex-col-start" style="row-gap: 1.5rem;">

            <!-- Breadcrumb -->
            <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                <a href="<?=__url(Links::$admin->panel)?>" class="font-13 color-gray hover-color-blue">Panel</a>
                <i class="mdi mdi-chevron-right font-14 color-gray"></i>
                <span class="font-13 color-dark">Cache</span>
            </div>

            <!-- Page Header -->
            <div class="flex-row-between flex-align-center w-100 flex-wrap" style="gap: 1rem;">
                <div class="flex-col-start">
                    <h1 class="mb-0 font-24 font-weight-bold">Cache Management</h1>
                    <p class="mb-0 font-14 color-gray">Ryd og administrer applikationens cache</p>
                </div>
            </div>

            <!-- Coming Soon -->
            <div class="card border-radius-10px">
                <div class="card-body flex-col-center flex-align-center py-5">
                    <div class="square-80 bg-light-gray border-radius-50 flex-row-center-center mb-3">
                        <i class="mdi mdi-database-outline font-40 color-gray"></i>
                    </div>
                    <p class="mb-0 font-18 font-weight-bold color-dark">Kommer snart</p>
                    <p class="mb-0 font-14 color-gray mt-2 text-center" style="max-width: 400px;">
                        Her vil du kunne rydde og administrere applikationens cache, session data og midlertidige filer.
                    </p>
                </div>
            </div>

        </div>
    </div>
</div>
