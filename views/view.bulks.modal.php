<?php

function getProjectFields() {
    $projectFields = \Form::getFieldDropdownOptions(limitToFieldType: "text");
    return $projectFields;
}

// Return list of From Emails
function getFromEmails () {
    $fromEmails = array();
    foreach (\User::getEmailAllProjectUsers(PROJECT_ID) as $thisEmail) {
        $fromEmails[$thisEmail] = $thisEmail;
    }
    return $fromEmails;
}

function getToEmails() {
    $toEmails = array();
    global $Proj, $longitudinal;

    // Get data types of all field validations for ONLY Email fields
    $validationDataTypes = array('email');
    foreach (getValTypes() as $valType=>$valAttr)  {
        if ($valAttr['data_type'] == 'email') {
            $validationDataTypes[] = $valType;
        }
    }
    $ddProjectVarLabel = "Email variables";
    $validationDataTypes = array_unique($validationDataTypes);
    $emailFieldsLabels = Form::getFieldDropdownOptions(false, false, false, false, $validationDataTypes);
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
?>
<style>
    .custom-select.is-invalid,.was-validated .form-control-custom.is-invalid, .was-validated .form-control-custom:invalid, .form-control.is-invalid, .was-validated .custom-select:invalid, .was-validated .form-control:invalid{
        border-color: #dc3545;
    }

    select.invalid-custom, .tox-tinymce.invalid-custom {
        border-color: #dc3545;
        border-width: 1px;
}
</style>
<div class="col-md-12">
    <form class="form-horizontal" method="post" id="saveBulkForm" novalidate>
        <div class="modal fade" id="external-modules-configure-modal-1" name="external-modules-configure-modal-1" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="true" data-bs-focus="false">
            <div class="modal-dialog" role="document" style="max-width: 950px !important;">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <button type="button" class="py-2 close closeCustomModal btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        <h4 id="add-edit-title-text" class="modal-title form-control-custom">Create new bulk</h4>
                    </div>
                    <div class="modal-body pt-2">
                        <div id="errMsgContainerModal" class="alert alert-danger col-md-12" role="alert" style="display:none;margin-bottom:20px;">
                        Please complete all required forms indicated in red.
                        </div>
                        <div id="errMsgContainerModal-2" class="alert alert-danger col-md-12" role="alert" style="display:none;margin-bottom:20px;">
                        <h4 class="alert-heading">Bad user input or missing dependency!</h4>
                        <p id="errMsgContent-2"></p>
                        <hr>
                        <p class="mb-0">Please check the relevant input and fix it.</p>
                        </div>                    
                        <div class="mb-2">
                            You may define the settings for your bulk below. After clicking the Save button at the bottom, your bulk send will immediately become active and may be triggered at any time thereafter. If you would like to remove or stop using an bulk send, it may be deactivated at any time. You may modify an existing bulk send at any time, even after some notificaitons have already been sent or scheduled.
                        </div>
                        <table class="code_modal_table" id="code_modal_table_update">

                            <!-- Title -->
                            <tr class="form-control-custom">
                                <td colspan="2" class="align-text-top pt-1">
                                    <label class="fs14 boldish">Title of this bulk:</label>
                                    <input type="text" name="bulk_title" placeholder="add bulk title" class="d-inline ms-3" style="font-size:15px;width:500px;" maxlength="100">
                                </td>
                            </tr>

                            <!-- Step 1 -->
                            <tr class="form-control-custom">
                                <td colspan="2">
                                    <div class="form-control-custom-title clearfix">
                                        <div class="boldish fs14" style="margin-top:2px;"><i class="fas fa-hand-point-right"></i> STEP 1: Selecting Bulk recipients</div>
                                    </div>
                                </td>
                            </tr>

                            <!-- A -->
                            <tr class="form-control-custom" field="">
                                <td class="align-text-top pt-1 pe-1">
                                    <label class="text-nowrap boldish">
                                        <span style="color:#0061b5;">A)</span> How will records be selected?
                                    </label>
                                </td>
                                <td class="external-modules-input-td">
                                    <div class="ms-2 nowrap">
                                        <input type="radio" id="bulk_type_list" name="bulk_type" value="list" style="height:20px;" class="external-modules-input-element align-middle">
                                        <label for="bulk_type_list" class="m-0 align-middle">By inserting a record list</label>
                                    </div>
                                    <div class="ms-2 nowrap">
                                        <input type="radio" id="bulk_type_logic" name="bulk_type" value="logic" style="height:20px;" class="external-modules-input-element align-middle">
                                        <label for="bulk_type_logic" class="m-0 align-middle">By defining a filter logic that will be evaluated on bulk creation</label>
                                    </div>
                                </td>
                            </tr>
                            <!-- B -->
                            <tr  class="form-control-custom" field="">
                                <td colspan="2" class="external-modules-input-td pb-1 boldish">
                                    <span style="color:#0061b5;">B)</span>  Records selection based on..
                                </td>
                            </tr>

                            <tr class="form-control-custom bulk-recipients-list" field="">
                                <td colspan="2" class="external-modules-input-td boldish pt-1 pb-1 ps-3">
                                Record List:
                                </td>
                            </tr>
                            <tr class="form-control-custom bulk-recipients-list mb-3" field="">
                                <td colspan="2" class="external-modules-input-td pb-0 ps-3">
                                    <div class="requiredlabel p-0">* must provide value</div>
                                    <textarea type="text" id="bulk_recipients_list" name="bulk_recipients_list" class="form-control external-modules-input-element ms-4" style="max-width:95%;"></textarea>
                                    <div class="invalid-feedback ms-4">
                                        Please enter at least one record.
                                    </div>                                
                                    <div id="LSC_id_alert-condition" class="fs-item-parent fs-item"></div>
                                    <div style='border: 0; font-weight: bold; text-align: left; vertical-align: middle; height: 20px;' id='alert-condition_Ok'>&nbsp;</div>
                                    <div class="clearfix">
                                        <div class='my-1 ms-4 fs11 float-start text-secondary'>
                                        (e.g., 1001,1002,302)
                                        </div>                                    
                                    </div>
                                </td>
                            </tr>
    
                            <tr class="form-control-custom bulk-recipients-logic" field="">
                                <td colspan="2" class="external-modules-input-td boldish pt-1 pb-1 ps-3">
                                Filter Logic:
                                </td>
                            </tr>
                            <tr class="form-control-custom bulk-recipients-logic mb-4" field="">
                                <td colspan="2" class="external-modules-input-td pb-0 ps-3">
                                    <div class="requiredlabel p-0">* must provide value</div>
                                    <textarea type="text" id="bulk_recipients_logic" name="bulk_recipients_logic" onfocus="openLogicEditor($(this))" onkeydown="logicSuggestSearchTip(this, event);" onblur='var val = this; setTimeout(function() { logicHideSearchTip(val); if(!checkLogicErrors(val.value,1)){ validate_logic(val.value,"",0,""); }; }, 0);' class="external-modules-input-element ms-4" style="max-width:95%;"></textarea>
                                    <div class="invalid-feedback ms-4">Please enter a valid logic to filter records.</div>                                
                                    <div id="LSC_id_bulk_recipients_logic" class="fs-item-parent fs-item"></div>
                                    <div style='border: 0; font-weight: bold; text-align: left; vertical-align: middle; height: 20px;' id='bulk_recipients_logic_Ok'>&nbsp;</div>
                                    <div id='bulk_recipients_logic_Ok' class='logicValidatorOkay ms-4'></div>
                                    <script type='text/javascript'>logicValidate($('#bulk_recipients_logic'), false, 1);</script>
                                </td>
                            </tr>

                            <!-- Step 2 -->
                            <tr class="form-control-custom">
                                <td colspan="2">
                                    <div class="form-control-custom-title clearfix">
                                        <div class="boldish fs14" style="margin-top:2px;"><i class="fas fa-hand-point-right"></i> STEP 2: File Repository binding</div>
                                    </div>
                                </td>
                            </tr>

                            <tr class="form-control-custom" field="">
                                <td class="align-text-top pe-2 ps-3" style="padding-top:0.3rem;">
                                    <label for="file_repo_folder_id" class="text-nowrap boldish">Specify file repository folder</label>
                                    <div class="requiredlabel p-0">* must provide value</div>
                                </td>
                                <td>
                                    <div class="float-start me-2 mt-1" style="width:65%;max-width:280px;">
                                    <?=RCView::select(array('name'=>"file_repo_folder_id",'class'=>'external-modules-input-element'), [
                                        "default" => " -- select a repository folder -- "
                                    ], null, 200)?>
                                    </div>
                                </td>
                            </tr>

                            <tr class="form-control-custom">
                                <td class="align-text-top pe-2 ps-3" style="padding-top:0.3rem;">
                                    <label for="file_repo_extension" class="text-nowrap boldish">Specify file repository extension</label>
                                    <div class="requiredlabel p-0">* must provide value</div>
                                </td>
                                <td>
                                    <div class="float-start me-2 mt-1" style="width:65%;max-width:280px;">
                                    <?=RCView::select(array('name'=>"file_repo_extension",'class'=>'external-modules-input-element'), [
                                        "default" => " -- select a file extension -- "
                                    ], null, 200)?>
                                    </div>
                                </td>                            
                            </tr>

                            <tr class="form-control-custom">
                                <td class="align-text-top pe-2 ps-3" style="padding-top:0.3rem;">
                                    <label for="file_repo_reference" class="text-nowrap boldish">Specify file repository reference</label>
                                    <div class="requiredlabel p-0">* must provide value</div>
                                </td>
                                <td>
                                    <div class="float-start me-2 mt-1" style="width:65%;max-width:280px;">
                                    <?=RCView::select(array('name'=>"file_repo_reference",'class'=>'external-modules-input-element'), [
                                        "default" => " -- select a reference field -- "
                                    ], null, 200)?>
                                    </div>
                                </td>                            
                            </tr>                        

                            <tr class="form-control-custom">
                                <td colspan="2">
                                    <div class="form-control-custom-title clearfix">
                                        <div class="boldish fs14" style="margin-top:2px;"><i class="fas fa-hand-point-right"></i> STEP 3: Message Settings</div>
                                    </div>
                                </td>
                            </tr>

                            <tr class="requiredm form-control-custom" field="email-from">
                                <td class="ps-3">
                                    <label for="email_from" class="mb-1 boldish">Email From:</label><div class="requiredlabel p-0">* must provide value</div>
                                </td>
                                <td class="external-modules-input-td clearfix nowrap">
                                    <div class="float-start me-2 mt-1" style="width:150px;<?php if (!$GLOBALS['use_email_display_name']) print "display:none;"; ?>">
                                        <input type="text" name="email_display" class="fs12 external-modules-input-element d-inline" style="width:100%;" placeholder="<?=js_escape2("Display name (optional)")?>">
                                    </div>
                                    <div class="float-start me-2 mt-1" style="width:65%;max-width:380px;">
                                    <?=RCView::select(array('name'=>"email_from",'class'=>'external-modules-input-element'), getFromEmails(), $user_email, 200)?>
                                    </div>
                                </td>
                            </tr>

                            <tr class="form-control-custom" field="email-to">
                                <td class="align-text-top pt-2 ps-3">
                                    <label for="email_to" class="mb-1 boldish">Email To:</label>
                                    <div class="requiredlabel p-0">* must provide value</div>
                                </td>                          
                                <td class="external-modules-input-td pt-2">
                                    <?php
                                    print RCView::select(array('name'=>"email_to", 'id'=>"email_to",
                                    'class'=>'external-modules-input-element fs12'), getToEmails(), "", 200);
                                    ?>
                                </td>
                            </tr>

                            <tr class="requiredm form-control-custom" field="email-subject">
                                <td class="ps-3">
                                    <label for="email_first_subject" class="mb-1 boldish">Subject (First mail)</label><div class="requiredlabel p-0">* must provide value</div>
                                </td>
                                <td class="external-modules-input-td">
                                    <input type="text" name="email_first_subject" class="form-control external-modules-input-element" value="" required>
                                </td>
                            </tr>
                            <tr class="requiredm form-control-custom" field="email-first-message">
                                <td class="align-text-top pt-2 ps-3"  id='alert-message-label-td'>
                                    <label for="email_first_message" class="mb-1 boldish">Message (First mail)</label>
                                    <div class="requiredlabel p-0">* must provide value</div>
                                </td>
                                <td class="external-modules-input-td">
                                <textarea class="external-modules-rich-text-field" name="email_first_message" id="email_first_message" onkeydown=""></textarea>
                                <div style="padding:8px 0px 2px;color:#555;font-size:11px;">
                                    In the subject or message, you may use <button class="btn btn-xs btn-rcpurple btn-rcpurple-light" style="margin-left:3px;margin-right:2px;font-size:11px;padding:0px 3px 1px;line-height: 14px;" onclick="pipingExplanation();return false;"><img src="/redcap/redcap_v14.5.18/Resources/images/pipe.png" style="width:12px;position:relative;top:-1px;margin-right:2px;" alt="">Piping</button>
                                    and	<button class="btn btn-xs btn-rcgreen btn-rcgreen-light" style="margin-left:3px;font-size:11px;padding:0px 3px 1px;line-height:14px;" onclick="smartVariableExplainPopup();return false;">[<i class="fas fa-bolt fa-xs" style="margin:0 1px;"></i>] Smart Variables</button>
                                    <div style="margin-top:8px;color:#999;font-size:11px;font-family:verdana;">
                                    <div>
                                </div>
                            </td>
                            

                            <tr class="form-control-custom" field="">
                                <td class="ps-3">
                                <label class="mb-1 boldish">
                                        How to create password for download?
                                    </label>
                                </td>
                                <td class="external-modules-input-td">
                                    <div class="ms-2 nowrap">
                                        <input type="radio" id="password_type_random" name="password_type" value="random" style="height:20px;" class="external-modules-input-element align-middle">
                                        <label for="password_type_random" class="m-0 align-middle">Randomly generated per record</span></label>
                                    </div>
                                    <div class="ms-2 nowrap">
                                        <input type="radio" id="password_type_custom" name="password_type" value="custom" style="height:20px;" class="external-modules-input-element align-middle">
                                        <label for="password_type_custom" class="m-0 align-middle">Specify a field to use as a password for each record</label>
                                    </div>
                                </td>
                            </tr>

                            <tr class="form-control-custom" field="custom-password">
                                <td class="align-text-top pt-2 ps-3">
                                    <label class="mb-1 boldish">Custom password field</label>
                                    <div class="requiredlabel p-0">* must provide value</div>
                                </td>                          
                                <td class="external-modules-input-td pt-2">
                                    <?php
                                    print RCView::select(array('name'=>"custom_pass_field", 'id'=>"custom_pass_field",
                                    'class'=>'external-modules-input-element fs12'), getProjectFields(), "", 200);
                                    ?>
                                </td>
                            </tr>                        

                            <tr class="form-control-custom" field="">
                                <td class="ps-3">
                                <label class="mb-1 boldish">
                                        Send a second email with password information?
                                    </label>
                                </td>
                                <td class="external-modules-input-td">
                                    <div class="ms-2 nowrap">
                                        <input type="radio" id="use_second_email_yes" name="use_second_email" value="yes" style="height:20px;" class="external-modules-input-element align-middle">
                                        <label for="use_second_email_yes" class="m-0 align-middle">Yes</span></label>
                                    </div>
                                    <div class="ms-2 nowrap">
                                        <input type="radio" id="use_second_email_no" name="use_second_email" value="no" style="height:20px;" class="external-modules-input-element align-middle">
                                        <label for="use_second_email_no" class="m-0 align-middle">No</label>
                                    </div>
                                </td>
                            </tr> 

                            <tr class="requiredm form-control-custom" field="email-subject-second">
                                <td class="ps-3">
                                    <label for="email_second_subject" class="mb-1 boldish">Subject (Second mail)</label><div class="requiredlabel p-0">* must provide value</div>
                                </td>
                                <td class="external-modules-input-td">
                                    <input type="text" name="email_second_subject" class="form-control external-modules-input-element" value="">
                                </td>
                            </tr>
                            <tr class="requiredm form-control-custom" field="email-message-second">
                                <td class="align-text-top pt-2 ps-3"  id='alert-message-label-td'>
                                    <label for="email_second_message" class="mb-1 boldish">Message (Second mail)</label>
                                    <div class="requiredlabel p-0">* must provide value</div>
                                </td>
                                <td class="external-modules-input-td">
                                <textarea class="external-modules-rich-text-field" name="email_second_message" id="email_second_message" onkeydown=""></textarea>
                                <div style="padding:8px 0px 2px;color:#555;font-size:11px;">
                                    In the subject or message, you may use <button class="btn btn-xs btn-rcpurple btn-rcpurple-light" style="margin-left:3px;margin-right:2px;font-size:11px;padding:0px 3px 1px;line-height: 14px;" onclick="pipingExplanation();return false;"><img src="/redcap/redcap_v14.5.18/Resources/images/pipe.png" style="width:12px;position:relative;top:-1px;margin-right:2px;" alt="">Piping</button>
                                    and	<button class="btn btn-xs btn-rcgreen btn-rcgreen-light" style="margin-left:3px;font-size:11px;padding:0px 3px 1px;line-height:14px;" onclick="smartVariableExplainPopup();return false;">[<i class="fas fa-bolt fa-xs" style="margin:0 1px;"></i>] Smart Variables</button>
                                    <div style="margin-top:8px;color:#999;font-size:11px;font-family:verdana;">
                                    <div>
                                </div>                            
                            </td>                        

                            <tr class="form-control-custom">
                                <td colspan="2">
                                    <div class="form-control-custom-title clearfix">
                                        <div class="boldish fs14" style="margin-top:2px;"><i class="fas fa-hand-point-right"></i> STEP 4: Set the Bulk Schedule</div>
                                    </div>
                                </td>
                            </tr>

                            <tr class="form-control-custom">
                                <td class="ps-3 pt-3 align-text-top">
                                    <label for="bulk_schedule" class="text-nowrap boldish">When to send the Bulk?</label>
                                    <div class="requiredlabel p-0">* must provide value</div>
                                </td>
                                <td class="pt-3 external-modules-input-td">
                                    <input type="text" name="bulk_schedule" class="form-control ms-1 fs12 bulk-datetimepicker external-modules-input-element d-inline"
                                            placeholder="<?=str_replace(array('M','D','Y'),array('MM','DD','YYYY'),DateTimeRC::get_user_format_label())." HH:MM"?>"
                                            style="height:26px;width:140px;" onblur="redcap_validate(this,'','','hard','datetime_'+user_date_format_validation,1,1,user_date_format_delimiter)" required>
                                    <div class="ms-2 mt-1 fs12" style="color:gray;">
                                        When clicking 'save' all emails witin the bulk will be created according to this date.
                                    </div>
                                </td>
                            </tr>               

                            <tr class="form-control-custom">
                                <td class="ps-3 pt-3 align-text-top">
                                <label for="bulk_expiration" class="text-nowrap boldish">Bulk expiration:</label>
                                <div class="text-secondary">Optional</div>
                                </td>
                                <td class="pt-3 external-modules-input-td">
                                    <input type="text" name="bulk_expiration" class="ms-1 fs12 bulk-datetimepicker external-modules-input-element d-inline"
                                            placeholder="<?=str_replace(array('M','D','Y'),array('MM','DD','YYYY'),DateTimeRC::get_user_format_label())." HH:MM"?>"
                                            style="height:26px;width:140px;" onblur="redcap_validate(this,'','','hard','datetime_'+user_date_format_validation,1,1,user_date_format_delimiter)">
                                    <div class="ms-2 mt-1 fs12" style="color:gray;">
                                        This expiration affects download links for emails that were already sent out.
                                    </div>
                                </td>
                            </tr>

                            <input type="hidden" id="is_edit_mode" name="is_edit_mode" value="">
                            <input type="hidden" id="bulk_id" name="bulk_id" value="">
                        </table>
                    </div>

                    <div class="modal-footer">
                        <button id="saveBulkBtn" type="submit" class="btn btn-rcgreen">Save</button>
                        <button type="button" class="btn btn-defaultrc" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>