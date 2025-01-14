<?php

namespace STPH\massSendIt;

abstract class ActionController {

    const TABLE_NAME = self::TABLE_NAME;

    private $module;
    private $project_id;
    private $event_id;
    
    protected function getActionError($msg) {
        //static::$module->log("Error: " . $msg);
        return array(
            "error" => true, 
            "message"=> $msg
        );
    }

    protected function getActionSuccess($data) {
        return array(
            "error" => false,
            "data" => $data
        );
    }

    protected function get_max_key_id() {
        $key = static::TABLE_NAME;
        $sql_get_max_key_id = "SELECT max({$key}_id) as max_key_id WHERE table_name = '{$key}' and project_id=? and event_id=?";
        $result = static::$module->queryLogs($sql_get_max_key_id, [$this->project_id, $this->event_id]);
        $max_key_id = $result->fetch_object()->max_key_id ?? 0;

        return $max_key_id;
    }
}