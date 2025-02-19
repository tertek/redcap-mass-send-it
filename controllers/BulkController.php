<?php

namespace STPH\massSendIt;

use Exception;
use REDCap;
use DateTimeRC;
use Project;

class BulkController extends ActionController {

    const TABLE_NAME = "bulk";

    protected $module;
    protected $project_id;
    protected $data;

    public function __construct($module, $project_id=null) {
        parent::__construct($module, $project_id);
    }

    public function action(string $task, array $data) {           
        try {
            $this->data = (object) $data;
            $response = $this->mapTasks($task);
        } catch (\Throwable $th) {
            //dump($th);
            return $this->getActionError($th->getMessage());
        }

        return $this->getActionSuccess($response);
        
    }

    private function mapTasks($task) {        
        switch ($task) {
            case 'create':
                return $this->createTask();
                break;

            case 'read':
                return $this->readTask();
                break;                

            case 'update':
                return $this->updateTask();
                break;

            case 'delete':
                return $this->deleteTask();
                break;

            default:
                throw new Exception("action not yet implemented");
                break;
        }
    }

    private function createTask() {

        $validated = $this->validate();
        $bulk = $this->store($validated);

        return array("bulk" => $bulk);
    }

    private function readTask() {
        $bulk_id = $this->data->bulk_id;
        $bulkModel = new BulkModel($this->module);
        $bulk = $bulkModel->readBulk($bulk_id);

        if(!$bulk) {
            throw new Exception("bulk with bulk_id $bulk_id not found");
        }

        return array("bulk" => $bulk);
    }

    private function updateTask() {    
        $validated = $this->validate();
        $bulk = $this->store($validated, true);
        
        return array("bulk" => $bulk);
    }

    private function deleteTask() {
        $bulk_id = $this->data->bulk_id;

        $bulkModel = new BulkModel($this->module);
        list($removedSchedules, $removedNotifications) = $bulkModel->deleteBulk($bulk_id);

        return array(
            "bulk_id" => $bulk_id, 
            "removed_schedules" => $removedSchedules, 
            "removed_notifications" => $removedNotifications
        );
    }    

    private function store($validated, $isUpdate = false) {
        //dump("validated\n", $validated);
        $bulkModel = new BulkModel($this->module);
        if($isUpdate === true) {
            $bulk = $bulkModel->updateBulk($validated);
        } else {
            $bulk = $bulkModel->createBulk($validated);
        }        
        return $bulk;
    }

    private function validate() {
        //  use validation helper methods
        $validationHelper = new validationHelper($this->project_id);
        $validated = (object) array();

        $form_data = $this->data->form_data;
        if(empty($form_data)) {
            throw new Exception("validation_error: form_data must not be empty");
        }
 
        //  set payload array from sanitized form data object
        $payload = [];
        foreach ( json_decode($form_data) as $key => $item) {
            $payload[$item->name] = htmlspecialchars($item->value, ENT_QUOTES);
        }

        //  set bulk_id
        if(isset($payload["bulk_id"]) && isset($payload["is_edit_mode"]) && $payload["is_edit_mode"] == "true" ) {
            $validated->bulk_id = $payload["bulk_id"];
        } elseif(!isset($payload["bulk_id"]) && isset($payload["is_edit_mode"]) && $payload["is_edit_mode"] == "true") {
            throw new Exception("validation_error: bulk_id must be set in edit_mode");
        } else {            
            $validated->bulk_id = $this->get_max_key_id() + 1;
        }

        //  set bulk_order
        if(!isset($payload["bulk_order"]) && isset($payload["bulk_id"]) && isset($payload["is_edit_mode"]) && $payload["is_edit_mode"] == "true") {
            throw new Exception("validation_error: bulk_order must not be empty");
        }
        $validated->bulk_order = $payload["bulk_order"];

        //  set bulk_title
        if(empty($payload["bulk_title"])) {
            $validated->bulk_title = "New Bulk " . $validated->bulk_id;
        } else {
            $validated->bulk_title = $payload["bulk_title"];
        }

        // set bulk_type
        if(empty($payload["bulk_type"])) {
            throw new Exception("validation_error: bulk_type must not be empty");
        }
        $validated->bulk_type = $payload["bulk_type"];
        
        //  set recipients
        $validated->bulk_recipients_list = "";
        $validated->bulk_recipients_logic = "";
        if($validated->bulk_type == "list") {            
            $validated->bulk_recipients_list = $payload["bulk_recipients_list"];            
            $recipients = $validationHelper->validateRecipientsList($validated->bulk_recipients_list);
            $validated->bulk_recipients = serialize($recipients);
        } else if ($validated->bulk_type == "logic") {       
            $validated->bulk_recipients_logic = $payload["bulk_recipients_logic"];
            $recipients = $validationHelper->validateRecipientsLogic($validated->bulk_recipients_logic);
            $validated->bulk_recipients = serialize($recipients);
        } else {
            throw new Exception("validation_error: bulk type must be either 'list' or 'logic'");
        }

        //  set file file_repo_folder_id
        if(empty($payload["file_repo_folder_id"])) {
            throw new Exception("validation_error: file_repo_folder_id must not be empty");
        }
        $validated->file_repo_folder_id = (int) $payload["file_repo_folder_id"];

        //  set file file_repo_extension
        if(empty($payload["file_repo_extension"])) {
            throw new Exception("validation_error: file_repo_extension must not be empty");
        }
        $validated->file_repo_extension = $payload["file_repo_extension"];

        //  set file file_repo_reference
        if(empty($payload["file_repo_reference"])) {
            throw new Exception("validation_error: file_repo_reference must not be empty");
        }
        $validated->file_repo_reference = $payload["file_repo_reference"];
        //  check if file_repo_reference field exists, not blank and referenced documents exist
        $validationHelper->validateFileRepoReference(
            $validated->file_repo_reference, 
            $validated->file_repo_extension,
            $validated->file_repo_folder_id,
            $recipients
        );

        $validated->email_display = $payload["email_display"];
        $validated->email_from = $payload["email_from"];
        $validated->email_to = $payload["email_to"];
        $validated->email_first_subject = $payload["email_first_subject"];
        $validated->email_first_message = $payload["email_first_message"];
        $validated->email_second_subject = "";
        $validated->email_second_message = "";

        $validated->custom_pass_field = "";
        if($payload["password_type"] == "random") {
            $validated->use_random_pass = "1";
        } else {
            //  TBD: validate custom_pass_field
            $validated->use_random_pass = "0";
            $validated->custom_pass_field = $payload["custom_pass_field"];
        }

        if($payload["use_second_email"] == "yes") {
            $validated->use_second_email = "1";
            $validated->email_second_subject = $payload["email_second_subject"];
            $validated->email_second_message = $payload["email_second_message"];
        } else {
            $validated->use_second_email = "0";
        }

        $validated->bulk_schedule = DateTimeRC::format_ts_to_ymd($payload["bulk_schedule"]);
        $validated->bulk_expiration = DateTimeRC::format_ts_to_ymd($payload["bulk_expiration"]);

        //  validate download_page_index: check if index exists in project's settings
        if(isset($payload["download_page_index"])) {
            $cdpi = $this->module->escape($payload["download_page_index"]);
            if(!array_key_exists($cdpi, $this->module->getSubSettings("project-custom-download-page")) && $cdpi !== "") {
                throw new Exception("validation_error: download_page_index $cdpi does not exist");
            }
            $validated->download_page_index = $cdpi;            
        }

        return $validated;

    }
}