<?php namespace STPH\massSendIt;

class GeneratorHelper {

   /**
    * Generate per default a new bulk in the past in list mode, for one recipient with primary and secondary notification
    * 
    */
   function generatePayload($title="Test Bulk", $type="list",$recipients_list="1", $recipients_logic="[field_1]=1",$repo_folder_id=TEST_FOLDER_ID_1, $repo_extension="pdf", $repo_reference="[document_reference]", $email_to="[email]", $isEditMode="", $bulk_id=false, $order=false, $isPast=true, $useSecondEmail=true) {

      /**
       * Time format glitch:
       * in testing there is a different return value for self::get_user_format_full() than in user land
       * this affects output of DateTimeRC::format_ts_to_ymd and leads to failing tests when in testing mode
       * that is why we are using here 'm-d-Y H:i' instead of 'm/d/Y H:i' which is the equivalent of user_date_format_jquery JS variable coming from the frontend and also being the default when running from the project itself. This issue may be checked on newer version of REDCap and may be fixed or opened as issue in the EMF
       * 
       */
      if($isPast) {
         $bulk_schedule = date('m-d-Y H:i', strtotime("-1 year"));
      } else {
         $bulk_schedule = date('m-d-Y H:i', strtotime("+1 year"));
      }
     
      $data =  array(
         "bulk_title" => $title,
         "bulk_type" => $type,
         "bulk_recipients_list" => $recipients_list,
         "bulk_recipients_logic" => $recipients_logic,
         "file_repo_folder_id" => strval($repo_folder_id),
         "file_repo_extension" => $repo_extension,
         "file_repo_reference" => $repo_reference,
         "email_display" => "Test Mass Send-It",
         "email_from" => "mass.send.it@redcap.test",
         "email_to" => $email_to,
         "email_first_subject" => "New Document available",
         "email_first_message" => "Hello [firstname] [lastname],<br>a file has been uploaded for you. A second follow-up email will be sent containing the password for retrieving the file at the link below.<br>You can access your document here: [share-file-link]<br>If the link does not open, copy and paste the following url into your browser:<br>[share-file-url].<br><br>",
         "password_type" => "random",
         "custom_pass_field" => "",
         "use_second_email" => "no",
         "email_second_subject" => "",
         "email_second_message" => "",
         "bulk_schedule" => $bulk_schedule,
         "bulk_expiration" => "",
         "is_edit_mode" => $isEditMode
      );
  
      if($bulk_id) {
         $data["bulk_id"] = $bulk_id;
      }

      if($isEditMode == "true" && !$order) {
         $order = strval($bulk_id-1);
      }
  
      if(!$order) {
         $data["bulk_order"] = $order;
      }

      if($useSecondEmail) {
         $data["use_second_email"] = "yes";
         $data["email_second_subject"] = "Access to your document";
         $data["email_second_message"] = "Hello,<br>below is the password for downloading the file mentioned in the previous email:<br>[share-file-password]";
      }
  
      $form_data = [];
      foreach ($data as $key => $value) {
         $form_data[] = array("name" => $key, "value" => $value);
      }
  
      $payload = array("form_data" => json_encode($form_data));

      return $payload;
  
   }
}


