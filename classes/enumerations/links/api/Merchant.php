<?php

namespace classes\enumerations\links\api;

class Merchant {

    public MerchantSupport $support;

    // Attention notifications
    public string $attentionNotifications = "api/merchant/attention-notifications";
    public string $attentionNotificationsResolve = "api/merchant/attention-notifications/{uid}/resolve";

    // Pending validation refunds
    public string $pendingValidationRefunds = "api/merchant/pending-validation-refunds";
    public string $pendingValidationRefundsMarkRefunded = "api/merchant/pending-validation-refunds/{uid}/mark-refunded";
    public string $pendingValidationRefundsAttemptRefund = "api/merchant/pending-validation-refunds/{uid}/refund";

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
