<?php

namespace STPH\massSendIt;

if (!class_exists("ActionModel")) require_once(__DIR__ ."/ActionModel.php");

class ScheduleModel extends ActionModel {

    private const TABLE_NAME = 'schedule';
    private static $module;
    private $project_id;

    public function __construct($module) {
        $this->module = $module;
        $this->project_id = $this->module->getProjectId();
    }



}