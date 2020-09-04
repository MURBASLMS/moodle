<?php
/**
 * This script is the url for creating a new batch that automatically has the mark
 * and grade overrides copied in from an existing batch. It ends by redirecting
 * back to index.php to show the new status.
 * 
 * @copyright  2012 onwards University of New England
 */

//$CFG is defined in config.php.
require_once '../../../config.php';
require_once $CFG->dirroot . '/grade/export/callista/controller/CallistaController.php';

$id = required_param('id', PARAM_INT); // course id
$basebatchid = required_param('basebatch', PARAM_INT); //the batch to duplicate

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('nocourseid');
}

require_login($course);
$context = context_course::instance($id);

require_capability('moodle/grade:export', $context);
require_capability('gradeexport/callista:view', $context);

$controller = new CallistaController();
$result = $controller->start_a_new_batch_based_on_another_batch($course, $basebatchid);

//Redirect to index.php, which will display the override page if the new batch was successfully saved.
//If any kind of error happened, the transfer results page will be displayed with an error message.
//Pass any error code to index.php as a url parameter so it can display an appropriate error message.
$urlparams = array('id' => $id);
if($result != BatchService::RESET_SUCCESSFUL) {
    $urlparams['errorcode'] = $result;
}
$indexurl = new moodle_url('/grade/export/callista/index.php', $urlparams);
redirect($indexurl);
