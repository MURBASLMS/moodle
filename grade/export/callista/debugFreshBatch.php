<?php
/**
 * @copyright  2012 onwards University of New England
 */

//$CFG is defined in config.php.
require_once '../../../config.php';
require_once $CFG->dirroot . '/grade/export/callista/controller/CallistaController.php';
require_once $CFG->dirroot . '/grade/export/callista/services/BatchService.php';
require_once $CFG->dirroot . '/grade/export/callista/daos/BatchDao.php';

$id = required_param('id', PARAM_INT); // course id
$teachingperiodname = required_param('teachingPeriodName', PARAM_RAW); // course id
$teachingperiodyear = required_param('teachingPeriodYear', PARAM_INT); // course id

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('nocourseid');
}

require_login($course);
$context = context_course::instance($id);

require_capability('moodle/grade:export', $context);
require_capability('gradeexport/callista:view', $context);

//create the bare minimum for a batch record in the database, then use the ordinary batch saving method to fill in other details.
$batch = new Batch();
$batch->set_courseid($id);
$batch->set_unitcode($course->shortname);
$batch->set_status(Batch::STATUS_INITIAL);
$batch->set_loadedbyusername($USER->username);
$batch->set_teachingperiodalternatecode($teachingperiodname);
$batch->set_academicyear($teachingperiodyear);
$batchdao = new BatchDao();
$batchdao->store_batch($batch, false);

//Redirect to index.php, which will display the queued page if the batch was successfully saved and then queued. If the batch was
//only to be saved or any kind of error happened, the override page will be displayed. Pass any error code to index.php as a url 
//parameter so it can display an appropriate error message.
$urlparams = array('id' => $id);
if($saveresult != BatchService::SAVING_SUCCESSFUL && $saveresult != BatchService::QUEUING_SUCCESSFUL) {
    $urlparams['errorcode'] = $saveresult;
}
$indexurl = new moodle_url('/grade/export/callista/index.php', $urlparams);
redirect($indexurl);
