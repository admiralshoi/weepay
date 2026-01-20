<?php

namespace classes\enumerations\links\api\admin;

class Admin {

    public Payments $payments;
    public Users $users;
    public Consumers $consumers;
    public Merchants $merchants;
    public Orders $orders;
    public Organisations $organisations;
    public Locations $locations;
    public Reports $reports;
    public Impersonate $impersonate;
    public Panel $panel;
    public Support $support;
    public Policies $policies;

    function __construct() {
        $this->payments = new Payments();
        $this->users = new Users();
        $this->consumers = new Consumers();
        $this->merchants = new Merchants();
        $this->orders = new Orders();
        $this->organisations = new Organisations();
        $this->locations = new Locations();
        $this->reports = new Reports();
        $this->impersonate = new Impersonate();
        $this->panel = new Panel();
        $this->support = new Support();
        $this->policies = new Policies();
    }
}

class Payments {
    public string $list = "api/admin/payments/list";
    public string $resetRykker = "api/admin/payments/{id}/reset-rykker";
    public string $markCollection = "api/admin/payments/{id}/mark-collection";
}

class Users {
    public string $list = "api/admin/users/list";
}

class Consumers {
    public string $list = "api/admin/users/list";
}

class Merchants {
    public string $list = "api/admin/users/list";
}

class Orders {
    public string $list = "api/admin/orders/list";
}

class Organisations {
    public string $list = "api/admin/organisations/list";
}

class Locations {
    public string $list = "api/admin/locations/list";
}

class Reports {
    public string $stats = "api/admin/reports/stats";
    public string $generateCsv = "api/admin/reports/generate-csv";
    public string $generatePdf = "api/admin/reports/generate-pdf";

    public function download(string $filename): string {
        return "api/admin/reports/download/{$filename}";
    }
}

class Impersonate {
    public string $start = "api/admin/impersonate/start";
    public string $stop = "api/admin/impersonate/stop";
}

class Panel {
    public string $updateSetting = "api/admin/panel/update-setting";
    public string $createUser = "api/admin/panel/create-user";
    public string $createRole = "api/admin/panel/create-role";
    public string $updateRole = "api/admin/panel/update-role";
    public string $rykkerSettings = "api/admin/panel/rykker-settings";

    // FAQ routes
    public string $faqsList = "api/admin/faqs/list";
    public string $faqsCreate = "api/admin/faqs/create";
    public string $faqsUpdate = "api/admin/faqs/update";
    public string $faqsDelete = "api/admin/faqs/delete";
    public string $faqsToggleActive = "api/admin/faqs/toggle-active";
    public string $faqsReorder = "api/admin/faqs/reorder";
}

class Support {
    public string $list = "api/admin/support/list";
    public string $reply = "api/admin/support/reply";
    public string $close = "api/admin/support/close";
    public string $reopen = "api/admin/support/reopen";
    public string $delete = "api/admin/support/delete";
}

class Policies {
    public string $list = "api/admin/policies/list";
    public string $get = "api/admin/policies/get";
    public string $save = "api/admin/policies/save";
    public string $publish = "api/admin/policies/publish";
    public string $delete = "api/admin/policies/delete";
    public string $versions = "api/admin/policies/versions";
}
