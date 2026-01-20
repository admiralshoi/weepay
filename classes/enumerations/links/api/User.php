<?php

namespace classes\enumerations\links\api;

class User {

    public string $updateProfile = "api/user/update-profile";
    public string $updateAddress = "api/user/update-address";
    public string $updatePassword = "api/user/update-password";
    public string $updateUsername = "api/user/update-username";
    public string $updateTwoFactor = "api/user/update-two-factor";
    public string $verifyPhone = "api/user/verify-phone";

    // Bell notifications
    public string $notificationsList = "api/user/notifications/list";
    public string $notificationsMarkRead = "api/user/notifications/mark-read";
    public string $notificationsMarkAllRead = "api/user/notifications/mark-all-read";
    public string $notificationsUnreadCount = "api/user/notifications/unread-count";

}
