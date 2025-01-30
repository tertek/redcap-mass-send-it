<?php namespace STPH\massSendIt;

use Exception;
use Project;
use REDCap;
use ExternalModules\ExternalModules;

class validationHelper {

    private $project;

    public function __construct($project_id) {
        $this->project = new Project($project_id);
    }

    public function validateRecipientsLogic($bulk_recipients_logic) {
        
        $params = array(
            'project_id'=>$this->project->project_id,
            //'event_id' => $this->event_id,
            'return_format' => 'array',
            'fields'=>array('record_id'), 
            'filterLogic' => $bulk_recipients_logic
        );    
        $recipients = array_keys(REDCap::getData($params));

        //  validate logic result (should be at least one record)
        if(count($recipients) == 0) {
            throw new Exception("validation_error: recipients count must be greater than 0.");
        }

        return $recipients;
    }

    public function validateRecipientsList($bulk_recipients_list) {
        $recipients = explode(",",  $bulk_recipients_list);

        //  validate recipients
        if(count($recipients) == 0) {
            throw new Exception("validation_error: recipients count must be greater than 0.");
        }

        //  check if records exist
        $params = array(
            'project_id'=>$this->project->project_id,
            //'event_id' => $this->event_id,
            'return_format' => 'array',
            'fields'=>array('record_id'), 
            'records' => $recipients
        );
        $records = array_keys(REDCap::getData($params));

        //  indicate non-existing records
        if(count($records) != count($recipients)) {                
            $diff = array_diff($recipients, $records);
            throw new Exception("validation_error: records in record list must exist. Non-exsiting records: " . implode(",", $diff));
        }
        return $recipients;
    }

    public function validateFileRepoReference($fieldTag, $fileExtension, $folderID, $recipients) {

        list($fieldName, $eventName) = $this->parseFieldTags($fieldTag);

        //  check field
        $project_fields = array_keys($this->project->metadata);

        if(!in_array($fieldName, $project_fields)) {
            throw new Exception("validation_error: file_repo_reference ".$fieldTag." does not exist on project with id ".$this->project->project_id.".");
        }

        //  check event 
        if($eventName !== null) {
            $eventNames = $this->project->getUniqueEventNames();
            $eventId = array_search($eventName, $eventNames);

            //  check if event exists
            if(!$eventId) {
                throw new Exception("validation_error: file_repo_reference '$fieldTag' does not have event '.$eventName.' on project with id ".$this->project->project_id.".");
            }

            $form_name = $this->project->metadata[$fieldName]["form_name"];
            $eventForms = $this->project->eventsForms[$eventId];
            if(!in_array($form_name, $eventForms)){
                throw new Exception("validation_error: file_repo_reference '$fieldTag' with form $form_name is not designated to '$eventName' ($eventId) on project with id ".$this->project->project_id.".");
            }

        }

        //  check for all records if file_repo_reference field not isblankormissingcode
        $params = array(
            'project_id'=>$this->project->project_id,
            'return_format' => 'array',
            'records' => $recipients,
            'fields'=>array($fieldName, 'record_id'),
            'filterLogic' => 'isblankormissingcode('.$fieldTag.') = false',
            'events' => $eventId
        ); 
        $references = REDCap::getData($params);

        //  indicate blank or missing reference fields per record
        if(count($references) != count($recipients)) {
            $diff = array_diff($recipients, array_keys($references));
            throw new Exception("validation_error: rile repository reference must not be empty for all records! Empty field ".$fieldTag ." in  records: " . implode(",", $diff));
        }

        //  check if all referenced documents exists
        foreach ($references as $key => $value) {
            $el = reset($value);
            $document_reference = $el[$fieldName];
            $document_name = $document_reference . "." . $fileExtension;

            $sql = "SELECT d.docs_name as docName, d.docs_name as storedName, d.docs_size as docSize, d.docs_id as fileId, f.folder_id as folderId, d.docs_type as docType FROM redcap_docs as d JOIN redcap_docs_folders_files AS f ON d.docs_id=f.docs_id WHERE d.project_id = ? AND f.folder_id = ? AND d.docs_name = ?";

            $q = ExternalModules::query($sql, [$this->project->project_id, $folderID, $document_name]);
            
            if($q->num_rows == 0) {
                $document_not_found[] = $document_name . " (record_id: " . $el["record_id"] . " )";
            }
        }

        //  indicate missing documents
        if(!empty($document_not_found)) {                       
            throw new Exception("validation_error: documents must exist for all referenced records! Following documents (with record_id) could not be found: " . implode(",", $document_not_found));
        }

    }

    
    private function parseFieldTags($input) {
        $parsed = [];
        $regex = "/\[(.*?)\]/";
        $foundTags = preg_match_all($regex, $input, $matches, PREG_PATTERN_ORDER);

        if(!$foundTags) {
            throw new Exception("Error parsing field meta: No tags were found.");
        }

        //  $longitudinal == true
        if(count($matches[1]) == 2) {

            $parsed =  array($matches[1][1], $matches[1][0]);
        } 
        
        //  $longitudinal == false
        elseif(count($matches[1]) == 1) {

            $parsed =  array($matches[1][0], null);
        }

        else {
            throw new Exception("Error parsing field meta: Invalid parsing match count: ".count($matches[0]));
        }

        return $parsed;
    }
}