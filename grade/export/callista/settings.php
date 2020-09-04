<?php

/**
 * @copyright  2012 onwards University of New England
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext(
            'gradeexport_callista/webserviceusername', 
            get_string('webserviceusername', 'gradeexport_callista'),
            get_string('webserviceusernamedescription', 'gradeexport_callista'),
            ''));
    $settings->add(new admin_setting_configpasswordunmask(
            'gradeexport_callista/webservicepassword', 
            get_string('webservicepassword', 'gradeexport_callista'), 
            get_string('webservicepassworddescription', 'gradeexport_callista'), 
            ''));
    $settings->add(new admin_setting_configtext(
            'gradeexport_callista/webserviceauthenticationnamespace',
            get_string('webserviceauthenticationnamespace', 'gradeexport_callista'),
            get_string('webserviceauthenticationnamespacedescription', 'gradeexport_callista'),
            'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd'));

    $defaultroles = array();
    $defaultroleid = 1;
    $roles = role_fix_names(get_all_roles(), null, ROLENAME_ORIGINALANDSHORT);
    foreach ($roles as $role) {
        $rolename = $role->localname;
        switch ($role->archetype) {
            case 'manager':
                $defaultroleid = isset($defaultroleid) ? $defaultroleid : $role->id;
            case 'coursecreator':
            case 'editingteacher':
                $defaultroles[$role->id] = $rolename;
            default:
                break;
        }
    }

    $settings->add(new admin_setting_configselect('gradeexport_callista/defaultroleid',
                          new lang_string('defaultroleid', 'gradeexport_callista'),
                          new lang_string('defaultroleid_help', 'gradeexport_callista'), $defaultroleid, $defaultroles));


    $settings->add(new admin_setting_configtext(
            'gradeexport_callista/adminnotificationemail',
            get_string('adminnotificationemail', 'gradeexport_callista'),
            get_string('adminnotificationemail_help', 'gradeexport_callista'),
            ''));

    $currentwdsls = array('' => 'Default', 'prod' => 'Prod', 'stage' => 'Stage');
    foreach ($currentwdsls as $key => $value) {
        $filename = $CFG->dirroot . "/grade/export/callista/localWsdl{$key}.xml";
        if (!is_readable($filename)) {
            unset($currentwdsls[$key]);
        }
    }

    $settings->add(new admin_setting_configselect('gradeexport_callista/currentwdsl',
                          new lang_string('currentwdsl', 'gradeexport_callista'),
                          new lang_string('currentwdsl_help', 'gradeexport_callista'), '', $currentwdsls));

}

