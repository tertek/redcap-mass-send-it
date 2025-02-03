<?php  namespace STPH\massSendIt;
$pids = $module->getProjectsWithModuleEnabled();
$projects = [];

//  Gather project data
foreach ($pids as $key => $pid) {
    $p = $module->getProject($pid);
    $bulkModel = new BulkModel($module, $pid);

    $project = (object) array(
        "title" => $p->getTitle(),
        "id" => $pid,
        "bulks" => $bulkModel->getAllBulks()
    );
    $projects[] = $project;
}
?>
<h2>Mass Send-It Admin</h2>
<p>This page is for administrative purposes of Mass Send-It module.</p>


<b>Module usage</b>
<p>Module is being used in <?=count($projects)?> projects.</p>
<ul>
<?php foreach($projects as $key=>$project): ?>
    <li><?= $project->title ?> (<?= $project->id ?>), Bulks: <?= count($project->bulks) ?></li>
    
<?php endforeach ?>
</ul>



