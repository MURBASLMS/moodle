<?php
/**
 * This script is the url for saving a batch of marks and optionally then queuing
 * the batch to be sent. It ends by redirecting back to index.php to show the new status.
 * 
 * @copyright  2012 onwards University of New England
 */

//$CFG is defined in config.php.
require_once '../../../config.php';
require_once $CFG->dirroot . '/grade/export/callista/controller/CallistaController.php';
require_once $CFG->dirroot . '/grade/export/callista/services/BatchService.php';

$id                 = required_param('id', PARAM_INT); // course id
$markoverrides      = optional_param_array('mark_overrides', array(), PARAM_RAW_TRIMMED);
$gradeoverrides     = optional_param_array('grade_overrides', array(), PARAM_RAW_TRIMMED);
$markoverridden     = optional_param_array('mark_overridden', array(), PARAM_RAW_TRIMMED);
$gradeoverridden    = optional_param_array('grade_overridden', array(), PARAM_RAW_TRIMMED);
$buttonclicked      = required_param('buttonClicked', PARAM_RAW);

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('nocourseid');
}

require_login($course);
$context = context_course::instance($id);

require_capability('moodle/grade:export', $context);
require_capability('gradeexport/callista:view', $context);

//If the button clicked was 'save', save the marks and grades with the given overrides in the database.
//If the button clicked was 'save and send', save the marks and grades, and if that is successful queue the batch to be sent.
$controller = new CallistaController();
if($buttonclicked == 'saveButton') {
    $saveresult = $controller->save_batch($course, $markoverrides, $gradeoverrides, $markoverridden, $gradeoverridden);
} else if($buttonclicked == 'sendButton') {
    $saveresult = $controller->save_batch($course, $markoverrides, $gradeoverrides, $markoverridden, $gradeoverridden);
    if($saveresult == BatchService::SAVING_SUCCESSFUL) {
        $saveresult = $controller->queue_for_cron($course);
    }
} else {
    //deliberately empty. There are no other buttons.
}

//Redirect to index.php, which will display the queued page if the batch was successfully saved and then queued. If the batch was
//only to be saved or any kind of error happened, the override page will be displayed. Pass any error code to index.php as a url 
//parameter so it can display an appropriate error message.
$urlparams = array('id' => $id);
if($saveresult != BatchService::SAVING_SUCCESSFUL && $saveresult != BatchService::QUEUING_SUCCESSFUL) {
    $urlparams['errorcode'] = $saveresult;
}
$indexurl = new moodle_url('/grade/export/callista/index.php', $urlparams);
redirect($indexurl);

