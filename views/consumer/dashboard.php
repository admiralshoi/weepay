<?php
/**
 * @var object $user
 */

use classes\enumerations\Links;
?>

<div class="page-content">
    <div class="page-inner-content">

        <div class="flex-col-start" style="row-gap: 2rem;">

            <div class="flex-row-between flex-align-center flex-wrap" style="gap: 1rem;">
                <div class="flex-col-start" style="row-gap: .25rem;">
                    <h1 class="mb-0 font-28 font-weight-700">Velkommen, <?=htmlspecialchars($user->full_name)?>!</h1>
                    <p class="mb-0 font-14 color-gray">Dit dashboard</p>
                </div>
            </div>

            <div class="card border-radius-10px">
                <div class="card-body">
                    <div class="flex-col-center flex-align-center" style="row-gap: 1rem; padding: 3rem 1rem;">
                        <i class="mdi mdi-view-dashboard-outline font-48 color-gray"></i>
                        <div class="flex-col-center flex-align-center" style="row-gap: .5rem;">
                            <p class="mb-0 font-18 font-weight-bold">Dit dashboard er klar</p>
                            <p class="mb-0 font-14 color-gray text-center">
                                Her vil du kunne se dine betalinger, ordrer og meget mere.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>
