<?php namespace STPH\massSendIt;

class massSendItTest extends BaseTest
{

   function generatePayload($title="Test Bulk", $type="list",$recipients_list="1,2", $recipients_logic="[field_1]=1",$repo_folder_id="7", $repo_extension="pdf", $repo_reference="document_reference", $email_to="email", $isEditMode="false", $id=false, $order=false) {      
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
         "bulk_schedule" => "12-31-2024 15:00",
         "bulk_expiration" => "",
         "is_edit_mode" => $isEditMode
      );

      if($id) {
         $data["bulk_id"] = $id;
      }

      if($order) {
         $data["bulk_order"] = $order;
      }

      $result = [];
      foreach ($data as $key => $value) {
         $result[] = array("name" => $key, "value" => $value);
      }

      return json_encode($result);

   }

   function testBulkActions(){

      $project_id = TEST_PROJECT_1;
      
      $payload = $this->generatePayload();      

      $bulkController = new BulkController($this, $project_id);

      $bulksCreated = [];
      for ($i=1; $i <=5 ; $i++) { 
         $actionDataCreate = $bulkController->action('create', $payload);
         $bulksCreated[] = $actionDataCreate["data"]["bulk"];
      }

      $actionDataRead = $bulkController->action('read', ["bulk_id" => $bulksCreated[0]->bulk_id]);
      $bulkRead = $actionDataRead["data"]["bulk"];

      $this->assertEquals($bulksCreated[0], $bulkRead);
   }

   function testUpdateBulkAction() {
      $payload = $this->generatePayload(title: "Test Bulk Edited", id: "1", isEditMode: true, order:"2");
      $bulkController = new BulkController($this, TEST_PROJECT_1);
      $actionDataUpdate = $bulkController->action("update", $payload);

      $bulkUpdated = $actionDataUpdate["data"]["bulk"];

      $actionDataRead = $bulkController->action('read', ["bulk_id" => "1"]);
      $bulkRead = $actionDataRead["data"]["bulk"];

      $this->assertSame("Test Bulk Edited", $bulkUpdated->bulk_title);
      $this->assertSame($bulkRead->bulk_title, $bulkUpdated->bulk_title);
   }


   function testDeleteBulkAction(){

      $payload = array("bulk_id" => 1);

      $bulkController = new BulkController($this,  TEST_PROJECT_1);
      $bulkController->action('delete', $payload);

      $actionDataRead = $bulkController->action('read', ["bulk_id" => "1"]);
      $actual = $actionDataRead["message"];

      $expected = "bulk with bulk_id 1 not found";

      $this->assertSame($expected, $actual);

      
      
      
   }

}