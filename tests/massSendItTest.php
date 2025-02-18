<?php namespace STPH\massSendIt;

if (!class_exists("BaseTest")) require_once(__DIR__ . "/BaseTest.php");
if (!class_exists("GeneratorHelper")) require_once(__DIR__ . "/helpers/GeneratorHelper.php");

use STPH\massSendIt\GeneratorHelper;

class massSendItTest extends BaseTest
{

   const TEST_BULK_ID = "1";

   /**
    * Bulk Action
    *
    */

   // create bulk scheduled in the past for 1 recipients with primary and secondary notification
   function testCreateBulkAction(){
      $bulkController = new BulkController($this, TEST_PROJECT_1);
      $generator = new GeneratorHelper();
      $payload_bulk_create = $generator->generatePayload(isPast: false);
      $actionBulkCreate = $bulkController->action('create', $payload_bulk_create);
      
      $this->assertFalse($actionBulkCreate["error"], "Error: " . $actionBulkCreate["message"]);
      $this->assertSame(($actionBulkCreate["data"]["bulk"])->bulk_id, self::TEST_BULK_ID);
   }

   // read bulk with bulk_id = 1
   function testReadBulkAction() {
      $bulkController = new BulkController($this, TEST_PROJECT_1);
      $payload_bulk_read = array("bulk_id" => self::TEST_BULK_ID);
      $actionBulkRead = $bulkController->action('read', $payload_bulk_read);

      $this->assertFalse($actionBulkRead["error"], "error: " . $actionBulkRead["message"]);
      $this->assertSame(($actionBulkRead["data"]["bulk"])->bulk_id, self::TEST_BULK_ID);
   }

   // update bulk with bulk_id = 1 to have 2 recipients
   function testUpdateBulkAction() {

      $newTitle = self::getRandomString();
      $newRecipients = "1,2";

      $bulkController = new BulkController($this, TEST_PROJECT_1);
      
      // fetch old data to ensure it does not change date format
      $payload_bulk_read = array("bulk_id" => self::TEST_BULK_ID);
      $actionBulkRead = $bulkController->action("read", $payload_bulk_read);
      $old_bulk_date = $actionBulkRead["data"]["bulk"]->bulk_schedule;

      $old_bulk_schedule = date('Y-m-d H:i', strtotime("-2 years",strtotime($old_bulk_date)));
      //$old_bulk_schedule = "Foo";

      // generate new payload for update with new title and recipients
      $generator = new GeneratorHelper();
      $payload_bulk_update = $generator->generatePayload(
         isEditMode: "true",
         title: $newTitle, 
         recipients_list: $newRecipients,
         bulk_id: self::TEST_BULK_ID,
         isPast: true
      );
      $actionBulkUpdate = $bulkController->action("update", $payload_bulk_update);
      
      $this->assertFalse($actionBulkUpdate["error"], "error: " . $actionBulkUpdate["message"]);
      $this->assertCount(2, unserialize(($actionBulkUpdate["data"]["bulk"])->bulk_recipients));
      $this->assertSame(($actionBulkUpdate["data"]["bulk"])->bulk_id, self::TEST_BULK_ID);
      $this->assertSame(($actionBulkUpdate["data"]["bulk"])->bulk_title, $newTitle);

      $this->assertSame(($actionBulkUpdate["data"]["bulk"])->bulk_schedule, $old_bulk_schedule);

   }

   // delete bulk with bulk_id = 1
   function testDeleteBulkAction(){
      $bulkController = new BulkController($this,  TEST_PROJECT_1);
      $payload_bulk_delete = array("bulk_id" => self::TEST_BULK_ID);
      $actionBulkDelete = $bulkController->action('delete', $payload_bulk_delete);

      $this->assertFalse($actionBulkDelete["error"], "error: " . $actionBulkDelete["message"]);

      // Check if bulk was deleted
      $payload_read  = array("bulk_id" => self::TEST_BULK_ID);
      $actionBulkRead = $bulkController->action('read', $payload_read);
      $this->assertSame($actionBulkRead["message"], "bulk with bulk_id ".self::TEST_BULK_ID." not found");
    }

   /**
    * Schedule Action
    *
    */

    //   create 2 schedules for bulk_id = 1 with 1 recipient
    function testCreateScheduleAction() {

      // create a new bulk with bulk_id = 1
      $generator = new GeneratorHelper();
      $payload_bulk_create = $generator->generatePayload();    
      $bulkController = new BulkController($this, TEST_PROJECT_1);      
      $actionBulkCreate = $bulkController->action('create', $payload_bulk_create);
      // ensure it has bulk_id = 1
      $this->assertSame($actionBulkCreate["data"]["bulk"]->bulk_id, self::TEST_BULK_ID);

      // create schedules for bulk_id = 1
      $scheduleController = new ScheduleController($this,  TEST_PROJECT_1);
      $payload_schedule_create = [
         "bulk_id" => self::TEST_BULK_ID,
         "overwrite" => false
      ];
      $actionScheduleCreate = $scheduleController->action("create", $payload_schedule_create);

      $this->assertFalse($actionScheduleCreate["error"], "error: " .$actionScheduleCreate["message"]);
      $this->assertCount(2, $actionScheduleCreate["data"]["schedules"]);
      $this->assertSame($actionScheduleCreate["data"]["schedules"][0]->bulk_id, self::TEST_BULK_ID);
   }

   //   update bulk_id = 1 to 2 recipients and create 4 schedules
   function testCreateScheduleActionWithOverwrite() {

      // update a bulk
      $generator = new GeneratorHelper();
      $payload_bulk_update = $generator->generatePayload(
         bulk_id: self::TEST_BULK_ID,
         isEditMode: "true",
         recipients_list: "1,2"
      );
      $bulkController = new BulkController($this, TEST_PROJECT_1);      
      $actionBulkUpdate = $bulkController->action('update', $payload_bulk_update);
      $this->assertFalse($actionBulkUpdate["error"], "error: ".$actionBulkUpdate["message"]);
      $this->assertSame(($actionBulkUpdate["data"]["bulk"])->bulk_id, self::TEST_BULK_ID);
      $this->assertCount(2, unserialize(($actionBulkUpdate["data"]["bulk"])->bulk_recipients));

      // (re)create schedules for same bulk
      $scheduleController = new ScheduleController($this,  TEST_PROJECT_1);
      $payload_schedule_create = [
         "bulk_id" => self::TEST_BULK_ID,
         "overwrite" => true
      ];
      $actionScheduleCreate = $scheduleController->action("create", $payload_schedule_create);

      $this->assertFalse($actionScheduleCreate["error"], "error: " .$actionScheduleCreate["message"]);
      $this->assertCount(4, $actionScheduleCreate["data"]["schedules"]);
      $this->assertSame($actionScheduleCreate["data"]["schedules"][0]->bulk_id, self::TEST_BULK_ID);
      $this->assertSame($actionScheduleCreate["data"]["schedules"][1]->message_type, "secondary");
      $this->assertSame($actionScheduleCreate["data"]["schedules"][3]->project_id, TEST_PROJECT_1);
   }

   // delete schedules with ids 1,2
   function testDeleteScheduleAction() {
      $schedule_ids = [1,2];
      $scheduleController = new ScheduleController($this,  TEST_PROJECT_1);
      foreach ($schedule_ids as $key => $schedule_id) {
         $payload_schedule_delete = array("schedule_id" => $schedule_id);
         $actionScheduleDelete = $scheduleController->action("delete", $payload_schedule_delete);

         $this->assertFalse($actionScheduleDelete["error"], "error: ".$actionScheduleDelete["message"]);
      }

      // check deletion
      $scheduleModel = new ScheduleModel($this->module, TEST_PROJECT_1);
      $schedules = $scheduleModel->getAllSchedules(self::TEST_BULK_ID);

      $this->assertSame(2, count($schedules));
      $this->assertSame($schedules[0]->bulk_id, self::TEST_BULK_ID);
      $this->assertSame($schedules[0]->schedule_id, "3");
      $this->assertSame($schedules[0]->record, "2");
   }

   /**
    * Notification Action
    *
    */
    function testSendNotification() {

      $notificationController = new NotificationController($this);
      $actionNotificationSend = $notificationController->action('send', array("dry" => true));

      $this->assertFalse($actionNotificationSend["error"], "error: ".$actionNotificationSend["message"]);
      $this->assertSame(2, $actionNotificationSend["data"]["num_sent"]);
      $this->assertSame(0, $actionNotificationSend["data"]["num_failed"]);

      $notifications = $actionNotificationSend["data"]["notifications"];
      $this->assertSame(($notifications[0])->project_id, TEST_PROJECT_1);

      $email = json_decode($notifications[0]->email);
      $this->assertSame("fgartrell1@google.com.hk", $email->to);
      $this->assertSame("mass.send.it@redcap.test", $email->from);

      $sendit = json_decode($notifications[0]->sendit);
      
      // Get sendit expiration from database
      $sql = "SELECT expire_date FROM redcap_sendit_docs WHERE document_id = ?";
      $q = $this->module->query($sql, $sendit->document_id);
      $bulk_expiration = $q->fetch_assoc()["expire_date"];
      // check that expiration is set to default (+3 months)
      $this->assertSame(date('Y-m-d H:i:s', strtotime("+3 months")), $bulk_expiration);
    }

    function testDoNotSendNotification() {
      // create bulk scheduled in the future
      $generator = new GeneratorHelper();
      $payload_bulk_create = $generator->generatePayload(isPast: false);
      $bulkController = new BulkController($this, TEST_PROJECT_1);
      $actionBulkCreate = $bulkController->action('create', $payload_bulk_create);
      $this->assertFalse($actionBulkCreate["error"], "Error: " . $actionBulkCreate["message"]);

      $bulk_id = ($actionBulkCreate["data"]["bulk"])->bulk_id;

      // create schedules
      $scheduleController = new ScheduleController($this,  TEST_PROJECT_1);
      $payload_schedule_create = [
         "bulk_id" => $bulk_id,
         "overwrite" => false
      ];
      $actionScheduleCreate = $scheduleController->action("create", $payload_schedule_create);
      $this->assertFalse($actionScheduleCreate["error"], "error: ".$actionScheduleCreate["message"]);

      // (do not) send notifications
      $notificationController = new NotificationController($this);
      $actionNotificationSend = $notificationController->action('send', array("dry" => true));

      $this->assertFalse($actionNotificationSend["error"], "error: ".$actionNotificationSend["message"]);
      $this->assertSame(0, $actionNotificationSend["data"]["num_sent"]);
      $this->assertSame(0, $actionNotificationSend["data"]["num_failed"]);
    }

}