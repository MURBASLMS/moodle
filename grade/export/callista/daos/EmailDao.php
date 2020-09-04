<?php

/**
 * EmailDao emails the unit coordinators of units when the unit's marks have been
 * transferred to Callista.
 *
 * @copyright  2012 onwards University of New England
 * @author anoble2
 */
require_once $CFG->dirroot . '/lib/weblib.php';

class EmailDao {
    
    /**
     * Sends an email to the unit coordinators of a given course telling them that
     * the grade export has happened.
     * @global type $DB Moodle database object.
     * @param int $courseid The Moodle id for the course whose marks have been transferred.
     * @param string $courseshortname The course's short name to use in the email.
     */
    public function send_email_to_coordinators($courseid, $courseshortname) {
        global $CFG, $DB;
        
        $roleid = get_config('gradeexport_callista', 'defaultroleid');
        $context = context_course::instance($courseid);
        $unitcoordinatorrecords = get_role_users($roleid, $context);
        
        $from = 'Callista Grade Export';
        
        $subject = $courseshortname . ' grades exported to Callista';
        
        $statusurl = new moodle_url($CFG->wwwroot . '/grade/export/callista/index.php', array('id' => $courseid));
        $message = 'The marks and grades for ' . $courseshortname . ' have been sent to Callista. Please view the status page at '
                    . $statusurl . ' to check there have been no errors.';
        
        foreach ($unitcoordinatorrecords as $unitcoordinator) {
            email_to_user($unitcoordinator, $from, $subject, $message);
        }

        $adminnotificationemail = get_config('gradeexport_callista', 'adminnotificationemail');
        if (!empty($adminnotificationemail)) {
            $touser = new stdClass;
            $touser->id = 1; // Required argument so set to admin user ID.
            $touser->email = $adminnotificationemail;
            $touser->firstname = '';
            $touser->lastname = '';
            $touser->maildisplay = true;
            $touser->mailformat = 1;
            email_to_user($touser, $from, $subject, $message);
        }
    }
}

?>
