<?php
/**
 * This script is the url for most of the user's interactions with the plugin.
 * It presents the views for the different stages a batch of marks can be in,
 * from the override page for a non-existent batch to the transfer results page
 * for a transferred batch.
 * 
 * @copyright  2012 onwards University of New England
 */

//$CFG is defined in config.php.
require_once '../../../config.php';
require_once $CFG->dirroot . '/grade/lib.php';
require_once $CFG->dirroot . '/grade/export/callista/controller/CallistaController.php';

$id                 = required_param('id', PARAM_INT); // course id
$batchid            = optional_param('batchid', null, PARAM_INT);
$markoverrides      = optional_param_array('mark_overrides', array(), PARAM_RAW_TRIMMED);
$gradeoverrides     = optional_param_array('grade_overrides', array(), PARAM_RAW_TRIMMED);
$errorcode          = optional_param('errorcode', null, PARAM_INT);

$PAGE->set_url('/grade/export/callista/index.php', array('id'=>$id));

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

// Get course total and makes sure it is out of 100.
$coursetotal = grade_item::fetch_course_item($id);
if (!empty($coursetotal) && $coursetotal->grademax <> 100) {
    print $OUTPUT->box_start('generalbox boxwidthwide boxaligncenter coursetotal');
    print get_string('errorcoursetotal', 'gradeexport_callista');
    print $OUTPUT->box_end();
    echo $OUTPUT->footer();
    exit();
}

//CallistaController determines which view to display.
$controller = new CallistaController();
echo $controller->get_html_content($course, $batchid, $markoverrides, $gradeoverrides, $errorcode);

if(has_capability('gradeexport/callista:viewdebugdata', $context)) {
    echo '<a href="tableData.php?id=' . $id . '">' . get_string('debugtabledata', 'gradeexport_callista') . '</a>';
}

//print the drop-down select menu
groups_print_course_menu($course, 'index.php?id='.$id);
echo '<div class="clearer"></div>';

echo $OUTPUT->footer();
