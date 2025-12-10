<?php

/**
 * @var object $args
 */


use classes\Methods;


?>
<script>
</script>
<div id="locationAction" class="rightSidebar">
    <div class="flex-col-start" style="row-gap: 1rem;">
        <div class="flex-col-start mb-4">
            <div class="flex-row-between flex-align-center flex-nowrap" style="column-gap: .75rem;">
                <p class="mb-0 font-18 font-weight-bold">Lokationshandlinger</p>
                <button class="closeRightSidebarBtn">&times;</button>
            </div>
            <p class="color-gray font-14">
                Handlinger for lokationen '<span data-preview="location-name">Roses Frisør</span>'
            </p>
        </div>

        <div class="flex-col-start " style="row-gap: 1.5rem;">
            <div class="flex-col-start " style="row-gap: .5rem;">
<!--                <button class="btn-v2 design-action-btn-lg" id="save-creator-action-change">Save Changes</button>-->
                <a href="<?=\classes\enumerations\Links::$merchant->locations->mangeTeamDynamic()?>" class="btn-v2 trans-btn-lg" id="manage-team">Administrer medarbejdere</a>
<!--                <button class="btn-v2 danger-btn-lg" id="remove-campaign-creator">Remove From Campaign</button>-->

                <ul id="error-box" class="error-box"></ul>
            </div>
        </div>
    </div>
</div>



<?php scriptStart(); ?>
<script>
    $(document).ready(function () {
        LocationActions.init();
    })
</script>
<?php scriptEnd(); ?>
