<?php
/**
 * @var object $user
 */

use classes\enumerations\Links;

$pageTitle = "Admin Dashboard";
?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
</script>

<div class="page-content py-3">
    <div class="page-inner-content">

        <div class="flex-col-start" style="row-gap: 1.5rem;">

            <!-- Page Header -->
            <div class="flex-row-between flex-align-center w-100">
                <div class="flex-col-start" style="row-gap: .25rem;">
                    <h1 class="mb-0 font-24 font-weight-bold">Dashboard</h1>
                    <p class="mb-0 font-14 color-gray">Velkommen tilbage, <?=$user->full_name ?? 'Administrator'?></p>
                </div>
            </div>


            <!-- Quick Stats Cards -->
            <div class="row" style="row-gap: 1rem;">
                <div class="col-md-6 col-lg-3">
                    <div class="card border-radius-10px h-100">
                        <div class="card-body flex-col-start" style="row-gap: .5rem;">
                            <div class="flex-row-between flex-align-center w-100">
                                <span class="font-12 color-gray text-uppercase font-weight-bold">Brugere</span>
                                <i class="mdi mdi-account-group-outline font-20 color-blue"></i>
                            </div>
                            <p class="mb-0 font-28 font-weight-bold">-</p>
                            <span class="font-12 color-gray">Kommer snart</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card border-radius-10px h-100">
                        <div class="card-body flex-col-start" style="row-gap: .5rem;">
                            <div class="flex-row-between flex-align-center w-100">
                                <span class="font-12 color-gray text-uppercase font-weight-bold">Forhandlere</span>
                                <i class="mdi mdi-store-outline font-20 color-green"></i>
                            </div>
                            <p class="mb-0 font-28 font-weight-bold">-</p>
                            <span class="font-12 color-gray">Kommer snart</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card border-radius-10px h-100">
                        <div class="card-body flex-col-start" style="row-gap: .5rem;">
                            <div class="flex-row-between flex-align-center w-100">
                                <span class="font-12 color-gray text-uppercase font-weight-bold">Ordrer</span>
                                <i class="mdi mdi-receipt-outline font-20 color-orange"></i>
                            </div>
                            <p class="mb-0 font-28 font-weight-bold">-</p>
                            <span class="font-12 color-gray">Kommer snart</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card border-radius-10px h-100">
                        <div class="card-body flex-col-start" style="row-gap: .5rem;">
                            <div class="flex-row-between flex-align-center w-100">
                                <span class="font-12 color-gray text-uppercase font-weight-bold">Omsætning</span>
                                <i class="mdi mdi-currency-usd font-20 color-purple"></i>
                            </div>
                            <p class="mb-0 font-28 font-weight-bold">-</p>
                            <span class="font-12 color-gray">Kommer snart</span>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Main Content Area -->
            <div class="card border-radius-10px">
                <div class="card-body">
                    <div class="flex-col-center flex-align-center py-5" style="row-gap: 1rem;">
                        <i class="mdi mdi-shield-account-outline font-60 color-gray"></i>
                        <p class="mb-0 font-18 font-weight-medium color-gray text-center">Admin dashboard under opbygning</p>
                        <p class="mb-0 font-14 color-gray text-center">Mere indhold kommer snart...</p>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>
