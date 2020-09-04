<?php
/**
 * This script is the url for removing the unit's most recent queued batch from
 * the queue. It ends by redirecting back to index.php to show the new status.
 * 
 * @copyright  2012 onwards University of New England
 */

//$CFG is defined in config.php.
require_once '../../../config.php';
require_once $CFG->dirroot . '/grade/export/callista/controller/CallistaController.php';
require_once $CFG->dirroot . '/grade/export/callista/services/BatchService.php';

$id = required_param('id', PARAM_INT); // course id

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('nocourseid');
}

require_login($course);
$context = context_course::instance($id);

require_capability('moodle/grade:export', $context);
require_capability('gradeexport/callista:view', $context);

//queue the marks and grades using overrides when present
$controller = new CallistaController();
$dequeuingresult = $controller->remove_from_cron_queue($course);

//Redirect to index.php, which will display the override view if the dequeuing worked. 
//If the dequeuing failed for any reason (such as a database exception), pass the error code
//to index.php as a url parameter so it can display an appropriate error message.
$urlparams = array('id' => $id);
if($dequeuingresult != BatchService::DEQUEUING_SUCCESSFUL) {
    $urlparams['errorcode'] = $dequeuingresult;
}
$indexurl = new moodle_url('/grade/export/callista/index.php', $urlparams);
redirect($indexurl);

