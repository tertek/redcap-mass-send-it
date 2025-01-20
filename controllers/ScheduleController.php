<?php

namespace STPH\massSendIt;

use Exception;

class ScheduleController extends ActionController {

    const TABLE_NAME = "schedule";

    protected $module;
    protected $project_id;
    protected $data;

    public function __construct($module, $project_id=null) {
        parent::__construct($module, $project_id);
    }

    public function action($task, $data) {           
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
            case 'create':
                return $this->createTask();
                break;
            default:
                throw new Exception("action not yet implemented");
                break;
        }
    }

    private function createTask() {
        $bulk_id = $this->data->bulk_id;
        $overwrite = $this->data->overwrite;

        $scheduleModel = new ScheduleModel($this->module);
        
        //check if we have old scheduled entries for this bulk
        $scheduled_old = $scheduleModel->readScheduled($bulk_id);
        
        if(count($scheduled_old) > 0 && !$overwrite) {
            throw new Exception("Old scheduled found - overwrite not allowed. Aborting reschedule.");
        } 

        if(count($scheduled_old) > 0) {
            //  remove old scheduled
            $scheduleModel->deleteScheduleByBulk($bulk_id);
        }

        //  create schedules
        $scheduled = $scheduleModel->createSchedule($bulk_id);

        return array("scheduled" => $scheduled);

    }
}