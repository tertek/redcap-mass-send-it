<?php

// Set the namespace defined in your config file
namespace STPH\massSendIt;

use Project;
use \ExternalModules\ExternalModules; 

if( file_exists("vendor/autoload.php") ){
    require 'vendor/autoload.php';
}

if (!class_exists("BulkController")) include_once("controllers/BulkController.php");
if (!class_exists("Bulk")) include_once("models/Bulk.php");

// Declare your module class, which must extend AbstractExternalModule 
class massSendIt extends \ExternalModules\AbstractExternalModule {

    private const IS_CRON_ENABLED = false;

    private const ALLOWED_FILE_EXTENSIONS = [
        "pdf","doc","docx","csv","html","txt","svg", "bmp", "jpg", "odt", "xlsx"
    ];

    private const NUM_NOTIFICATIONS_PER_PAGE = 15;


    private function testModule() {

        $i = 1;

        $recipients = "1,2";
        $recipients_new = "1,2";

        $payload_create = '[{"name":"bulk_title","value":"Test Bulk '.$i.'"},{"name":"bulk_type","value":"list"},{"name":"bulk_recipients_list","value":"'.$recipients.'"},{"name":"bulk_recipients_logic","value":""},{"name":"file_repo_folder_id","value":"6"},{"name":"file_repo_extension","value":"pdf"},{"name":"file_repo_reference","value":"example_document_reference"},{"name":"email_display","value":"REDCap Bulk Send"},{"name":"email_from","value":"user@admin.com"},{"name":"email_to","value":"[email]"},{"name":"email_first_subject","value":"New Document available"},{"name":"email_first_message","value":"Hello [firstname] [lastname],<br>a file has been uploaded for you. A second follow-up email will be sent containing the password for retrieving the file at the link below.<br>You can access your document here: [share-file-link]<br>If the link does not open, copy and paste the following url into your browser:<br>[share-file-url].<br><br>"},{"name":"password_type","value":"random"},{"name":"custom_pass_field","value":""},{"name":"use_second_email","value":"yes"},{"name":"email_second_subject","value":"Access to your document"},{"name":"email_second_message","value":"Hello,<br>below is the password for downloading the file mentioned in the previous email:<br>[share-file-password]"},{"name":"bulk_schedule","value":"12-31-2024 15:00"},{"name":"bulk_expiration","value":""},{"name":"is_edit_mode","value":"false"}]';
        
        $payload_update = '[{"name":"bulk_id", "value":"1"},{"name":"bulk_title","value":"Test Bulk Edited"},{"name":"bulk_type","value":"list"},{"name":"bulk_recipients_list","value":"'.$recipients_new.'"},{"name":"bulk_recipients_logic","value":""},{"name":"file_repo_folder_id","value":"6"},{"name":"file_repo_extension","value":"pdf"},{"name":"file_repo_reference","value":"example_document_reference"},{"name":"email_display","value":"REDCap Bulk Send"},{"name":"email_from","value":"user@admin.com"},{"name":"email_to","value":"[email]"},{"name":"email_first_subject","value":"New Document available"},{"name":"email_first_message","value":"Hello [firstname] [lastname],<br>a file has been uploaded for you. A second follow-up email will be sent containing the password for retrieving the file at the link below.<br>You can access your document here: [share-file-link]<br>If the link does not open, copy and paste the following url into your browser:<br>[share-file-url].<br><br>"},{"name":"password_type","value":"random"},{"name":"custom_pass_field","value":""},{"name":"use_second_email","value":"yes"},{"name":"email_second_subject","value":"Access to your document"},{"name":"email_second_message","value":"Hello,<br>below is the password for downloading the file mentioned in the previous email:<br>[share-file-password]"},{"name":"bulk_schedule","value":"12-31-2027 15:00"},{"name":"bulk_expiration","value":""},{"name":"is_edit_mode","value":"true"},{"name":"bulk_order","value":"0"}]';

        $payload_delete = array("bulk_id" => 1);

        $bulkController = new BulkController($this, $this->getProjectId());
        //$response = $bulkController->action('create', $payload_create);
        //$response = $bulkController->action("update", $payload_update);
        //$response = $bulkController->action('delete', $payload_delete);
        //$response = $bulkController->action('read', array("bulk_id" => "1"));


        
    
        //dump($response);
        exit();
    }    

    public function renderModuleProjectPage() {

        $sql = "SELECT max(cast(bulk_id as UNSIGNED)) as max_key_id WHERE table_name = 'bulk' and project_id=22";
        dump($this->getQueryLogsSql($sql));

        //$this->testModule();

        $this->includeJavascript();
        if(!isset($_GET['log']) || $_GET['log'] != 1) {
            $this->renderBulks();
        } else {
            $this->renderNotifications();
        }
    }

    private function renderBulks() {
        $bulks = $this->getBulks();
        dump($bulks);
    }

    private function renderNotifications() {
        $notifications = $this->getNotifications();
        dump($notifications);
    }

    private function getBulks() {
        $fields = "timestamp, bulk_title, bulk_id, bulk_order, bulk_recipients, bulk_type";
        $sql = "SELECT $fields WHERE table_name = 'bulk'";
        
        $result = $this->queryLogs($sql, []);
        $bulks = [];
        while($bulk = $result->fetch_object()) {
            $bulks[] = $bulk;
        }
        return $bulks;
    }

    private function getNotifications() {

        $fields = "id, bulk_id, event_id, record, sendit_docs_id, sendit_recipients_id, time_sent, was_sent, error, log";
        $sql = "SELECT $fields WHERE table_name='notifications'";
        $result = $this->queryLogs($sql, []);
        $notifications = [];
        while($notification = $result->fetch_object()) {
            //  $log = json_decode($notification->log, false);
            //  $notification->log = $log;
            $notifications[] = $notification;
        }
        return $notifications;
    }
    
   /**
    * Include JavaScript files
    *
    * @since 1.0.0
    */
    private function includeJavascript() {
        $this->initializeJavascriptModuleObject();
        ?>
        <script> 
             /**
             * Passthrough:
             * JavascriptModuleObject JS(M)O
             * Data Transfer Object DTO
             * 
             */
            const JSO_STPH_BULK_SEND = <?=$this->getJavascriptModuleObjectName()?>;
            const DTO_STPH_BULK_SEND = <?=$this->getDataTransferObject()?>;
        </script>
        <script 
            type="module"  
            src="<?php print $this->getUrl('dist/main.js'); ?>">
            /**
             * Include JavaScript Module
             * https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Modules
             */
        </script>
        <?php
    }

    private function getDataTransferObject() {
        $DTO = array(            
            "modal_defaults"  => array(
                "repo_folders" => $this->getRepoFolders(),
                "repo_extensions" => self::ALLOWED_FILE_EXTENSIONS,
                "repo_fields" => $this->getFields(),
                "form_defaults" => [
                    "email_first_message" => $this->tt('default_first_message'),
                    "email_first_subject" => $this->tt('default_first_subject'),
                    "email_second_message" => $this->tt('default_second_message'),
                    "email_second_subject" => $this->tt('default_second_subject'),  
                ]
            )
        );

        return json_encode($DTO);
    }

    private function getRepoFolders() {
        $project_id = $this->getProjectId();
        $sql = "SELECT folder_id, name, parent_folder_id FROM redcap_docs_folders WHERE project_id = ?";
        $result = $this->query($sql, [$project_id]);

        $folders = [];
        while($row = $result->fetch_assoc()){
            $folders[] = $row;
        }

        return $this->escape($folders);
    }

    private function getFields() {
        $project = new Project($this->getProjectId());
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

        return  $this->escape($fields);
    }
    
   /**
    * Include Style files
    *
    * @since 1.0.0    
    */
    private function includeCSS() {
        ?>
        <link rel="stylesheet" href="<?= $this->getUrl('style.css')?>">
        <?php
    }


    /**
     * Cron Job Function
     * 
     */
    public function sendNotificationsViaCron() {

        if(!self::IS_CRON_ENABLED) {
            return;
        }
    }
    
}