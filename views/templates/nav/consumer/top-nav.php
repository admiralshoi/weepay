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


    <div class="flex-row-end flex-align-center" style="column-gap: 1rem">

        <p class="mb-0 color-gray font-14 font-weight-medium ellipsis-single-line" style="max-width: 200px;">
            <?=\classes\utility\Titles::cleanUcAll(__name())?>
        </p>

        <a href="<?=__url(Links::$app->logout)?>" class="btn-v2 trans-hover-design-action-btn flex-row-start flex-align-center flex-nowrap font-14" style="gap: .5rem;">
            <i class="mdi mdi-logout"></i>
            <span>Log ud</span>
        </a>

    </div>
</div>


