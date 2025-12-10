<?php

namespace classes\payments;

use classes\utility\Crud;
use Database\Collection;
use Database\model\Orders;
use env\api\Viva;

class OrderHandler extends Crud {



    function __construct() {
        parent::__construct(Orders::newStatic(), "orders");
    }



    public function getByOrganisation(?string $organisationId = null, array $status = ['DRAFT', 'PENDING', 'COMPLETED'], array $fields = []): Collection {
        return $this->getByX(['organisation' => $organisationId, 'status' => $status], $fields);
    }
    public function getByPrid(int|string $prid = null, array $fields = []): ?object {
        return $this->getFirst(['prid' => $prid], $fields);
    }



    public function insert(
        string $organisation,
        string $location,
        ?string $customerId,
        string $provider,
        string $plan,
        string $currency,
        float|int $amount,
        float|int $isvAmount,
        float|int $isvFee,
        string $sourceCode,
        string $caption,
        ?string $prid,
        ?string $terminalSessionId,
    ): bool {

        return $this->create([
            "organisation" => $organisation,
            "location" => $location,
            "uuid" => $customerId,
            "provider" => $provider,
            "payment_plan" => $plan,
            "currency" => $currency,
            "amount" => $amount,
            "fee_amount" => $isvAmount,
            "fee" => $isvFee,
            "source_code" => $sourceCode,
            "caption" => $caption,
            "prid" => $prid,
            "terminal_session" => $terminalSessionId,
            "test" => (int)Viva::isSandbox()
        ]);

    }


}