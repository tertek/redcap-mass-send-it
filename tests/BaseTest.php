<?php namespace STPH\massSendIt;
use ReflectionClass;
use ExternalModules\ExternalModules;
// For now, the path to "redcap_connect.php" on your system must be hard coded.
require_once __DIR__ . '/../../../redcap_connect.php';

abstract class BaseTest extends \ExternalModules\ModuleBaseTest{

    const PATH_FIXTURE_DICT = "/fixtures/data_dictionary_mass_send_it.csv";
    const PATH_FIXTURE_DATA = "/fixtures/data_import_mass_send_it.json";
    const PATH_FIXTURE_DOCS = "/documents";


    static function setUpBeforeClass(): void {
      self::echo("\n=== Setting up before class ===\n\n", 'raw');
      parent::setUpBeforeClass();

      self::createTestProjects();
      self::defineProjectConstants();

      /**
       * Fixtures
       * 
       * 0. Clear module data ✓
       * 1. Import data dictionary ✓
       * 2. Import record data ✓
       * 3. Upload documents to file repository ⨉ : must be done manually
       */
      self::fixtureClearModuleData();
      self::fixtureDataDictionary();
      self::fixtureRecordData();
      self::fixtureFileRepository();
      self::echo("\n=== === === === === === === ===\n\n", 'raw');
    }

    static function tearDownAfterClass(): void {
      self::echo("\n=== Tearing down after class ===\n\n", 'raw');
      parent::tearDownAfterClass();

      /**
       * Fixtures
       * 
       * 0. Delete documents in file repository x
       * 
       * 
       */

    }

    /**
     * Call private methods
     * https://stackoverflow.com/a/8702347/3127170
     */
    public static function getPrivateMethod($obj, $name, array $args= []) {
      $class = new ReflectionClass($obj);
      $method = $class->getMethod($name);
      $method->setAccessible(true); // Use this if you are running PHP older than 8.1.0
      return $method->invokeArgs($obj, $args);
    }

    /**
     * REDCap Test Helpers
     * https://github.com/Research-IT-Swiss-TPH/redcap-device-tracker/blob/unit-tests/tests/BaseTest.php
     */

    /**
     * Create Test Projects 
     * 
     */     
    static function createTestProjects() {
      // Get test PIDs
      ExternalModules::getTestPIDs();
      self::echo("Test Projects have been retrieved. (PIDs: ". $GLOBALS['external_modules_test_pids'] . ")");
    }

    static private function defineProjectConstants() {
      define('TEST_PROJECT_1', explode(',', $GLOBALS['external_modules_test_pids'])[0]);
      define('TEST_PROJECT_2', explode(',', $GLOBALS['external_modules_test_pids'])[1]);
      define('TEST_PROJECT_3', explode(',', $GLOBALS['external_modules_test_pids'])[2]);
    }

    /**
     * Fixture to clear module data from test project
     * 
     */
    static private function fixtureClearModuleData() {
      $sql = "DELETE FROM redcap_external_modules_log WHERE project_id = ?";
      ExternalModules::query($sql, [TEST_PROJECT_1]);
      self::echo("Bulk Data has been cleared.", "fixture");
    }


    /**
     * Fixture to import data dictionary into  Project
     * 
     */
    static private function fixtureDataDictionary() {
            
      $dictionary_array = \Design::excel_to_array( dirname(__FILE__) . self::PATH_FIXTURE_DICT, "," );
      \MetaData::save_metadata($dictionary_array, false, true, TEST_PROJECT_1);

      self::echo("Data Dictionary imported.", "fixture");            

    }

    /**
     * Fixture to import record data into Project
     * 
     */
    static private function fixtureRecordData() {

      $json = file_get_contents( dirname(__FILE__) . self::PATH_FIXTURE_DATA);
      $params = array(
          "project_id" => TEST_PROJECT_1,
          "dataFormat" => 'json',
          "data" => $json
      );
      $result = \Records::saveData($params);

      self::echo(count($result["ids"]) . " records added.", "fixture");

    }

    /**
     * Fixture to upload documents into FileRepository
     * 
     */
    static private function fixtureFileRepository() {

      //  get documents from PATH_FIXTURE_DOCS
      //  move documents to fileRepository
      //  define global for folder_id
      self::echo("0 documents added to File Repository (manual process to be fixed).", "fixture");

    }


    /**
     * Output formatted message string to console during testing.
     * 
     */
    protected static function echo($message, $mode = "")
    {
        // if output buffer has not started yet
        if (ob_get_level() == 0) {
            // current buffer existence
            $hasBuffer = false;
            // start the buffer
            ob_start();
        } else {
            // current buffer existence
            $hasBuffer = true;
        }


        // echo message to output with color and unicode
        if($mode != 'raw') {

            //$unicode = "\u{2588}\u{2588}";
            $unicode = "\u{2724}";           
            $format = " \33[34m".$unicode."\33[0m \33[44m";

            //  Different color for fixtures
            if($mode == "fixture") {
                $format = " \33[35m".$unicode."\33[0m \33[45mFixture success: ";
            }

             $message = $format . $message . "\33[0m\n";
        }

        echo $message;

        // flush current buffer to output stream
        ob_flush();
        flush();
        ob_end_flush();

        // if there were a buffer before this method was called
        //      in my version of PHPUNIT it has its own buffer running
        if ($hasBuffer) {
            // start the output buffer again
            ob_start();
        }
    }

    protected static function getRandomString() {
      $bytes = random_bytes(16);
      return bin2hex($bytes);
    }

}