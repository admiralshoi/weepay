<?php
namespace features;

class Settings {
    public static bool $migrating = false;
    public static bool $omnipotent = false;
    public static bool $testing = false;
    public static object $app;
    public static array $postData = [];
    public static ?string $testerAuth = null;



    public static bool $knownRole = false;
    public static bool $isGuest = false;
    public static bool $isAdmin = false;
    public static bool $isConsumer = false;
    public static bool $isMerchant = false;


    public static bool $viewingAdminDashboard = false;
    public static bool $viewingOrganisationDashboard = false;
    public static ?array $encryptionDetails = [];


    public static ?object $user = null;
    public static ?object $organisation = null;

    // Admin impersonation
    public static bool $impersonatingOrganisation = false;
    public static ?string $impersonatedOrganisationId = null;

    // Cookie consent (GDPR)
    public static bool $cookiesAccepted = false;





}