<?php

namespace STPH\massSendIt;

use Exception;
use ReflectionClass;
use ReflectionProperty;

class BulkModel {

    private const TABLE_NAME = 'bulk';
    private static $module;


    public int $bulk_id;
    public int $bulk_order;
    public string $bulk_title;
    public string $bulk_type;

    public array $bulk_recipients;
    public ?string $bulk_recipients_list;
    public ?string $bulk_recipients_logic;

    public int $file_repo_folder_id;
    public string $file_repo_extension;
    public string $file_repo_reference;

    public string $email_display;
    public string $email_from;
    public string $email_to;
    public string $email_first_subject;
    public string $email_first_message;
    public bool $use_random_pass;
    public ?string $custom_pass_field;
    public bool $use_second_email;
    public ?string $email_second_subject;
    public ?string $email_second_message;
    
    public string $bulk_schedule;
    public ?string $bulk_expiration;

    public static function test() {
        return static::$module->getProjectId();
    }

    public function __construct($module) {
        $this->module = $module;
    }

    private function getPublicProperties() {
        $reflection = new ReflectionClass($this);
        $vars = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        $publicProperties = [];

        foreach ($vars as $publicVar) {
            $publicProperties[] = $publicVar->getName();
        }

        return $publicProperties;
    }

    public function getFields() {
        return implode(",", $this->getPublicProperties());
    }

    public function readBulk($bulk_id) {
        $fields = $this->getFields();
        $sql = "SELECT $fields WHERE table_name='BULK' AND bulk_id = ?";
                
        $result = $this->module->queryLogs($sql, [$bulk_id]);
        if($result->num_rows == 0) {
            return false;
        }

        $bulk = $result->fetch_object();

        return $bulk;
    }

    public function createBulk($validated) {
        
        dump($validated);
        if($this->readBulk($validated->bulk_id) !== false) {
            throw new Exception("bulk_id $validated->bulk_id already exists. Cannot create bulk with same bulk_id!");
        }

        $basic_params = array(
            "table_name" => self::TABLE_NAME,
            "project_id" => $validated->project_id,
            "event_id" => $validated->event_id,
            "record" => null
        );

        // cast validated object to array, so we can merge it with other params
        $bulk_params = (array) $validated;

        $not_implemented_params = array(
            "bulk_order" => $bulk_params["bulk_id"] - 1 //  set order same as id until implemented
        );

        //  merge all params
        $merged_params = array_merge($basic_params, $bulk_params, $not_implemented_params);

        $created = $this->module->log("bulk_create", $merged_params);

        if(!$created) {
            throw new Exception("Unknown error: Bulk could not be created.");
        }

        return $this->readBulk($validated->bulk_id);

    }


    public function updateBulk($validated) {

        //  check difference
        $bulk_old = $this->readBulk($validated->bulk_id);
        if(!$bulk_old) {
            throw new Exception("bulk with bulk_id $validated->bulk_id does not exist! Aborting update.");
        }
        
        $diff = array_diff((array)$validated, (array) $bulk_old);
        if(count($diff) == 0) {
            throw new Exception("No difference found! Aborting update.");
        }
        if(in_array("bulk_id", $diff)) {
            throw new Exception("Cannot change bulk_id! Aborting update.");
        }

        $errors = [];
        foreach ($diff as $key => $value) {            
            if(!$this->updateQuery($value, $key, $validated->bulk_id)) {
                $errors[] = $key;
            }
        }

        if(count($errors) > 0) {
            throw new Exception("The update query was partially unsuccessful. Following keys did not update: " .  implode(",", $errors));
        }

        //  return bulk
        $params = $this->readBulk($validated->bulk_id);

        //  mock log_id and params
        $log_id = "Foo";
        return array($log_id, $params);
    }

    public function deleteBulk($bulk_id) {
        
        if(empty($bulk_id)) {
            throw new Exception("bulk_id must not be null");
        }

        $where = "table_name = ? and bulk_id = ?";
        $removedBulk = $this->module->removeLogs($where, [self::TABLE_NAME, $bulk_id]);

        if($removedBulk != 1) {
            throw new Exception("Bulk with bulk_id $bulk_id not found. ");
        }

        //  additionally delete schedules of this bulk
        $where = "table_name = 'SCHEDULE' and bulk_id = ?";
        $this->module->removeLogs($where, [$bulk_id]);
    }

    private function updateQuery($value, $key, $bulk_id) {

        $sql = "UPDATE redcap_external_modules_log_parameters AS to_change 
                INNER JOIN redcap_external_modules_log_parameters AS bulk_id
                ON to_change.log_id = bulk_id.log_id 
                INNER JOIN redcap_external_modules_log_parameters AS table_name
                ON to_change.log_id = table_name.log_id
                SET to_change.value = ? 
                WHERE to_change.name = ? 
                AND bulk_id.name = 'bulk_id'
                AND bulk_id.value = ?
                AND table_name.name = 'table_name'
                AND table_name.value = ?";
        
        return $this->module->query($sql, [$value, $key, $bulk_id, self::TABLE_NAME]);
        
    }
}