class MassSendIt{init(){console.log("Initiating BulkSend module..");let l=this;this.setupModal(),$("#addNewBulk").on("click",function(){l.showModal()}),$(".bulk-edit-btn").on("click",function(){var e=$(this).data("bulkId");l.showModal(e)}),$("#saveBulkForm").on("submit",function(e){e.preventDefault();var e=l.checkRequired(),a=l.checkExtra();if(!e||!a)return $("#errMsgContainerModal").show(),$("html,body").scrollTop(0),$("[name=external-modules-configure-modal-1]").scrollTop(0),!1;l.submitForm()}),$(".bulk-delete-btn").on("click",function(){let a=$(this).data("bulkId");simpleDialog("Are you sure?","Reschedule",null,null,null,"Cancel",()=>{var e={task:"delete",data:{bulk_id:a}};JSO_STPH_BULK_SEND.ajax("bulk",e).then(e=>{e=JSON.parse(e);console.log(e),e.error?l.swalError(e.message):l.swalSuccess("Bulk with id "+e.data.bulk_id+" and "+e.data.removed_schedules+" schedules were deleted and "+e.data.removed_notifications+" notifications were deleted")})},"Confirm",void 0)}),$(".bulk-schedule-btn").on("click",function(e){let a=$(this).data("bulkId");simpleDialog("Are you sure? This action cannot be reversed. All schedules for this bulk will be deleted and created from new.","Reschedule",null,null,null,"Cancel",()=>{var e={task:"create",data:{bulk_id:a,overwrite:!0}};showProgress(1,void 0,void 0),JSO_STPH_BULK_SEND.ajax("schedule",e).then(a=>{a=JSON.parse(a);if(showProgress(0,0,void 0),console.log(a),a.error)l.swalError(a.message);else{let e="Scheduling finished. ";0==a.data.scheduled.length&&0==a.data.numIgnored?e+="0 notifications were scheduled. 0 recipients were ignored.":0!=a.data.numIgnored?e+=a.data.scheduled.length+" notifications were scheduled. "+a.data.numIgnored+" recipients were ignored.":e+=a.data.scheduled.length+" notifications were scheduled.",l.swalSuccess(e)}})},"Confirm",void 0)}),$(".schedule-now-btn").on("click",function(e){showProgress(1,void 0,void 0);let a=$(this).data("bulkId");var i={task:"create",data:{bulk_id:a,overwrite:!1}};JSO_STPH_BULK_SEND.ajax("schedule",i).then(e=>{e=JSON.parse(e);showProgress(0,0,void 0),console.log(e),e.error?l.swalError(e.message):l.swalSuccess(e.data.scheduled.length+" notifications were scheduled for bulk id: "+a)})})}setupModal(){let a=this;a.initDateTimePicker(),$("[name=email_to]").prop("required",!0),DTO_STPH_BULK_SEND.modal_defaults.repo_folders.forEach(e=>{$("[name=file_repo_folder_id]").append(new Option(e.name+" - ("+e.folder_id+")",e.folder_id+""))}),DTO_STPH_BULK_SEND.modal_defaults.repo_extensions.forEach(e=>{$("[name=file_repo_extension]").append(new Option(e,e))}),DTO_STPH_BULK_SEND.modal_defaults.repo_fields.forEach(e=>{$("[name=file_repo_reference]").append(new Option(e.element_label+" - ("+e.field_name+")",e.field_name))}),$("[name=bulk_type]").on("change",function(e){$("[name=bulk_recipients_logic]").removeAttr("required"),$("[name=bulk_recipients_list]").removeAttr("required");e=e.target;"list"==e.value?($(".bulk-recipients-list").show(),$(".bulk-recipients-logic").hide(),$("[name=bulk_recipients_list]").prop("required",!0)):"logic"==e.value&&($(".bulk-recipients-logic").show(),$(".bulk-recipients-list").hide(),$("[name=bulk_recipients_logic]").prop("required",!0))}),$("[name=password_type]").on("change",function(e){"random"==e.target.value?$("[field=custom-password]").hide():$("[field=custom-password]").show()}),$("[name=use_second_email]").on("change",function(e){$("[name=email_second_subject]").removeAttr("required"),$("[name=email_second_message]").removeAttr("required"),"no"==e.target.value?($("[field=email-message-second]").hide(),$("[field=email-subject-second]").hide()):($("[field=email-message-second]").show(),$("[field=email-subject-second]").show(),$("[name=email_second_subject]").prop("required",!0),$("[name=email_second_message]").prop("required",!0))}),$("[name=external-modules-configure-modal-1]").on("show.bs.modal",function(e){tinymce.remove(),a.initTinyMCE(),$("#errMsgContainerModal").hide(),$("#errMsgContainerModal-2").hide()}),$("[name=external-modules-configure-modal-1]").on("hidden.bs.modal",function(){$("#saveBulkForm").trigger("reset").removeClass("was-validated")})}showModal(e=null){let i=this;e?($("[name=is_edit_mode]").val("true"),$("#add-edit-title-text").html("Edit Bulk"),$("[name=bulk_id]").val(e),JSO_STPH_BULK_SEND.ajax("bulk",{task:"read",data:{bulk_id:e}}).then(e=>{console.log(e);var a=JSON.parse(e);if(a.error)throw console.log(a),new Error("Error: "+a.message);a=JSON.parse(e).data.bulk;$("[name=bulK-order]").val(a.bulk_order),$("[name=bulk_title]").val(a.bulk_title),("list"==a.bulk_type?($("[name=bulk_type]#bulk_type_list").prop("checked",!0),$("[name=bulk_type]#bulk_type_logic").prop("checked",!1),$(".bulk-recipients-logic").hide(),$("[name=bulk_recipients_list").val(a.bulk_recipients_list),$("[name=bulk_recipients_list]").prop("required",!0),$("[name=bulk_recipients_logic]")):($("[name=bulk_type]#bulk_type_logic").prop("checked",!0),$("[name=bulk_type]#bulk_type_list").prop("checked",!1),$(".bulk-recipients-list").hide(),$("[name=bulk_recipients_logic").val(a.bulk_recipients_logic),$("[name=bulk_recipients_logic]").prop("required",!0),$("[name=bulk_recipients_list]"))).removeAttr("required"),$("[name=file_repo_folder_id]").val(a.file_repo_folder_id),$("[name=file_repo_extension]").val(a.file_repo_extension),$("[name=file_repo_reference]").val(a.file_repo_reference),$("[name=email_display]").val(a.email_display),$("[name=email_from]").val(a.email_from),$("[name=email_to]").val(a.email_to),$("[name=email_first_subject]").val(a.email_first_subject),$("[name=email_first_message]").val(a.email_first_message),"1"===a.use_random_pass?($("[name=password_type]#password_type_random").prop("checked",!0),$("[field=custom-password]").hide()):($("[name=password_type]#password_type_custom").prop("checked",!0),$("[name=custom_pass_field]").val(a.custom_pass_field)),"1"===a.use_second_email?($("[name=use_second_email]#use_second_email_yes").prop("checked",!0),$("[field=email-message-second]").show(),$("[field=email-subject-second]").show(),$("[name=email_second_subject]").val(a.email_second_subject),$("[name=email_second_message]").val(a.email_second_message),$("[name=email_second_subject]").prop("required",!0),$("[name=email_second_message]").prop("required",!0)):($("[name=use_second_email]#use_second_email_no").prop("checked",!0),$("[field=email-message-second]").hide(),$("[field=email-subject-second]").hide(),$("[name=email_second_subject]").removeAttr("required"),$("[name=email_second_message]").removeAttr("required")),a.bulk_schedule&&$("[name=bulk_schedule]").val(i.formatDateTime(a.bulk_schedule)),a.bulk_expiration&&$("[name=bulk_expiration]").val(i.formatDateTime(a.bulk_expiration)),$("#external-modules-configure-modal-1").modal("show")}).catch(e=>{alert(e.message)})):($("[name=is_edit_mode]").val("false"),$("#add-edit-title-text").html("Create new bulk"),e=DTO_STPH_BULK_SEND.modal_defaults.form_defaults,$("[name=bulk_type]#bulk_type_list").prop("checked",!0),$(".bulk-recipients-list").show(),$(".bulk-recipients-logic").hide(),$("[name=bulk_recipients_list]").prop("required",!0),$("[name=email_first_subject]").val(e.email_first_subject),$("[name=email_first_message]").val(e.email_first_message),$("[name=password_type]#password_type_random").prop("checked",!0),$("[field=custom-password]").hide(),$("[field=email-message-second]").show(),$("[field=email-subject-second]").show(),$("[name=use_second_email]#use_second_email_yes").prop("checked",!0),$("[name=email_second_subject]").val(e.email_second_subject),$("[name=email_second_message]").val(e.email_second_message),$("#external-modules-configure-modal-1").modal("show"))}submitForm(){let a=this;var e=$("[name=is_edit_mode]").val(),i=$("#saveBulkForm"),e={task:"true"==e?"update":"create",data:{form_data:JSON.stringify($(i).serializeArray())}};JSO_STPH_BULK_SEND.ajax("bulk",e).then(e=>{e=JSON.parse(e);console.log(e),e.error?($("#errMsgContent-2").html(e.message),$("#errMsgContainerModal-2").show(),$("html,body").scrollTop(0),$("[name=external-modules-configure-modal-1]").scrollTop(0)):($("[name=external-modules-configure-modal-1]").modal("hide"),a.swalSuccess("Bulk has been created/updated!"))})}swalSuccess(e){Swal.fire({title:"Success!",text:e,icon:"success"}).then(()=>{location.reload()})}swalError(e){Swal.fire({title:"Error!",text:e,icon:"error"}).then(()=>{})}checkExtra(){var e=[];let a=!0;return $("[name=file_repo_folder_id]").removeClass("invalid-custom"),$("[name=file_repo_extension]").removeClass("invalid-custom"),$("[name=file_repo_reference]").removeClass("invalid-custom"),"default"==$("[name=file_repo_folder_id]").find(":selected").val()&&($("[name=file_repo_folder_id]").addClass("invalid-custom"),a=!1,e.push("file_repo_folder_id")),"default"==$("[name=file_repo_extension]").find(":selected").val()&&($("[name=file_repo_extension]").addClass("invalid-custom"),a=!1,e.push("file_repo_extension")),"default"==$("[name=file_repo_reference]").find(":selected").val()&&($("[name=file_repo_reference]").addClass("invalid-custom"),a=!1,e.push("file_repo_reference")),$("[name=email_first_message]").parent().find(".tox-tinymce").removeClass("invalid-custom"),$("[name=email_second_message]").parent().find(".tox-tinymce").removeClass("invalid-custom"),""==$("[name=email_first_message]").val()&&($("[name=email_first_message]").parent().find(".tox-tinymce").addClass("invalid-custom"),a=!1,e.push("email_first_message")),""==$("[name=email_second_message]").val()&&"true"==$("[name=email_second_message]").attr("required")&&($("[name=email_second_message]").parent().find(".tox-tinymce").addClass("invalid-custom"),a=!1,e.push("email_second_message")),a}checkRequired(){$("#succMsgContainer").hide(),$("#errMsgContainerModal").hide(),$("#errMsgContainerModal-2").hide();var e=$("#saveBulkForm").get(0).checkValidity();return $("#saveBulkForm").addClass("was-validated"),e}initTinyMCE(){"undefined"==typeof tinymce&&loadJS(app_path_webroot+"Resources/webpack/css/tinymce/tinymce.min.js");var e=rich_text_image_embed_enabled?"image":" ",a=rich_text_attachment_embed_enabled?"fileupload":" ",e=trim(e+" "+a);tinymce.init({license_key:"gpl",font_family_formats:"Open Sans=Open Sans; Andale Mono=andale mono,times; Arial=arial,helvetica,sans-serif; Arial Black=arial black,avant garde; Book Antiqua=book antiqua,palatino; Comic Sans MS=comic sans ms,sans-serif; Courier New=courier new,courier; Georgia=georgia,palatino; Helvetica=helvetica; Impact=impact,chicago; Symbol=symbol; Tahoma=tahoma,arial,helvetica,sans-serif; Terminal=terminal,monaco; Times New Roman=times new roman,times; Trebuchet MS=trebuchet ms,geneva; Verdana=verdana,geneva; Webdings=webdings; Wingdings=wingdings,zapf dingbats",promotion:!1,entity_encoding:"raw",default_link_target:"_blank",selector:".external-modules-rich-text-field",setup:function(e){e.on("change",function(){e.save()})},height:325,menubar:!1,branding:!1,statusbar:!0,elementpath:!1,plugins:"autolink lists link image searchreplace code fullscreen table directionality hr",toolbar1:"fontfamily blocks fontsize bold italic underline strikethrough forecolor backcolor",toolbar2:"align bullist numlist outdent indent table pre hr link "+e+" fullscreen searchreplace removeformat undo redo code",contextmenu:"copy paste | link image inserttable | cell row column deletetable",content_css:app_path_webroot+"Resources/webpack/css/bootstrap.min.css,"+app_path_webroot+"Resources/webpack/css/fontawesome/css/all.min.css,"+app_path_webroot+"Resources/css/style.css",relative_urls:!1,convert_urls:!1,extended_valid_elements:"i[class]",file_picker_types:"image",images_upload_handler:rich_text_image_upload_handler})}initDateTimePicker(){$(".filter_datetime_mdy").datetimepicker({yearRange:"-100:+10",changeMonth:!0,changeYear:!0,dateFormat:user_date_format_jquery,hour:currentTime("h"),minute:currentTime("m"),buttonText:"Foo",timeFormat:"HH:mm",constrainInput:!0}),$(".bulk-datetimepicker").datetimepicker({dateFormat:user_date_format_jquery,buttonImage:app_path_images+"datetime.png",buttonText:"lang.alerts_42",yearRange:"-10:+10",changeMonth:!0,changeYear:!0,hour:currentTime("h"),minute:currentTime("m"),showOn:"both",buttonImageOnly:!0,timeFormat:"HH:mm",constrainInput:!1})}formatDateTime(e){e=new Date(e);return e.getMonth()+1+"-"+e.getDate()+"-"+e.getFullYear()+" "+e.getHours()+":"+e.getMinutes()}deleteRecurrence(a){let i=this;simpleDialog("Are you sure? The schedule (schedule_id: "+a+") will be deleted.","Delete",null,null,null,"Cancel",()=>{JSO_STPH_BULK_SEND.ajax("schedule",{task:"delete",data:{schedule_id:a}}).then(e=>{e=JSON.parse(e);e.error?i.swalError(e.message):i.swalSuccess("Scheduled notification with schedule_id: "+a+" was deleted.")})},"Confirm",void 0)}loadPreviewEmailAlertRecord(e){alert("Email Message Preview: "+e)}loadBulkNotificationLog(e){showProgress(1,void 0,void 0),window.location.href=app_path_webroot+"ExternalModules/?pid="+pid+"&prefix=mass_send_it&page=project-page&log=1&pagenum="+e+"&filterBeginTime="+$("#filterBeginTime").val()+"&filterEndTime="+$("#filterEndTime").val()+"&filterRecord="+$("#filterRecord").val()+"&filterAlert="+$("#filterAlert").val()+"&filterType="+$("#filterType").val()}}let STPH_MassSendIt=new MassSendIt;Object.assign(globalThis,{STPH_MassSendIt:STPH_MassSendIt}),STPH_MassSendIt.init();