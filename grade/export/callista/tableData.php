<?php
/**
 * This script is the url for showing the data in the plugin's tables for the
 * specified unit. The page is to aid with debugging when database access is
 * restricted.
 * 
 * @copyright  2012 onwards University of New England
 */

require_once '../../../config.php';
require_once $CFG->dirroot . '/grade/lib.php';
require_once $CFG->dirroot . '/grade/export/callista/controller/CallistaController.php';

$id = required_param('id', PARAM_INT); // course id

$PAGE->set_url('/grade/export/callista/tableData.php', array('id'=>$id));

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('nocourseid');
}

require_login($course);
$context = context_course::instance($id);

require_capability('moodle/grade:export', $context);
require_capability('gradeexport/callista:viewdebugdata', $context);

//prints the page title
$pagetitle = get_string('exporttitle', 'gradeexport_callista') . " - $course->shortname";
print_grade_page_head($COURSE->id, 'export', 'callista', $pagetitle);

//CallistaController determines which view to display.
$controller = new CallistaController();
echo $controller->get_debug_table_data($course);

//print the drop-down select menu
groups_print_course_menu($course, 'index.php?id='.$id);
echo '<div class="clearer"></div>';

echo $OUTPUT->footer();


