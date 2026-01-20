<?php

namespace classes\enumerations\links\api;

class Merchant {

    public MerchantSupport $support;

    function __construct() {
        $this->support = new MerchantSupport();
    }

}

class MerchantSupport {
    public string $create = "api/merchant/support/create";
    public string $reply = "api/merchant/support/reply";
    public string $close = "api/merchant/support/close";
    public string $reopen = "api/merchant/support/reopen";
}
