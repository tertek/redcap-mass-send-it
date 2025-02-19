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

    function getFieldsWithEvents($firstLabel = "-- select a field --", $limitToFieldTypes = ["text"] ,$addFormLabelDividers=true, $excludeValidationFields=["email"], $alsoIncludeRecordIdField=false) {
        global $Proj,$longitudinal;
        $fields = [];
        $fields[""] = $firstLabel;

        foreach ($Proj->metadata as $this_field=>$attr1) {
            
            //  Skip fields if they are not of type text
            if(!empty($limitToFieldTypes) &&  !in_array($attr1["element_type"], $limitToFieldTypes)) {
                continue;
            } 

            //  Skip primary key
            if(!$alsoIncludeRecordIdField && $attr1["field_order"] == 1){
                continue;
            }

            //  Skip fields that are using validation
            if(!empty($excludeValidationFields) &&  in_array($attr1["element_validation_type"], $excludeValidationFields)){
                continue;
            }

            if ($addFormLabelDividers) {
                // Add to fields/forms array. Get form of field.
                $this_form_label = $Proj->forms[$attr1['form_name']]['menu'];
                // Clean the label
                $attr1['element_label'] = trim(str_replace(array("\r\n", "\n"), array(" ", " "), strip_tags($attr1['element_label']."")));
                // Truncate label if long
                if (mb_strlen($attr1['element_label']) > 65) {
                    $attr1['element_label'] = trim(mb_substr($attr1['element_label'], 0, 47)) . "... " . trim(mb_substr($attr1['element_label'], -15));
                }
            }
		
            if ($longitudinal) {
                foreach ($Proj->eventsForms as $thisEventId=>$theseForms) {
                    $thisEventName = $Proj->getUniqueEventNames($thisEventId);
                    $thisForm = $Proj->metadata[$this_field]['form_name'];
                    if (in_array($thisForm, $theseForms)) {
                        $key = "[$thisEventName][$this_field]";
                        $value = "[$thisEventName][$this_field] \"{$attr1['element_label']}\" (".$Proj->eventInfo[$thisEventId]['name_ext'].")";

                        if ($addFormLabelDividers) {
                            $fields[$this_form_label][$key] = $value;
                        } else {
                            $fields[$key] = $value;
                        }
                    }
                }
            } else {

                $key = "[$this_field]";
                $value = "[$this_field] \"{$attr1['element_label']}\"";

                if ($addFormLabelDividers) {
                    $fields[$this_form_label][$key] = $value;
                } else {
                    $fields[$key] = $value;
                }
            }
        }

        return $fields;
    }


    
    // Return list of From Emails
    function getFromEmails () {
        $fromEmails = array();
        foreach (User::getEmailAllProjectUsers(PROJECT_ID) as $thisEmail) {
            $fromEmails[$thisEmail] = $thisEmail;
        }
        if (SUPER_USER && !isset($fromEmails[$GLOBALS['user_email']])) {
            // If admin is not a user in the project, add their primary email to the drop-down
            $fromEmails[] = $GLOBALS['user_email'];
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

    function getDownloadPages($settings) {
        $downloadPages[""] = " -- select a download page -- ";
        foreach ($settings as $key => $setting) {
            $downloadPages[$key] = $setting["custom-download-page-id"] . " - ($key)";
        }
        return $downloadPages;
    }
}