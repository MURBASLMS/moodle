<?php
/**
 * @copyright  2012 onwards University of New England
 */

//$CFG is defined in config.php.
require_once '../../../config.php';
//require_once $CFG->dirroot . '/grade/export/callista/daos/MarkTransferDao.php';
//require_once $CFG->dirroot . '/grade/export/callista/daos/BatchDao.php';
require_once $CFG->dirroot . '/grade/export/callista/services/GradeTransferService.php';

$id = required_param('id', PARAM_INT); // course id

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('nocourseid');
}

require_login($course);
$context = context_course::instance($id);

require_capability('moodle/grade:export', $context);
require_capability('gradeexport/callista:view', $context);

//$marktransferdao = new MarkTransferDao();
//$batchdao = new BatchDao();

//$batch = $batchdao->get_batch_with_marks_for_course($id);            
//$marktransferdao->transfer_batch($batch);
//$batchdao->store_batch($batch);

$gradetransferservice = new GradeTransferService();
$gradetransferservice->transfer_all_queued_marks();

//Redirect to index.php, which will display the queued page if the batch was successfully saved and then queued. If the batch was
//only to be saved or any kind of error happened, the override page will be displayed. Pass any error code to index.php as a url 
//parameter so it can display an appropriate error message.
$urlparams = array('id' => $id);
$indexurl = new moodle_url('/grade/export/callista/index.php', $urlparams);
redirect($indexurl);