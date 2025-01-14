<?php

namespace STPH\massSendIt;

abstract class ActionController {

    const TABLE_NAME = self::TABLE_NAME;

    protected $module;
    protected $project_id;
    protected $event_id;

    public function __construct() {
        $this->project_id = null;
        $this->event_id = null;
        $this->module = (object) [];
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

    protected function get_max_key_id() {
        $key = static::TABLE_NAME;    
        $sql_get_max_key_id = "SELECT max({$key}_id) as max_key_id WHERE table_name = '{$key}' and project_id=?";
        $result = $this->module->queryLogs($sql_get_max_key_id, [$this->project_id]);
        $max_key_id = $result->fetch_object()->max_key_id ?? 0;
        
        return $max_key_id;
    }
}