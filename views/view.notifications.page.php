<?php
namespace STPH\massSendIt;

use DateTimeRC;
use RCView;
global $Proj, $longitudinal, $lang;


/**
 * Get log of all future and past notifications
 * 
 */
function getPageData($notificationLog, $num_per_page_config) {
    global $lang;
    $showFullTableDisplay = true;
    ## BUILD THE DROP-DOWN FOR PAGING THE INVITATIONS
    // Get participant count
    $notificationCount = count($notificationLog);
    // Section the Participant List into multiple pages
    $num_per_page = $num_per_page_config;

    // Calculate number of pages of for dropdown
    $num_pages = ceil($notificationCount/$num_per_page);
    $pageDropdown = "";
    if ($num_pages == 0) {
        $pageDropdown .= "<option value=''>0</option>";
    } else {
        $pageDropdown .= "<option value='ALL'>-- All --</option>";
    }
    // Limit
    $limit_begin  = 0;
    if (isset($_GET['pagenum']) && $_GET['pagenum'] == 'last') {
        $_GET['pagenum'] = $num_pages;
    }
    if (isset($_GET['pagenum']) && is_numeric($_GET['pagenum']) && $_GET['pagenum'] > 1) {
        $limit_begin = ($_GET['pagenum'] - 1) * $num_per_page;
    }

    ## Build the paging drop-down for participant list
    $pageDropdown = "<select id='pageNumInviteLog' onchange='STPH_bulkSend.loadBulkNotificationLog(this.value)' style='vertical-align:middle;font-size:11px;'>";
    //Loop to create options for dropdown
    for ($i = 1; $i <= $num_pages; $i++) {
        $end_num   = $i * $num_per_page;
        $begin_num = $end_num - $num_per_page + 1;
        //$value_num = $end_num - $num_per_page;
        if ($end_num > $notificationCount) $end_num = $notificationCount;
        $pageDropdown .= "<option value='$i' " . ($_GET['pagenum'] == $i ? "selected" : "") . ">$begin_num - $end_num</option>";
    }
    $pageDropdown .= "</select>";
    $pageDropdown  = "{$lang['survey_45']} $pageDropdown {$lang['survey_133']} $notificationCount";

    // If viewing ALL invitations, then set $num_per_page to null to return all invitations
    if ($_GET['pagenum'] == 'ALL' || !$showFullTableDisplay) $num_per_page = null;

    return array($num_per_page, $pageDropdown, $limit_begin);
}

 function getTitle($pageDropdown, $bulks, $reset_url) {
    global $lang;
    $showFullTableDisplay = true;
    // Set NOW in user defined date format but with military time
    $now_user_date_military_time = DateTimeRC::format_ts_from_ymd(TODAY).date(' H:i');

    $all_active_bulks = array();
    foreach ($bulks as $attr) {
        if ($attr['bulk_deleted'] == '1') continue;
        $all_active_bulks[$attr['bulk_id']] = "Bulk: "." #".$attr['bulk_id'];
        if ($attr['bulk_title'] != '') {
            $all_active_bulks[$attr['bulk_id']] .= "" . " " . $attr['bulk_title'];
        }
        $all_active_bulks[$attr['bulk_id']] .= " ("."B-".$attr['bulk_id'].")";
    }    

    // Set some flags to disable buttons
    $disableViewPastInvites = $disableViewFutureInvites = "";
    // Set flags (if timestamp is within the same hour as now, then consider it now)
    if ($_GET['filterBeginTime'] == '' && substr($_GET['filterEndTime'], 0, -2) == substr($now_user_date_military_time, 0, -2)) {
        $disableViewPastInvites = "disabled";
    }
    if ((!isset($_GET['filterEndTime']) || $_GET['filterEndTime'] == '') && substr($_GET['filterBeginTime'], 0, -2) == substr($now_user_date_military_time, 0, -2)) {
        $disableViewFutureInvites = "disabled";
    }

    // Define title
    $title = "";
    if ($showFullTableDisplay) {
        $title =	
        RCView::div(
            array('style'=>''),
            RCView::div(
                array('style'=>'padding:2px 20px 0 5px;float:left;font-size:14px;'),
                $lang['alerts_20'] . RCView::br() .
                RCView::span(
                    array('style'=>'line-height:24px;color:#666;font-size:11px;font-weight:normal;'),
                    $lang['survey_570']
                ) . 
                RCView::br() . RCView::br() .
                RCView::span(
                    array('style'=>'color:#555;font-size:11px;font-weight:normal;'),
                    $pageDropdown
                )
            ) .
            ## QUICK BUTTONS
            RCView::div(
                array('style'=>'font-weight:normal;float:left;font-size:11px;padding-left:12px;border-left:1px solid #ccc;'),
                RCView::button(
                    array(
                        $disableViewPastInvites=>$disableViewPastInvites, 'class'=>'jqbuttonsm', 'style'=>'margin-top:12px;font-size:11px;color:green;display:block;',
                        'onclick'=>"$('#filterBeginTime').val('');$('#filterEndTime').val('$now_user_date_military_time');STPH_MassSendIt.loadBulkNotificationLog('last')"
                    ), 
                    $lang['alerts_18']
                ) .
                RCView::button(
                    array(
                        $disableViewFutureInvites=>$disableViewFutureInvites, 
                        'class'=>'jqbuttonsm', 'style'=>'margin-top:12px;font-size:11px;color:#000066;display:block;',
                        'onclick'=>"$('#filterBeginTime').val('$now_user_date_military_time');$('#filterEndTime').val('');STPH_MassSendIt.loadBulkNotificationLog(1)"), $lang['alerts_19']
                    )
            ) .
            ## FILTERS
            RCView::div(
                array('style'=>'max-width:500px;font-weight:normal;float:left;font-size:11px;padding-left:15px;margin-left:15px;border-left:1px solid #ccc;'),
                // Date/time range
                $lang['survey_439'] .
                RCView::text(
                    array('id'=>'filterBeginTime','value'=>$_GET['filterBeginTime'],'class'=>'x-form-text x-form-field filter_datetime_mdy','style'=>'margin-right:8px;margin-left:3px;width:102px;height:20px;line-height:20px;font-size:11px;', 'onblur'=>"redcap_validate(this,'','','hard','datetime_'+user_date_format_validation,1,1,user_date_format_delimiter);")
                    ) .
                $lang['survey_440'] .
                RCView::text(
                    array('id'=>'filterEndTime','value'=>(isset($_GET['filterEndTime']) ? $_GET['filterEndTime'] : ""),'class'=>'x-form-text x-form-field filter_datetime_mdy','style'=>'margin-left:3px;width:102px;height:20px;line-height:20px;font-size:11px;', 'onblur'=>"redcap_validate(this,'','','hard','datetime_'+user_date_format_validation,1,1,user_date_format_delimiter);")
                    ) .
                RCView::span(
                    array('class'=>'df','style'=>'color:#777;'), '('.DateTimeRC::get_user_format_label().' H:M)'
                ) . 
                RCView::br() .
                // Display all active alerts displayed in this view
                $lang['survey_441'] .
                RCView::select(
                    array('id'=>'filterAlert','style'=>'font-size:11px;margin:2px 3px;'),
                    (array(''=>$lang['alerts_27'])+$all_active_bulks), 
                    $_GET['filterAlert'],300
                ) .
                RCView::br() .
                // Display record names displayed in this view
                $lang['survey_441'] .
                \Records::renderRecordListAutocompleteDropdown(
                    PROJECT_ID, true, 5000, 'filterRecord',
                    "", "margin-left:3px;font-size:11px;", $_GET['filterRecord'], $lang['reporting_37'], $lang['alerts_205']
                ) .
                RCView::br() .
                // "Apply filters" button
                RCView::button(
                    array('class'=>'jqbuttonsm','style'=>'margin-top:5px;font-size:11px;color:#800000;','onclick'=>"STPH_MassSendIt.loadBulkNotificationLog(1)"), $lang['survey_442']) .
                RCView::a(array('href'=> $reset_url,'style'=>'vertical-align:middle;margin-left:15px;text-decoration:underline;font-weight:normal;font-size:11px;'), $lang['setup_53'])
            ) .
            ## CLEAR
            RCView::div(array('class'=>'clear'), '')
        );
        }
    return $title;     
 }

function getHeaders() {
    global $lang;

    // Define table headers
    $headers = array();
    $headers[] = array(160, RCView::img(array('class'=>'survlogsendarrow', 'src'=>'draw-arrow-down.png', 'style'=>'vertical-align:middle;')) .
    RCView::img(array('class'=>'survlogsendarrow', 'src'=>'draw-arrow-up.png', 'style'=>'display:none;vertical-align:middle;')) .
    RCView::SP .
    $lang['alerts_21']);
    $headers[] = array(64,  RCView::span(array('class'=>'wrap'), "Bulk"), "center");
    $headers[] = array(64,  RCView::span(array('class'=>'wrap'), $lang['alerts_22']), "center", "string", false);
    $headers[] = array(120, RCView::div(array('class'=>'wrap'), $lang['global_49']), "center");
    $headers[] = array(64, RCView::span(array('class'=>'wrap'), "Type"));
    $headers[] = array(100, RCView::span(array('class'=>'wrap'), $lang['alerts_26']));
    $headers[] = array(260, RCView::span(array('class'=>'wrap'), $lang['alerts_23']));

    return $headers;
}

function getRows($notificationLog, $limit_begin, $num_per_page) {
     global $Proj, $longitudinal, $lang;
       // Loop through all invitations for THIS PAGE and build table
       $rownum = 0;
       foreach (array_slice($notificationLog, $limit_begin, $num_per_page) as $row)
       {
           // Set color of timestamp (green if already sent, red if failed) and icon
           // TBD: exchange with has_error or adjust bulk_sent and use was_sent instead of has_error
           $tsIcon  = ($row['was_sent'] == '0') ? "clock_small.png" : ($row['was_sent'] == '1' ? "tick_small_circle.png" : "bullet_delete.png");
           $tsColor = ($row['was_sent'] == '0') ? "gray" : ($row['was_sent'] == '1' ? "green" : "red");
   
           //$alert_number = $bulks[$row['bulk_id']];
           $alert_number = $row['bulk_id'];
   
           // If scheduled and not sent yet, display cross icon to delete the invitation
           //  TBD
           $deleteEditInviteIcons = '';
           if ($row['was_sent'] == '0') {
               $deleteEditInviteIcons =
                   RCView::a(array('href'=>'javascript:;','style'=>'margin:0 2px 0 5px;','onclick'=>"STPH_MassSendIt.deleteRecurrence()"),
                       RCView::img(array('src'=>'cross_small2.png','class'=>'inviteLogDelIcon opacity50','title'=>$lang['alerts_29']))
                   );
           }
   
           // Send time (and icon)
           $rows[$rownum][] = 	// Invisible YMD timestamp (for sorting purposes
               RCView::span(array('class'=>'hidden'), $row['last_sent']) .
               // Display time and icon
               RCView::span(array('style'=>"color:$tsColor;"),
                   RCView::img(array('src'=>$tsIcon, 'style'=>'margin-right:2px;')) .
                   DateTimeRC::format_ts_from_ymd($row['last_sent']) .
                   $deleteEditInviteIcons
               );
   
           $rows[$rownum][] = '#'.$alert_number." ".$lang['leftparen']."B-".$row['bulk_id'].$lang['rightparen'];
   
           $onclick = "STPH_MassSendIt.loadPreviewEmailAlertRecord($(this).data('content'));";
           $rows[$rownum][] = 	RCView::a(array('href'=>'javascript:;', 'onclick'=>$onclick."return false;", 'data-content' => $row["message"]),
                                   RCView::img(array('src'=>'mail_open_document.png', 'title'=>$lang['alerts_28']))
                               );
           // Record ID (if not anonymous response)
           if ($row['instrument'] != '' && $row['event_id'] != '') {
               $recordLink = "DataEntry/index.php?pid=".PROJECT_ID."&page={$row['instrument']}&event_id={$row['event_id']}&id={$row['record']}&instance={$row['instance']}";
           } else {
               $recordLink = "DataEntry/record_home.php?pid=".PROJECT_ID."&id={$row['record']}";
               if ($Proj->multiple_arms) {
                   if ($row['event_id'] != '') {
                       $recordLink .= "&arm=" . $Proj->eventInfo[$row['event_id']]['arm_num'];
                   }
               }
           }
           $rows[$rownum][] = 	RCView::div(array('class'=>'wrap', 'style'=>'word-wrap:break-word;'),
               ($row['record'] == '' ? "" : ($row['record'] == '' ? '<i class="far fa-eye-slash" style="color:#ddd;"></i>' :
                   RCView::a(array('href'=>APP_PATH_WEBROOT.$recordLink, 'style'=>'font-size:12px;text-decoration:underline;'), $row['record']) .
                   ($Proj->isRepeatingFormOrEvent($row['event_id'], $row['instrument']) ? "&nbsp;&nbsp;<span style='color:#777;'>(#{$row['instance']})</span>" : "") .
                   (!$longitudinal ? "" : "&nbsp;&nbsp;<span style='color:#777;'>-&nbsp;".$Proj->eventInfo[$row['event_id']]['name_ext']."</span>")
               ))
           );
           $rows[$rownum][] = $row['type'];
           $rows[$rownum][] = "<i class='fas fa-envelope me-1 opacity35'></i>".$row['email_to'];
           $rows[$rownum][] = strip_tags($row['subject']);

   
           // Increment counter
           $rownum++;            
       }

        // Give message if no invitations were sent
        if (empty($rows)) {
            $rows[$rownum] = array(RCView::div(array('class'=>'wrap','style'=>'color:#800000;'), $lang['alerts_25']),"","","");
        }

       return $rows;
}
function getNotificationLog($module) {
    $record=null;
    $notificationLog = array();
    $bulks = array();
    $errorMsg = '';
   
    // Get bulks
    $sql = "SELECT bulk_id, bulk_schedule, bulk_title, email_to, email_first_subject, email_first_message, email_second_subject, email_second_message WHERE table_name = 'bulk'";
    $result = $module->queryLogs($sql, []);
    $bulks = [];
    while ( $bulk = $result->fetch_assoc()) {
        $bulks[] = $bulk;
    }

    // Set NOW in user defined date format but with military time
    $now_user_date_military_time = DateTimeRC::format_ts_from_ymd(TODAY).date(' H:i');
   
    ## DEFINE FILTERING VALUES
    // Set defaults
    if (isset($_GET['pagenum']) && (is_numeric($_GET['pagenum']) || $_GET['pagenum'] == 'last')) {
        // do nothing
    } elseif (!isset($_GET['pagenum'])) {
        $_GET['pagenum'] = 1;
    } else {
        $_GET['pagenum'] = 'ALL';
    }
    $_GET['filterRecord'] = isset($_GET['filterRecord']) ? urldecode(rawurldecode($_GET['filterRecord'])) : '';
    $_GET['filterAlert'] = (isset($_GET['filterAlert']) && is_numeric($_GET['filterAlert'])) ? (int)$_GET['filterAlert'] : '';
    // Run the value through the regex pattern
    if (!isset($_GET['filterBeginTime'])) {
        // Default beginTime = right now
        $_GET['filterBeginTime'] = $now_user_date_military_time;
    }

    //  Get notifications (past)
    //  tbd replace by notificationModel->getFields()
    $fields = "bulk_id, notification_id, sendit_recipient_id, sendit_docs_id, record, time_sent, was_sent, error_sent, log";
    $sql = "SELECT ".$fields." WHERE table_name='notification'";
    if ($record !== null) $sql .= " and record = '".db_escape($record)."'";
    if (!empty($_GET['filterAlert'])) $sql .= " and bulk_id = '".db_escape($_GET['filterAlert'])."'";
    //$sql .= " order by l.time_sent";  //TBD
    //  tbd: unserialize log column
    $result = $module->queryLogs($sql, []);
    while($row = $result->fetch_assoc()) {
        $notificationLog[] = $row;
    }

    ## PERFORM MORE FILTERING
    // Now filter $notificationLog by filters defined
    if ($_GET['filterBeginTime'] != '') {
        $filterBeginTimeYmd = DateTimeRC::format_ts_to_ymd($_GET['filterBeginTime']);
    }
    if (isset($_GET['filterEndTime']) && $_GET['filterEndTime'] != '') {
        $filterEndTimeYmd = DateTimeRC::format_ts_to_ymd($_GET['filterEndTime']);
    }
    // Make sure begin time occurs *before* end time. If not, display error message to user.
    if (isset($filterBeginTimeYmd) && isset($filterEndTimeYmd) && $filterBeginTimeYmd > $filterEndTimeYmd) {
        $errorMsg = \RCView::div(array('class'=>'yellow','style'=>'margin-bottom:10px;'),
            \RCView::b('begin time has to occure befor end time!')
        );
    }

    //  Loop through all invitations and remove those that should be filtered
    foreach ($notificationLog as $key=>$attr)
    {
        // Filter by *displayed* record named
        if ($_GET['filterRecord'] != '' && $attr['record'] != $_GET['filterRecord']) {
            unset($notificationLog[$key]); continue;
        }
        // Filter by begin time
        if (isset($filterBeginTimeYmd) && substr($attr['last_sent'], 0, 16) < $filterBeginTimeYmd) {
            unset($notificationLog[$key]); continue;
        }
        // // Filter by end time
        if (isset($filterEndTimeYmd) && substr($attr['last_sent'], 0, 16) > $filterEndTimeYmd) {
            unset($notificationLog[$key]); continue;
        }
    } 

    // Now add all projected future notifications to the notification log
    // (SKIP THIS SECTION if we're looking at past timestamps only - this is only for future projections)
    $schedules = array();
    if (!isset($filterEndTimeYmd) || $filterEndTimeYmd == '' || $filterEndTimeYmd > substr(NOW, 0, 16))
    {
        $sql = "SELECT schedule_id, bulk_id, record, status, send_time, message_type WHERE table_name = 'schedule'";
        if ($_GET['filterRecord'] != '') $sql .= " and record = '" . db_escape($_GET['filterRecord']) . "'";
        if (!empty($_GET['filterAlert'])) $sql .= " and bulk_id = '".db_escape($_GET['filterAlert'])."'"; 
        $sql .= " order by send_time";
        $result = $module->queryLogs($sql, []);

        while($row = $result->fetch_assoc()) {
            $schedules[] = $row;
        }
    }

    $bulks_by_bulk_id = [];
    // key bulks by bulk_id
    foreach ($bulks as $key => $value) {
        $bulks_by_bulk_id[$value["bulk_id"]] = array(
            "email_first_subject" => $value["email_first_subject"],
            "email_first_message" => $value["email_first_message"],
            "email_second_subject" => $value["email_second_subject"],
            "email_second_message" => $value["email_second_message"],
            "email_to" => $value["email_to"]
        );
    }

    // Store schedule values in array
    //  TBD add missing values
    foreach ($schedules as $key => $schedule) {
        $log = [];
        $log["time_sent"] = $schedule["send_time"];
        $log["last_sent"] = $schedule["send_time"];
        $log["was_sent"] = false;
        $log["bulk_id"] =  $schedule["bulk_id"];
        $log["record"] = $schedule["record"];
        $log["type"] = ucfirst($schedule["message_type"]);

        $log["email_to"] = $bulks_by_bulk_id[$schedule["bulk_id"]]["email_to"];
        if($schedule["message_type"] == "primary") {
            $log["subject"] = $bulks_by_bulk_id[$schedule["bulk_id"]]["email_first_subject"];
            $log["message"] = $bulks_by_bulk_id[$schedule["bulk_id"]]["email_first_message"];
        } else {
            $log["subject"] = $bulks_by_bulk_id[$schedule["bulk_id"]]["email_second_subject"];
            $log["message"] = $bulks_by_bulk_id[$schedule["bulk_id"]]["email_Second_message"];
        }        

        $notificationLog[] = $log;

    }

    return array($bulks, $notificationLog);
   
}

function renderNotificationLog($notificationLog, $bulks, $num_per_page_config, $reset_url) {

    list($num_per_page, $pageDropdown, $limit_begin) = getPageData($notificationLog, $num_per_page_config);
    
    $rows = getRows($notificationLog, $limit_begin, $num_per_page);

    $headers = getHeaders();
    $title = getTitle($pageDropdown, $bulks, $reset_url);    
    $width = 948;
        
    // Build Bulk Log table
    return renderGrid("notification_log_table", $title, $width, 'auto', $headers, $rows, true, true, false);

}
$reset_url = $this->getModulePath()."?pid=".PROJECT_ID."&prefix=".$this->getModulePrefix()."&page=project-page&log=1";
list($bulks, $notificationLog) = getNotificationLog($this);
$notificationLogRender = renderNotificationLog($notificationLog, $bulks, self::NUM_NOTIFICATIONS_PER_PAGE, $reset_url);
?>
<div class="mt-3" style="width:950px;max-width:950px;">
    <?= $notificationLogRender?>
</div>