<?php

namespace STPH\massSendIt;

use Exception;

class NotificationController extends ActionController {
    const TABLE_NAME = "notification";

    protected $module;
    protected $project_id;
    protected $data;

    public function __construct($module, $project_id=null) {
        parent::__construct($module, $project_id);
    }
    
    public function action($task, $data=null) {   
        try {
            $this->data = (object) $data;
            $response = $this->mapTasks($task);
        } catch (\Throwable $th) {
            //dump($th);
            return $this->getActionError($th->getMessage());
        }

        return $this->getActionSuccess($response);        
    }

    private function mapTasks($task) {        
        switch ($task) {
            case 'send':
                return $this->sendTask();
                break;
            default:
                throw new Exception("action not yet implemented");
                break;
        }
    }

    private function doublecheckScheduleExistsBeforeSend($schedule_id) {
        $sql_4 = "SELECT l.record AS record, l.project_id AS project_id, l.log_id AS log_id, p2.value AS schedule_id FROM redcap_external_modules_log l, redcap_external_modules_log_parameters p0, redcap_external_modules_log_parameters p1, redcap_external_modules_log_parameters p2 WHERE l.log_id = p0.log_id AND l.log_id = p1.log_id AND l.log_id = p2.log_id AND p0.name = 'table_name' AND p0.value = 'schedule' AND p1.name = 'status' AND p1.value = 'SENDING' AND p2.name = 'schedule_id' AND p2.value = ?";

        return $this->module->query($sql_4, $schedule_id);        
    }

    private function getSchedulesReadySENDING($sq_ids) {
        $sql = "SELECT redcap_external_modules_log.record, schedule_id.value as schedule_id, status.value as STATUS, 
                message_type.value as message_type, bulk_id.value as bulk_id, table_name.value as TABLE_NAME  
                from redcap_external_modules_log

                left join redcap_external_modules_log_parameters schedule_id
                on schedule_id.log_id = redcap_external_modules_log.log_id
                and schedule_id.name = 'schedule_id'

                left join redcap_external_modules_log_parameters status
                on status.log_id = redcap_external_modules_log.log_id
                and status.name = 'status'

                left join redcap_external_modules_log_parameters message_type
                on message_type.log_id = redcap_external_modules_log.log_id
                and message_type.name = 'message_type'

                left join redcap_external_modules_log_parameters bulk_id
                on bulk_id.log_id = redcap_external_modules_log.log_id
                and bulk_id.name = 'bulk_id'

                left join redcap_external_modules_log_parameters table_name
                on table_name.log_id = redcap_external_modules_log.log_id
                and table_name.name = 'table_name'

                WHERE (
                    status.value = 'SENDING' 
                    AND table_name.value = 'schedule' 
                    AND schedule_id.value IN(".prep_implode($sq_ids).")
                )";

        return $this->module->query($sql, []);
    }

    private function getBatchedSchedulesReadyIDLE() {
        $sql_1 = "SELECT l.project_id, p0.value AS table_name ,p1.value AS status, p2.value AS schedule_id, p3.value AS send_time FROM redcap_external_modules_log l, redcap_external_modules_log_parameters p0, redcap_external_modules_log_parameters p1, redcap_external_modules_log_parameters p2, redcap_external_modules_log_parameters p3 WHERE l.record IS NOT NULL AND l.log_id = p0.log_id AND l.log_id = p1.log_id AND l.log_id =p2.log_id AND l.log_id = p3.log_id AND p0.name = 'table_name' AND p0.value = 'schedule' AND p1.name = 'status' AND p1.value = 'IDLE' AND p2.name = 'schedule_id' AND p3.name = 'send_time' AND p3.value <= NOW() limit " . \SurveyScheduler::determineEmailsPerBatch();
        
        return $this->module->query($sql_1, []);
    }

    /**
     * 
     */
    private function setStatusSendingBeforeSend($q1) {
        $sq_ids = [];
        while($row_1 = $q1->fetch_assoc()) {

            //  Update scheduled notification to status = "SENDING"
            $sql_2 = "UPDATE redcap_external_modules_log l, redcap_external_modules_log_parameters p0, redcap_external_modules_log_parameters p1, redcap_external_modules_log_parameters p2 SET p1.value = 'SENDING' WHERE l.log_id = p0.log_id AND l.log_id = p1.log_id AND l.log_id=p2.log_id AND p0.name = 'schedule_id' AND p0.value = ? AND p1.name = 'status' AND p1.value = 'IDLE' AND p2.name = 'table_name' AND p2.value = 'schedule'";
                            
            db_query($sql_2, [$row_1['schedule_id']]);
            // If already set as SENDING, then skip it here because another cron must've picked it up
            if (db_affected_rows() == 0) continue;
            // Add bq_id's to array
            $sq_ids[] = $row_1['schedule_id'];

        }
        return $sq_ids;
    }

    private function sendTask() {
        try {
            //  Begin database transaction
            $this->beginDbTx();

            $dry = $this->data->dry;

            $sq_ids = [];
            $notificationModel = new NotificationModel($this->module);
                
            // tbd?: deactivate any alerts that are expiring right now (this does not make sense for bulks, since the expiration is relevant for the download - not for the bulk itself)
            // tbd: if any alerts have been stuck in SENDING status for more than one hour (which means they likely won't ever send), then set them back to IDLE.
    
            // Select all scheduled notifications that are ready to be send.
            $q1 = $this->getBatchedSchedulesReadyIDLE();
    
            // Set notifications with SENDING status if they should be sent right now
    
            if($q1->num_rows > 0) {
                $sq_ids = $this->setStatusSendingBeforeSend($q1);
            }
    
    
            // SEND NOTIFICATIONS
            // Initialize counter of number of notification sent
            $numSent = $numFailed = 0;

            if (empty($sq_ids)) {
                $this->endDbTx();
                return array("num_sent" => $numSent, "num_failed" => $numFailed);
            } 
    
            //  Select all schedules that are in status SENDING and ready to be send
            $q3 = $this->getSchedulesReadySENDING($sq_ids);
    
    
            while ($schedule = $q3->fetch_object()) {
    
                // Double check one last time that the notification has not already been sent (just in case a lagging simultaneous cron just sent it).
                $q4 = $this->doublecheckScheduleExistsBeforeSend($schedule->schedule_id);
    
                // If schedule is NOT in SENDING state, then skip this notification and move to next loop.
                if($q4->num_rows < 1) {
                    continue;
                };
    
                //  Send notification
                list($sent, $notification) = $notificationModel->sendNotification($schedule, $dry);
                /**
                 * Set status back to IDLE if it was a dry run
                 * 
                 */
                if($sent === null) {
                    // If email failed to send due to *whatever EMAIL SENDING reason*, set as IDLE.
                    $sql = "UPDATE redcap_external_modules_log_parameters p0 SET p0.value = 'IDLE' WHERE table_name='bulk' AND bulk_id='?' AND p0.name='status'";
    
                    $q = $this->module->query($sql, [$schedule->schedule_id]);
    
                    \Logging::logEvent($sql, "redcap_external_modules_log_parameters", "UPDATE", $schedule->record, "Bulk #{$schedule->schedule_id} reset to IDLE because email sending failed.");
                    $numFailed++;
    
                } else {
                    $numSent++;
                    //  remove from bulk_schedule
                    $where = "schedule_id = ? AND project_id = ? AND table_name='schedule'";
                    $this->module->removeLogs($where, [$schedule->schedule_id, $this->project_id]);
                }
            }
    
    
            if($dry) {
                foreach ($sq_ids as $key => $bq_id) {
                    $sql = "UPDATE redcap_external_modules_log_parameters AS s_status 
                            INNER JOIN redcap_external_modules_log_parameters AS tablename
                            ON s_status.log_id = tablename.log_id
                            INNER JOIN redcap_external_modules_log_parameters AS schedule_id
                            ON s_status.log_id = schedule_id.log_id
                            SET s_status.value = 'IDLE'
                            WHERE tablename.name = 'table_name'
                            AND tablename.value = 'schedule'
                            AND s_status.name = 'status'
                            AND schedule_id.name = 'schedule_id'
                            AND schedule_id.value = ?";
    
                    $this->module->query($sql, [$bq_id]);
                }
           }            
           //  End database transaction
           $this->endDbTx();

           return array("num_sent" => $numSent, "num_failed" => $numFailed);

        } catch (\Throwable $th) {
            //  Rollback database
            $this->rollbackDbTx();
            throw new Exception("There was an exception during sendTask: " . $th->getMessage() . "\n: Trace: " . $th->getTrace());
        }
    }


    /**
     * Use database transactions in case there is an error
     * no data will be saved in any of the save procedures
     * https://www.mysqltutorial.org/mysql-transaction.aspx
     * 
     * 
     * @since 1.0.0
     */
    private function beginDbTx() {    
        $this->module->query("SET autocommit = 0;", []);
        $this->module->query("START TRANSACTION;", []);
    }

    private function endDbTx() {
        $this->module->query("COMMIT;", []);
        $this->module->query("SET autocommit = 1;", []);
    }

    private function rollbackDbTx() {
        $this->module->query("ROLLBACK;", []);
        $this->module->query("SET autocommit = 1;", []);
    }

}