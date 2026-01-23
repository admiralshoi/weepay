<?php
/**
 * User Dropdown Partial
 * Include this in top navs to show user profile dropdown
 *
 * @var string $settingsUrl - URL to settings page (optional)
 */
use classes\enumerations\Links;
use features\Settings;

$userName = Settings::$user->full_name ?? 'Bruger';
$userEmail = Settings::$user->email ?? '';
$userInitials = '';

// Get initials from name
$nameParts = explode(' ', trim($userName));
if (count($nameParts) >= 2) {
    $userInitials = strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1));
} else {
    $userInitials = strtoupper(substr($userName, 0, 2));
}

// Default settings URL based on user type if not provided
if (!isset($settingsUrl)) {
    if (\classes\Methods::isMerchant()) {
        $settingsUrl = __url(Links::$merchant->settings);
    } elseif (\classes\Methods::isConsumer()) {
        $settingsUrl = __url(Links::$consumer->settings);
    } else {
        $settingsUrl = __url('settings');
    }
}
?>
<div class="bell-notifications-container">
    <button type="button" class="bell-notifications-trigger" id="userDropdownTrigger" title="<?=htmlspecialchars($userName)?>"
            style="background: var(--action-color, #3b82f6); border-radius: 50%; color: white; font-size: 13px; font-weight: 600;">
        <?=$userInitials?>
    </button>
    <div class="bell-notifications-dropdown" id="userDropdownMenu" style="width: 260px;">
        <div class="bell-notifications-header" style="gap: 12px; padding: 14px 16px;">
            <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--action-color, #3b82f6); color: white; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 600; flex-shrink: 0;">
                <?=$userInitials?>
            </div>
            <div style="min-width: 0; flex: 1;">
                <p class="font-14 font-weight-bold mb-0" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?=htmlspecialchars($userName)?></p>
                <?php if (!empty($userEmail)): ?>
                <p class="font-12 color-gray mb-0" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?=htmlspecialchars($userEmail)?></p>
                <?php endif; ?>
            </div>
        </div>
        <div style="padding: 8px 0; border-top: 1px solid #eee;">
            <a href="<?=$settingsUrl?>" class="flex-row-start flex-align-center px-3 py-2" style="gap: 10px; text-decoration: none; color: #374151; transition: background 0.15s;">
                <i class="mdi mdi-cog-outline font-18 color-gray"></i>
                <span class="font-14">Indstillinger</span>
            </a>
            <a href="<?=__url(Links::$app->logout)?>" class="flex-row-start flex-align-center px-3 py-2" style="gap: 10px; text-decoration: none; color: #dc2626; transition: background 0.15s;">
                <i class="mdi mdi-logout font-18"></i>
                <span class="font-14">Log ud</span>
            </a>
        </div>
    </div>
</div>
