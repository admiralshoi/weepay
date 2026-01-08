<?php

namespace classes\enumerations\links\api;

class Consumer {

    public string $orders = "api/consumer/orders";
    public string $payments = "api/consumer/payments";
    public string $updateProfile = "api/consumer/update-profile";
    public string $updateAddress = "api/consumer/update-address";
    public string $updatePassword = "api/consumer/update-password";
    public string $verifyPhone = "api/consumer/verify-phone";

}
