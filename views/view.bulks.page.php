<?php namespace STPH\massSendIt; ?>
<div class="mt-3" style="width:950px;max-width:950px;">
    <div class="mb-5 clearfix">
        <button id='addNewBulk' type="button" class="btn btn-sm btn-rcgreen float-start">
            <i class="fas fa-plus"></i> Add New Bulk
        </button>        
    </div>        
    <table class="table table-bordered table-hover email_preview_forms_table dataTable " id="customizedAlertsPreview" style="width:100%;table-layout: fixed;">
        <thead>
            <tr class="table_header d-none">
                <th>Bulks</th>
                <th style="width:350px;"><span class="fas fa-envelope"></span> Bulk</th>
                <th style="display:none;">Active</th>
                <th style="display:none;">Deleted</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($this->getBulks() as $key => $bulk ): ?>
            <?php
                $schedule_count = $this->getScheduledCount($bulk->bulk_id);
                $sent_count = 0;
            ?>
            <tr id="bulk_<?= $bulk->bulk_id ?>" class="<?= ($key+1) % 2 == 0 ? 'even' : 'odd' ?>"><td class="pt-0 pb-4" style="border-right:0;" data-order="1">
                    <div class="clearfix" style="margin-left: -11px;">
                        <div style="max-width:340px;" class="card-header alert-num-box  float-start text-truncate"><i class="fas fa-mail-bulk fs13" style="margin-right:5px;"></i>Bulk #<?= $bulk->bulk_id . " ". $bulk->bulk_title ?? ""?></div>
                            <div class="btn-group nowrap float-start mb-1 ms-2" role="group">
                                <button class="bulk-edit-btn" type="button" class="btn btn-link fs13 py-1 ps-1 pe-2" data-bulk-id="<?= $bulk->bulk_id ?>">
                                    <i class="fas fa-pencil-alt"></i> Edit
                                </button>
                                <button class="bulk-schedule-btn ml-2" type="button" class="btn btn-link fs13 py-1 ps-1 pe-2" data-bulk-id="<?= $bulk->bulk_id ?>">
                                    <i class="fas fa-calendar-check"></i> (Re-)Schedule
                                </button>
                                <button class="bulk-delete-btn ml-2" type="button" class="btn btn-link fs13 py-1 ps-1 pe-2" data-bulk-id="<?= $bulk->bulk_id ?>">
                                    <i class="fas fa-trash"></i> Delete
                                </button>                                
                            </div>
                            <div style="padding:4px 4px 4px 5px; float: right;">
                                <span class="fs11"><span class="text-secondary">Unique Bulk ID:</span> <span style="color:#A00000;">B-<?= $bulk->bulk_id ?></span></span>
                            </div>
                        </div>
                    <div class="card mt-3">
                        <div class="card-body p-2">
                            <div id="trigger-descrip0" class="mb-1 trigger-descrip" style="overflow: hidden; text-overflow: ellipsis; -webkit-box-orient: vertical; display: -webkit-box; -webkit-line-clamp: 3;">
                                <?php if($bulk->bulk_type == "list"): ?>
                                    <b class="fs14"><i class="fas fa-hand-point-right"></i></b> Based on <b>record list</b>
                                <?php elseif($bulk->bulk_type == "logic"): ?>
                                    'Based on <b>filter logic:</b> <span class="code" style="font-size:85%;"><?= $bulk->bulk_recipients_logic ?></span>
                                <?php endif; ?>                                
                            </div>
                            <div class="mt-1" style="color:green;">
                                <i class="far fa-clock"></i> Schedule to send on <?= $bulk->bulk_schedule?>
                            </div>
                            <div class="mt-1">
                                <?php if($bulk->use_second_email == false): ?>
                                    <b class="code box-1x">1x</b> Send one email <span class="text-secondary ms-1 fs12"></span>
                                <?php else: ?>
                                    <b class="code box-1x">2x</b> Send two emails <span class="text-secondary ms-1 fs12"></span>
                                <?php endif;?>                             
                            </div>
                            <div class="mt-1">
                                <?php if($bulk->use_random_pass): ?>
                                    <i class="fa-solid fa-lock"></i> Using <b>random password</b><span class="text-secondary ms-1 fs12">(generated for each record)</span>
                                <?php else: ?>
                                    <i class="fa-solid fa-lock"></i> Using <b>custom password</b><span class="text-secondary ms-1 fs12">(with custom password field: <span class="code" style="font-size:85%;"><?= $bulk->custom_pass_field ?></span>)</span> 
                                <?php endif;?>
                                
                            </div>
                        </div>
                    </div>
                    <div class="card mt-3">
                        <div class="card-body p-2">
                            <div class="clearfix">
                                <div class="float-start boldish" style="color:#a920ac;width:90px;">
                                    <i class="fs14 fas fa-folder-open"></i> File Repository:
                                </div>
                                <div class="float-start">
                                    <div class="">
                                        <i class="fas fa-folder"></i> Folder Id: <?= $bulk->file_repo_folder_id ?>                         
                                    </div>
                                    <div class="">
                                        <i class="fas fa-file"></i> File Extension: <?= $bulk->file_repo_extension ?> 
                                    </div>
                                    <div class="">
                                        <i class="fas fa-link"></i> Reference Field: <?= $bulk->file_repo_reference ?> 
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>                    
                    <div class="card mt-3">
                        <div class="card-body p-2">
                            <div class="clearfix">
                                <div class="float-start boldish" style="color:#6320ac;width:90px;">
                                    <i class="fs14 fas fa-tachometer-alt"></i> Activity:
                                </div>
                                <div class="float-start">
                                    <div class="">
                                        <i class="far fa-clock"></i> <?= $schedule_count ?> records are currently scheduled                        
                                    </div>
                                    <?php if($sent_count == 0): ?>
                                    <div class="text-secondary">
                                        <i class="far fa-envelope-open"></i> No notifcations were sent yet
                                    </div>
                                    <?php else: ?>
                                    <div class="text-secondary">
                                        <i class="far fa-envelope-open"></i> <?= $sent_count ?> notifcations were sent.
                                    </div>
                                <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>                    
                </td>
                <td class="pt-3 pb-4" style="width:350px;border-left:0;">
                    <div class="card">
                        <div class="card-header bg-light py-1 px-3 clearfix" style="color:#004085;background-color:#d5e3f3 !important;">
                            <div class="float-start"><i class="fas fa-envelope"></i> Email (1)</div>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                    <li class="list-group-item py-1 px-3 text-truncate fs12">
                                <span class="me-1 boldish">From:</span> 
                                <a class="fs12"><?= $bulk->email_from ?></a>
                                </li><li class="list-group-item py-1 px-3 text-truncate fs12">
                                <span class="me-1 boldish">To:</span> <a class="fs12"><?= $bulk->email_to ?></a>
                                </li><li class="list-group-item py-1 px-3 text-truncate fs12">
                                <span class="me-1 boldish">Subject:</span><?= $bulk->email_first_subject ?>
                                </li><li class="list-group-item py-1 px-3 text-truncate fs12">
                                <?= $bulk->email_first_message ?>
                                </li>       
                            </ul>                                                                  
                        </div>
                    </div>
                    <?php if($bulk->use_second_email) : ?>
                    <div class="card mt-2">
                        <div class="card-header bg-light py-1 px-3 clearfix" style="color:#004085;background-color:#d5e3f3 !important;">
                            <div class="float-start"><i class="fas fa-envelope"></i> Email (2)</div>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item py-1 px-3 text-truncate fs12">
                                    <span class="me-1 boldish">Subject:</span><?= $bulk->email_second_subject ?>
                                </li>
                                <li class="list-group-item py-1 px-3 text-truncate fs12">
                                    <?= $bulk->email_second_message ?>
                                </li>       
                            </ul>                                                                  
                        </div>
                    </div>
                    <?php endif; ?>                    
                </td>
                <td style="display:none;">Y</td><td style="display:none;">N</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>