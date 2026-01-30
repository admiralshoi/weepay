<?php
namespace routing\routes;
use classes\Methods;
use classes\utility\Titles;
use http\Env\Response;

class LandingController {

    public static function home(array $args): mixed  {
        $args['meta'] = [
            'schema' => [
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => BRAND_NAME,
                'url' => __url(''),
                'logo' => __asset(OG_IMAGE),
                'description' => 'WeePay gør det nemt at betale senere eller dele betalingen op. Fleksible betalingsløsninger for forbrugere og forhandlere i Danmark.',
            ],
        ];
        return Views("LANDING_HOME", $args);
    }



    public static function mitIdTestSuccess(array $args): mixed  {
        $sessionId = $args["sessionId"];

        $clientId  = "sandbox-tricky-turtle-183";
        $clientSecret  = "aB8b34QPuOeGd7Sym4f7UbyHUBkTP7OwGma7KbGLrH8oUYUF";



        /**
         * oAuth 2.0
         */
        $authUrl = "https://api.signicat.com/auth/open/connect/token";
        $authBody = [
            'grant_type' => 'client_credentials',
            'scope' => 'signicat-api',
        ];

        $requests =  Methods::requests();
        $requests->basicAuth($clientId, $clientSecret);
        $requests->setHeaderContentTypeFormEncoded();
        $requests->setBody($authBody);
        $requests->post($authUrl);

        $responseBody = $requests->getResponse();
        $accessToken = $responseBody['access_token'];






        $sessionUrl = "https://api.signicat.com/auth/rest/sessions/$sessionId";
        $requests =  Methods::requests();
        $requests->setBearerToken($accessToken);
        $requests->setHeaderContentTypeJson();
        $requests->get($sessionUrl);

        $responseHeaders = $requests->getHeaders();
        $responseBody = $requests->getResponse();



        return Views("LANDING_MITID_TEST_SUCCESS", [$responseHeaders, $responseBody]);
    }
    public static function mitIdTest(array $args): mixed  {
        return Views("LANDING_MITID_TEST", $args);
    }
    public static function vivaTestReturnUrl(array $args): mixed  {
        return Views("LANDING_VIVA_TEST_RETURN", $args);
    }
    public static function vivaTest(array $args): mixed  {
        return Views("LANDING_VIVA_TEST", $args);
    }
    public static function login(array $args): mixed  {
        return Views("LANDING_LOGIN", $args);
    }

    public static function signup(array $args): mixed  {
        return Views("LANDING_SIGNUP", $args);
    }
    public static function affiliateSignup(array $args): mixed  {
        return Views("AFFILIATE_SIGNUP", $args);
    }

    public static function passwordRecovery(array $args): mixed  {
        return Views("LANDING_PASSWORD_RECOVERY", $args);
    }

    public static function consumerPrivacyPolicy(array $args): mixed  {
        $args['policy'] = self::getPolicyByVersionOrCurrent('consumer_privacy', $args);
        $args['previousPolicy'] = Methods::policyTypes()->getPreviousVersion('consumer_privacy');
        $args['fallbackTitle'] = 'Privatlivspolitik for forbrugere';
        $args['meta'] = [
            'schema' => self::buildWebPageSchema(
                'Privatlivspolitik for forbrugere',
                'Læs WeePays privatlivspolitik for forbrugere. Vi beskytter dine personlige data og forklarer hvordan vi behandler dine oplysninger.',
                __url('privacy/consumer')
            ),
        ];
        return Views("CONSUMER_PRIVACY_POLICY", $args);
    }

    public static function consumerTerms(array $args): mixed  {
        $args['policy'] = self::getPolicyByVersionOrCurrent('consumer_terms', $args);
        $args['previousPolicy'] = Methods::policyTypes()->getPreviousVersion('consumer_terms');
        $args['fallbackTitle'] = 'Handelsbetingelser for forbrugere';
        $args['meta'] = [
            'schema' => self::buildWebPageSchema(
                'Vilkår for forbrugere',
                'Læs WeePays handelsbetingelser og vilkår for forbrugere. Forstå dine rettigheder og forpligtelser når du bruger vores betalingsløsninger.',
                __url('terms/consumer')
            ),
        ];
        return Views("CONSUMER_TERMS", $args);
    }

    public static function merchantPrivacyPolicy(array $args): mixed  {
        $args['policy'] = self::getPolicyByVersionOrCurrent('merchant_privacy', $args);
        $args['previousPolicy'] = Methods::policyTypes()->getPreviousVersion('merchant_privacy');
        $args['fallbackTitle'] = 'Privatlivspolitik for forhandlere';
        $args['meta'] = [
            'schema' => self::buildWebPageSchema(
                'Privatlivspolitik for forhandlere',
                'Læs WeePays privatlivspolitik for forhandlere. Vi beskytter dine virksomhedsdata og forklarer hvordan vi behandler dine oplysninger.',
                __url('privacy/merchant')
            ),
        ];
        return Views("MERCHANT_PRIVACY_POLICY", $args);
    }

    public static function merchantTerms(array $args): mixed  {
        $args['policy'] = self::getPolicyByVersionOrCurrent('merchant_terms', $args);
        $args['previousPolicy'] = Methods::policyTypes()->getPreviousVersion('merchant_terms');
        $args['fallbackTitle'] = 'Vilkår for forhandlere';
        $args['meta'] = [
            'schema' => self::buildWebPageSchema(
                'Vilkår for forhandlere',
                'Læs WeePays handelsbetingelser og vilkår for forhandlere. Forstå dine rettigheder og forpligtelser som partner.',
                __url('terms/merchant')
            ),
        ];
        return Views("MERCHANT_TERMS", $args);
    }

    public static function cookiesPolicy(array $args): mixed  {
        $args['policy'] = self::getPolicyByVersionOrCurrent('cookies', $args);
        $args['previousPolicy'] = Methods::policyTypes()->getPreviousVersion('cookies');
        $args['fallbackTitle'] = 'Cookiepolitik';
        $args['meta'] = [
            'schema' => self::buildWebPageSchema(
                'Cookiepolitik',
                'Læs om WeePays brug af cookies. Vi forklarer hvilke cookies vi bruger og hvordan de hjælper med at forbedre din oplevelse.',
                __url('cookies')
            ),
        ];
        return Views("COOKIES_POLICY", $args);
    }

    /**
     * Build WebPage schema for public pages
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
     * Get policy by version number if provided, otherwise get current version
     * Falls back to current version if the specified version doesn't exist
     */
    private static function getPolicyByVersionOrCurrent(string $type, array $args): ?object {
        if (isset($args['version']) && is_numeric($args['version'])) {
            $version = (int) $args['version'];
            $policy = Methods::policyVersions()->getByTypeAndVersion($type, $version);
            // Fall back to current version if specified version doesn't exist
            if (!isEmpty($policy)) {
                return $policy;
            }
        }
        return Methods::policyTypes()->getCurrentVersion($type);
    }

    public static function faqConsumer(array $args): mixed  {
        $faqs = Methods::faqs()->getGroupedByCategory('consumer', true);
        $args['faqs'] = $faqs;
        $args['meta'] = ['schema' => self::buildFaqSchema($faqs, 'FAQ - Forbrugere')];
        return Views("FAQ_CONSUMER", $args);
    }

    public static function faqMerchant(array $args): mixed  {
        $faqs = Methods::faqs()->getGroupedByCategory('merchant', true);
        $args['faqs'] = $faqs;
        $args['meta'] = ['schema' => self::buildFaqSchema($faqs, 'FAQ - Forhandlere')];
        return Views("FAQ_MERCHANT", $args);
    }

    /**
     * Build FAQPage schema from FAQ data
     */
    private static function buildFaqSchema(array $faqs, string $name = 'FAQ'): array {
        $faqItems = [];
        foreach ($faqs as $category => $categoryFaqs) {
            foreach ($categoryFaqs as $faq) {
                $faq = toArray($faq);
                $question = $faq['title'] ?? null;
                $answer = $faq['content'] ?? null;

                if ($question && $answer) {
                    $faqItems[] = [
                        '@type' => 'Question',
                        'name' => $question,
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => strip_tags($answer),
                        ],
                    ];
                }
            }
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'name' => $name,
            'mainEntity' => $faqItems,
        ];
    }

    public static function guideMerchantOnboarding(array $args): mixed {
        $args['meta'] = [
            'schema' => self::buildWebPageSchema(
                'Kom godt i gang som forhandler',
                'Guide til at komme i gang som forhandler hos WeePay. Lær trin-for-trin hvordan du opsætter din butik og begynder at tilbyde fleksible betalinger.',
                __url('guide/merchant-onboarding')
            ),
        ];
        return Views("GUIDE_MERCHANT_ONBOARDING", $args);
    }

}