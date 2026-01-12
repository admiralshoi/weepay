<?php
/**
 * Admin Panel Home
 */

$pageTitle = "System Panel";
?>
<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "panel-home";
</script>

<div class="page-content py-3">
    <div class="page-inner-content">
        <div class="flex-col-start" style="row-gap: 1.5rem;">
            <div class="flex-row-between flex-align-center w-100">
                <h1 class="mb-0 font-24 font-weight-bold">System Panel</h1>
            </div>
            <div class="card border-radius-10px">
                <div class="card-body flex-col-center flex-align-center py-5">
                    <i class="mdi mdi-cog-outline font-50 color-gray"></i>
                    <p class="mb-0 font-16 color-gray mt-2">System panel oversigt kommer snart</p>
                </div>
            </div>
        </div>
    </div>
</div>
