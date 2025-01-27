<?php namespace STPH\massSendIt;

if (!class_exists("GeneratorHelper")) require_once(__DIR__ . "/helpers/GeneratorHelper.php");

use STPH\massSendIt\GeneratorHelper;

class massSendItTest extends BaseTest
{

   /**
    * Bulk Action
    *
    */
   function testCreateBulkAction(){

      $generator = new GeneratorHelper();
      $payload_bulk_create = $generator->generatePayload();    

      $bulkController = new BulkController($this, TEST_PROJECT_1);
      $actionBulkCreate = $bulkController->action('create', $payload_bulk_create);

      $this->assertFalse($actionBulkCreate["error"], "Error: " . $actionBulkCreate["message"]);
   }

   function testReadBulkAction() {
      $payload_bulk_read = array("bulk_id" => "1");
      $bulkController = new BulkController($this, TEST_PROJECT_1);
      $actionBulkRead = $bulkController->action('read', $payload_bulk_read);

      $this->assertFalse($actionBulkRead["error"], "Error: " . $actionBulkRead["message"]);
   }

   function testUpdateBulkAction() {

      $generator = new GeneratorHelper();
      $payload_bulk_update = $generator->generatePayload(
         isEditMode: "true",
         title: "Test Bulk Edited", 
         recipients_list: "1",
         bulk_id: 1,
      );

      $bulkController = new BulkController($this, TEST_PROJECT_1);
      $actionBulkUpdate = $bulkController->action("update", $payload_bulk_update);
      
      $this->assertFalse($actionBulkUpdate["error"], "Error: " . $actionBulkUpdate["message"]);
      $this->assertCount(1, unserialize(($actionBulkUpdate["data"]["bulk"])->bulk_recipients));
   }

   function testDeleteBulkAction(){

      $bulk_id = 1;
      $payload_bulk_delete = array("bulk_id" => $bulk_id);

      $bulkController = new BulkController($this,  TEST_PROJECT_1);
      $actionBulkDelete = $bulkController->action('delete', $payload_bulk_delete);

      $this->assertFalse($actionBulkDelete["error"], $actionBulkDelete["message"] ?? "There was an unknown error.");

      // Check if bulk was deleted
      $payload_read  = array("bulk_id" => "1");
      $actionBulkRead = $bulkController->action('read', $payload_read);
      $this->assertSame($actionBulkRead["message"], "bulk with bulk_id {$bulk_id} not found");
    }


}