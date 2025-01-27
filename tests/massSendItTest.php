<?php namespace STPH\massSendIt;

if (!class_exists("GeneratorHelper")) require_once(__DIR__ . "/helpers/GeneratorHelper.php");

use STPH\massSendIt\GeneratorHelper;

class massSendItTest extends BaseTest
{

   const TEST_BULK_ID = 1;

   /**
    * Bulk Action
    *
    */
   function testCreateBulkAction(){
      // create bulk scheduled in the past
      $generator = new GeneratorHelper();
      $payload_bulk_create = $generator->generatePayload();

      $bulkController = new BulkController($this, TEST_PROJECT_1);
      $actionBulkCreate = $bulkController->action('create', $payload_bulk_create);

      $this->assertFalse($actionBulkCreate["error"], "Error: " . $actionBulkCreate["message"]);
   }

   function testReadBulkAction() {
      $payload_bulk_read = array("bulk_id" => self::TEST_BULK_ID);
      $bulkController = new BulkController($this, TEST_PROJECT_1);
      $actionBulkRead = $bulkController->action('read', $payload_bulk_read);

      $this->assertFalse($actionBulkRead["error"], "error: " . $actionBulkRead["message"]);
   }

   function testUpdateBulkAction() {

      $generator = new GeneratorHelper();
      $payload_bulk_update = $generator->generatePayload(
         isEditMode: "true",
         title: "Test Bulk Edited", 
         recipients_list: "1,2",
         bulk_id: self::TEST_BULK_ID,
      );

      $bulkController = new BulkController($this, TEST_PROJECT_1);
      $actionBulkUpdate = $bulkController->action("update", $payload_bulk_update);
      
      $this->assertFalse($actionBulkUpdate["error"], "error: " . $actionBulkUpdate["message"]);
      $this->assertCount(2, unserialize(($actionBulkUpdate["data"]["bulk"])->bulk_recipients));
   }

   function testDeleteBulkAction(){

      $payload_bulk_delete = array("bulk_id" => self::TEST_BULK_ID);

      $bulkController = new BulkController($this,  TEST_PROJECT_1);
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

    function testCreateScheduleActtion() {

      // create a bulk
      $generator = new GeneratorHelper();
      $payload_bulk_create = $generator->generatePayload();    
      $bulkController = new BulkController($this, TEST_PROJECT_1);      
      $bulkController->action('create', $payload_bulk_create);

      // create schedules
      $scheduleController = new ScheduleController($this,  TEST_PROJECT_1);
      $payload_schedule_create = [
         "bulk_id" => self::TEST_BULK_ID,
         "overwrite" => false
      ];
      $actionScheduleCreate = $scheduleController->action("create", $payload_schedule_create);

      $this->assertFalse($actionScheduleCreate["error"], "error: " .$actionScheduleCreate["message"]);
      $this->assertCount(2, $actionScheduleCreate["data"]["scheduled"]);
   }

   function testCreateScheduleActtionWithOverwrite() {

      // update a bulk
      $generator = new GeneratorHelper();
      $payload_bulk_update = $generator->generatePayload(
         bulk_id: self::TEST_BULK_ID,
         isEditMode: "true",
         recipients_list: "1,2"
      );
      $bulkController = new BulkController($this, TEST_PROJECT_1);      
      $actionBulkCreate = $bulkController->action('update', $payload_bulk_update);
      $this->assertFalse($actionBulkCreate["error"], "error: ".$actionBulkCreate["message"]);

      // (re)create schedules for same bulk
      $scheduleController = new ScheduleController($this,  TEST_PROJECT_1);
      $payload_schedule_create = [
         "bulk_id" => self::TEST_BULK_ID,
         "overwrite" => true
      ];
      $actionScheduleCreate = $scheduleController->action("create", $payload_schedule_create);
      //dump($actionScheduleCreate);

      $this->assertFalse($actionScheduleCreate["error"], "error: " .$actionScheduleCreate["message"]);
      $this->assertCount(4, $actionScheduleCreate["data"]["scheduled"]);
   }

   function testDeleteScheduleAction() {
      $schedule_ids = [1,2];
      $scheduleController = new ScheduleController($this,  TEST_PROJECT_1);
      foreach ($schedule_ids as $key => $schedule_id) {

         $payload_schedule_delete = array("schedule_id" => $schedule_id);
         $actionScheduleDelete = $scheduleController->action("delete", $payload_schedule_delete);

         $this->assertFalse($actionScheduleDelete["error"], "error: ".$actionScheduleDelete["message"]);
      }

      // check deletion
      $sql = "SELECT schedule_id WHERE table_name = 'schedule' AND bulk_id = ? AND project_id = ?";
      $result = $this->module->queryLogs($sql, [self::TEST_BULK_ID, TEST_PROJECT_1]);
      $this->assertSame(2, $result->num_rows);
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