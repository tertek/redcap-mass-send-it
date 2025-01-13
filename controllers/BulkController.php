<?php

namespace STPH\massSendIt;

include_once("ActionController.php");

use Exception;
use REDCap;
use DateTimeRC;

class BulkController extends ActionController {

    const TABLE_NAME = "BULK";

    static $module;
    private $project_id;
    private $event_id;

    private $data;

    public function __construct($module, $project_id=null, $event_id=null) {
        static::$module = $module;
        if(empty($project_id)) {
            $this->project_id = $module->getProjectId();
        } else {
            $this->project_id = $project_id;
        }

        if(empty($event_id)) {
            $this->event_id = $module->getEventId();
        } else {
            $this->event_id = $event_id;
        }
        
    }

    public function action($name, $data) {
        
        $this->data = $data;
        switch ($name) {
            case 'create':
                $response = $this->create();
                break;
            
            default:
                throw new Exception("Action not yet implemented");
                break;
        }

        return $response;
    }

    private function create() {
      
        try {
            $validated = $this->validate();
            $log_id = $this->store($validated);
            // Create schedules

        } catch (\Throwable $th) {
            $error = $this->getActionError($th->getMessage());
        }

        $response = array("bulk" => $validated, "log_id" => $log_id);

        // tbd: return schedule count instead
        return $this->getActionSuccess($response);

    }

    private function store($validated) {
        $serialized_recipients = serialize($validated->bulk_recipients);
        $validated->bulk_recipients = $serialized_recipients;
        $bulk_parameters = (array) $validated;

        $basic_parameters = array(
            "table_name" => self::TABLE_NAME,
            "project_id" => $this->project_id,
            "event_id" => $this->event_id,
            "record" => null
        );

        $not_implemented_params = array(
            "bulk_order" => $bulk_parameters["bulk_id"] - 1 //  set order same as id until implemented
        );
        
        $parameters = array_merge($basic_parameters, $bulk_parameters, $not_implemented_params);

        return static::$module->log("bulk_create", $parameters);

    }

    private function validate() {

        $validated = (object) array();

        if(empty($this->data->payload)) {
            throw new Exception("Payload must not be empty");
        }
 
        //  set payload object from form data
        $decoded = [];
        foreach ( json_decode($this->data->payload) as $key => $item) {
            $decoded[$item->name] = $item->value;
        }
        
        //  sanitize form data after json decode
        $payload = static::$module->escape($decoded);

        //  set bulk_id
        if(isset($payload["bulk_id"])) {
            $validated->bulk_id = $payload["bulk_id"];
        } else {
            $validated->bulk_id = $this->get_max_key_id() + 1;
        }

        //  set bulk_title
        if(empty($payload["bulk_title"])) {
            $validated->bulk_title = "New Bulk " . $validated->bulk_id;
        } else {
            $validated->bulk_title = $payload["bulk_title"];
        }

        // set bulk_type
        if(empty($payload["bulk_type"])) {
            throw new Exception("bulk_type must not be empty");
        }
        $validated->bulk_type = $payload["bulk_type"];

        
        //  set recipients
        if($validated->bulk_type == "list") {            
            $validated->bulk_recipients_list = $payload["bulk_recipients_list"];
            $recipients = explode(",",  $payload["bulk_recipients_list"]);

            //  validate recipients
            if(count($recipients) == 0) {
                throw new Exception("Recipients count must be greater than 0.");
            }

            //  check if records exist
            $params = array(
                'project_id'=>$this->project_id,
                'return_format' => 'array',
                'fields'=>array('record_id'), 
                'records' => $recipients
            );            
            $records = array_keys(REDCap::getData($params));

            //  indicate non-existing records
            if(count($records) != count($recipients)) {                
                $diff = array_diff($recipients, $records);
                throw new Exception("Records in record list must exist. Non-exsiting records: " . implode(",", $diff));
            }
                
            $validated->bulk_recipients = $recipients;

        } else if ($validated->bulk_type == "logic") {       

            $validated->bulk_recipients_logic = $payload["bulk_recipients_logic"];

            $params = array(
                'project_id'=>$this->project_id,
                'return_format' => 'array',
                'fields'=>array('record_id'), 
                'filterLogic' => $validated->bulk_recipients_logic
            );    
            $recipients = array_keys(REDCap::getData($params));

            //  validate logic result (should be at least one record)
            if(count($recipients) == 0) {
                throw new Exception("Recipients count must be greater than 0.");
            }

            $validated->bulk_recipients = $recipients;
        } else {
            throw new Exception("Bulk type must be either 'list' or 'logic'");
        }

        //  set file file_repo_folder_id
        //  tbd: check folder access permission for project_id
        if(empty($payload["file_repo_folder_id"])) {
            throw new Exception("file_repo_folder_id must not be empty");
        }
        $validated->file_repo_folder_id = (int) $payload["file_repo_folder_id"];

        //  set file file_repo_extension
        if(empty($payload["file_repo_extension"])) {
            throw new Exception("file_repo_extension must not be empty");
        }
        $validated->file_repo_extension = $payload["file_repo_extension"];

        //  set file file_repo_reference
        if(empty($payload["file_repo_reference"])) {
            throw new Exception("file_repo_reference must not be empty");
        }
        $validated->file_repo_reference = $payload["file_repo_reference"];

        //  check for all records if file_repo_reference field not isblankormissingcode
        $params = array(
            'project_id'=>$this->project_id,
            'return_format' => 'array',
            'records' => $validated->bulk_recipients,
            'fields'=>array($payload["file_repo_reference"], 'record_id'),
            'filterLogic' => 'isblankormissingcode(['.$payload["file_repo_reference"].']) = false'
        ); 
        $references = REDCap::getData($params);

        //  indicate blank or missing reference fields per record
        if(count($references) != count($recipients)) {
            $diff = array_diff($recipients, array_keys($references));
            throw new Exception("File Repository reference must not be empty for all records! Empty field ".$validated->file_repo_reference ." in  records: " . implode(",", $diff));
        }

        //  check if all referenced documents exists
        foreach ($references as $key => $value) {
            $el = reset($value);
            $document_reference = $el[$validated->file_repo_reference];
            $document_name = $document_reference . "." . $validated->file_repo_extension;

            $sql = "SELECT d.docs_name as docName, d.docs_name as storedName, d.docs_size as docSize, d.docs_id as fileId, f.folder_id as folderId, d.docs_type as docType FROM redcap_docs as d JOIN redcap_docs_folders_files AS f ON d.docs_id=f.docs_id WHERE d.project_id = ? AND f.folder_id = ? AND d.docs_name = ?";

            $q = static::$module->query($sql, [$this->project_id, $validated->file_repo_folder_id, $document_name]);
            
            if($q->num_rows == 0) {
                $document_not_found[] = $document_name . " (record_id: " . $el["record_id"] . " )";
            }
        }

        //  indicate missing documents
        if(!empty($document_not_found)) {                       
            throw new Exception("Documents must exist for all referenced records! Following documents (with record_id) could not be found:<br>" . implode(",", $document_not_found));
        }

        $validated->email_display = $payload["email_display"];
        $validated->email_from = $payload["email_from"];
        $validated->email_to = $payload["email_to"];
        $validated->email_first_subject = $payload["email_first_subject"];
        $validated->email_first_message = $payload["email_first_message"];

        if($payload["password_type"] == "random") {
            $validated->use_random_pass = true;
        } else {
            $validated->use_random_pass = false;
            $validated->custom_pass_field = $payload["custom_pass_field"];
        }

        if($payload["use_second_email"] == "yes") {
            $validated->use_second_email = true;
            $validated->email_second_subject = $payload["email_second_subject"];
            $validated->email_second_message = $payload["email_second_message"];
        } else {
            $validated->use_second_email = false;
        }

        $validated->bulk_schedule = DateTimeRC::format_ts_to_ymd($payload["bulk_schedule"]);
        $validated->bulk_expiration = DateTimeRC::format_ts_to_ymd($payload["bulk_expiration"]);       

        return $validated;

    }
}