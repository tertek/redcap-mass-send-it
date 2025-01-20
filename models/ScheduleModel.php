<?php

namespace STPH\massSendIt;


class ScheduleModel extends ActionModel {

    private static $module;
    private const TABLE_NAME = 'schedule';
    private $project_id;

    public $bulk_id;
    public $schedule_id;
    public $status;
    public $send_time;
    public $creation_date;
    public $message_type;
    public $record;

    public function __construct($module) {
        $this->module = $module;
        $this->project_id = $this->module->getProjectId();
    }

    function readScheduled($bulk_id) {
        
        $sql = "SELECT schedule_id WHERE table_name = ? and bulk_id = ? and project_id=?";
        $result = $this->module->queryLogs($sql, [self::TABLE_NAME, $bulk_id, $this->project_id]);
        $scheduled = [];
        while($row = $result->fetch_object()) {
            $scheduled[] = $row->schedule_id;
        }

        return $scheduled;
    }

    function createSchedule($bulk_id) {
        $scheduled = [];

        //  Get bulk
        $sql = "SELECT bulk_recipients, bulk_schedule, use_second_email WHERE table_name = 'bulk' and bulk_id = ?";
        $result = $this->module->queryLogs($sql, [$bulk_id]);
        $bulk = $result->fetch_object();        
        
        //  Get max_schedule_id
        $max_schedule_id = $this->get_max_key_id();


        //  Prepare message types
        $message_types = ["primary"];
        if($bulk->use_second_email) {
            $message_types[] = "secondary";
        }

        //  prepare recipients
        $recipients = unserialize($bulk->bulk_recipients);

        //  loop through records and schedule emails
        foreach ($recipients as $recipient_key => $record) {

            for ($i=0; $i < count($message_types); $i++) { 
                //  Store in database
                $schedule_parameters = array(
                    "table_name" => self::TABLE_NAME,
                    "project_id" => $this->project_id,
                    "bulk_id" => $bulk_id,
                    "schedule_id" => $max_schedule_id + $recipient_key + $i + 1,
                    "status" => "IDLE",
                    "record" => $record,
                    "send_time" => $bulk->bulk_schedule,
                    "creation_date" => date('Y-m-d H:i:s'),
                    "message_type" => $message_types[$i]
                );               

                $log_id = $this->module->log('schedule_create', $schedule_parameters);
                $scheduled[] = $log_id;                
            }
            $max_schedule_id = $max_schedule_id + 1;
        }
        
        return $scheduled;
    }

    public function deleteScheduleByBulk($bulk_id) {
        $where = "table_name = ? and bulk_id = ?";
        $removeSchedules = $this->module->removeLogs($where, [self::TABLE_NAME, $bulk_id]);

        return $removeSchedules;
    }

    protected function get_max_key_id() {
        $key = static::TABLE_NAME;
        $sql_get_max_key_id = "SELECT max(cast({$key}_id.value AS UNSIGNED)) AS max_key_id 
            from redcap_external_modules_log
            left join redcap_external_modules_log_parameters {$key}_id
            on {$key}_id.log_id = redcap_external_modules_log.log_id
            and {$key}_id.name = '{$key}_id'
            left join redcap_external_modules_log_parameters table_name
            on table_name.log_id = redcap_external_modules_log.log_id
            and table_name.name = 'table_name'
            WHERE redcap_external_modules_log.external_module_id = (SELECT external_module_id FROM redcap_external_modules WHERE directory_prefix = '{$this->module->getModulePrefix()}') and (table_name.value = '{$key}' and redcap_external_modules_log.project_id = ?)";
        $result = $this->module->query($sql_get_max_key_id, [$this->project_id]);
        $max_key_id = $result->fetch_object()->max_key_id ?? 0;
        return $max_key_id;
    }

}