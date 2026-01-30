<?php

namespace routing\routes\demo;

use classes\enumerations\Links;
use features\Settings;

class DemoController {

    /**
     * Initialize or get demo session data
     */
    private static function initDemoSession(): array {
        if (!isset($_SESSION['demo'])) {
            $_SESSION['demo'] = [
                'organisation' => [
                    'uid' => 'demo-org-001',
                    'name' => 'Demo Butik ApS',
                    'cvr' => '12345678',
                ],
                'location' => [
                    'uid' => 'demo-loc-001',
                    'name' => 'Demo Butik',
                    'slug' => 'demo-butik',
                    'caption' => 'En demo-butik til at teste WeePay',
                    'hero_image' => DEFAULT_LOCATION_HERO,
                    'logo' => DEFAULT_LOCATION_LOGO,
                ],
                'terminal' => [
                    'uid' => 'demo-term-001',
                    'name' => 'Demo Terminal 1',
                    'state' => 'IDLE',
                ],
                'session' => [
                    'uid' => null,
                    'session_id' => null,
                    'state' => null,
                    'customer' => null,
                ],
                'basket' => [
                    'uid' => null,
                    'name' => null,
                    'price' => null,
                    'currency' => 'DKK',
                    'status' => null,
                    'note' => null,
                ],
                'order' => [
                    'uid' => null,
                    'prid' => null,
                    'payment_plan' => null,
                    'status' => null,
                ],
                'payments' => [],
                'flow' => [
                    'customer_appeared_at' => null,
                    'consumer_logged_in' => false,
                ],
            ];
        }
        return $_SESSION['demo'];
    }

    /**
     * Get demo data from session
     */
    private static function getDemoData(): array {
        return self::initDemoSession();
    }

    /**
     * Generate payment plans based on Settings::$app->payment_plans
     */
    private static function generateDemoPaymentPlans(float $price, string $currency = 'DKK'): array {
        $plans = [];
        $currencySymbol = currencySymbol($currency);

        // Get payment plans from app settings
        $appPlans = Settings::$app->paymentPlans ?? null;
        if (is_string($appPlans)) {
            $appPlans = json_decode($appPlans);
        }

        // Direct payment (Betal Nu)
        if (!empty($appPlans->direct->enabled)) {
            $plans[] = [
                'name' => 'direct',
                'title' => $appPlans->direct->title ?? 'Betal Nu',
                'caption' => $appPlans->direct->caption ?? 'Fuld betaling med det samme',
                'subtitle' => '',
                'price_title' => number_format($price, 2, ',', '.') . ' ' . $currencySymbol,
                'default' => true,
                'to_pay_now' => $price,
                'payments' => [],
            ];
        }

        // Pushed payment (Betal d. 1. i Måneden)
        if (!empty($appPlans->pushed->enabled)) {
            $firstOfNextMonth = new \DateTime('first day of next month');
            $plans[] = [
                'name' => 'pushed',
                'title' => $appPlans->pushed->title ?? 'Betal d. 1. i Måneden',
                'caption' => $appPlans->pushed->caption ?? 'Udskyd betalingen til næste måned',
                'subtitle' => '',
                'price_title' => number_format($price, 2, ',', '.') . ' ' . $currencySymbol,
                'default' => empty($plans),
                'to_pay_now' => 0,
                'payments' => [
                    ['installment' => 1, 'date_title' => $firstOfNextMonth->format('d/m/Y'), 'price' => number_format($price, 2, ',', '.')],
                ],
            ];
        }

        // Installments (Del i 4 Rater)
        if (!empty($appPlans->installments->enabled)) {
            $numInstallments = $appPlans->installments->installments ?? 4;
            $installmentAmount = round($price / $numInstallments, 2);
            $payments = [];

            for ($i = 1; $i <= $numInstallments; $i++) {
                // Last installment gets the remainder to avoid rounding issues
                $amount = ($i === $numInstallments)
                    ? $price - ($installmentAmount * ($numInstallments - 1))
                    : $installmentAmount;

                $dateTitle = $i === 1 ? 'I dag' : date('d/m/Y', strtotime('+' . ($i - 1) . ' month'));

                $payments[] = [
                    'installment' => $i,
                    'date_title' => $dateTitle,
                    'price' => number_format($amount, 2, ',', '.'),
                ];
            }

            $plans[] = [
                'name' => 'installments',
                'title' => $appPlans->installments->title ?? 'Del i ' . $numInstallments . ' Rater',
                'caption' => $appPlans->installments->caption ?? 'Betal over ' . (($numInstallments - 1) * 30) . ' dage',
                'subtitle' => '',
                'price_title' => number_format($installmentAmount, 2, ',', '.') . ' ' . $currencySymbol . '/md.',
                'default' => empty($plans),
                'to_pay_now' => $installmentAmount,
                'payments' => $payments,
            ];
        }

        // If no plans from settings, add a default direct payment
        if (empty($plans)) {
            $plans[] = [
                'name' => 'direct',
                'title' => 'Betal Nu',
                'caption' => 'Fuld betaling med det samme',
                'subtitle' => '',
                'price_title' => number_format($price, 2, ',', '.') . ' ' . $currencySymbol,
                'default' => true,
                'to_pay_now' => $price,
                'payments' => [],
            ];
        }

        return $plans;
    }

    /**
     * Build WebPage schema for demo pages
     */
    private static function buildWebPageSchema(string $name, string $description, string $url): array {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $name,
            'description' => $description,
            'url' => $url,
            'publisher' => [
                '@type' => 'Organization',
                'name' => BRAND_NAME,
                'logo' => __asset(OG_IMAGE),
            ],
        ];
    }

    /**
     * Landing page - Demo entry point with role selection
     */
    public static function landing(array $args): mixed {
        // Reset demo session when returning to landing
        unset($_SESSION['demo']);
        self::initDemoSession();

        $data = [
            'location' => (object) $_SESSION['demo']['location'],
            'meta' => [
                'schema' => self::buildWebPageSchema(
                    'Prøv WeePay Demo',
                    'Prøv WeePay demo og oplev vores betalingsløsning fra både kasserer- og kundesiden. Ingen tilmelding krævet.',
                    __url('demo')
                ),
            ],
        ];

        return Views("DEMO_LANDING", $data);
    }

    /**
     * Merchant start page - waiting for customers
     */
    public static function merchantStart(array $args): mixed {
        $demo = self::initDemoSession();

        // Reset session state for fresh start
        $_SESSION['demo']['terminal']['state'] = 'IDLE';
        $_SESSION['demo']['session'] = [
            'uid' => null,
            'session_id' => null,
            'state' => null,
            'customer' => null,
        ];
        $_SESSION['demo']['basket'] = [
            'uid' => null,
            'name' => null,
            'price' => null,
            'currency' => 'DKK',
            'status' => null,
            'note' => null,
        ];
        $_SESSION['demo']['order'] = [
            'uid' => null,
            'prid' => null,
            'payment_plan' => null,
            'status' => null,
        ];
        $_SESSION['demo']['payments'] = [];
        $_SESSION['demo']['flow']['customer_appeared_at'] = null;

        $data = [
            'terminal' => (object) $_SESSION['demo']['terminal'],
            'location' => (object) $_SESSION['demo']['location'],
            'meta' => [
                'schema' => self::buildWebPageSchema(
                    'WeePay Demo - Kasserer',
                    'Oplev WeePay fra kasserersiden. Se hvordan du nemt kan tilbyde fleksible betalingsplaner til dine kunder.',
                    __url('demo/cashier')
                ),
            ],
        ];

        return Views("DEMO_MERCHANT_START", $data);
    }

    /**
     * Merchant details page - enter basket info
     */
    public static function merchantDetails(array $args): mixed {
        $demo = self::getDemoData();

        // Require a session
        if (empty($demo['session']['uid'])) {
            Response()->redirect(__url(Links::$demo->cashier));
            return null;
        }

        $data = [
            'terminal' => (object) $demo['terminal'],
            'location' => (object) $demo['location'],
            'session' => (object) $demo['session'],
            'customer' => (object) $demo['session']['customer'],
        ];

        return Views("DEMO_MERCHANT_DETAILS", $data);
    }

    /**
     * Merchant checkout page - awaiting customer payment
     */
    public static function merchantCheckout(array $args): mixed {
        $demo = self::getDemoData();

        // Require a session and basket
        if (empty($demo['session']['uid']) || empty($demo['basket']['uid'])) {
            Response()->redirect(__url(Links::$demo->cashier));
            return null;
        }

        $data = [
            'terminal' => (object) $demo['terminal'],
            'location' => (object) $demo['location'],
            'session' => (object) $demo['session'],
            'customer' => (object) $demo['session']['customer'],
            'basket' => (object) $demo['basket'],
        ];

        return Views("DEMO_MERCHANT_CHECKOUT", $data);
    }

    /**
     * Merchant fulfilled page - order completed
     */
    public static function merchantFulfilled(array $args): mixed {
        $demo = self::getDemoData();

        // Require completed order
        if (empty($demo['order']['uid']) || $demo['session']['state'] !== 'COMPLETED') {
            Response()->redirect(__url(Links::$demo->cashier));
            return null;
        }

        $data = [
            'terminal' => (object) $demo['terminal'],
            'location' => (object) $demo['location'],
            'session' => (object) $demo['session'],
            'customer' => (object) $demo['session']['customer'],
            'basket' => (object) $demo['basket'],
            'order' => (object) $demo['order'],
            'payments' => $demo['payments'],
        ];

        return Views("DEMO_MERCHANT_FULFILLED", $data);
    }

    /**
     * Consumer start page - MitID simulation
     */
    public static function consumerStart(array $args): mixed {
        $demo = self::initDemoSession();

        // Reset consumer login state
        $_SESSION['demo']['flow']['consumer_logged_in'] = false;

        $data = [
            'location' => (object) $_SESSION['demo']['location'],
            'meta' => [
                'schema' => self::buildWebPageSchema(
                    'WeePay Demo - Kunde',
                    'Oplev WeePay fra kundesiden. Se hvordan du nemt kan vælge mellem fleksible betalingsplaner.',
                    __url('demo/consumer')
                ),
            ],
        ];

        return Views("DEMO_CONSUMER_START", $data);
    }

    /**
     * Consumer info page - waiting for basket
     */
    public static function consumerInfo(array $args): mixed {
        $demo = self::getDemoData();

        // Require consumer to be logged in
        if (empty($demo['flow']['consumer_logged_in'])) {
            Response()->redirect(__url(Links::$demo->consumer));
            return null;
        }

        $data = [
            'location' => (object) $demo['location'],
            'session' => (object) $demo['session'],
            'basket' => !empty($demo['basket']['uid']) ? (object) $demo['basket'] : null,
            'customer' => !empty($demo['session']['customer']) ? (object) $demo['session']['customer'] : null,
        ];

        return Views("DEMO_CONSUMER_INFO", $data);
    }

    /**
     * Consumer choose plan page
     */
    public static function consumerChoosePlan(array $args): mixed {
        $demo = self::getDemoData();

        // Require basket
        if (empty($demo['basket']['uid']) || empty($demo['flow']['consumer_logged_in'])) {
            Response()->redirect(__url(Links::$demo->consumerInfo));
            return null;
        }

        $price = (float) $demo['basket']['price'];
        $paymentPlans = self::generateDemoPaymentPlans($price, $demo['basket']['currency']);

        $data = [
            'location' => (object) $demo['location'],
            'session' => (object) $demo['session'],
            'basket' => (object) $demo['basket'],
            'customer' => (object) $demo['session']['customer'],
            'paymentPlans' => $paymentPlans,
            'defaultToPayNow' => $price,
        ];

        return Views("DEMO_CONSUMER_CHOOSE_PLAN", $data);
    }

    /**
     * Consumer order confirmation page
     */
    public static function consumerConfirmation(array $args): mixed {
        $demo = self::getDemoData();

        // Require completed order
        if (empty($demo['order']['uid'])) {
            Response()->redirect(__url(Links::$demo->consumer));
            return null;
        }

        // Build order object
        $order = (object) array_merge($demo['order'], [
            'location' => (object) $demo['location'],
            'amount' => $demo['basket']['price'],
            'currency' => $demo['basket']['currency'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Build payments collection
        $payments = [];
        foreach ($demo['payments'] as $payment) {
            $payments[] = (object) $payment;
        }

        $data = [
            'order' => $order,
            'customer' => (object) $demo['session']['customer'],
            'payments' => (object) [
                'list' => function() use ($payments) { return $payments; },
                'count' => function() use ($payments) { return count($payments); },
                'first' => function() use ($payments) { return !empty($payments) ? $payments[0] : null; },
            ],
        ];

        // Convert payments to a simple object with methods
        $paymentsObj = new class($payments) {
            private array $items;
            public function __construct(array $items) { $this->items = $items; }
            public function list(): array { return $this->items; }
            public function count(): int { return count($this->items); }
            public function first(): ?object { return !empty($this->items) ? $this->items[0] : null; }
        };
        $data['payments'] = $paymentsObj;

        return Views("DEMO_CONSUMER_CONFIRMATION", $data);
    }

}
