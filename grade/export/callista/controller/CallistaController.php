<?php

/**
 * Description of CallistaController
 *
 * @copyright  2012 onwards University of New England
 * @author anoble2
 */
require_once $CFG->dirroot . '/grade/export/callista/daos/BatchDao.php';
require_once $CFG->dirroot . '/grade/export/callista/daos/OfferingsDao.php';
require_once $CFG->dirroot . '/grade/export/callista/daos/RetrieveGradeDao.php';
require_once $CFG->dirroot . '/grade/export/callista/services/GradeCalculationService.php';
require_once $CFG->dirroot . '/grade/export/callista/services/BatchService.php';
require_once $CFG->dirroot . '/grade/export/callista/views/OverridePage.php';
require_once $CFG->dirroot . '/grade/export/callista/views/QueuedPage.php';
require_once $CFG->dirroot . '/grade/export/callista/views/TransferResultsPage.php';
require_once $CFG->dirroot . '/grade/export/callista/views/WebServiceErrorPage.php';
require_once $CFG->dirroot . '/grade/export/callista/views/UnknownErrorPage.php';
require_once $CFG->dirroot . '/grade/export/callista/views/TransferredBatchesPage.php';
require_once $CFG->dirroot . '/grade/export/callista/views/DebugTableDataPage.php';

class CallistaController {
    
    private $gradecalculationservice;
    
    private $batchservice;
    
    function __construct() {
        
        $this->gradecalculationservice = new GradeCalculationService();
        
        $this->batchservice = new BatchService($this->gradecalculationservice);
    }
    
    public function get_html_content($course, $batchid = null, array $markoverrides = array(), array $gradeoverrides = array(), $errorcode = null) {
        /* if a batch id is given, retrieve that batch
         * if a batch id is not given, get the most recent one for the given course
         */
        if($batchid == null) {
            $batch = $this->batchservice->get_batch_for_course($course->id);
        } else {
            $batch = $this->batchservice->get_batch_by_id($batchid);
        }
        
        // if there is no batch, auto save and start with the inital batch
        if($batch == null) {
            $saveresult = $this->save_batch($course, array(), array(), array(), array());
            $batch = $this->batchservice->get_batch_for_course($course->id);
        }

        /* if there is no batch, show the override page with marks taken from the gradebook.
         * if there is a batch, show a view based on the batch's status.
         */
        if($batch == null) {
            $courseid = $course->id;
            $courseshortname = $course->shortname;
            $marks = $this->gradecalculationservice->get_calculated_marks_raw_overrides($course, $markoverrides, $gradeoverrides, $batch);
            $callistagrades = new RetrieveGradeDao($courseid);
            $qgrades = $callistagrades->retrieve_grades(Mark::QGRADE);
            $page = new OverridePage($courseid, $courseshortname, $marks, $qgrades, $errorcode);
        } else {
            $status = $batch->get_status();
            if($status == Batch::STATUS_INITIAL) {
                $courseid = $course->id;
                $courseshortname = $course->shortname;
                $marks = $this->gradecalculationservice->get_calculated_marks_copy_overrides($course, $batch->get_marks(), $batch);
                $callistagrades = new RetrieveGradeDao($courseid);
                $qgrades = $callistagrades->retrieve_grades(Mark::QGRADE);
                $page = new OverridePage($courseid, $courseshortname, $marks, $qgrades, $errorcode);
            } else if($status == Batch::STATUS_QUEUED || $status == Batch::STATUS_SENDING) {
                $late = $this->batchservice->find_late_enrolments_and_withdrawals($course, $batch);
                $page = new QueuedPage($batch, 
                                       $late->enrolments,
                                       $late->withdrawals,
                                       $errorcode);
            } else if($status == Batch::STATUS_SUCCESS || $status == Batch::STATUS_DATA_ERROR) {
                $markcategories = $this->batchservice->separate_marks_into_categories($course, $batch);
                $page = new TransferResultsPage($batch, 
                                                $markcategories->successes, 
                                                $markcategories->warnings,
                                                $markcategories->errors,
                                                $markcategories->enrolments, 
                                                $markcategories->withdrawals,
                                                $errorcode);
            } else if($status == Batch::STATUS_GENERAL_ERROR) {
                $page = new WebServiceErrorPage($batch);
            } else {
                $page = new UnknownErrorPage($batch);
            }
        }
        
        return $page->get_page_html();
    }
    
    public function save_batch($course, array $markoverrides, array $gradeoverrides, array $markoverridden, array $gradeoverridden) {
        return $this->batchservice->save_unqueued_batch($course, $markoverrides, $gradeoverrides, $markoverridden, $gradeoverridden);
    }
    
    public function queue_for_cron($course) {
        return $this->batchservice->queue_saved_batch($course);
    }
    
    public function remove_from_cron_queue($course) {
        return $this->batchservice->remove_batch_from_cron_queue($course);
    }
    
    public function start_a_new_batch_based_on_another_batch($course, $basebatchid) {
        return $this->batchservice->start_a_new_batch_based_on_another_batch($course, $basebatchid);
    }
    
    public function get_batch_archive_html_content($course) {
        $batches = $this->batchservice->get_all_batches_for_course($course->id);
        $page = new TransferredBatchesPage($course, $batches);
        return $page->get_page_html();
    }
    
    public function get_debug_table_data($course) {
        $tabledata = $this->batchservice->get_debug_table_data($course);
        $page = new DebugTableDataPage($tabledata, $course->id);
        return $page->get_page_html();
    }
}

?>
