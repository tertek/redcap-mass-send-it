<?php namespace STPH\massSendIt;

use Exception;
use Form;
use User;
use Project;
use FileRepository;

class dropdownHelper {

    public const ALLOWED_FILE_EXTENSIONS = [
        "pdf","doc","docx","csv","html","txt","svg", "bmp", "jpg", "odt", "xlsx"
    ];

    public function __construct() {
       if(empty(PROJECT_ID)) {
        throw new Exception("Cannot use dropdownHelper outside project context. PROJECT_ID empty!");
       }
    }

    function getFileExtensions() {
        $fileExtensions = array("" => " -- select a file extension -- ");
        foreach (self::ALLOWED_FILE_EXTENSIONS as $key => $value) {
            $fileExtensions[$value] = $value;
        }
        return $fileExtensions;
    }

    function getRepoFolders() {
        $folderList = FileRepository::getFolderList(PROJECT_ID);
        $folderList[""] = " -- select a repository folder -- ";
 
        return $folderList;
    }

    function getFieldsWithEvents() {
        $project = new Project();
        $fields = [];

        foreach ($project->metadata as $key => $field) {
            //  Skip fields if they are not of type text
            if($field["element_type"] != "text") {
                continue;
            } 

            //  Skip primary key
            if($field["field_order"] == 1){
                continue;
            }

            //  Skip fields that are using validation
            if($field["element_validation_type"] != null){
                continue;
            }

            $fields[] = array(
                "element_label" => $field["element_label"],
                "field_name" => $field["field_name"]
            );
        }

        return $fields;
    }

    function getFields() {
        $project = new Project();
        $fields = [];

        foreach ($project->metadata as $key => $field) {
            //  Skip fields if they are not of type text
            if($field["element_type"] != "text") {
                continue;
            } 

            //  Skip primary key
            if($field["field_order"] == 1){
                continue;
            }

            //  Skip fields that are using validation
            if($field["element_validation_type"] != null){
                continue;
            }

            $fields[] = array(
                "element_label" => $field["element_label"],
                "field_name" => $field["field_name"]
            );
        }

        return $fields;
    }    

    function getProjectFields($limitToFieldType = "text", $limitToValidationType=null) {

        $projectFields = Form::getFieldDropdownOptions(limitToFieldType: $limitToFieldType, limitToValidationType:$limitToValidationType);

        return $projectFields;
    }
    
    // Return list of From Emails
    function getFromEmails () {
        $fromEmails = array();
        foreach (User::getEmailAllProjectUsers(PROJECT_ID) as $thisEmail) {
            $fromEmails[$thisEmail] = $thisEmail;
        }
        return $fromEmails;
    }    

    function getToEmails() {
        $toEmails = array();
        global $Proj, $longitudinal;
    
        $ddProjectVarLabel = "Email variables";

        $emailFieldsLabels = Form::getFieldDropdownOptions(limitToValidationType : 'email');
        if (!empty($emailFieldsLabels)) {
            foreach ($emailFieldsLabels as $formLabel=>$emailFields) {
                if (!is_array($emailFields)) continue;
                foreach ($emailFields as $thisVar=>$thisOptionLabel) {
                    list ($thisVarLabel, $thisOptionLabel) = explode(" ", $thisOptionLabel, 2);
                    if ($longitudinal) {
                        foreach ($Proj->eventsForms as $thisEventId=>$theseForms) {
                            $thisEventName = $Proj->getUniqueEventNames($thisEventId);
                            $thisForm = $Proj->metadata[$thisVar]['form_name'];
                            if (in_array($thisForm, $theseForms)) {
                                $toEmails[$ddProjectVarLabel]["[$thisEventName][$thisVar]"] = "[$thisEventName][$thisVar] $thisOptionLabel (".$Proj->eventInfo[$thisEventId]['name_ext'].")";
                            }
                        }
                    } else {
                        $toEmails[$ddProjectVarLabel]["[$thisVar]"] = "[$thisVar] $thisOptionLabel";
                    }
                }
            }
        }    
        return $toEmails;
    }
}