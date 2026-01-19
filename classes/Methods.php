<?php
namespace classes;

use classes\organisations\LocationMemberHandler;
use classes\utility\QrHandler;
use JetBrains\PhpStorm\Pure;
use classes\notifications\NotificationHandler;
use classes\auth\PasswordHandler;
use classes\utility\SortByKey;
use classes\auth\UserCreation;
use classes\auth\LocalAuthentication;
use classes\auth\LocalSignup;
use classes\auth\TwoFactorAuth;
use classes\user\Roles;
use classes\app\Meta;
use classes\user\Sidebars;
use classes\utility\Misc;
use Database\Collection;
use classes\user\UserHandler;
use classes\http\CronRequestHandler;
use classes\app\EventTracker;
use classes\app\CronWorker;
use classes\data\MediaStream;
use classes\http\Proxy;
use classes\app\UploadsHandler;
use classes\api\OpenAi;
use classes\data\Calculate;
use classes\payments\PaymentHandler;
use classes\organisations\OrganisationMemberHandler;
use classes\organisations\OrganisationHandler;
use classes\payments\ProcessOrder;
use classes\app\CountryHandler;
use classes\app\TaskManager;
use classes\app\App;


class Methods {









    public static function toCollection(array|object $items = []): Collection { return new Collection($items); }
    public static function qr(): QrHandler { return new QrHandler(); }
    public static function vivaConnectedAccounts(): \classes\organisations\VivaConnectedAccountsHandler { return new \classes\organisations\VivaConnectedAccountsHandler(); }
    public static function oidcAuthentication(): \classes\auth\OidcAuthentication { return new \classes\auth\OidcAuthentication(); }
    public static function oidcSession(): \classes\auth\OidcSessionHandler { return new \classes\auth\OidcSessionHandler(); }
    public static function viva(): \classes\api\Viva { return new \classes\api\Viva(); }
    public static function reCaptcha(): \classes\api\ReCaptcha { return new \classes\api\ReCaptcha(); }
    public static function signicact(): \classes\api\Signicat { return new \classes\api\Signicat(); }
    public static function gatewayApi(): \classes\api\GatewayApi { return new \classes\api\GatewayApi(); }
    public static function checkoutBasket(): \classes\organisations\CheckoutBasketHandler { return new \classes\organisations\CheckoutBasketHandler(); }
    public static function orders(): \classes\payments\OrderHandler { return new \classes\payments\OrderHandler(); }
    public static function payments(): \classes\payments\PaymentsHandler { return new \classes\payments\PaymentsHandler(); }
    public static function paymentMethods(): \classes\payments\PaymentMethodsHandler { return new \classes\payments\PaymentMethodsHandler(); }
    public static function paymentProviders(): \classes\payments\PaymentProvidersHandler { return new \classes\payments\PaymentProvidersHandler(); }
    public static function terminals(): \classes\organisations\TerminalHandler { return new \classes\organisations\TerminalHandler(); }
    public static function terminalSessions(): \classes\organisations\TerminalSessionHandler { return new \classes\organisations\TerminalSessionHandler(); }
    public static function locations(): \classes\organisations\LocationHandler { return new \classes\organisations\LocationHandler(); }
    public static function requests(): \classes\http\Requests { return new \classes\http\Requests(); }

    public static function publicContactForm(): \classes\data\forms\PublicContactFormHandler { return new \classes\data\forms\PublicContactFormHandler(); }
    #[Pure] public static function app(): App { return new App(); }
    #[Pure] public static function taskManager(): TaskManager { return new TaskManager(); }
    #[Pure] public static function countries(): CountryHandler { return new CountryHandler(); }
    #[Pure] public static function processOrder(): ProcessOrder { return new ProcessOrder(); }
    #[Pure] public static function locationMembers(): LocationMemberHandler { return new LocationMemberHandler(); }
    #[Pure] public static function organisationMembers(): OrganisationMemberHandler { return new OrganisationMemberHandler(); }
    #[Pure] public static function organisations(): OrganisationHandler { return new OrganisationHandler(); }
    #[Pure] public static function organisationFees(): \classes\organisations\OrganisationFeesHandler { return new \classes\organisations\OrganisationFeesHandler(); }
    #[Pure] public static function locationPages(): \classes\organisations\LocationPagesHandler { return new \classes\organisations\LocationPagesHandler(); }
    #[Pure] public static function paymentHandler(): PaymentHandler { return new PaymentHandler(); }
    #[Pure] public static function calc(): Calculate { return new Calculate(); }
    #[Pure] public static function openAi(): OpenAi { return new OpenAi(); }
    #[Pure] public static function uploadsHandler(): UploadsHandler { return new UploadsHandler(); }
    #[Pure] public static function proxy(): Proxy { return new Proxy(); }
    #[Pure] public static function mediaStream(): MediaStream { return new MediaStream(); }
    public static function eventTracker(): EventTracker { return new EventTracker(); }
    #[Pure] public static function cronRequestHandler(): CronRequestHandler { return new CronRequestHandler(); }
    #[Pure] public static function users(): UserHandler { return new UserHandler(); }
    #[Pure] public static function misc(): Misc { return new Misc(); }
    #[Pure] public static function sidebars(): Sidebars { return new Sidebars(); }
    public static function roles(): Roles { return new Roles(); }
    #[Pure] public static function appMeta(): Meta { return new Meta(); }
    public static function localAuthentication(): LocalAuthentication { return new LocalAuthentication(); }
    public static function localSignup(): LocalSignup { return new LocalSignup(); }
    public static function twoFactorAuth(): TwoFactorAuth { return new TwoFactorAuth(); }
    #[Pure] public static function cronWorker(string $type = ""): CronWorker { return new CronWorker($type); }
    public static function cronLogFiles(string $type): ?array { return (new CronWorker())->getLogFiles($type); }
    public static function userCreation(array $params, bool $thirdParty = false): bool { return UserCreation::run($params, $thirdParty); }
    #[Pure] public static function userCreationError(): ?array { return UserCreation::$error; }
    #[Pure] public static function passwordHandler(): PasswordHandler { return new PasswordHandler(); }
    #[Pure] public static function notificationHandler(): NotificationHandler { return new NotificationHandler(); }

    // Notification System Handlers
    public static function notificationTemplates(): \classes\notifications\NotificationTemplateHandler { return new \classes\notifications\NotificationTemplateHandler(); }
    public static function notificationBreakpoints(): \classes\notifications\NotificationBreakpointHandler { return new \classes\notifications\NotificationBreakpointHandler(); }
    public static function notificationFlows(): \classes\notifications\NotificationFlowHandler { return new \classes\notifications\NotificationFlowHandler(); }
    public static function notificationFlowActions(): \classes\notifications\NotificationFlowActionHandler { return new \classes\notifications\NotificationFlowActionHandler(); }
    public static function notificationQueue(): \classes\notifications\NotificationQueueHandler { return new \classes\notifications\NotificationQueueHandler(); }
    public static function notificationLogs(): \classes\notifications\NotificationLogHandler { return new \classes\notifications\NotificationLogHandler(); }
    public static function userNotifications(): \classes\notifications\UserNotificationHandler { return new \classes\notifications\UserNotificationHandler(); }
    public static function sortByKey(&$arr,$key = "", $ascending = false, array $specialReplacement = array(), array $splitReplace = array(), $key2 = ""): void {
        (new SortByKey())->run($arr, $key, $ascending, $specialReplacement, $splitReplace, $key2); }
    public static function hasAccess(string $type, string $name, int $actionType, string|int $requestingLevel): bool {
        return true; }







    public static function isAdmin(string|int $accessLevel = 0): bool { return self::roles()->isAdmin($accessLevel); }
    public static function isConsumer(string|int $accessLevel = 0): bool { return self::roles()->isConsumer($accessLevel); }
    public static function isMerchant(string|int $accessLevel = 0): bool { return self::roles()->isMerchant($accessLevel); }
    public static function isGuest(string|int $accessLevel = 0): bool { return self::roles()->isGuest($accessLevel); }
    public static function registrationIsComplete(): bool { return self::users()->registrationIsComplete(); }
    public static function integrationUnderway(): bool { return self::users()->integrationUnderway(); }



}