<?php namespace STPH\massSendIt;

use Exception;
use Project;

//  Include classes
require_once(__DIR__ . "/bootstrap.php");

class massSendIt extends \ExternalModules\AbstractExternalModule {

    private const IS_CRON_ENABLED = true;

    private const ALLOWED_FILE_EXTENSIONS = [
        "pdf","doc","docx","csv","html","txt","svg", "bmp", "jpg", "odt", "xlsx"
    ];

    private const NUM_NOTIFICATIONS_PER_PAGE = 100;

    public function redcap_module_ajax($action, $payload, $project_id) {

        $payload = (object) $payload;

        switch ($action) {
            case 'bulk':
                $bulkController = new BulkController($this, $project_id);
                $response = $bulkController->action($payload->task, $payload->data);
                break;

            case 'schedule':
                $scheduleController = new ScheduleController($this, $project_id);
                $response = $scheduleController->action($payload->task, $payload->data);
                break;
            
            default:
                $response = null;
                break;
        }

        return json_encode($response);
    }

    /**
     * Send Notifications
     * Called from Cron
     * 
     */
    private function sendNotifications($dry=false) {

        $notificationController = new NotificationController($this);
        $response = $notificationController->action('send', array("dry" => $dry));

        //  Special response in case of cron job and crob manual page
        if(php_sapi_name() === 'cli' || PAGE === "cron.php") {

            if($response["error"] == true) {
                throw new Exception("There was an error when the cron job tried to call sendNotifications: " . $response["message"]);
            }
            $cronResponse = array($response["data"]["num_sent"] ?? 0, $response["data"]["num_failed"] ?? 0);

            return $cronResponse;
        }

        return $response;
    }    

    public function renderModulePage() {

        //$this->sendNotifications(false);
        
        $this->includeView('page.header');

        if(!isset($_GET['log']) || $_GET['log'] != 1) {
            $this->includeView('bulks.page');
            $this->includeView('bulks.modal');
        } else {
            $this->includeView('notifications.page');
        }
        
        $this->includeJavascript();
    }

    /**
     * To be replaced with Twig Views
     * Available from REDCap Version 14.6.4
     * 
     */
    private function includeView($name) {
        $path = 'views/view.'.$name.'.php';
        include_once($path);
    }

    /**
     * Calld from view
     * 
     */
    public function getBulks() {
        $fields = (new BulkModel($this))->getFields();        
        $sql = "SELECT $fields WHERE table_name = 'bulk'";
        
        $result = $this->queryLogs($sql, []);
        $bulks = [];
        while($bulk = $result->fetch_object()) {
            $bulks[] = $bulk;
        }
        return $bulks;
    }


    private function getNotifications() {

        $fields = "id, bulk_id, event_id, record, time_sent, was_sent, error, email, sendit";
        //  $fields = (new NotificationModel($this))->getFields();
        $sql = "SELECT $fields WHERE table_name='notification'";
        $result = $this->queryLogs($sql, []);
        $notifications = [];
        while($notification = $result->fetch_object()) {
            //  $log = json_decode($notification->log, false);
            //  $notification->log = $log;
            $notifications[] = $notification;
        }
        return $notifications;
    }    

    public function getScheduledCount($bulk_id) {
        $sql = "SELECT schedule_id WHERE table_name='schedule' AND bulk_id = ?";
        $result = $this->queryLogs($sql, [$bulk_id]);
        return $result->num_rows;
    }

    public function getSentCount($bulk_id) {
        $sql = "SELECT notification_id WHERE table_name='notification' AND bulk_id = ?";
        $result = $this->queryLogs($sql, [$bulk_id]);
        return $result->num_rows;
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
    * Include JavaScript files
    *
    * @since 1.0.0
    */
    public function includeJavascript() {
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
            src="<?php print $this->getUrl('dist/mass_send_it.js'); ?>">
            /**
             * Include JavaScript Module
             * https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Modules
             */
        </script>
        <?php
    }    
    
   /**
    * Include Style files
    *
    * @since 1.0.0    
    */
    public function includeCSS() {
        ?>
        <!-- We are using Alerts.css since the styling has not change -->
        <link rel="stylesheet" type="text/css" href="<?php echo APP_PATH_CSS ?>Alerts.css" media="screen,print">
        <link rel="stylesheet" type="text/css" href="<?= $this->getUrl('style.css')?>" media="screen,print">
        <?php
    }

    /**
     * Only needed when EMF is installed
     */
    public function getModulePath(){
        if(EXTMOD_EXTERNAL_INSTALL) {
            return "/redcap/external_modules";
        } else {
            return APP_PATH_WEBROOT . "ExternalModules";
        }
    }
    
    public function getModulePrefix() {
        return \ExternalModules\ExternalModules::getParseModuleDirectoryPrefixAndVersion($this->getModuleDirectoryName())[0];
    }


    /**
     * Cron Job 
     * Called every 30 seconds
     * @param array $cronAttributes A copy of the cron's configuration block from config.json.
     * https://github.com/vanderbilt-redcap/external-module-framework-docs/blob/main/crons.md
     * 
     */
    function cronSendNotifications($cronAttributes) {

        //  exit if cron is disabled for the module
        if(!self::IS_CRON_ENABLED) {
            return;
        }

        //  retrieve dry flag from cron attributes
        $dry = $cronAttributes["dry"];

        //  set project context within cron and call sendNotifications
        foreach($this->getProjectsWithModuleEnabled() as $localProjectId){
            $this->setProjectId($localProjectId);

            $_GET['pid'] = $localProjectId;
            $this->log("Running mass_send_it from cron", $cronAttributes);

            list($numSent, $numFailed) = $this->sendNotifications($dry) ;

            echo "(PID {$localProjectId}) Notifications sent: {$numSent}. Notifications failed: {$numFailed}.";
        }


    }
    
}