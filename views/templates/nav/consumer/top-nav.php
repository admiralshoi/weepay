<?php
/**
 * @var string|null $pageHeaderTitle
 */
use classes\Methods;
use classes\enumerations\Links;
use features\Settings;

$userName = Settings::$user->full_name ?? 'Bruger';
?>

<div id="top-nav" class="home">
    <?php if(Settings::$impersonatingOrganisation): ?>
    <div id="impersonation-banner" class="flex-row-center flex-align-center flex-nowrap py-1 px-3" style="background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%); gap: .75rem; border-radius: 6px;">
        <i class="mdi mdi-shield-account font-16 color-white"></i>
        <p class="mb-0 font-12 font-weight-medium color-white">
            Admin: <strong><?=htmlspecialchars($userName)?></strong>
        </p>
        <button onclick="stopImpersonation()" class="btn-v2 font-11" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); padding: .2rem .5rem;">
            <i class="mdi mdi-arrow-left"></i> Tilbage
        </button>
    </div>
    <script>
    function stopImpersonation() {
        post('<?=__url(Links::$api->admin->impersonate->stop)?>', {})
            .then(response => {
                if(response.data && response.data.redirect) {
                    window.location.href = response.data.redirect;
                } else {
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Error stopping impersonation:', error);
                alert('Der opstod en fejl. Prøv igen.');
            });
    }
    </script>
    <?php else: ?>
    <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .75rem; max-width: var(--left-nav-width)">
        <button class="mobileOnlyInlineFlex btn-unstyled p-0 m-0 border-0 bg-transparent" id="topNavSidebarToggle" style="cursor: pointer;">
            <i class="mdi mdi-menu font-30 color-gray hover-color-blue"></i>
        </button>
        <p class="hideOnSmallScreen mb-0 font-18 font-weight-bold color-blue text-nowrap">WeePay Kunde</p>
    </div>
    <?php endif; ?>


    <div class="flex-row-end flex-align-center" style="column-gap: .75rem">

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


