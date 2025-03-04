<?php namespace STPH\massSendIt;

use Exception;
use ReflectionClass;
use ExternalModules\ExternalModules;

require_once __DIR__ . '/../../../redcap_connect.php';
require_once(__DIR__ . '/../bootstrap.php');

abstract class BaseTest extends \ExternalModules\ModuleBaseTest{

    const PATH_FIXTURE_DICT = "/fixtures/data_dictionary_mass_send_it.csv";
    const PATH_FIXTURE_DATA = "/fixtures/data_import_mass_send_it.json";
    const PATH_FIXTURE_DOCS = "/documents";


    static function setUpBeforeClass(): void {
      self::echo("\n=== Setting up before class ===\n\n", 'raw');
      parent::setUpBeforeClass();

      self::createTestProjects();
      self::defineProjectConstants();
      self::runFixtures();

      self::echo("\n=== === === === === === === ===\n\n", 'raw');
    }

    static function tearDownAfterClass(): void {
      self::echo("\n=== Tearing down after class ===\n\n", 'raw');
      parent::tearDownAfterClass();
    }

    /**
     * Fixtures
     * 
     * 0. Clear module data ✓
     * 1. Import data dictionary ✓
     * 2. Import record data ✓
     * 3. Upload documents to file repository ✓
     */    
    public static function runFixtures() {
      self::fixtureClearModuleData();
      self::fixtureDataDictionary();
      self::fixtureRecordData();
      self::fixtureFileRepository();
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

      self::echo("Record data imported. ". count($result["ids"]) . " records added.", "fixture");

    }

    /**
     * Fixture to upload documents into FileRepository
     * 
     */
    static private function fixtureFileRepository() {
      
      //  delete edocs files from EDOC_PATH
      $edocs_to_delete = [];
      $edocs = scandir(EDOC_PATH);
      foreach ($edocs as $key => $edoc) {
        if(str_contains($edoc, "_pid".TEST_PROJECT_1."_")){
          $edocs_to_delete[] = $edoc;
          unlink(EDOC_PATH . DIRECTORY_SEPARATOR . $edoc);
        }
      }

      //  delete from redcap_edocs_metadata
      $sql = "DELETE FROM redcap_edocs_metadata WHERE project_id = ? AND stored_name IN('".implode("','",$edocs_to_delete)."')";
      ExternalModules::query($sql, [TEST_PROJECT_1]);

      //  delete from redcap_docs
      $sql = "DELETE FROM redcap_docs WHERE project_id = ? AND docs_comment = 'Uploaded for Mass Send-It Testing.'";
      ExternalModules::query($sql, [TEST_PROJECT_1]);

      // delete from redcap_docs_folders
      $sql = "DELETE FROM redcap_docs_folders WHERE project_id = ? AND name like 'test_%'";
      ExternalModules::query($sql, [TEST_PROJECT_1]);

      // Create folder and define global folder_id
      $folder_name = 'test_'.time();
      $sql = "insert into redcap_docs_folders (project_id, name, parent_folder_id, dag_id, role_id) values 
              (?, ?, "."NULL".", "."NULL".", "."NULL".")";
      if (!ExternalModules::query($sql, [TEST_PROJECT_1, $folder_name])) {
          throw new Exception("unknown error occurred");
      }
      define('TEST_FOLDER_ID_1', db_insert_id());

      //  get documents from PATH_FIXTURE_DOCS
      $file_names = array_diff(scandir(__DIR__ . self::PATH_FIXTURE_DOCS), array('..', '.'));

      foreach ($file_names as $key => $file_name) {
        $file_path = __DIR__ . self::PATH_FIXTURE_DOCS. "/" . $file_name;
        $temp = tmpfile();
        fwrite($temp, file_get_contents($file_path));
        $file_tmp_name = stream_get_meta_data($temp)["uri"];
        $file_size = filesize($file_tmp_name);
        $file = array(
          "name" => $file_name,
          "tmp_name" => $file_tmp_name,
          "size" => $file_size
        );

        $doc_id = \Files::uploadFile($file, TEST_PROJECT_1);
        if ($doc_id == 0) {
          throw new Exception("unknown error: could not upload file.");
        }

        //  add file to repository
        \REDCap::addFileToRepository($doc_id, TEST_PROJECT_1,"Uploaded for Mass Send-It Testing.");

        //  get docs_id
        $sql = "select docs_id from redcap_docs_to_edocs where doc_id = ?";
        $result = ExternalModules::query($sql, [$doc_id]);
        $docs_id = $result->fetch_assoc() ["docs_id"];
        //$docs_id = db_result(db_query($sql), 0);

        //  add file to folder
        $sql2 = "insert into redcap_docs_folders_files (docs_id, folder_id) values (?, ?)";
        ExternalModules::query($sql2, [$docs_id, TEST_FOLDER_ID_1]);
        //db_query($sql2);

      }
      
      //  upoad documents to fileRepository (mocking $_FILES) FileRepository::upload()
      self::echo("File Repository adjusted. ".count($file_names)." documents added to ".$folder_name."(".TEST_FOLDER_ID_1.").", "fixture");
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