<?php

namespace STPH\massSendIt;

use Records;
use Piping;
use Message;
use Exception;

class NotificationModel extends ActionModel {

    private static $module;
    private const TABLE_NAME = 'notification';
    private $bulk;

    public $project_id;
    public $event_id;
    public $record;

    public int $bulk_id;
    public int $notification_id;
    public bool $was_sent;
    public string $time_sent;
    public string $error_sent;
    public string $email;
    public string $sendit;


    public function __construct($module) {
        $this->module = $module;
        $this->project_id = $this->module->getProjectId();
        $this->event_id = $this->module->getEventId();
    }

    private function getBulk($bulk_id) {
        $sql = "SELECT bulk_id, email_to, email_from, email_display, email_first_message, email_first_subject, email_second_subject, email_second_message, use_random_pass, use_second_email, file_repo_extension, file_repo_folder_id, file_repo_reference WHERE table_name='bulk' AND bulk_id=?";
        $q = $this->module->queryLogs($sql, [$bulk_id]);
        return $q->fetch_object();        
    }

    public function sendNotification($schedule, $dry) {

        //  set bulk data
        $this->bulk= $this->getBulk($schedule->bulk_id);

        //  set record data
        $data = Records::getData($this->project_id, 'array', $schedule->record);
        $this->record = $data[$schedule->record][$this->event_id];

        //  Get email header 
        list($email_to, $email_from, $email_display) = $this->getEmailHeader();

        //  Get send-it data
        $sendIt = $this->getSendItData($schedule, $email_to);

        //  Get share file variables
        $shareFileVariables = $this->getShareFileVariables($sendIt);

        //  Get message data
        list($message, $subject) = $this->getMessageData($schedule, $shareFileVariables);

        //  Set email data
        $email = (object) array(
            "to" => $email_to,
            "from" => $email_from,
            "display" => $email_display,
            "subject" => $subject,
            "message" => $message
        );

        //  Send Email
        list($sent, $error) = $this->sendEmail($email, $dry);

        //  Set notification
        $notification = array(
            "project_id" => $this->project_id,
            "event_id" => $this->event_id,
            "record" => $schedule->record,
            "table_name" => self::TABLE_NAME,
            "bulk_id" => $this->bulk->bulk_id,
            "notification_id" => $this->get_max_key_id('notification') + 1,
            "message_type" => $schedule->message_type,
            "was_sent" => $sent,
            "time_sent" => $sent ? date('Y-m-d H:i:s') : "",
            "error_sent" => $error,
            "email" => json_encode($email),
            "sendit" => json_encode($sendIt)
        );

        //  add a reference parameter for secondary mail
        if($this->bulk->use_second_email && $schedule->message_type == "primary") {
            $secondary_ref = $this->getSecondaryReference($this->bulk->bulk_id, $schedule->record, $email_to);
            $notification["secondary_ref"] =  $secondary_ref;
        }

        //  store notification
        $this->module->log("create_notification", $notification);

        return array($sent, $notification);
    }

    private function getSecondaryReference($bulk_id, $record, $email_to) {
        $data = $bulk_id . $record . $email_to;
        return hash('sha256', $data);
    }

    private function sendEmail($email, $dry=false) {
        $this_record=$this_event_id=$this_form=$this_instance=null;

        if($dry) {
            sleep(0.5);
            return array(true, null);
        }

        $mail = new Message($this->project_id, $this_record, $this_event_id, $this_form, $this_instance);

        $mail->setTo($email->to);
        $mail->setFrom($email->from);
        $mail->setFromName($email->display);
        $mail->setSubject($email->subject);
        $mail->setBody($email->message, true);

        $sent = $mail->send();

        return array($sent, $sent == false ?? $mail->ErrorInfo);
    }

    private function getMessageData($schedule, $shareFile) {

        $message = "";
        $subject = "";

        if($schedule->message_type == "primary") {
            $message = $this->bulk->email_first_message;
            $subject = $this->bulk->email_first_subject;
        } else {
            $message = $this->bulk->email_second_message;
            $subject = $this->bulk->email_second_subject;
        }

        //  Replace [share-file-url], [share-file-link] and [share-file-password]
        $message = str_replace("[share-file-url]", $shareFile->url, $message);
        $message = str_replace("[share-file-link]", $shareFile->link, $message);
        $message = str_replace("[share-file-password]", $shareFile->password, $message);
        
        //  Pipe REDCap data / smart variables
        $message = Piping::replaceVariablesInLabel($message, $schedule->record, null, 1, $this->record, true, $this->project_id, false);
        $subject = Piping::replaceVariablesInLabel($subject, $schedule->record, null, 1, $this->record, true, $this->project_id, false);

        return array($message, $subject);
    }

    private function getSendItData($schedule, $email_to) {
       
        //  In case it is a primary message, we need to first add the document and recipient to the database
        if($schedule->message_type == 'primary') {

            //  get document data first
            $document_reference = $this->record[$this->bulk->file_repo_reference];
            
            if(empty($document_reference)) {
                throw new Exception("The document reference for record ".$this->record[$this->module->getRecordIdField()]." is empty.");
            }
            $document_name = $document_reference . "." . $this->bulk->file_repo_extension;
            $document = $this->get_document($this->project_id, $this->bulk->file_repo_folder_id, $document_name);        

            //  add send-it document to get sendit_id
            $sendit_docs_id = $this->add_sendit_document($document);

            //  get password
            $custom_pwd = $this->bulk->use_random_pass ? null : $this->record[$this->bulk->custom_pass_field];

            //  add recipient to get key and password
            $sendItData = $this->add_sendit_recipient($email_to, $sendit_docs_id, $custom_pwd);
        }

        // In case of a secondary message, we retrieve the sendit data from the database
        elseif( $this->bulk->use_second_email && $schedule->message_type == 'secondary') {       
            
            $secondary_ref = $this->getSecondaryReference($this->bulk->bulk_id, $schedule->record, $email_to);

            $sql = "SELECT sendit WHERE table_name = 'notification' AND secondary_ref = ? LIMIT 1";
            $result = $this->module->queryLogs($sql, [$secondary_ref]);
            $sendit_json = ($result->fetch_assoc())["sendit"];
            
            $sendItData = json_decode($sendit_json);
        }

        if(empty($sendItData)) {
            throw new Exception("Critical Error: Could not find sendItData! schedule_id: " . $schedule->schedule_id . " secondary_ref: " . $secondary_ref);
        }
        
        return $sendItData;
    }

    private function add_sendit_document($document) {
        $userid = "SYSTEM";
        $fileLocation = 2;  // REDCap internal default for file repository
        
        //  tbd: set this from bulk_expiration
        $expireDays = 14;
        $expireDate = date('Y-m-d H:i:s', strtotime("+$expireDays days"));

        $originalFilename = $document['docName'];
        $fileSize = $document['docSize'];
        $newFilename = $document['storedName'];
        $fileType = $document['docType'];
        $fileId = $document['fileId'];
        $send = 0;

        // Add entry to sendit_docs table
        $query = "INSERT INTO redcap_sendit_docs (doc_name, doc_orig_name, doc_type, doc_size, send_confirmation, expire_date, username,
        location, docs_id, date_added)
        VALUES ('".db_escape($newFilename)."', '".db_escape($originalFilename)."', '".db_escape($fileType)."', '".db_escape($fileSize)."', $send, '$expireDate', '".db_escape($userid)."',
        $fileLocation, '".db_escape( $fileId)."', '".NOW."')";
        db_query($query);
        $sendit_docs_id = db_insert_id();

        return $sendit_docs_id;
    }    

    private function add_sendit_recipient($email_to, $sendit_docs_id, $custom_pwd=null) {
        // create key for unique url
        $key = strtoupper(substr(uniqid(sha1(random_int(0,(int)999999))), 0, 25));

        // create password
        if($custom_pwd == null) {
            $pwd = generateRandomHash(8, false, true);
        } else {
            $pwd = $this->module->escape($custom_pwd);
        }

        $query = "INSERT INTO redcap_sendit_recipients (email_address, sent_confirmation, download_date, download_count, document_id, guid, pwd) VALUES ('".db_escape($email_to)."', 0, NULL, 0, '".db_escape($sendit_docs_id)."', '$key', '" . md5($pwd) . "')";
        db_query($query);

        $sendItData = (object) array(
            "docs_id" => $sendit_docs_id,
            "recipients_id"=> db_insert_id(),
            "url_key" => $key,
            "url_pwd" => $pwd
        );

        return $sendItData;
    }     

    private function getShareFileVariables($sendItData) {

        $share_file_url = APP_PATH_SURVEY_FULL. 'index.php?__passthru=index.php&route=' . urlencode('SendItController:download') . '&'. $sendItData->url_key;
        $share_file_link = '<a target="_blank" href="'.$share_file_url.'">Download Link</a>';
        $share_file_password = '<pre>'.$sendItData->url_pwd.'</pre>';

        $shareFileVariables = (object) array(
            "url" => $share_file_url,
            "link" => $share_file_link,
            "password" => $share_file_password
        );

        return $shareFileVariables;
    }


    private function getEmailHeader() {
        $email_to = "";
        $email_from = $this->bulk->email_from;
        $email_display = $this->bulk->email_display;

        //  Retrieve email from record data        
        $email_to_field = str_replace(array('[', ']' ), '', $this->bulk->email_to);
        $email_to = $this->record[$email_to_field];

        return [$email_to, $email_from,  $email_display];
    }

    private function get_document($project_id, $folder_id, $docsName) {
        $sql = "SELECT d.docs_name as docName, d.docs_name as storedName, d.docs_size as docSize, d.docs_id as fileId, f.folder_id as folderId, d.docs_type as docType FROM redcap_docs as d JOIN redcap_docs_folders_files AS f ON d.docs_id=f.docs_id WHERE d.project_id = ? AND f.folder_id = ? AND d.docs_name = ?";

        $q = $this->module->query($sql, [$project_id, $folder_id, $docsName]);
        
        if($q->num_rows == 0) {
            throw new Exception("Could not find document in database.");
        }
        $document = $q->fetch_assoc();

        return $document;
    }    

    protected function get_max_key_id() {
        $key = static::TABLE_NAME;
        $sql_get_max_key_id = "SELECT max(cast({$key}_id.value AS UNSIGNED)) AS max_key_id 
            from redcap_external_modules_log
            left join redcap_external_modules_log_parameters {$key}_id
            on {$key}_id.log_id = redcap_external_modules_log.log_id
            and {$key}_id.name = '{$key}_id'
            left join redcap_external_modules_log_parameters table_name
            on table_name.log_id = redcap_external_modules_log.log_id
            and table_name.name = 'table_name'
            WHERE redcap_external_modules_log.external_module_id = (SELECT external_module_id FROM redcap_external_modules WHERE directory_prefix = '{$this->module->getModulePrefix()}') and (table_name.value = '{$key}' and redcap_external_modules_log.project_id = ?)";
        $result = $this->module->query($sql_get_max_key_id, [$this->project_id]);
        $max_key_id = $result->fetch_object()->max_key_id ?? 0;
        return $max_key_id;
    }
   
}