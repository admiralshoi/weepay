<?php
namespace Database\model;

class AppMeta extends \Database\Model {
    public static ?string $uidPrefix = null;
    protected static array $schema = [
        "name" => "string",
        "value" => "text",
        "type" => "text",
    ];
    protected static array $indexes = [

    ];
    protected static array $uniques = [
        "name"
    ];


    protected static array $requiredRows = [

        [
            "name" => "user_role_settings",
            "value" => '[]',
            "type" => "array",
        ],
        [
            "name" => "payment_providers",
            "value" => '["viva"]',
            "type" => "array",
        ],
        [
            "name" => "active_payment_providers",
            "value" => '["viva"]',
            "type" => "array",
        ],
        [
            "name" => "available_payment_methods",
            "value" => '{"viva": ["smart_checkout"]}',
            "type" => "array",
        ],
        [
            "name" => "default_payment_provider",
            "value" => 'viva',
            "type" => "string",
        ],
        [
            "name" => "default_currency",
            "value" => 'DKK',
            "type" => "string",
        ],
        [
            "name" => "default_country",
            "value" => 'DK',
            "type" => "string",
        ],
        [
            "name" => "currencies",
            "value" => '["DKK","EUR","GBP","RON","PLN","CZK","HUF","SEK","BGN"]',
            "type" => "array",
        ],
        [
            "name" => "organisation_roles",
            "value" => '["owner","admin","team_manager","analyst", "location_employee"]',
            "type" => "array",
        ],
        [
            "name" => "location_roles",
            "value" => '["store_manager","team_manager","cashier"]',
            "type" => "array",
        ],
        [
            "name" => "taskManager",
            "value" => '{"subscriptionRevert": {"ttl": 600}, "subscriptionCreateFailedCancel": {"ttl": 600}, "voidInvoice": {"ttl": 600}}',
            "type" => "array",
        ],
        [
            "name" => "paymentPlans",
            "value" => '{"direct": {"enabled": true, "title": "Betal Nu", "caption": "Fuld betaling med det samme", "installments": 1, "start": "now"}, "pushed": {"enabled": true, "title": "Betal d. 1. i Måneden", "caption": "Udskyd betalingen til næste måned", "installments": 1, "start": "first day of next month"}, "installments": {"enabled": true, "title": "Del i 4 Rater", "caption": "Betal over 90 dage", "installments": 4, "start": "now"}}',
            "type" => "array",
        ],
        [
            "name" => "resellerFee",
            "value" => '5.95',
            "type" => "float",
        ],
        [
            "name" => "cardFee",
            "value" => '0.39',
            "type" => "float",
        ],
        [
            "name" => "paymentProviderFee",
            "value" => '0.39',
            "type" => "float",
        ],
        [
            "name" => "paymentProviderFlatFee",
            "value" => '0.75',
            "type" => "float",
        ],
        [
            "name" => "currencyConversionRates",
            "value" => '{"DKK":1,"EUR":0.13,"GBP":0.11,"SEK":1.40,"NOK":1.45,"PLN":0.57,"CZK":3.05,"HUF":47.5,"RON":0.60,"BGN":0.26}',
            "type" => "array",
        ],
        [
            "name" => "bnplInstallmenMaxDuration",
            "value" => '90',
            "type" => "int",
        ],
        [
            "name" => "oidc_session_lifetime",
            "value" => '300',
            "type" => "int",
        ],
        [
            "name" => "platform_max_bnpl_amount",
            "value" => '1000',
            "type" => "float",
        ],
        [
            "name" => "payment_max_attempts",
            "value" => '6',
            "type" => "int",
        ],
        [
            "name" => "payment_retry_day_interval",
            "value" => '1',
            "type" => "int",
        ],
        // Rykker (Dunning) Settings
        [
            "name" => "rykker_1_days",
            "value" => '7',
            "type" => "int",
        ],
        [
            "name" => "rykker_2_days",
            "value" => '14',
            "type" => "int",
        ],
        [
            "name" => "rykker_3_days",
            "value" => '21',
            "type" => "int",
        ],
        [
            "name" => "rykker_1_fee",
            "value" => '0',
            "type" => "float",
        ],
        [
            "name" => "rykker_2_fee",
            "value" => '100',
            "type" => "float",
        ],
        [
            "name" => "rykker_3_fee",
            "value" => '100',
            "type" => "float",
        ],
        [
            "name" => "reserved_names",
            "value" => '["www","api","app","web","mobile","admin","administrator","root","system","sys","server","backend","frontend","dashboard","panel","console","portal","internal","status","health","monitor","metrics","logs","login","logout","signin","signup","register","auth","authentication","authorize","callback","oauth","password","reset","forgot","verify","verification","activate","deactivate","account","profile","settings","preferences","security","pay","payment","payments","checkout","invoice","invoices","billing","bill","subscription","subscriptions","plan","plans","pricing","price","fee","fees","payout","payouts","settlement","settlements","refund","refunds","chargeback","chargebacks","balance","balances","wallet","wallets","transaction","transactions","transfer","transfers","currency","currencies","tax","taxes","vat","weepay","wee-pay","weepaydk","weepay-support","support","help","service","services","official","staff","team","admin-team","moderator","moderators","operator","operations","legal","terms","conditions","privacy","policy","policies","compliance","kyc","aml","gdpr","risk","fraud","dispute","disputes","appeal","appeals","report","reports","audit","store","stores","shop","shops","merchant","merchants","seller","sellers","vendor","vendors","market","marketplace","product","products","catalog","inventory","order","orders","cart","dk","denmark","eu","europe","us","uk","global","intl","international","en","da","null","nil","none","undefined","true","false","test","testing","demo","example","sample","guest","anonymous","user","username","owner","json","xml","csv","html","js","css","php","asp","jsp","consumer","customers","cron","cronjob","invitation","location","locations","location-pages","migration","mitid","oidc","pos","qr","terminals","webhook","asign","asign-editor","access-denied","receipts","past-due-payments","pending-payments","outstanding-payments","upcoming-payments","password-recovery","reset-password","marketing-materials","cookies","contact","contact-forms","faq","faqs","notifications","rykker","bnpl","buy-now-pay-later","contract","contracts","download","downloads","public","private","static","assets","images","img","files","upload","uploads","media","video","videos","embed","share","redirect","link","links","short","url","tracking","analytics","pixel","cdn","cache","config","configuration","error","errors","404","500","maintenance","offline","online","live","staging","dev","development","production","sandbox","beta","alpha","preview","draft","publish","published","archive","archived","trash","deleted","restore","backup","export","import","sync","connect","disconnect","integrate","integration","integrations","viva","vivawallet","mitid-test","viva-test"]',
            "type" => "array",
        ],
    ];
    protected static array $requiredRowsTesting = [];


    public static array $encodeColumns = [
        //Should be fetched using the Meta class
    ];
    //Not for columns that uses encode columns (does not support array converting)
    public static array $encryptedColumns = [];
    public static function foreignkeys(): array {
        return [
        ];
    }
}