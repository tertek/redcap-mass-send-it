<?php

// Set the namespace defined in your config file
namespace STPH\massSendIt;

?>
<!-- We are using Alerts.css since the styling has not change -->
<link rel="stylesheet" type="text/css" href="<?php echo APP_PATH_CSS ?>Alerts.css" media="screen,print">

<div class="projhdr"><i class="fas fa-mail-bulk"></i>Mass Send-It</div>
<div style="width:950px;max-width:950px;" class="d-none d-md-block mt-3 mb-2">
Mass Send-It is an advanced module form of the REDCap feature "Send-it". It allows you to create bulks of Send-It, enabling secure data transfer based on multiple files that each have a single recipient, mapped through available records.
</div>

<div class="clearfix">
    <div id="sub-nav" class="d-none d-sm-block" style="margin:5px 0 15px;width:950px;">
        <ul>
            <li<?php echo (!isset($_GET['log']) ? ' class="active"' : '') ?>>
                <a href="<?php echo $module->getUrl('project-page.php') ?>" style="font-size:13px;color:#393733;padding:6px 12px 7px 13px;"><i class="fas fa-mail-bulk me-1"></i>My Bulks</a>
            </li>
            <li<?php echo (isset($_GET['log']) ? ' class="active"' : '') ?>>
                <a href="<?php echo $module->getUrl('project-page.php') ?>&log=1" style="font-size:13px;color:#393733;padding:6px 12px 7px 13px;"><i class="fas fa-table me-1"></i>Notification Log</a>
            </li>
        </ul>
    </div>
</div>
<div class="mt-3" style="width:950px;max-width:950px;">
<?php $module->renderModuleProjectPage(); ?>
</div>
