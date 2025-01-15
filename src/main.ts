export {};  //  indicate that the file is a module

/**
 * Declare variables
 */
declare const JSO_STPH_BULK_SEND: any
declare const DTO_STPH_BULK_SEND: BULK_SEND_DTO
declare const tinymce: any;
declare const Swal: any;
declare const currentTime:(type:string,showSeconds?:boolean,returnUTC?:boolean) => number;

interface BULK_SEND_DTO {
    modal_defaults: {
        repo_folders: {
            folder_id: number,
            name: string,
            parent_folder_id: null | number
        } [],
        repo_extensions: string[],
        repo_fields: {
            element_label: string, 
            field_name: string
        }[],
        form_defaults: any
    }
}

/**
 * Class Definition
 * 
 */
class MassSendIt {
    init() {
        console.log("Initiating BulkSend module..")
        //console.log(DTO_STPH_BULK_SEND)
        let that = this

        this.setupModal()

        $('#addNewBulk').on('click', function(){
            that.showModal()
        })

        $('.bulk-edit-btn').on('click', function(){
            const bulk_id = $(this).data("bulkId")
            that.showModal(bulk_id)
        })

        $('#saveBulkForm').on('submit', function(event){
            event.preventDefault()

            //  CheckRequired and showErrors, see checkRequiredFieldsAndLoadOption
            let validRequired = that.checkRequired()
            let validExtra = that.checkExtra()
            if( validRequired && validExtra) {
                //  valid, submit the form
                //console.log("validation success")
                that.submitForm()
            } else {
                //console.log("validation failure")
                //  show errors
                $('#errMsgContainerModal').show();
                $('html,body').scrollTop(0);
                $('[name=external-modules-configure-modal-1]').scrollTop(0);
                return false;
            }

        })        

    }

    setupModal() {

        let that = this;

        //  Setup DateTimerPicker
        that.initDateTimePicker()

        //  add required
        $('[name=email_to]').prop('required', true)

        // setup modal defaults
        let repo_folders = DTO_STPH_BULK_SEND.modal_defaults.repo_folders
        repo_folders.forEach(folder => {
            $('[name=file_repo_folder_id]').append(new Option(folder.name + " - " + "("+folder.folder_id+")", folder.folder_id+""))
        });

        let repo_extensions = DTO_STPH_BULK_SEND.modal_defaults.repo_extensions
        repo_extensions.forEach(ext => {
            $('[name=file_repo_extension]').append(new Option(ext, ext))
        })

        let repo_fields = DTO_STPH_BULK_SEND.modal_defaults.repo_fields
        repo_fields.forEach(field => {
            $('[name=file_repo_reference]').append(new Option(field.element_label + " - " + "("+field.field_name+")",field.field_name))
        })

        $('[name=bulk_type]').on('change', function(e){
            $('[name=bulk_recipients_logic]').removeAttr('required');​​​​​
            $('[name=bulk_recipients_list]').removeAttr('required');​​​​​
            let target  = e.target as HTMLInputElement
            if(target.value  == "list") {
                $('.bulk-recipients-list').show();
                $('.bulk-recipients-logic').hide();
                $('[name=bulk_recipients_list]').prop('required',true);               
            } else if(target.value == "logic") {
                $('.bulk-recipients-logic').show();
                $('.bulk-recipients-list').hide();
                $('[name=bulk_recipients_logic]').prop('required',true);
            }
        })

        $('[name=password_type]').on('change', function(e){
            let target = e.target as HTMLInputElement
            if(target.value == "random") {
                $('[field=custom-password]').hide()                
            } else {
                $('[field=custom-password]').show()
            }
        })

        $('[name=use_second_email]').on('change', function(e){
            $('[name=email_second_subject]').removeAttr('required');​​​​​
            $('[name=email_second_message]').removeAttr('required');​​​​​
            let target = e.target as HTMLInputElement
            if(target.value == "no") {
                $('[field=email-message-second]').hide()                
                $('[field=email-subject-second]').hide()
            } else {
                $('[field=email-message-second]').show()                
                $('[field=email-subject-second]').show()
                $('[name=email_second_subject]').prop('required',true); 
                $('[name=email_second_message]').prop('required',true);
            }
        })

        //  Default on-show actions
        $('[name=external-modules-configure-modal-1]').on('show.bs.modal', function(e) {            
            tinymce.remove();
            that.initTinyMCE();                        
            $('#errMsgContainerModal').hide();
            $('#errMsgContainerModal-2').hide();
        });

        $('[name=external-modules-configure-modal-1]').on('hidden.bs.modal', function () {
            //  @ts-ignore
            $('#saveBulkForm').trigger('reset').removeClass("was-validated");
        })

    }

    showModal(bulk_id:number|null=null) {

        let that = this

        if(bulk_id) {

            //  Set "edit" flag and bulk_id for modal
            $('[name=is_edit_mode]').val("true")
            $('#add-edit-title-text').html("Edit Bulk")
            $('[name=bulk_id]').val(bulk_id)
            
            //  prepare payload
            const payload = {
                task: 'read',
                data: {
                    bulk_id: bulk_id
                }
            }

            JSO_STPH_BULK_SEND.ajax("bulk", payload).then((json:string)=>{
                let response = JSON.parse(json)

                if(response.error) {
                    console.log(response)
                    throw new Error("Error: " + response.message)                    
                }
                let data = JSON.parse(json).data
                let bulk = data.bulk

                //  Setup bulk data
                //  title
                $('[name=bulk_title]').val(bulk.bulk_title)

                //  type
                if(bulk.bulk_type == "list") {
                    $('[name=bulk_type]#bulk_type_list').prop("checked", true);
                    $('[name=bulk_type]#bulk_type_logic').prop("checked", false);
                    $('.bulk-recipients-logic').hide()
                    $('[name=bulk_recipients_list').val(bulk.bulk_recipients_list)
                    $('[name=bulk_recipients_list]').prop('required',true)
                    $('[name=bulk_recipients_logic]').removeAttr('required')
                } else {
                    $('[name=bulk_type]#bulk_type_logic').prop("checked", true);
                    $('[name=bulk_type]#bulk_type_list').prop("checked", false);
                    $('.bulk-recipients-list').hide()
                    $('[name=bulk_recipients_logic').val(bulk.bulk_recipients_logic)
                    $('[name=bulk_recipients_logic]').prop('required',true)
                    $('[name=bulk_recipients_list]').removeAttr('required')
                }

                //  file_repo
                $('[name=file_repo_folder_id]').val(bulk.file_repo_folder_id)
                $('[name=file_repo_extension]').val(bulk.file_repo_extension)
                $('[name=file_repo_reference]').val(bulk.file_repo_reference)

                //  email general
                $('[name=email_display]').val(bulk.email_display)
                $('[name=email_from]').val(bulk.email_from)
                $('[name=email_to]').val(bulk.email_to)

                //  email first
                $('[name=email_first_subject]').val(bulk.email_first_subject)
                $('[name=email_first_message]').val(bulk.email_first_message)

                // password
                if(bulk.use_random_pass === "1") {
                    $('[name=password_type]#password_type_random').prop("checked", true)
                    $('[field=custom-password]').hide()
                } else {
                    $('[name=password_type]#password_type_custom').prop("checked", true)
                    $('[name=custom_pass_field]').val(bulk.custom_pass_field)
                }

                //  email second
                if(bulk.use_second_email === "1") {
                    $('[name=use_second_email]#use_second_email_yes').prop("checked", true)
                    $('[field=email-message-second]').show()
                    $('[field=email-subject-second]').show()
                    $('[name=email_second_subject]').val(bulk.email_second_subject)
                    $('[name=email_second_message]').val(bulk.email_second_message)
                    $('[name=email_second_subject]').prop('required',true); 
                    $('[name=email_second_message]').prop('required',true);
                } else {
                    $('[name=use_second_email]#use_second_email_no').prop("checked", true)
                    $('[field=email-message-second]').hide()
                    $('[field=email-subject-second]').hide()
                    $('[name=email_second_subject]').removeAttr('required'); 
                    $('[name=email_second_message]').removeAttr('required'); 
                }

                //  schedule & expiration
                
                if(bulk.bulk_schedule) {
                    $('[name=bulk_schedule]').val(that.formatDateTime(bulk.bulk_schedule))
                }

                if(bulk.bulk_expiration) {
                    $('[name=bulk_expiration]').val(that.formatDateTime(bulk.bulk_expiration))
                }
                

                $('#external-modules-configure-modal-1').modal('show')

            }).catch((error:Error) => {
                alert(error.message)
            })

        } else {

            $('[name=is_edit_mode]').val("false")
            $('#add-edit-title-text').html("Create new bulk")
            //  Setup Defaults
            let form_defaults = DTO_STPH_BULK_SEND.modal_defaults.form_defaults

            //  type
            $('[name=bulk_type]#bulk_type_list').prop("checked", true)
            $('.bulk-recipients-logic').hide()
            $('[name=bulk_recipients_list]').prop('required',true)

            //  email first
            $('[name=email_first_subject]').val(form_defaults.email_first_subject)
            $('[name=email_first_message]').val(form_defaults.email_first_message)

            //  password
            $('[name=password_type]#password_type_random').prop("checked", true)
            $('[field=custom-password]').hide()

            //  email second
            $('[name=use_second_email]#use_second_email_yes').prop("checked", true)
            $('[name=email_second_subject]').val(form_defaults.email_second_subject)
            $('[name=email_second_message]').val(form_defaults.email_second_message)

            $('#external-modules-configure-modal-1').modal('show')
        }
    }

    submitForm() {
        let that = this
        let isEdit = $('[name=is_edit_mode]').val()
        let form = $('#saveBulkForm')

        let payload = {
            task: 'create',
            data: {
                form_data: JSON.stringify($(form).serializeArray())
            }
        }

        JSO_STPH_BULK_SEND.ajax("bulk", payload).then((json:string)=>{
            let response = JSON.parse(json)            
    
            if(response.error) {
                console.log(response.message)
                $('#errMsgContent-2').html(response.message);
                $('#errMsgContainerModal-2').show();
                $('html,body').scrollTop(0);
                $('[name=external-modules-configure-modal-1]').scrollTop(0);                
            } else {
                console.log("SAVE")
                //  Hide modal and show progress dialog
                $('[name=external-modules-configure-modal-1]').modal('hide'); 
                that.showSuccess("Bulk has been created!")
                //  check if we have a callback
                //that.ajaxRunSchedule(response.data.bulk_id)
            }
        })
    }

    showSuccess(message:string) {
        Swal.fire({
            title: "Success!",
            text: message,
            icon: "success"
        }
        ).then(()=>{
            location.reload();
        });        
    }

    checkExtra() {
        let report = []
        let validExtra = true
        $('[name=file_repo_folder_id]').removeClass('invalid-custom')
        $('[name=file_repo_extension]').removeClass('invalid-custom')
        $('[name=file_repo_reference]').removeClass('invalid-custom')

        if($('[name=file_repo_folder_id]').find(":selected").val() == 'default') {
            $('[name=file_repo_folder_id]').addClass('invalid-custom')
            validExtra = false
            report.push("file_repo_folder_id")
        }

        if($('[name=file_repo_extension]').find(":selected").val() == 'default') {
            $('[name=file_repo_extension]').addClass('invalid-custom')
            validExtra = false
            report.push("file_repo_extension")
        }
        if($('[name=file_repo_reference]').find(":selected").val() == 'default') {
            $('[name=file_repo_reference]').addClass('invalid-custom')
            validExtra = false
            report.push("file_repo_reference")
        }

        $('[name=email_first_message]').parent().find(".tox-tinymce").removeClass("invalid-custom")
        $('[name=email_second_message]').parent().find(".tox-tinymce").removeClass("invalid-custom")

        if($('[name=email_first_message]').val() == "") {
            $('[name=email_first_message]').parent().find(".tox-tinymce").addClass("invalid-custom")
            validExtra = false
            report.push("email_first_message")
        }

        if($('[name=email_second_message]').val() == "" && $('[name=email_second_message]').attr("required") == "true") {
            $('[name=email_second_message]').parent().find(".tox-tinymce").addClass("invalid-custom")
            validExtra = false
            report.push("email_second_message")
        }
        
        return validExtra

    }

    checkRequired() {
        $('#succMsgContainer').hide();
        $('#errMsgContainerModal').hide();
        $('#errMsgContainerModal-2').hide();

        let form = $('#saveBulkForm').get(0) as HTMLFormElement
        let validRequired = form.checkValidity()
        //console.log("Valid required", validRequired)
        $('#saveBulkForm').addClass("was-validated")

        return validRequired
    }

    initTinyMCE() {
        //  @ts-ignore
        if (typeof tinymce == 'undefined') loadJS(app_path_webroot+"Resources/webpack/css/tinymce/tinymce.min.js")
        //  @ts-ignore
        var imageuploadIcon = rich_text_image_embed_enabled ? 'image' : ' '
        //  @ts-ignore
        var fileuploadIcon = rich_text_attachment_embed_enabled ? 'fileupload' : ' '
        //  @ts-ignore
        var fileimageicons = trim(imageuploadIcon + ' ' + fileuploadIcon)
        tinymce.init({
            license_key: 'gpl',
            font_family_formats: 'Open Sans=Open Sans; Andale Mono=andale mono,times; Arial=arial,helvetica,sans-serif; Arial Black=arial black,avant garde; Book Antiqua=book antiqua,palatino; Comic Sans MS=comic sans ms,sans-serif; Courier New=courier new,courier; Georgia=georgia,palatino; Helvetica=helvetica; Impact=impact,chicago; Symbol=symbol; Tahoma=tahoma,arial,helvetica,sans-serif; Terminal=terminal,monaco; Times New Roman=times new roman,times; Trebuchet MS=trebuchet ms,geneva; Verdana=verdana,geneva; Webdings=webdings; Wingdings=wingdings,zapf dingbats',
            promotion: false,
            entity_encoding : "raw",
            default_link_target: '_blank',
            selector: '.external-modules-rich-text-field',
            //  @ts-ignore
            setup: function (editor) {
                editor.on('change', function () {
                    editor.save();
                });
            },
            height: 325,
            menubar: false,
            branding: false,
            statusbar: true,
            elementpath: false, // Hide this, since it oddly renders below the textarea.
            plugins: 'autolink lists link image searchreplace code fullscreen table directionality hr',
            toolbar1: 'fontfamily blocks fontsize bold italic underline strikethrough forecolor backcolor',        
            toolbar2: 'align bullist numlist outdent indent table pre hr link '+fileimageicons+' fullscreen searchreplace removeformat undo redo code',
            contextmenu: "copy paste | link image inserttable | cell row column deletetable",
            // @ts-ignore
            content_css: app_path_webroot + "Resources/webpack/css/bootstrap.min.css," + app_path_webroot + "Resources/webpack/css/fontawesome/css/all.min.css,"+app_path_webroot+"Resources/css/style.css",
            relative_urls: false,
            convert_urls : false,
            extended_valid_elements: 'i[class]',
            // Embedded image uploading
            file_picker_types: 'image',
            // @ts-ignore
            images_upload_handler: rich_text_image_upload_handler
        });
    }

    initDateTimePicker() {

        // Set datetime pickers
        $('.filter_datetime_mdy').datetimepicker({
            //  @ts-ignore
            yearRange: '-100:+10', changeMonth: true, changeYear: true, dateFormat: user_date_format_jquery,
            hour: currentTime('h'), minute: currentTime('m'), buttonText: "Foo",
            timeFormat: 'HH:mm', constrainInput: true
        });
    
        $('.bulk-datetimepicker').datetimepicker({
            //  @ts-ignore
            dateFormat: user_date_format_jquery, buttonImage: app_path_images+'datetime.png',
            buttonText: "lang.alerts_42", yearRange: '-10:+10', changeMonth: true, changeYear: true, 
            hour: currentTime('h'), minute: currentTime('m'),
            showOn: 'both',  buttonImageOnly: true, timeFormat: 'HH:mm', constrainInput: false,
        })    
        
    } 

    formatDateTime(ds:string) {
        const sd = new Date(ds)
        return sd.getMonth() + 1+"-"+sd.getDate()+"-"+sd.getFullYear()+" "+sd.getHours()+":"+sd.getMinutes()
       
    }
}


/**
 * Instantiate the class
 * 
 * Publish module object on global object
 * https://stackoverflow.com/a/72374303/3127170
 * 
 */
let STPH_MassSendIt = new MassSendIt();
Object.assign(globalThis, { STPH_MassSendIt });
STPH_MassSendIt.init();