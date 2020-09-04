<?php

/**
 * TeachingPeriodDao gets information about the teaching period a course is 
 * offerred in.
 *
 * @copyright  2012 onwards University of New England
 * @author anoble2
 */
require_once $CFG->dirroot . '/local/callista/classes/enrolment_database.php';

class TeachingPeriodDao {
    
    /**
     * Gets data about the teaching period that a given course is
     * offerred in.
     * @global type $DB Moodle database object.
     * @param int $courseid Moodle's id number for the course.
     * @return stdClass An object containing the teaching period's data, or
     *         stdClass('short_name' => null,
     *                  'year'       => null) if there is no data.
     */
    public function get_teaching_period_data_for_course($courseid) {
        global $DB;

        $holding = new local_callista_enrolment_database();
        $courses = $holding->get_course_sis_unit_offers($courseid);

        $teachingperiod = new stdClass();
        $teachingperiod->short_name = null;
        $teachingperiod->year = null;
        if ($courses) {
            while (!$courses->EOF) {
                $teachingperiod->short_name = (!empty($courses->fields['alternate_semester_code'])) ?  $courses->fields['alternate_semester_code'] : null;
                $teachingperiod->year = (!empty($courses->fields['year'])) ?  $courses->fields['year'] : null;
                $courses->MoveNext();
            }
        }
        $courses->Close();
        return $teachingperiod;
    }
}

?>
