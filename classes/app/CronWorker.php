<?php
namespace classes\app;
use classes\utility\Crud;
use Database\model\Cronjob;

class CronWorker extends Crud {
    private string $type = "";
    public int $timeStamp = 0;
    private object|array $info = [];
    protected bool $log = true;
    private array $typesList = array(
        "take_payments" => array(
            "row_id" => 'crn_take_payments',
            "time_gab" => 10, // 10 seconds for testing
            "max_run_time" => (60 * 10), // 10 min max
            "sleep_timer" => 10 // 10 seconds for testing
        ),
        "retry_payments" => array(
            "row_id" => 'crn_retry_payments',
            "time_gab" => 10, // 10 seconds for testing
            "max_run_time" => (60 * 10), // 10 min max
            "sleep_timer" => 10 // 10 seconds for testing
        ),
        "payment_notifications" => array(
            "row_id" => 'crn_payment_notifications',
            "time_gab" => 10, // 10 seconds for testing
            "max_run_time" => (60 * 10), // 10 min max
            "sleep_timer" => 10 // 10 seconds for testing
        ),
        "notification_queue" => array(
            "row_id" => 'crn_notification_queue',
            "time_gab" => 10, // 10 seconds for testing
            "max_run_time" => (60 * 5), // 5 min max
            "sleep_timer" => 10 // 10 seconds for testing
        ),
        "rykker_checks" => array(
            "row_id" => 'crn_rykker_checks',
            "time_gab" => 10, // 10 seconds for testing
            "max_run_time" => (60 * 10), // 10 min max
            "sleep_timer" => 10 // 10 seconds for testing
        ),
        "weekly_reports" => array(
            "row_id" => 'crn_weekly_reports',
            "time_gab" => 10, // 10 seconds for testing
            "max_run_time" => (60 * 15), // 15 min max
            "sleep_timer" => 10 // 10 seconds for testing
        ),
        "policy_publish" => array(
            "row_id" => 'crn_policy_publish',
            "time_gab" => 60, // 1 minute between runs
            "max_run_time" => (60 * 5), // 5 min max
            "sleep_timer" => 30 // 30 seconds between cycles
        ),
        "system_cleanup" => array(
            "row_id" => 'crn_system_cleanup',
            "time_gab" => 3600, // 1 hour minimum between runs
            "max_run_time" => (60 * 15), // 15 min max
            "sleep_timer" => 60 // 1 minute between cycles
        ),
    );

    function __construct(string $type = "") {
        parent::__construct(Cronjob::newStatic(), "cronjob");
        $this->type = $type;
    }


    /**
     * Get log file paths for a specific type and date
     * @param string $type Cronjob type (optional if already set)
     * @param string|null $date Date in Y-m-d format (default: today)
     */
    public function getLogFiles(string $type = "", ?string $date = null): ?array {
        if(!empty($type)) $this->type = $type;
        if(!array_key_exists($this->type, $this->typesList)) return null;

        $date = $date ?? date('Y-m-d');
        return [
            "log" => CRON_LOGS . "cronLog_{$this->type}_{$date}.log",
            "date" => CRON_LOGS . "cronDate_{$this->type}_{$date}.log",
            "memory" => CRON_LOGS . "cronMemory_{$this->type}_{$date}.log",
        ];
    }

    /**
     * Get available log dates for a cronjob type
     * @param string $type Cronjob type
     * @return array Array of dates (Y-m-d format) sorted most recent first
     */
    public function getAvailableLogDates(string $type = ""): array {
        if(!empty($type)) $this->type = $type;
        if(empty($this->type)) return [];

        $pattern = CRON_LOGS . "cronLog_{$this->type}_*.log";
        $files = glob($pattern);
        $dates = [];

        foreach ($files as $file) {
            if (preg_match('/cronLog_' . preg_quote($this->type, '/') . '_(\d{4}-\d{2}-\d{2})\.log$/', $file, $m)) {
                $dates[] = $m[1];
            }
        }

        rsort($dates); // Most recent first
        return $dates;
    }

    /**
     * Cleanup old cron log files
     * @param int $daysOld Delete logs older than this many days (default: 5)
     * @return int Number of files deleted
     */
    public function cleanupOldLogs(int $daysOld = 5): int {
        $cutoff = date('Y-m-d', strtotime("-{$daysOld} days"));
        $deleted = 0;

        // Clean up date-based log files
        foreach (['cronLog_*', 'cronDate_*', 'cronMemory_*'] as $prefix) {
            $files = glob(CRON_LOGS . $prefix . '_*.log');
            foreach ($files as $file) {
                if (preg_match('/_(\d{4}-\d{2}-\d{2})\.log$/', $file, $m)) {
                    if ($m[1] < $cutoff) {
                        @unlink($file);
                        $deleted++;
                    }
                }
            }
        }

        // Also clean up old format logs without date suffix (legacy cleanup)
        foreach ($this->typesList as $type => $config) {
            $legacyFiles = [
                CRON_LOGS . "cronLog_{$type}.log",
                CRON_LOGS . "cronDate_{$type}.log",
                CRON_LOGS . "cronMemory_{$type}.log",
            ];
            foreach ($legacyFiles as $file) {
                if (file_exists($file)) {
                    @unlink($file);
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    /**
     * Get all cronjob types configuration
     */
    public function getTypesList(): array {
        return $this->typesList;
    }

    public function log($string,$init = false):void {
        $logFiles = $this->getLogFiles();
        if(!$logFiles) return;

        if($init) {
            if(file_exists($logFiles["date"])) {
                $dates = file_get_contents($logFiles["date"]);
                $dates = explode(PHP_EOL,$dates);
            }
            else $dates = [];
            if(count($dates) >= CRON_LOG_MAX_ENTRIES && !empty($dates[(CRON_LOG_MAX_ENTRIES-1)])) {
                cronLog("", $logFiles["date"], false);
                cronLog("", $logFiles["log"], false);
                cronLog("", $logFiles["memory"], false);
            }
            cronLog(time(), $logFiles["date"]);
            cronLog(PHP_EOL."<b style='font-size: 20px;'>Log initiation at => ".date("d/m-Y H:i:s",time())."</b>", $logFiles["log"]);
            $this->memoryLog("",true);
        } else {
            cronLog($string, $logFiles["log"]);
        }
    }

    public function memoryLog(string $keyWord="", $init = false):void{
        $logFiles = $this->getLogFiles();
        if(!$logFiles) return;

        if($init) {
            cronLog("", $logFiles["memory"]);
            return;
        }

        $date = date("d/m-Y H:i:s",time());
        $string = $date." => ".memory_get_usage()." of total " . memory_get_usage(true) . " (".$keyWord.") ";
        cronLog($string, $logFiles["memory"]);
    }


    public function canRun(): bool {
        $type = $this->typesList[$this->type];
        $params = array("can_run" => 1, "uid" => $type["row_id"]);
        return $this->exists($params) && (($this->timeStamp + $type["max_run_time"]) > time());
    }

    public function init(int $stamp, bool $forceStart = false): bool {
        if(!array_key_exists($this->type,$this->typesList)) return false;

        $this->timeStamp = $stamp;
        $access_level = 8;
        $rowId =  $this->typesList[$this->type]["row_id"];
        $this->info = $this->get($rowId);
        $type = $this->typesList[$this->type];

        if((int)$this->info->access_level > $access_level) return false;

        if($forceStart) {
            $setValues = array("is_running" => 1, "started_at" => $stamp, "can_run" => 1);
            $this->update($setValues, ['uid' => $rowId]);

            $this->log("",true);
            return true;
        }



        if((int)$this->info->can_run === 1 && (int)$this->info->is_running === 0) {
            $slept = ($stamp-(int)$this->info->finished_at);
            if($type["sleep_timer"] > $slept) {//Ensures that at least x minutes pass in between pauses
                $this->log("Pause ends in ".($type["sleep_timer"] - $slept)." seconds");
//                sleep(($type["sleep_timer"] - $slept));
                return false;
            }

            $setValues = array("is_running" => 1, "started_at" => $stamp);
            $this->update($setValues, ['uid' => $rowId]);

            $this->log("<b><h5>Ending break and resuming... => ".date("d/m-Y H:i:s")."</h5></b>");
        } elseif(((int)$this->info->can_run === 0 && (int)$this->info->is_running === 0) ||
            (int)$this->info->can_run === 1 && (int)$this->info->is_running === 1) {
            $min_time_gab = $type["time_gab"]; //Min 24 hours
            $time_diff = $stamp - (int)$this->info->started_at;

            if($min_time_gab > $time_diff) return false;

            $setValues = array("is_running" => 1, "started_at" => $stamp, "can_run" => 1);
            $this->update($setValues, ['uid' => $rowId]);
        }  else {
            $this->end();
            return false;
        }

        $this->log("",true);
        return true;
    }

    public function end(): void {
        $setValues = array("is_running" => 0, "finished_at" => time(), "can_run" => 0);
        $this->update($setValues, ['uid' => $this->typesList[$this->type]["row_id"]]);
    }

    public function pause(): bool {
        $tableType = "cronJob";
        $rowId = $this->typesList[$this->type]["row_id"];
        $info = $this->get($rowId);
        if(((int)$info->can_run === 0 && (int)$info->is_running === 0)) {
            $this->log("<u>The cronjob was manually terminated.</u>");
            return false;
        }

        $setValues = array("is_running" => 0, "finished_at" => time(), "can_run" => 1);
        $this->update($setValues, ['uid' => $rowId]);
        $this->log("<u>Pausing cronJob => ".date("d/m-Y H:i:s")."</u>");
        return true;
    }


    public function finishedAt(): int { return property_exists($this->info, "finished_at") ? (int)$this->info->finished_at : 0; }


}
