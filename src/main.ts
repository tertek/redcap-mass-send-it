export {};  //  indicate that the file is a module

/**
 * Declare variables
 */
declare const JSO_STPH_BULK_SEND: any
declare const DTO_STPH_BULK_SEND: BULK_SEND_DTO
declare const tinymce: any;
declare const Swal: any;

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
        console.log("MassSendIt ready!");
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