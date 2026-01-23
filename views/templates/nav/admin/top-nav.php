<?php
/**
 * @var string|null $pageHeaderTitle
 */
use classes\Methods;
use classes\enumerations\Links;

?>


<div id="top-nav" class="home">
    <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .75rem; max-width: var(--left-nav-width)">
        <button class="mobileOnlyInlineFlex btn-unstyled p-0 m-0 border-0 bg-transparent" id="topNavSidebarToggle" style="cursor: pointer;">
            <i class="mdi mdi-menu font-30 color-gray hover-color-blue"></i>
        </button>
        <p class="hideOnSmallScreen mb-0 font-18 font-weight-bold color-danger text-nowrap">WeePay Admin</p>
    </div>


    <div class="flex-row-end flex-align-center" style="column-gap: .75rem">

        <?php if(empty($isPanel)): ?>
        <a href="<?=__url(Links::$admin->panel)?>" class="btn-v2 trans-hover-btn flex-row-start flex-align-center flex-nowrap font-14" style="gap: .5rem;" title="System Panel">
            <i class="mdi mdi-cog-outline"></i>
            <span class="hideOnSmallScreen">Panel</span>
        </a>
        <?php endif; ?>

        <!-- Bell Notifications -->
        <div class="bell-notifications-container position-relative">
            <button type="button" class="btn-unstyled bell-notifications-trigger" id="bellNotificationsTrigger" title="Notifikationer">
                <i class="mdi mdi-bell-outline font-22 color-gray"></i>
                <span class="bell-badge" id="bellBadge" style="display: none;">0</span>
            </button>
            <div class="bell-notifications-dropdown" id="bellNotificationsDropdown">
                <div class="bell-notifications-header">
                    <span class="font-14 font-weight-bold">Notifikationer</span>
                    <button type="button" class="btn-unstyled font-12 color-blue" id="markAllReadBtn">Marker alle som læst</button>
                </div>
                <div class="bell-notifications-list" id="bellNotificationsList">
                    <div class="bell-notifications-loading" id="bellLoading">
                        <i class="mdi mdi-loading mdi-spin font-20 color-gray"></i>
                    </div>
                    <div class="bell-notifications-empty" id="bellEmpty" style="display: none;">
                        <i class="mdi mdi-bell-off-outline font-30 color-gray mb-2"></i>
                        <p class="mb-0 font-13 color-gray">Ingen notifikationer</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Dropdown -->
        <?php include __DIR__ . '/../partials/user-dropdown.php'; ?>

    </div>
</div>
