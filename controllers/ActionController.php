<?php

namespace STPH\massSendIt;

use Throwable;

abstract class ActionController {

    const TABLE_NAME = self::TABLE_NAME;

    protected $module;
    protected $project_id;

    public function __construct($module, $project_id=null) {
        $this->module = $module;
        
        /**
         * Set $project_id only if not called from cron job
         * missing phpunit check
         */
        empty($project_id) ? $this->project_id = $module->getProjectId() : $this->project_id = $project_id;

        if(!isset($_GET['pid'])) {
            $_GET['pid'] = $this->project_id;
        }        
    }
   
    protected function getActionError($msg) {
        //static::$module->log("Error: " . $msg);
        return array(
            "error" => true, 
            "message"=> $msg,
            "callback" => null
        );
    }

    protected function getActionSuccess($data, $callback=null) {
        return array(
            "error" => false,
            "data" => $data,
            "callback" => $callback
        );
    }

    protected function formatError(Throwable $th ) {
        $file = $th->getFile();
        $line = $th->getLine();
        $code = $th->getCode();
        $message = $th->getMessage();

        $output = "File: $file \nLine: $line\nMessage: $message";

        $format = "\n\e[0;31;40m$output\e[0m\n\n";

        return $format;
    }

    /**
     * 
     * DANGER: max only works properly for number above 9, if the column is casted correctly!
     */
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
            WHERE redcap_external_modules_log.external_module_id = (SELECT external_module_id FROM redcap_external_modules WHERE directory_prefix = 'mass_send_it') and (table_name.value = '{$key}' and redcap_external_modules_log.project_id = ?)";
        $result = $this->module->query($sql_get_max_key_id, [$this->project_id]);
        $max_key_id = $result->fetch_object()->max_key_id ?? 0;
        return $max_key_id;
    }
}