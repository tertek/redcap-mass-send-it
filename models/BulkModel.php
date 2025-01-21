<?php

namespace STPH\massSendIt;

use Exception;

if (!class_exists("ActionModel")) require_once(__DIR__ ."/ActionModel.php");

class BulkModel extends ActionModel {

    private const TABLE_NAME = 'bulk';
    private static $module;
    private $project_id;


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


    public function __construct($module) {
        $this->module = $module;
        $this->project_id = $this->module->getProjectId();
    }

    public function readBulk($bulk_id) {        
        $fields = $this->getFields();
        $sql = "SELECT $fields WHERE table_name='BULK' AND bulk_id = ? and project_id=?";
               
        $result = $this->module->queryLogs($sql, [$bulk_id, $this->project_id]);
        if($result->num_rows == 0) {
            return false;
        }

        $bulk = $result->fetch_object();

        //  decode special chars, so that tinymce preview does not break
        $bulk->email_first_message = htmlspecialchars_decode($bulk->email_first_message, ENT_QUOTES);
        $bulk->email_second_message = htmlspecialchars_decode($bulk->email_second_message, ENT_QUOTES);

        return $bulk;
    }

    public function createBulk($validated) {
        
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

        //dump($validated);
        //  check difference
        $bulk_old = $this->readBulk($validated->bulk_id);
        if(!$bulk_old) {
            throw new Exception("bulk_model_error: bulk with bulk_id $validated->bulk_id does not exist! Aborting update.");
        }

        //  decode special chars, difference does not break
        $validated->email_first_message = htmlspecialchars_decode($validated->email_first_message, ENT_QUOTES);
        $validated->email_second_message = htmlspecialchars_decode($validated->email_second_message, ENT_QUOTES);
        
        $diff = array_diff((array)$validated, (array) $bulk_old);
        if(count($diff) == 0) {
            throw new Exception("no difference found! Aborting update.");
        }
        if(in_array("bulk_id", $diff)) {
            throw new Exception("cannot change bulk_id! Aborting update.");
        }

        /**
         * DEBUGGING
         */
        //return array("diff" => $diff, "bulk_old" => $bulk_old, "validated" => $validated);

        $errors = [];
        foreach ($diff as $key => $value) {      
            //  no need to escape again, since we are using calculated difference from $validated
            if(!$this->updateQuery($value, $key, $validated->bulk_id)) {
                $errors[] = $key;
            }
        }

        if(count($errors) > 0) {
            throw new Exception("bulk_model_error: the update query was partially unsuccessful. Following keys did not update: " .  implode(",", $errors));
        }

        //  return bulk
        return $this->readBulk($validated->bulk_id);
    }

    public function deleteBulk($bulk_id) {
        
        if(empty($bulk_id)) {
            throw new Exception("bulk_model_error: bulk_id must not be null");
        }

        //  remove bulk
        $where = "table_name = ? and bulk_id = ?";
        $removedBulk = $this->module->removeLogs($where, [self::TABLE_NAME, $bulk_id]);

        if($removedBulk != 1) {
            throw new Exception("bulk_model_error: bulk with bulk_id $bulk_id not found. ");
        }

        //  remove schedules
        $where = "table_name = 'schedule' and bulk_id = ?";
        $removedSchedules = $this->module->removeLogs($where, [$bulk_id]);

        // remove notifications
        $where = "table_name = 'notification' and bulk_id = ?";
        $removedNotifications = $this->module->removeLogs($where, [$bulk_id]);

        return array($removedSchedules, $removedNotifications);
    }

    private function updateQuery($value, $key, $bulk_id) {

        $sql = "UPDATE redcap_external_modules_log_parameters AS to_change 
                INNER JOIN redcap_external_modules_log_parameters AS bulk_id
                ON to_change.log_id = bulk_id.log_id 
                INNER JOIN redcap_external_modules_log_parameters AS table_name
                ON to_change.log_id = table_name.log_id
                INNER JOIN redcap_external_modules_log AS bulk
                ON to_change.log_id = bulk.log_id
                SET to_change.value = ? 
                WHERE to_change.name = ? 
                AND bulk_id.name = 'bulk_id'
                AND bulk_id.value = ?
                AND table_name.name = 'table_name'
                AND table_name.value = ?
                AND bulk.project_id = ?
                AND bulk.message = 'bulk_create'";
        
        return $this->module->query(
            $sql, 
            [$value, $key, $bulk_id, self::TABLE_NAME, $this->project_id]
        );        
    }
}