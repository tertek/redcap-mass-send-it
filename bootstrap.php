<?php namespace STPH\massSendIt;

if (file_exists("vendor/autoload.php")) require 'vendor/autoload.php';

/**
 * Helpers
 */
if (!class_exists("dropdownHelper")) require_once(__DIR__ . "/helpers/dropdownHelper.php");
if (!class_exists("validationHelper")) require_once(__DIR__ . "/helpers/validationHelper.php");


/**
 * Controllers
 */
if (!class_exists("ActionController")) require_once(__DIR__ . "/controllers/ActionController.php");
if (!class_exists("BulkController")) require_once(__DIR__ . "/controllers/BulkController.php");
if (!class_exists("ScheduleController")) require_once(__DIR__ . "/controllers/ScheduleController.php");
if (!class_exists("NotificationController")) require_once(__DIR__ . "/controllers/NotificationController.php");

/**
 * Models
 */
if (!class_exists("ActionModel")) require_once(__DIR__ . "/models/ActionModel.php");
if (!class_exists("BulkModel")) require_once(__DIR__ . "/models/BulkModel.php");
if (!class_exists("ScheduleModel")) require_once(__DIR__ . "/models/ScheduleModel.php");
if (!class_exists("NotificationModel")) require_once(__DIR__ . "/models/NotificationModel.php");