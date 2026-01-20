<?php
namespace routing\routes\admin;

use classes\Methods;
use classes\utility\Misc;
use Database\model\Countries;
use features\Settings;

/**
 * Admin Panel Controller
 * Handles all panel/system configuration pages for admin users
 */
class PanelController {

    // =====================================================
    // PANEL PAGES
    // =====================================================

    public static function home(array $args): mixed {
        return Views("ADMIN_PANEL_HOME", $args);
    }

    public static function settings(array $args): mixed {
        // Get all AppMeta settings
        $settings = Methods::appMeta()->getAllAsKeyPairs();
        $args['settings'] = $settings->empty() ? [] : $settings->list();

        // Get enabled countries from Countries table
        $enabledCountries = Countries::where('enabled', 1)->all();
        $args['enabledCountries'] = $enabledCountries->empty() ? [] : $enabledCountries->list();

        // Get all countries from library for adding new ones (with native names for search)
        $args['worldCountries'] = Misc::getCountriesLib(WORLD_COUNTRIES);

        // Get currencies library for multi-select
        $currenciesLib = [];
        $currenciesFile = ROOT . CURRENCIES;
        if (file_exists($currenciesFile)) {
            $currenciesLib = json_decode(file_get_contents($currenciesFile), true) ?? [];
        }
        $args['currenciesLibrary'] = $currenciesLib;

        return Views("ADMIN_PANEL_SETTINGS", $args);
    }

    public static function marketing(array $args): mixed {
        return Views("ADMIN_PANEL_MARKETING", $args);
    }

    public static function fees(array $args): mixed {
        // Get platform fees
        $defaultFee = Methods::appMeta()->get('resellerFee') ?? 5.95;
        $cardFee = Methods::appMeta()->get('cardFee') ?? 0.39;
        $paymentProviderFee = Methods::appMeta()->get('paymentProviderFee') ?? 0.39;

        // Get all organisation fee overrides (with resolved organisations)
        $orgFees = Methods::organisationFees()->getByX(['enabled' => 1]);
        $args['defaultFee'] = $defaultFee;
        $args['cardFee'] = $cardFee;
        $args['paymentProviderFee'] = $paymentProviderFee;
        $args['minOrgFee'] = $cardFee + $paymentProviderFee;
        $args['orgFees'] = $orgFees;

        // Get rykker settings
        $args['rykker_1_days'] = (int)(Methods::appMeta()->get('rykker_1_days') ?? 7);
        $args['rykker_2_days'] = (int)(Methods::appMeta()->get('rykker_2_days') ?? 14);
        $args['rykker_3_days'] = (int)(Methods::appMeta()->get('rykker_3_days') ?? 21);
        $args['rykker_1_fee'] = (float)(Methods::appMeta()->get('rykker_1_fee') ?? 0);
        $args['rykker_2_fee'] = (float)(Methods::appMeta()->get('rykker_2_fee') ?? 100);
        $args['rykker_3_fee'] = (float)(Methods::appMeta()->get('rykker_3_fee') ?? 100);

        return Views("ADMIN_PANEL_FEES", $args);
    }

    public static function webhooks(array $args): mixed {
        return Views("ADMIN_PANEL_WEBHOOKS", $args);
    }

    public static function api(array $args): mixed {
        return Views("ADMIN_PANEL_API", $args);
    }

    public static function paymentPlans(array $args): mixed {
        // Get payment plans from AppMeta
        $paymentPlans = Methods::appMeta()->get('paymentPlans') ?? [];
        $maxBnplAmount = Methods::appMeta()->get('platform_max_bnpl_amount') ?? 1000;
        $bnplInstallmentMaxDuration = Methods::appMeta()->get('bnplInstallmentMaxDuration') ?? 90;
        $args['paymentPlans'] = $paymentPlans;
        $args['maxBnplAmount'] = $maxBnplAmount;
        $args['bnplInstallmentMaxDuration'] = $bnplInstallmentMaxDuration;

        return Views("ADMIN_PANEL_PAYMENT_PLANS", $args);
    }

    public static function maintenance(array $args): mixed {
        return Views("ADMIN_PANEL_MAINTENANCE", $args);
    }

    public static function cache(array $args): mixed {
        return Views("ADMIN_PANEL_CACHE", $args);
    }

    public static function jobs(array $args): mixed {
        // Get all cronjobs from database
        $cronjobs = Methods::cronWorker()->getByX([]);
        $args['cronjobs'] = $cronjobs;

        // Get cronjob configuration from CronWorker
        $worker = Methods::cronWorker();
        $args['cronConfig'] = $worker->getTypesList();

        return Views("ADMIN_PANEL_JOBS", $args);
    }

    // =====================================================
    // CONTENT & POLICIES
    // =====================================================

    public static function policies(array $args): mixed {
        $args['policies'] = Methods::policyTypes()->getAllWithStatus();
        return Views("ADMIN_PANEL_POLICIES", $args);
    }

    public static function policiesPrivacy(array $args): mixed {
        $type = ($args['type'] ?? 'consumer') === 'merchant' ? 'merchant_privacy' : 'consumer_privacy';
        $args['policyType'] = $type;
        return Views("ADMIN_PANEL_POLICIES_PRIVACY", $args);
    }

    public static function policiesTerms(array $args): mixed {
        $type = ($args['type'] ?? 'consumer') === 'merchant' ? 'merchant_terms' : 'consumer_terms';
        $args['policyType'] = $type;
        return Views("ADMIN_PANEL_POLICIES_TERMS", $args);
    }

    public static function policiesCookies(array $args): mixed {
        $args['policyType'] = 'cookies';
        return Views("ADMIN_PANEL_POLICIES_COOKIES", $args);
    }

    public static function contactForms(array $args): mixed {
        return Views("ADMIN_PANEL_CONTACT_FORMS", $args);
    }

    public static function notifications(array $args): mixed {
        return Views("ADMIN_PANEL_NOTIFICATIONS", $args);
    }

    public static function faqs(array $args): mixed {
        // Get all FAQs grouped by type
        $faqHandler = Methods::faqs();

        $args['consumerFaqs'] = $faqHandler->getGroupedByCategory('consumer', false);
        $args['merchantFaqs'] = $faqHandler->getGroupedByCategory('merchant', false);
        $args['consumerCategories'] = $faqHandler->getCategories('consumer');
        $args['merchantCategories'] = $faqHandler->getCategories('merchant');

        return Views("ADMIN_PANEL_FAQS", $args);
    }

}
