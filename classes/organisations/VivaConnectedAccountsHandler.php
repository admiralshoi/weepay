<?php

namespace classes\organisations;

use classes\Methods;
use classes\utility\Crud;
use Database\model\VivaConnectedAccounts;

class VivaConnectedAccountsHandler extends Crud {



    function __construct() {
        parent::__construct(VivaConnectedAccounts::newStatic(), "organisation");
    }


    public function myConnection(?string $organisationId = null, array $state = ['COMPLETED', 'DRAFT']): ?object {
        if(empty($organisationId)) $organisationId = __oUuid();
        return $this->getFirst(['organisation' => $organisationId, 'state' => $state]);
    }

    public function getMyVivaAccount(?string $organisationId = null): ?object {
        if(empty($organisationId)) $organisationId = __oUuid();
        $prid = $this->getColumn(['organisation' => $organisationId], 'prid');
        if(isEmpty($prid)) return null;
        return toObject(Methods::viva()->getConnectedMerchant($prid));
    }



    public function insert(
        string $email,
        string $organisation,
        string $prid,
        string $link,
        string $state = "DRAFT",
    ): bool {
        return $this->create([
            'email' => $email,
            'organisation' => $organisation,
            'prid' => $prid,
            'link' => $link,
            'state' => $state,
        ]);
    }

}