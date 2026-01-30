<?php

namespace routing\routes\demo;

use classes\enumerations\Links;

class DemoApiController {

    /**
     * Get or initialize demo session
     */
    private static function getDemoSession(): array {
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
     * Generate a random 4-digit session ID
     */
    private static function generateSessionId(): string {
        return str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Simulate customer appearance (called after 2 seconds from merchant start)
     */
    public static function simulateCustomerAppearance(array $args): array {
        $demo = self::getDemoSession();

        // Create fake customer
        $customer = [
            'uid' => 'demo-cust-' . uniqid(),
            'name' => 'Anders Andersen',
            'full_name' => 'Anders Andersen',
            'email' => 'anders@example.dk',
            'phone' => '+45 12 34 56 78',
        ];

        // Create session
        $_SESSION['demo']['session'] = [
            'uid' => 'demo-sess-' . uniqid(),
            'session_id' => self::generateSessionId(),
            'state' => 'PENDING',
            'customer' => $customer,
            'created_at' => time(),
        ];
        $_SESSION['demo']['terminal']['state'] = 'AWAITING_MERCHANT';
        $_SESSION['demo']['flow']['customer_appeared_at'] = time();

        return Response()->json([
            'status' => 'success',
            'data' => [
                'session' => $_SESSION['demo']['session'],
            ],
        ]);
    }

    /**
     * Get merchant sessions for the table
     */
    public static function getMerchantSessions(array $args): array {
        $demo = self::getDemoSession();

        $sessions = [];
        if (!empty($demo['session']['uid']) && $demo['session']['state'] !== 'COMPLETED' && $demo['session']['state'] !== 'VOID') {
            $sessions[] = [
                'uid' => $demo['session']['uid'],
                'session' => $demo['session']['session_id'],
                'state' => $demo['session']['state'],
                'customer' => [
                    'name' => $demo['session']['customer']['name'] ?? 'Ukendt',
                ],
                'dateFormat' => date('H:i', $demo['session']['created_at'] ?? time()),
                'hash' => md5(json_encode($demo['session'])),
                'link' => __url(Links::$demo->cashierDetails),
            ];
        }

        return Response()->json([
            'status' => 'success',
            'data' => [
                'sessions' => $sessions,
            ],
        ]);
    }

    /**
     * Create basket from merchant details form
     */
    public static function createBasket(array $args): array {
        $demo = self::getDemoSession();

        $name = $args['name'] ?? $args['POST']['name'] ?? 'Demo vare';
        $price = (float) ($args['price'] ?? $args['POST']['price'] ?? 100);
        $note = $args['note'] ?? $args['POST']['note'] ?? '';

        if ($price <= 0) {
            return Response()->jsonError('Prisen skal vaere storre end 0', 400);
        }

        $_SESSION['demo']['basket'] = [
            'uid' => 'demo-basket-' . uniqid(),
            'name' => $name,
            'price' => $price,
            'currency' => 'DKK',
            'status' => 'DRAFT',
            'note' => $note,
            'created_at' => time(),
        ];
        $_SESSION['demo']['session']['state'] = 'ACTIVE';
        $_SESSION['demo']['terminal']['state'] = 'AWAITING_CUSTOMER';

        return Response()->json([
            'status' => 'success',
            'data' => [
                'basket' => $_SESSION['demo']['basket'],
                'redirect' => __url(Links::$demo->cashierCheckout),
            ],
        ]);
    }

    /**
     * Get merchant session status for polling
     */
    public static function getMerchantSessionStatus(array $args): array {
        $demo = self::getDemoSession();

        $state = $demo['session']['state'] ?? 'IDLE';
        $statusTitle = match($state) {
            'PENDING' => 'Afventer',
            'ACTIVE' => 'Afventer kunde betaling',
            'COMPLETED' => 'Fulfort',
            'VOID' => 'Annulleret',
            default => 'Ukendt',
        };

        $redirect = null;
        if ($state === 'COMPLETED') {
            $redirect = __url(Links::$demo->cashierFulfilled);
        }

        return Response()->json([
            'status' => 'success',
            'data' => [
                'state' => $state,
                'statusTitle' => $statusTitle,
            ],
            'redirect' => $redirect,
            'message' => $state === 'COMPLETED' ? 'Kunden har betalt!' : null,
        ]);
    }

    /**
     * Simulate MitID login for consumer
     */
    public static function simulateLogin(array $args): array {
        $demo = self::getDemoSession();

        // Create fake customer if not exists
        if (empty($demo['session']['customer'])) {
            $customer = [
                'uid' => 'demo-cust-' . uniqid(),
                'name' => 'Anders Andersen',
                'full_name' => 'Anders Andersen',
                'email' => 'anders@example.dk',
                'phone' => '+45 12 34 56 78',
            ];

            // Create session if not exists
            if (empty($demo['session']['uid'])) {
                $_SESSION['demo']['session'] = [
                    'uid' => 'demo-sess-' . uniqid(),
                    'session_id' => self::generateSessionId(),
                    'state' => 'PENDING',
                    'customer' => $customer,
                    'created_at' => time(),
                ];
            } else {
                $_SESSION['demo']['session']['customer'] = $customer;
            }
        }

        $_SESSION['demo']['flow']['consumer_logged_in'] = true;

        return Response()->json([
            'status' => 'success',
            'data' => [
                'customer' => $_SESSION['demo']['session']['customer'],
                'redirect' => __url(Links::$demo->consumerInfo),
            ],
        ]);
    }

    /**
     * Get consumer basket for polling
     */
    public static function getConsumerBasket(array $args): array {
        $demo = self::getDemoSession();

        $basket = null;
        if (!empty($demo['basket']['uid']) && $demo['basket']['status'] === 'DRAFT') {
            $basket = $demo['basket'];
        }

        return Response()->json([
            'status' => 'success',
            'data' => [
                'basket' => $basket,
                'hasBasket' => !empty($basket),
            ],
        ]);
    }

    /**
     * Simulate payment from consumer
     */
    public static function simulatePayment(array $args): array {
        $demo = self::getDemoSession();

        if (empty($demo['basket']['uid'])) {
            return Response()->jsonError('Ingen kurv fundet', 400);
        }

        $planName = $args['plan'] ?? $args['POST']['plan'] ?? 'full';
        $price = (float) $demo['basket']['price'];

        // Generate order
        $orderUid = 'demo-order-' . uniqid();
        $orderPrid = 'WP-DEMO-' . strtoupper(substr(md5(uniqid()), 0, 8));

        $_SESSION['demo']['order'] = [
            'uid' => $orderUid,
            'prid' => $orderPrid,
            'payment_plan' => $planName,
            'status' => 'COMPLETED',
            'amount' => $price,
            'currency' => $demo['basket']['currency'],
        ];

        // Generate payments based on plan
        $payments = [];
        switch ($planName) {
            case 'pushed':
                // Pushed payment - pay on 1st of next month
                $firstOfNextMonth = new \DateTime('first day of next month');
                $payments[] = [
                    'uid' => 'demo-pay-' . uniqid(),
                    'installment_number' => 1,
                    'amount' => $price,
                    'currency' => $demo['basket']['currency'],
                    'status' => 'SCHEDULED',
                    'due_date' => $firstOfNextMonth->format('Y-m-d'),
                    'paid_at' => null,
                ];
                break;

            case 'installments':
                // 4 installments over 90 days
                $numInstallments = 4;
                $installmentAmount = round($price / $numInstallments, 2);

                for ($i = 1; $i <= $numInstallments; $i++) {
                    $amount = ($i === $numInstallments)
                        ? $price - ($installmentAmount * ($numInstallments - 1))
                        : $installmentAmount;

                    $dueDate = $i === 1 ? date('Y-m-d') : date('Y-m-d', strtotime('+' . ($i - 1) . ' month'));
                    $isFirst = $i === 1;

                    $payments[] = [
                        'uid' => 'demo-pay-' . $i . '-' . uniqid(),
                        'installment_number' => $i,
                        'amount' => $amount,
                        'currency' => $demo['basket']['currency'],
                        'status' => $isFirst ? 'COMPLETED' : 'SCHEDULED',
                        'due_date' => $dueDate,
                        'paid_at' => $isFirst ? date('Y-m-d H:i:s') : null,
                    ];
                }
                break;

            case 'direct':
            default:
                // Direct payment - full payment now
                $payments[] = [
                    'uid' => 'demo-pay-' . uniqid(),
                    'installment_number' => 1,
                    'amount' => $price,
                    'currency' => $demo['basket']['currency'],
                    'status' => 'COMPLETED',
                    'due_date' => date('Y-m-d'),
                    'paid_at' => date('Y-m-d H:i:s'),
                ];
                break;
        }

        $_SESSION['demo']['payments'] = $payments;
        $_SESSION['demo']['basket']['status'] = 'FULFILLED';
        $_SESSION['demo']['session']['state'] = 'COMPLETED';
        $_SESSION['demo']['terminal']['state'] = 'IDLE';

        return Response()->json([
            'status' => 'success',
            'data' => [
                'order' => $_SESSION['demo']['order'],
                'payments' => $payments,
                'redirect' => __url(Links::$demo->consumerConfirmation),
            ],
        ]);
    }

    /**
     * Reset demo session
     */
    public static function resetDemo(array $args): array {
        unset($_SESSION['demo']);

        return Response()->json([
            'status' => 'success',
            'data' => [
                'redirect' => __url(Links::$demo->landing),
            ],
        ]);
    }

}
