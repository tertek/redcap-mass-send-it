<?php

namespace STPH\massSendIt;

require_once(__DIR__ . "/ActionController.php");
require_once(__DIR__ ."./../models/ScheduleModel.php");
use Exception;

class ScheduleController extends ActionController {

    const TABLE_NAME = "SCHEDULE";

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
        $scheduleModel = new ScheduleModel($this->module);
    }
}