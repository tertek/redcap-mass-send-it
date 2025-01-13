<?php

namespace STPH\massSendIt;

use Exception;

class Bulk {

    public int $id;
    public int $bulk_order;
    public string $title;
    public string $bulk_type;

    public array $recipients;
    public ?string $recipients_list;
    public ?string $recipients_logic;

    public int $file_repo_folder_id;
    public string $file_repo_extension;
    public string $file_repo_reference;

    public string $email_display;
    public string $email_from;
    public string $email_to;
    public string $email_first_subject;
    public string $email_first_message;
    public bool $use_random_pass;
    public ?string $custom_pass_field;
    public bool $use_second_email;
    public ?string $email_second_subject;
    public ?string $email_second_message;
    
    public string $bulk_schedule;
    public ?string $bulk_expiration;

    public function __construct() {

    }

    public function getFieldNames() {
        return array_keys(get_class_vars(__CLASS__));
    }

}