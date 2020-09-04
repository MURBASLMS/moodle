<?php
/**
 * This script is the url for showing the list of batches of marks a unit has had.
 * 
 * @copyright  2012 onwards University of New England
 */

//$CFG is defined in config.php.
require_once '../../../config.php';
require_once $CFG->dirroot . '/grade/lib.php';
require_once $CFG->dirroot . '/grade/export/callista/controller/CallistaController.php';

$id = required_param('id', PARAM_INT); // course id

$PAGE->set_url('/grade/export/callista/batchArchive.php', array('id'=>$id));

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('nocourseid');
}

require_login($course);
$context = context_course::instance($id);

require_capability('moodle/grade:export', $context);
require_capability('gradeexport/callista:view', $context);

//prints the page title
$pagetitle = get_string('exporttitle', 'gradeexport_callista') . " - $course->shortname";
print_grade_page_head($COURSE->id, 'export', 'callista', $pagetitle);

//CallistaController is used to get the page's html content.
$controller = new CallistaController();
echo $controller->get_batch_archive_html_content($course);

//print the drop-down select menu
groups_print_course_menu($course, 'index.php?id='.$id);
echo '<div class="clearer"></div>';

echo $OUTPUT->footer();
