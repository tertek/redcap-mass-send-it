<?php namespace STPH\massSendIt;
use ReflectionClass;
// For now, the path to "redcap_connect.php" on your system must be hard coded.
require_once __DIR__ . '/../../../redcap_connect.php';

abstract class BaseTest extends \ExternalModules\ModuleBaseTest{

        //  https://stackoverflow.com/a/8702347/3127170
        public static function getPrivateMethod($obj, $name, array $args= []) {
            $class = new ReflectionClass($obj);
            $method = $class->getMethod($name);
            $method->setAccessible(true); // Use this if you are running PHP older than 8.1.0
            return $method->invokeArgs($obj, $args);
          }
    
}