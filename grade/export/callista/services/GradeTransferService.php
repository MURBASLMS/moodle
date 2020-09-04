<?php

/**
 * Calls Callista's resultLoad web service for each batch of marks that is currently queued and stores the results in the database.
 *
 * @copyright  2012 onwards University of New England
 * @author anoble2
 */
require_once $CFG->dirroot . '/grade/export/callista/daos/BatchDao.php';
require_once $CFG->dirroot . '/grade/export/callista/daos/MarkTransferDao.php';
require_once $CFG->dirroot . '/grade/export/callista/daos/EmailDao.php';

class GradeTransferService {
    
    //A BatchDao object used to read and write Batch and Mark objects to and from the Moodle database.
    private $batchdao;
    
    //A MarkTransferDao object used to call the resultLoad web service and interpret the results.
    private $marktransferdao;
    
    //An EmailDao object used to send out the notification to unit coordinators that the transfer of marks has been completed.
    private $emaildao;
    
    function __construct() {
        $this->batchdao = new BatchDao();
        
        $this->marktransferdao = new MarkTransferDao();
        
        $this->emaildao = new EmailDao();
    }

    /**
     * Retrieves a list of queued batches from the database and uses the $marktransferdao to send them to Callista one at a time.
     * The $marktransferdao stores the results in the Batch and Mark model objects, which are then written to the Moodle database
     * by the $batchdao. 
     */
    public function transfer_all_queued_marks() {
        global $DB;
        
        $courseids = $this->batchdao->get_course_ids_of_queued_batches();
        
        //Export each course.
        foreach ($courseids as $courseid) {
            if ($course = $DB->get_record('course', array('id' => $courseid))) {
                $batch = $this->batchdao->get_batch_with_marks_for_course($courseid);

                $this->marktransferdao->transfer_batch($batch);

                $this->batchdao->store_batch($batch);

                $this->emaildao->send_email_to_coordinators($courseid, $batch->get_unitcode());
            } else {
                mtrace('Course with id ' . $courseid . ' does not exist. Skipping the export request for this course.');
            }
        }
        
        $this->batchdao->clean_up_batches_still_considered_sending();
    }
    
}

?>
