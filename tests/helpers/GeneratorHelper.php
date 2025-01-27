<?php namespace STPH\massSendIt;

class GeneratorHelper {
    

   function generatePayload($title="Test Bulk", $type="list",$recipients_list="1", $recipients_logic="[field_1]=1",$repo_folder_id="7", $repo_extension="pdf", $repo_reference="document_reference", $email_to="email", $isEditMode="", $bulk_id=false, $order=false, $isPast=true) {

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
         "file_repo_folder_id" => $repo_folder_id,
         "file_repo_extension" => $repo_extension,
         "file_repo_reference" => $repo_reference,
         "email_display" => "Test Mass Send-It",
         "email_from" => "mass.send.it@redcap.test",
         "email_to" => "[$email_to]",
         "email_first_subject" => "New Document available",
         "email_first_message" => "Hello [firstname] [lastname],<br>a file has been uploaded for you. A second follow-up email will be sent containing the password for retrieving the file at the link below.<br>You can access your document here: [share-file-link]<br>If the link does not open, copy and paste the following url into your browser:<br>[share-file-url].<br><br>",
         "password_type" => "random",
         "custom_pass_field" => "",
         "use_second_email" => "yes",
         "email_second_subject" => "Access to your document",
         "email_second_message" => "Hello,<br>below is the password for downloading the file mentioned in the previous email:<br>[share-file-password]",
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
  
      $form_data = [];
      foreach ($data as $key => $value) {
         $form_data[] = array("name" => $key, "value" => $value);
      }
  
      $payload = array("form_data" => json_encode($form_data));
  
      return $payload;
  
   }
}


