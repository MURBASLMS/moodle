<?php

/**
 * The Gradebook dao uses a graded_users_iterator to retrieve the marks and grades for all the graded users (ie students) in a Moodle
 * course. The grade (e.g. C) is derived from the numeric mark.
 *
 * @copyright  2012 onwards University of New England
 * @author anoble2
 */
require_once $CFG->dirroot . '/grade/lib.php';
require_once $CFG->libdir . '/grade/grade_item.php';
require_once $CFG->dirroot . '/grade/export/callista/model/Mark.php';

class GradebookDao {
    /**
     * Gets the student number, mark and grade for the graded users (i.e. students) in a given course, and returns them as an array
     * of Mark objects.
     * @param type $course Moodle's object for the course we want the marks for.
     * @return \Mark An array of Mark objects for the graded users of the course.
     */
    public function get_marks_for_course($course) {
        global $CFG, $DB;
        $marks = array();

        $coursetotalgradeitem = grade_item::fetch_course_item($course->id);
        $gu_iterator = new graded_users_iterator($course, array($coursetotalgradeitem->id => $coursetotalgradeitem));
        $gu_iterator->require_active_enrolment();
        $gu_iterator->init();

        list($insql, $inparams) = $DB->get_in_or_equal($CFG->gradebookroles, SQL_PARAMS_NAMED);
        $sql = "SELECT DISTINCT ra.userid
                  FROM {enrol} e
                  JOIN {user_enrolments} ue ON (e.id = ue.enrolid AND enrol='manual')
                  JOIN {context} ctx ON (ctx.instanceid = e.courseid AND ctx.contextlevel = :level)
                  JOIN {role_assignments} ra ON (ra.contextid = ctx.id AND ue.userid = ra.userid)
                 WHERE courseid=:cid AND ra.roleid $insql
                   AND NOT EXISTS (
                       SELECT 1 FROM {enrol} e2
                         JOIN {user_enrolments} ue2 ON (e2.id = ue2.enrolid AND e2.enrol='database')
                        WHERE e2.id = ue2.enrolid
                          AND e2.courseid = e.courseid
                          AND ue2.userid = ue.userid
                          AND ue2.status = 0)";

        $params = array('cid' => $course->id, 'level' => CONTEXT_COURSE);
        $params = array_merge($params, $inparams);
        $manualusers = $DB->get_records_sql($sql, $params);
        $outcomeid = 1;
        while ($userdata = $gu_iterator->next_user()) {
            $mark = $userdata->grades[$coursetotalgradeitem->id]->finalgrade;
            if (array_key_exists($userdata->user->id, $manualusers)) {
                continue;
            }
            if($mark == null) {
                $grade = null;
            } else {
                $grade = grade_format_gradevalue($mark, $coursetotalgradeitem, true, GRADE_DISPLAY_TYPE_LETTER);
            }
            $marks[] = new Mark(null,
                                null,
                                $userdata->user->idnumber,
                                $userdata->user->firstname,
                                $userdata->user->lastname,
                                $mark,
                                null,
                                $grade,
                                null,
                                $outcomeid,
                                null, 
                                null,
                                null, 
                                null,
                                null,
                                null);
            $outcomeid++;
        }
        
        $gu_iterator->close();
        
        return $marks;
    }
}

?>
