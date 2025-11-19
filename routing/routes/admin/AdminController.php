<?php
namespace routing\routes\admin;
use classes\Methods;
use classes\utility\Numbers;
use Database\Collection;
use Database\model\UserRoles;
use Database\model\Users;
use features\Migration;
use features\Settings;
use GuzzleHttp\Psr7\Response;
use JetBrains\PhpStorm\NoReturn;
use routing\routes\api\WebhookController;

class AdminController {



    #[NoReturn] public static function testWebhook(array $args): void {
        $file = $args["file"];
        if(!file_exists(ROOT . $file)) Response()->jsonError("Invalid file");
        $hook = json_decode(file_get_contents(ROOT . $file), true);

        if(TESTING) WebhookController::metaForward($hook, false);
        else WebhookController::meta($hook, false);
    }


    public static function home(array $args): mixed  {
        return Views("ADMIN_HOME", $args);
    }
    public static function appSettings(array $args): mixed  {
        return Views("APP_SETTINGS", $args);
    }

    public static function cookieManager(array $args): mixed  {
        return Views("COOKIE_MANAGER", ["cookies" => Methods::cookieManager()->getCookieDisplayList()]);
    }




    public static function affiliates(array $args): mixed  {
        $userHandler = Methods::affiliates();
        $periodHandler = Methods::affiliatePaymentPeriod();
        $resultsHandler = Methods::affiliatePayoutResults();

        $users = $userHandler->getByX([], [
            "username", "full_name", "created_at", "uid", "deactivated", "email",
            "payout_bank_info","address_country", "address_region", "address_city", "address_zip", "address_street"
        ]);

        /*
         * STAT CARDS SECTION
         */
        $period = $periodHandler->getCurrentPeriod();
        $totalPaid = $resultsHandler->totalPaid();
        $totalContribution = $resultsHandler->totalContribution();
        $totalContributingUsers = $resultsHandler->contributorCount();

        $unpaidRows = $resultsHandler->getByX(["payout_status" => "pending"]);
        $totalUnpaid = $unpaidRows->reduce(function ($initial, $row) {
            return (!isset($initial) ? 0 : $initial) + $row["payout"];
        });
        $unpaidAffiliateCount = $unpaidRows->count();
        $periodContributingUsers = $periodContributions = $totalPayoutPool  = 0;

        if(!isEmpty($period)) {
            $periodContributingUsers = $userHandler->getPeriodContributingUsersCount($period);
            $periodContributions = $userHandler->getAllSuccessPeriodContributionCount($period);
            $totalPayoutPool = $period->total_payout;
        }



        /*
         * UNPAID USER SECTION
         */
        $unpaidUsers = [];
        if(!isEmpty($unpaidRows)) {
            foreach ($unpaidRows->list() as $row) {
                $user = $users->filter(function ($user) use ($row) {
                    return (int)$user["uid"] === $row->affiliate_id;
                })->first();
                if(isEmpty($user)) {
                    $resultsHandler->setStatusClosed($row->id);
                    continue;
                }

                $payoutInfo = $user->payout_bank_info;
                if(!isEmpty($payoutInfo) && property_exists($payoutInfo, "bank_country")) {
                    $payoutInfo->bank_country_name = Methods::misc()::countryCodeToName($payoutInfo->bank_country);
                    $user->payout_bank_info = $payoutInfo;
                }

                $unpaidUsers[] = array_merge(
                    toArray($row),
                    toArray($user),
                );;
            }
        }
        $unpaidUsers = new Collection($unpaidUsers);

        /*
         * ALL USERS SECTION
         */
        $users = $users->map(function ($user) use ($period, $resultsHandler, $userHandler)  {
            $uid = $user["uid"];
            $user["total_contribution"] = $resultsHandler->userTotalContribution($uid);
            $user["total_paid"] = $resultsHandler->userTotalEarnings($uid);
            $user["period_contribution"] = isEmpty($period) ? 0 : $userHandler->getSuccessPeriodContributionCount($uid, $period);
            return $user;
        });

        return Views("ADMIN_AFFILIATES", compact(
            "users", "totalPaid", "totalContribution", 'totalContributingUsers',
            'totalUnpaid', 'unpaidAffiliateCount', 'periodContributingUsers', 'periodContributions',
            'unpaidUsers', "period", "totalPayoutPool"
        ));
    }




    public static function users(array $args = []): mixed  {
        $view = array_key_exists("role", $args) ? $args["role"] : "all";
        $userRoles = UserRoles::where("defined", 1)->where("access_level", "<=", __accessLevel())->select(["access_level","name"])->all();
        $query = Users::where("access_level", "<=", __accessLevel());
        $accessLevels = match($view) {
            default => 0,
            "creators" => [1,3],
            "brands" => [2,4],
            "admins" => [8,9]
        };
        if($accessLevels !== 0) $query->where("access_level", $accessLevels);
        $query->select(["id", "username", "access_level", "full_name", "created_at", "uid", "deactivated"]);
        $users = $query->paginate(100, 0, "id","ASC");





        $users = $users->map(function ($user) use ($userRoles) {
            $accessLevel = (int)$user["access_level"];
            $name = "";
            foreach ($userRoles->list() as $role) {
                if((int)$role->access_level === $accessLevel) {
                    $name = $role->name;
                    break;
                }
            }
            return array_merge($user, ["role" => $name]);
        });

        return Views("ADMIN_USERS", compact("view", "users", "userRoles"));
    }







    public static function scraperLogs(array $args): mixed  {
        return Views("SCRAPER_LOG", []);
    }






    public static function logList(array $args = [], array $list = []): mixed  {
        if(empty($list)) $list = [
            "AFFILIATE_PAY_PERIOD" => "Cronjob - Affiliate payment period",
            "HASHTAG_TRACKING" => "Cronjob - Hashtag Tracking",
            "MEDIA_UPDATE" => "Cronjob - Campaign Media Updates",
            "TAG_MENTIONS" => "Cronjob - Tag Mentions",
            "ACCOUNT_INSIGHTS" => "Cronjob - Account Analytics",
            "EVENT_MODE" => "Cronjob - Event",
            "ERRORS" => "Errors ",
            "DEBUGGING" => "Debugging ",
            "REQUESTS" => "Api requests ",
            "WEBHOOK" => "Webhooks",
            "MIGRATION" => "Migration",
        ];

        return Views("LOG_LIST", compact("list"));
    }

    public static function logView(array $args): mixed  {
        $type = strtoupper($args["type"]);
        $content = [];
        $timer = "";
        $cronLogs = ['HASHTAG_TRACKING', 'MEDIA_UPDATE', 'TAG_MENTIONS', 'ACCOUNT_INSIGHTS', "EVENT_MODE", "AFFILIATE_PAY_PERIOD"];
        $normalLogs = ['ERRORS', 'DEBUGGING', 'REQUESTS', 'WEBHOOK', "MIGRATION"];
        if(!in_array($type, $cronLogs) && !in_array($type, $normalLogs)) return self::logList();


        if(in_array($type, $cronLogs)) {
            $logFiles = Methods::cronLogFiles($args["type"]);
            if(!empty($logFiles)) {
                $content = file_exists($logFiles["log"]) ? file_get_contents($logFiles["log"]) : "";
                $dates = file_exists($logFiles["date"]) ? file_get_contents($logFiles["date"]) : "";
                $dates = explode(PHP_EOL,$dates);
                $lastDate = 0;
                for($x = count($dates)-1; $x >= 0; $x--) {
                    if(!empty($dates[$x])) {
                        $lastDate = (int)$dates[$x];
                        break;
                    }
                }
                $timer = empty($lastDate) ? "" : Numbers::translateSeconds(time() - $lastDate);
            }
        }
        elseif(in_array($type, $normalLogs)) {
            $base = ROOT . "logs/";
            $base .= match ($type) {
                default => "",
                "ERRORS" => "errors/",
                "DEBUGGING" => "debug/",
                "REQUESTS" => "requests/",
                "WEBHOOK" => "webhook/",
                "MIGRATION" => "migration/",
            };
            if(!is_dir($base)) return self::logList();
            $dir = "";
            if(array_key_exists("month", $args)) $dir .= "/" . $args["month"];
            if(array_key_exists("day", $args)) $dir .= "/" . $args["day"];
            $endpoint = ADMIN_PANEL_PATH . "/logs/" . $args["type"] . $dir;


            if(!(array_key_exists("month", $args) && array_key_exists("day", $args))) {
                if(!array_key_exists("month", $args) && !array_key_exists("day", $args))
                    $content = directoryContent($base ."$dir/", true);
                else $content = directoryContent($base . "$dir/");

                $content = array_map(function ($fn) use ($endpoint) {
                    $name = explode(".", $fn)[0];
                    return [
                        "name" => str_contains($name, "-") ? "Month: $name" : "Day: $name",
                        "endpoint" => "$endpoint/$name"
                    ];
                }, $content);


                return self::logList([], $content);
            }


            $fn = $base . $dir . ".log";
            $content = file_exists($fn) ? file_get_contents($fn) : "";
        }


        $content = empty($content) ? [] : array_reverse(explode(PHP_EOL, $content));
        return Views("LOG_VIEW", compact("timer", "content", "type"));
    }




    #[NoReturn] public static function migrationInit(): mixed  {
        Settings::$migrating =  true;
        migrationLog("Step 1/3: Backup production files and ", "migration-init");
        Migration::_migrate_do_backup();
        $_SESSION["migrating"] = MIGRATION_TOKEN;
        migrationLog("Step 1/3: COMPLETE", "migration-init");
        return self::returnJsonResponse([
            "next" => "migration/move",
            "status" => "success",
        ]);
    }


    #[NoReturn] public static function migrationMoveFiles(): mixed  {
        Settings::$migrating =  true;
        migrationLog("Step 2/3: Replacing production files", "migration-init");
        Migration::_migrate_do_move_files();
        migrationLog("Step 2/3: COMPLETE", "migration-init");
        return self::returnJsonResponse([
            "next" => "migration/db",
            "status" => "success"
        ]);
    }


    #[NoReturn] public static function migrationDb(): mixed  {
        Settings::$migrating =  true;
        migrationLog("Step 3/3: Run db-migration", "migration-init");
        Migration::_migrate_do_db();
        removeSessions();
        migrationLog("Step 3/3: COMPLETE", "migration-init");
        return self::returnJsonResponse([
            "redirect" => __url(""),
            "status" => "success"
        ]);
    }





    private static function returnJsonResponse(string|array|null $res, int $responseCode = 200): array {
        return [
            "result" => $res,
            "response_code" => $responseCode
        ];
    }

}