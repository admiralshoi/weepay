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
            "log_file" => CRON_LOGS."cronLog_take_payments.log",
            "log_date_file" => CRON_LOGS."cronDate_take_payments.log",
            "log_memory_file" => CRON_LOGS."cronMemory_take_payments.log",
            "row_id" => 'crn_take_payments',
            "time_gab" => 10, // 10 seconds for testing
            "max_run_time" => (60 * 10), // 10 min max
            "sleep_timer" => 10 // 10 seconds for testing
        ),
        "retry_payments" => array(
            "log_file" => CRON_LOGS."cronLog_retry_payments.log",
            "log_date_file" => CRON_LOGS."cronDate_retry_payments.log",
            "log_memory_file" => CRON_LOGS."cronMemory_retry_payments.log",
            "row_id" => 'crn_retry_payments',
            "time_gab" => 10, // 10 seconds for testing
            "max_run_time" => (60 * 10), // 10 min max
            "sleep_timer" => 10 // 10 seconds for testing
        ),
        "cleanup_logs" => array(
            "log_file" => CRON_LOGS."cronLog_cleanup_logs.log",
            "log_date_file" => CRON_LOGS."cronDate_cleanup_logs.log",
            "log_memory_file" => CRON_LOGS."cronMemory_cleanup_logs.log",
            "row_id" => 'crn_cleanup_logs',
            "time_gab" => 10, // 10 seconds for testing
            "max_run_time" => (60 * 5), // 5 min max
            "sleep_timer" => 10 // 10 seconds for testing
        ),
        "payment_notifications" => array(
            "log_file" => CRON_LOGS."cronLog_payment_notifications.log",
            "log_date_file" => CRON_LOGS."cronDate_payment_notifications.log",
            "log_memory_file" => CRON_LOGS."cronMemory_payment_notifications.log",
            "row_id" => 'crn_payment_notifications',
            "time_gab" => 10, // 10 seconds for testing
            "max_run_time" => (60 * 10), // 10 min max
            "sleep_timer" => 10 // 10 seconds for testing
        ),
        "notification_queue" => array(
            "log_file" => CRON_LOGS."cronLog_notification_queue.log",
            "log_date_file" => CRON_LOGS."cronDate_notification_queue.log",
            "log_memory_file" => CRON_LOGS."cronMemory_notification_queue.log",
            "row_id" => 'crn_notification_queue',
            "time_gab" => 10, // 10 seconds for testing
            "max_run_time" => (60 * 5), // 5 min max
            "sleep_timer" => 10 // 10 seconds for testing
        ),
        "rykker_checks" => array(
            "log_file" => CRON_LOGS."cronLog_rykker_checks.log",
            "log_date_file" => CRON_LOGS."cronDate_rykker_checks.log",
            "log_memory_file" => CRON_LOGS."cronMemory_rykker_checks.log",
            "row_id" => 'crn_rykker_checks',
            "time_gab" => 10, // 10 seconds for testing
            "max_run_time" => (60 * 10), // 10 min max
            "sleep_timer" => 10 // 10 seconds for testing
        ),
        "weekly_reports" => array(
            "log_file" => CRON_LOGS."cronLog_weekly_reports.log",
            "log_date_file" => CRON_LOGS."cronDate_weekly_reports.log",
            "log_memory_file" => CRON_LOGS."cronMemory_weekly_reports.log",
            "row_id" => 'crn_weekly_reports',
            "time_gab" => 10, // 10 seconds for testing
            "max_run_time" => (60 * 15), // 15 min max
            "sleep_timer" => 10 // 10 seconds for testing
        ),
    );

    function __construct(string $type = "") {
        parent::__construct(Cronjob::newStatic(), "cronjob");
        $this->type = $type;
    }


    public function getLogFiles(string $type = ""): ?array {
        if(!empty($type)) $this->type = $type;
        if(!array_key_exists($this->type, $this->typesList)) return null;
        $item = $this->typesList[$this->type];
        return [
            "log" => $item["log_file"],
            "date" => $item["log_date_file"],
            "memory" => $item["log_memory_file"],
        ];
    }

    /**
     * Get all cronjob types configuration
     */
    public function getTypesList(): array {
        return $this->typesList;
    }

    public function log($string,$init = false):void {
        $type = $this->typesList[$this->type];
        if($init) {
            if(file_exists($type["log_date_file"])) {
                $dates = file_get_contents($type["log_date_file"]);
                $dates = explode(PHP_EOL,$dates);
            }
            else $dates = [];
            if(count($dates) >= CRON_LOG_MAX_ENTRIES && !empty($dates[(CRON_LOG_MAX_ENTRIES-1)])) {
                cronLog("", $type["log_date_file"], false);
                cronLog("", $type["log_file"], false);
                cronLog("", $type["log_memory_file"], false);
            }
            cronLog(time(), $type["log_date_file"]);
            cronLog(PHP_EOL."<b style='font-size: 20px;'>Log initiation at => ".date("d/m-Y H:i:s",time())."</b>", $type["log_file"]);
            $this->memoryLog("",true);
        } else {
            cronLog($string, $type["log_file"]);
        }
    }
    public function memoryLog(string $keyWord="", $init = false):void{
        $type = $this->typesList[$this->type];
        if($init) {
            cronLog("", $type["log_memory_file"]);
            return;
        }

        $date = date("d/m-Y H:i:s",time());
        $string = $date." => ".memory_get_usage()." of total " . memory_get_usage(true) . " (".$keyWord.") ";
        cronLog($string, $type["log_memory_file"]);
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
