<?php

/**
 * Mass Send-It Download Page
 * The download page is heavily based on the original REDCap Send-It implementation:
 * \Classes\SendIt.php SendIt:renderDownloadPage
 * \Views\SendIt\Download.php
 * 
 * If download page is not enabled through module's system settings, a redirect to native REDCap Send-It download page will be triggered.
 * The page is configured as a no-auth page. 
 * CSRF protection has been added to the post action.
 * Custom texts can be retrieved from project settings of the module and configured at bulk level.
 * 
 * DISCLAIMER: ONLY LOCAL STORAGE IS SUPPORTED. IN ANY OTHER CASE AN EXCEPTION WILL BE THROWN.
 * 
 */

namespace STPH\massSendIt;

use Exception;
use HtmlPage;
use Logging;
use DateTimeRC;
use Message;

global $error, $doc_size, $edoc_storage_option;

// Initial variables
$error = '';
$key = '';
$data = [];
$useCDP = false;


//  Redirect to REDCap Send-It Controller if system setting has not been set to true
if($module->getSystemSetting("system-use-mass-sendit-download-page") !== true) {
    $send_it_url = APP_PATH_SURVEY_FULL. 'index.php?__passthru=index.php&route=' . urlencode('SendItController:download') . '&'. $_GET["key"];

    redirect($send_it_url);
}

//  Die if there is no project id
if(!isset($_GET["pid"]) || empty($_GET["pid"])) {
    print "Invalid route. No project id was found.";
    die();
}

// Setup custom download page index if supplied
if(isset($_GET["cdpi"])) {
    $cdpi = $module->escape($_GET["cdpi"]);
    //  check if index exists on download page data
    if(array_key_exists($cdpi, $module->getSubSettings("project-custom-download-page"))) {
        $data = $module->getSubSettings("project-custom-download-page")[$cdpi];
        $useCDP = true;
    }
}

// Check file's expiration and if key is valid
if (isset($_GET["key"]) || !empty($_GET["key"])) {
    $key = $module->escape($_GET["key"]);

    $sql = "select r.*, d1.*,
            (select e.gzipped from redcap_docs d, redcap_docs_to_edocs de, redcap_edocs_metadata e
            where d.docs_id = de.docs_id and de.doc_id = e.doc_id and d.docs_id = d1.docs_id) as gzipped
            from redcap_sendit_recipients r, redcap_sendit_docs d1
            where r.document_id = d1.document_id and r.guid = ?";

    $result = $module->query($sql, [$key]);

    // $query = "select r.*, d1.*,
    //             (select e.gzipped from redcap_docs d, redcap_docs_to_edocs de, redcap_edocs_metadata e
    //             where d.docs_id = de.docs_id and de.doc_id = e.doc_id and d.docs_id = d1.docs_id) as gzipped
    //             from redcap_sendit_recipients r, redcap_sendit_docs d1
    //             where r.document_id = d1.document_id and r.guid = '".db_escape($key)."'";
    //  $result = db_query($query);
    //  if (db_num_rows($result))
    if($result->num_rows > 0)
    {
        // Set file attributes in array
        //  $row = db_fetch_assoc($result);
        $row = $result->fetch_assoc();
        // Set expiration date
        $expireDate = $row['expire_date'];
        // Determine if file is gzipped
        $gzipped = $row['gzipped'];
        // Set error msg if file has expired
        if ($expireDate < NOW) $error = $useCDP ? $data["custom-error-text-expired"] : $module->tt("default_download_error_expired");

        $doc_size = round_up($row['doc_size']/1024/1024);

    }
    else
    {
        $error = $useCDP ? $data["custom-error-text-invalid"] : $module->tt("default_download_error_invalid"); //invalid key
    }
}
else {
    $error = $useCDP ? $data["custom-error-text-invalid"] : $module->tt("default_download_error_invalid"); //invalid key
}

// Process the password submitted and begin file download
if ( isset($_POST['submit']) )
{
    if ( $row['pwd'] == md5(trim($_POST['pwd'])) )
    {

        // If user requested confirmation, then send them email (but only the initial time it was downloaded, to avoid multiple emails)
        if ($row['send_confirmation'] == 1 && $row['sent_confirmation'] == 0)
        {
            // Get the uploader's email address
            // $sql = "SELECT user_email FROM redcap_user_information WHERE username = '{$row['username']}' limit 1";            
            $sql = "SELECT user_email FROM redcap_user_information WHERE username = ? limit 1";
            $result = $module->query($sql, [$row['username']]);

            if($result->num_rows > 0) {
                $uploader_email = ($result->fetch_assoc())["user_email"];
                // $uploader_email = db_result(db_query($sql), 0);
    
                // Send confirmation email to the uploader
                $body =    "<html><body style=\"font-family:arial,helvetica;\">
                            {$lang['sendit_46']} \"{$row['doc_orig_name']}\" ($doc_size MB){$lang['sendit_47']} {$row['email_address']} {$lang['global_51']}
                            " . date('l') . ", " . DateTimeRC::format_ts_from_ymd(NOW) . "{$lang['period']}<br><br><br>
                            {$lang['sendit_48']} <a href=\"" . APP_PATH_WEBROOT_FULL . "\">REDCap Send-It</a>!
                            </body></html>";
                $email = new Message();
                $email->setFrom($project_contact_email);
                $email->setFromName($GLOBALS['project_contact_name']);
                $email->setTo($uploader_email);
                $email->setBody($body);
                $email->setSubject('[REDCap Send-It] '.$lang['sendit_49']);
                $email->send();
            }
        }

        // Log this download event in the table
        $recipientId = $row['recipient_id'];
        // $querylog = "UPDATE redcap_sendit_recipients SET download_date = '".NOW."', download_count = (download_count+1),
        //                 sent_confirmation = 1 WHERE recipient_id = $recipientId";
        $sql = "UPDATE redcap_sendit_recipients SET download_date = '".NOW."',      download_count = (download_count+1), sent_confirmation = 1 WHERE recipient_id = ? ";
        $result = $module->query($sql, [$recipientId]);    
        //  db_query($querylog);

        // Set flag to determine if we're pulling the file from the file system or redcap_docs table (legacy storage for File Repository)
        $pullFromFileSystem = ($row['location'] == '3' || $row['location'] == '1');

        // If file is in File Repository, retrieve it from redcap_docs table (UNLESS we determine that it's in the file system)
        if ($row['location'] == '2')
        {
            // Determine if in redcap_docs table or file system and then download it
            // $query = "SELECT d.*, e.doc_id as edoc_id FROM redcap_docs d LEFT JOIN redcap_docs_to_edocs e
            //             ON e.docs_id = d.docs_id LEFT JOIN redcap_edocs_metadata m ON m.doc_id = e.doc_id
            //             WHERE d.docs_id = " . $row['docs_id'];
            $sql = "SELECT d.*, e.doc_id as edoc_id FROM redcap_docs d LEFT JOIN redcap_docs_to_edocs e ON e.docs_id = d.docs_id LEFT JOIN redcap_edocs_metadata m ON m.doc_id = e.doc_id WHERE d.docs_id = ? ";
            $result = $module->query($sql, [$row['docs_id']]);     
            // $result = db_query($query);
            $row = $result->fetch_assoc();
            //  $row = db_fetch_assoc($result);

            // Check location
            if ($row['edoc_id'] === NULL) {
                // Download file from redcap_docs table (legacy BLOB storage)
                header('Pragma: anytextexeptno-cache', true);
                header('Content-Type: '. $module->escape($row['docs_type']));
                header('Content-Disposition: attachment; filename='. str_replace(' ', '', $row['docs_name']));
                ob_clean();
                flush();
                print $module->escape($row['docs_file']);
            } else {
                // Set flag to pull the file from the file system instead
                $pullFromFileSystem = true;
                // Reset values that were overwritten
                $row['docs_id'] = $row['edoc_id'];
                $row['location'] = '2';

            }
        }

        // If file stored on form or uploaded from Home page Send-It location, retrieve it from edocs location
        if ($pullFromFileSystem)
        {
            // Retrieve values for loc=3 (since loc=1 values are already stored in $row) or for loc=2 if stored in file system
            if ($row['location'] == '3' || $row['location'] == '2')
            {
                // $query = "SELECT project_id, mime_type as doc_type, doc_name as doc_orig_name, stored_name as doc_name
                //             FROM redcap_edocs_metadata WHERE doc_id = " . $row['docs_id'];
                $sql = "SELECT project_id, mime_type as doc_type, doc_name as doc_orig_name, stored_name as doc_name FROM redcap_edocs_metadata WHERE doc_id = ?";
                $result = $module->query($sql, [$row['docs_id']]);
                //  $result = db_query($query);
                $row = $result->fetch_assoc();
                //  $row = db_fetch_assoc($result);
            }

            // Retrieve from EDOC_PATH location (LOCAL STORAGE)
            if ($edoc_storage_option == '0' || $edoc_storage_option == '3')
            {
                // Download file
                header('Pragma: anytextexeptno-cache', true);
                header('Content-Type: '. $module->escape($row['doc_type']));
                header('Content-Disposition: attachment; filename=' . str_replace(array(' ',','), array('',''), $row['doc_orig_name']));
                // GZIP decode the file (if is encoded)
                if ($gzipped) {
                    list ($contents, $nothing) = gzip_decode_file(file_get_contents(EDOC_PATH . $module->escape($row['doc_name'])));
                    ob_clean();
                    flush();
                    print $contents;
                } else {
                    ob_start();ob_end_flush();
                    readfile_chunked(EDOC_PATH . $module->escape($row['doc_name']));
                }
            } 
            //  Throw Exception for unsupported edoc storage options
            else {
                throw new Exception("File Storage Option \"$edoc_storage_option\" is not supported in Mass Send-It module. Please disable Mass Send-It module or change your system's edoc storage option back to LOCAL STORAGE. If your organization would like to use Mass Send-It module with other edoc storage options, feel free to open a Pull Request on the module's github repository.");
            }

        }


        ## Logging
        if ($row['project_id'] != "" && $row['project_id'] != "0") {
            // Get project id if file is existing project file
            define("PROJECT_ID", $row['project_id']);
        }
        Logging::logEvent($querylog,"redcap_sendit_recipients","MANAGE",$recipientId,"recipient_id = $recipientId","Download file (Send-It)");

        // Stop here now that file has been downloaded
        exit;

    }
    else
    {
        $error = $useCDP ? $data["custom-error-text-password"] : $module->tt("default_download_error_password");
    }
}  

$objHtmlPage = new HtmlPage();
$objHtmlPage->addStylesheet("home.css", 'screen,print');
$objHtmlPage->PrintHeader();

?>
<style type="text/css">
    #pagecontent { margin: 0; }
</style>
<div>
    <a href="<?php echo APP_PATH_WEBROOT_FULL ?>"><img src="<?php echo APP_PATH_IMAGES . 'redcap-logo-large.png' ?>" title="REDCap" alt="REDCap"></a>
</div>
<div style="padding-top:10px;font-size: 18px;border-bottom:1px solid #aaa;padding-bottom:2px;">
    <img src='<?php echo APP_PATH_IMAGES ?>mail_arrow.png'> Send-It: <span style="color:#777;"><?= $useCDP ? $data["custom-download-page-title"] : $module->tt("default_download_page_title")?></span>
</div>    
<?php

if ($error == '' || $error == 'Password incorrect') {
    ?>
    <div style="padding:15px 0 20px;">
        <b><?php echo ($useCDP ? $data["custom-instructions-title"] : $module->tt("default_download_instruction_title")) . $lang['colon'] ?></b>
        <?= $useCDP ? $data["custom-instructions-text"] : $module->tt("default_download_instruction_text")?>
        <?= ($useCDP ? $data["custom-meta-text"] : $module->tt("default_download_meta_text")) . " <b>" . $doc_size ?> MB</b>.
    </div>
    <div id="formdiv">
            <form action="<?php echo $_SERVER['REQUEST_URI'] ?>" method="post" id="Form1" name="Form1">
            <?= $useCDP ? $data["custom-input-text"] : $module->tt("default_download_input_text") ?> &nbsp;
            <input type="password" name="pwd" autocomplete="new-password" value="" /> &nbsp;
            <input type="hidden" name="redcap_csrf_token" value="<?=$module->getCSRFToken() ?>" />
            <input type="submit" name="submit" value="<?= $useCDP ? $data["custom-button-text"] : $module->tt("default_download_button_text") ?>" onclick="
                document.getElementById('formdiv').style.visibility='hidden';
                if (document.getElementById('errormsg') != null) document.getElementById('errormsg').style.visibility='hidden';
                setTimeout(function(){
                    $('#progress').toggle('blind','fast');
                },1000);
                return true;
            "/>
            </form>
        </div>

        <div id="progress" class="darkgreen" style="display:none;font-weight:bold;">
            <img src="<?php echo APP_PATH_IMAGES ?>tick.png"> <?= $useCDP ? $data["custom-success-text"] : $module->tt("default_download_success_text") ?>
        </div>
    <?php 
}

// Display error message, if error occurs
if ($error != '') {
	?>
	<p id="errormsg" style='padding-top:5px;font-weight:bold;color:#800000;'>
		<img src="<?php echo APP_PATH_IMAGES ?>exclamation.png">
		<?php echo $error ?>.
	</p>
	<?php
}


print "<br><br><br>";

$objHtmlPage->PrintFooter();