<?php

/**
 * The BatchService class can retrieve Batch objects (containing Mark objects) from the database, save batches to the database,
 * put a saved batch on the queue for cron to transfer, remove a batch from the cron queue, separate a batch's marks into groups
 * based on criteria such as whether the mark was transferred to Callista successfully or not, or retrieve the raw database table
 * data for a course for debugging purposes.
 * 
 * @copyright  2012 onwards University of New England
 * @author anoble2 
 */
require_once $CFG->dirroot . '/grade/export/callista/model/Batch.php';
require_once $CFG->dirroot . '/grade/export/callista/daos/BatchDao.php';
require_once $CFG->dirroot . '/grade/export/callista/daos/TeachingPeriodDao.php';

class BatchService {
    //Used if there are no problems saving a batch
    const SAVING_SUCCESSFUL = 100;
    
    //Used if the database throws an exception of some kind while saving a batch. E.g. constraint violation.
    const SAVING_DATABASE_ERROR = 101;
    
    //Used if the batch has a status that shouldn't allow it to be saved (i.e. not Batch::STATUS_INITIAL)
    const SAVING_NON_SAVING_STATUS = 102;
    
    //Used if any of the overrides given in the batch are not of the correct format.
    const SAVING_MALFORMED_OVERRIDES = 103;
    
    //Used if there are no problems queuing a saved batch.
    const QUEUING_SUCCESSFUL = 200;
    
    //Used if the database throws an exception of some kind while queuing a saved batch. E.g. constraint violation.
    const QUEUING_DATABASE_ERROR = 201;
    
    //Used if the batch has a status that shouldn't allow it to be queued (i.e. not Batch::STATUS_INITIAL)
    const QUEUING_NON_QUEUING_STATUS = 202;
    
    //Used if any of the marks in the batch have null values for both calculated_mark and mark_override.
    const QUEUING_EMPTY_MARKS = 203;
    
    //Used if any of the marks in the batch have null values for both derived_grade and grade_override.
    const QUEUING_EMPTY_GRADES = 204;
    
    //Used if any of the marks in the batch have null values for all of calculated_mark, mark_override, derived_grade and grade_override.
    const QUEUING_EMPTY_MARKS_AND_GRADES = 205;
    
    //Used if the batch cannot be found in the database.
    const QUEUING_NON_EXISTENT_BATCH = 206;
    
    //Used if there are no problems removing a batch from the queue.
    const DEQUEUING_SUCCESSFUL = 300;
    
    //Used if the database throws an exception of some kind while dequeuing a batch. E.g. constraint violation.
    const DEQUEUING_DATABASE_ERROR = 301;
    
    //Used if the batch in the database has a status other than Batch::STATUS_QUEUED
    const DEQUEUING_NON_QUEUED_STATUS = 302;
    
    //Used if the batch cannot be found in the database.
    const DEQUEUING_NON_EXISTENT_BATCH = 306;
    
    //Used if a new batch was successfully created based on another batch's data.
    const RESET_SUCCESSFUL = 400;
    
    //Used if the database throws an exception of some kind while creating a new batch based on another one.
    const RESET_DATABASE_ERROR = 401;
    
    //Used if the base batch in the database has a status other than Batch::STATUS_SUCCESS, Batch::STATUS_DATA_ERROR or Batch::STATUS_GENERAL_ERROR
    const RESET_NON_RESET_STATUS = 402;
    
    //Used if the base batch cannot be found in the database.
    const RESET_NON_EXISTENT_BATCH = 406;
    
    //A BatchDao object to read and write Batches from and to the database with.
    private $batchdao;
    
    //A TeachingPeriodDao object used to retrieve a course's teaching period information.
    private $teachingPeriodDao;
    
    //A GradeCalculationService object used to retrieve a unit's calculated marks and grades from the Moodle Gradebook.
    private $gradecalculationservice;
    
    //An array(string) listing the valid grades that can be used as overrides.
    private $allowedgrades;
    
    //Regex that matches either an empty string, simple integer or simple float.
    private $wellformedmarkoverrideregex;
    
    //Regex that matches the empty string or one of the allowed letter grades.
    private $wellformedgradeoverrideregex;
    
    function __construct(GradeCalculationService $gradecalculationservice) {
        $this->batchdao = new BatchDao();
        
        $this->teachingPeriodDao = new TeachingPeriodDao();
        
        $this->gradecalculationservice = $gradecalculationservice;
        
        $this->allowedgrades = array ('HD', 'D', 'C', 'P', 'N', 'SA', 'SX', 'NA', 'DNS');
        $this->wellformedmarkoverrideregex = '/' . Mark::NO_MARK_STRING . '|(?:^$)|(?:^[0-9]+$)|(?:^[0-9]*\.[0-9]+$)/';
        $this->wellformedgradeoverrideregex = '/(?:^$)|(?:^' . implode('$)|(?:^', array_map('preg_quote', $this->allowedgrades)) . '$)/';
    }

    /**
     * Retrieves the most recent batch (by time_when_saved) from the database for the given Moodle course id.
     * @param int $courseid Moodle's course id for the course whose batch of marks is to be retrieved.
     * @return Batch A Batch object containing Marks objects.
     */
    public function get_batch_for_course($courseid) {
        return $this->batchdao->get_batch_with_marks_for_course($courseid);
    }
    
    /**
     * Retrieves the batch with the given id and its marks from the database.
     * @param int $batchid The id number of the batch to retrieve.
     * @return Batch The batch with the given id, or null if it does not exist.
     */
    public function get_batch_by_id($batchid) {
        return $this->batchdao->get_batch_with_marks_by_id($batchid);
    }
    
    /**
     * Retrieves all the batches in the database for a nominated course.
     * @param int $courseid The Moodle id for the course whose batches we want.
     * @return array(Batch) An array of Batch objects of all the batches in the database for this course.
     */
    public function get_all_batches_for_course($courseid) {
        return $this->batchdao->get_all_batches_without_marks_for_course($courseid);
    }
    
    /**
     * Calculates the marks for the given course and applies the given overrides to create an array of Marks. If there is no batch
     * for the given course, a new one is created. Otherwise the most recent existing batch for the course is found. The array of
     * marks is put in the batch and the batch stored in the database.
     * @global type $USER Moodle's object for the current user.
     * @param type $course Moodle's object for the course to make a batch for.
     * @param array $markoverrides An array (student number => overriding mark) of marks to use instead of the calculated marks from
     *                             Moodle's gradebook.
     * @param array $gradeoverridesAn array (student number => overriding grade) of grades to use instead of the grade derived from
     *                              the mark calculated by Moodle's gradebook.
     * @param array $markoverridden An array (student number => overridden mark) of marks to use instead of the calculated marks
     *                              from Moodle's gradebook.
     * @param array $gradeoverridden An array (student number => overridden grade) of grades to use instead of the grade derived
     *                              from the mark calculated by Moodle's gradebook.
     * @return int One of BatchService's SAVING constants. 
     */
    public function save_unqueued_batch($course, array $markoverrides, array $gradeoverrides, array $markoverridden, array $gradeoverridden) {
        global $USER;
        
        //check if any of the overrides are malformed. We avoid a database read if this fails here than if we check later in the
        //function.
        if($this->are_overrides_malformed($markoverrides, $gradeoverrides)) {
            return BatchService::SAVING_MALFORMED_OVERRIDES;
        }
        
        //Get the most recent batch for the course from the database. If there isn't one, create a batch in memory.
        $batch = $this->batchdao->get_batch_with_marks_for_course($course->id);
        if($batch == null) {
            $batch = new Batch();
            $batch->set_courseid($course->id);
            $batch->set_unitcode($course->shortname);
            $batch->set_status(Batch::STATUS_INITIAL);
            $batch->set_loadedbyusername($USER->username);

            $teachingperiod = $this->teachingPeriodDao->get_teaching_period_data_for_course($course->id);
            $batch->set_teachingperiodalternatecode($teachingperiod->short_name);
            $batch->set_academicyear($teachingperiod->year);
        } 
        
        if($batch->get_status() == Batch::STATUS_INITIAL) {
            //copy over the outcome messages to the new batch
            $originalmarks = $batch->get_marks();
            $marks = $this->gradecalculationservice->get_calculated_marks_raw_overrides($course, $markoverrides, $gradeoverrides, $batch);
            foreach ($originalmarks as $originalmark) {
                foreach ($marks as $mark) {
                    if($originalmark->is_for_same_person_and_unit($mark)) {
                        $mark->set_outcomeloadedflag($originalmark->get_outcomeloadedflag());

                        if ($originalmark->get_grade() == Mark::QGRADE_GRADE && $mark->get_grade() != Mark::QGRADE_GRADE) {
                            $mark->set_outcomeloadmessagenumber(null);
                        } else {
                            $mark->set_outcomeloadmessagenumber($originalmark->get_outcomeloadmessagenumber());
                        }
                        $mark->set_outcomeloadmessage($originalmark->get_outcomeloadmessage());
                    }
                }
            }
            // Handle special cases for Q Grades
            foreach ($marks as $mark) {
                $studentnumber = $mark->get_studentnumber();
                if (!empty($markoverridden[$studentnumber])) {
                    $mark->set_markoverride($markoverridden[$studentnumber]);
                }

                if (!empty($gradeoverridden[$studentnumber])) {
                    $mark->set_gradeoverride($gradeoverridden[$studentnumber]);
                }

                if (!empty($gradeoverridden[$studentnumber]) && $gradeoverridden[$studentnumber] == Mark::QGRADE) {
                    $mark->set_gradeoverride(Mark::QGRADE_GRADE);
                    $mark->set_outcomeloadmessagenumber(Mark::QGRADE_ERRORNO);
                }
            }
            $batch->set_marks($marks);
            
            try {
                $this->batchdao->store_batch($batch);
            } catch (Exception $e) {
                return BatchService::SAVING_DATABASE_ERROR;
            }
        } else {
            return BatchService::SAVING_NON_SAVING_STATUS;
        }
        
        return BatchService::SAVING_SUCCESSFUL;
    }
    
    /**
     * Checks whether any of the mark or grade overrides are in a format not allowed by the database.
     * @param array $markoverrides An array of the mark overrides to be used.
     * @param array $gradeoverrides An array of the grade overrides to be used.
     * @return boolean True if any overrides are not in the correct format. False if all the overrides are ok.
     */
    private function are_overrides_malformed(array $markoverrides, array $gradeoverrides) {
        foreach ($markoverrides as $markoverride) {
            if(!preg_match($this->wellformedmarkoverrideregex, $markoverride)) {
                return true;
            }
        }
        
        foreach ($gradeoverrides as $letteroverride) {
            if(!preg_match($this->wellformedgradeoverrideregex, $letteroverride)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Finds the most recently saved batch for a course and attempts to queue it. The batch must have a status of 2 and have a 
     * calculated mark or mark override and a derived grade or grade override specified for every mark component of the batch.
     * @param type $course Moodle's course object for the course whose marks are to be queued.
     * @return int One of BatchService's QUEUING constants. 
     */
    public function queue_saved_batch($course) {
        $batch = $this->batchdao->get_batch_with_marks_for_course($course->id);
        if($batch == null) {
            return BatchService::QUEUING_NON_EXISTENT_BATCH;
        }
        
        if($batch->get_status() != Batch::STATUS_INITIAL) {
            return BatchService::QUEUING_NON_QUEUING_STATUS;
        }
        
        $emptycheck = $this->batchdao->check_batch_for_empty_marks_and_grades($batch);
        if($emptycheck['numberwithoutmarks'] != 0) {
            if($emptycheck['numberwithoutgrades'] != 0) {
                return BatchService::QUEUING_EMPTY_MARKS_AND_GRADES;
            } else {
                return BatchService::QUEUING_EMPTY_MARKS;
            }
        } else if($emptycheck['numberwithoutgrades'] != 0) {
            return BatchService::QUEUING_EMPTY_GRADES;
        }
        
        try {
            //wipe out any copied-over outcome messages unless its a Q Grade error number
            foreach ($batch->get_marks() as $mark) {
                $mark->set_outcomeloadedflag(null);
                if ($mark->get_outcomeloadmessagenumber() != Mark::QGRADE_ERRORNO) {
                    $mark->set_outcomeloadmessagenumber(null);
                }
                $mark->set_outcomeloadmessage(null);
            }
            
            $batch->set_status(Batch::STATUS_QUEUED);
            $batch->set_timewhenqueued(time());
            $this->batchdao->store_batch($batch);
            return BatchService::QUEUING_SUCCESSFUL;
        } catch(Exception $e) {
            return BatchService::QUEUING_DATABASE_ERROR;
        }
    }
    
    /**
     * Produces a stdClass containing two arrays. The first, called enrolments, contains Marks for all the students that are enrolled
     * in the course but do not have a Mark in the Batch. The second, called withdrawals, contains Marks for all the students who
     * have a Mark in the Batch but are not enrolled in the course. Comparisons are done using student numbers only.
     * @param type $course Moodle's object for the course whose marks we are comparing.
     * @param Batch $batch The Batch of Marks we are comparing to the current marks for the given Moodle course.
     * @return \stdClass A stdClass->enrolments  = array(Marks) of students who are currently enrolled in the specified course in 
     *                                             Moodle but do not have a Mark in the Batch.
     *                             ->withdrawals = array(Marks) of students who have a Mark in the Batch but are not currently
     *                                             enrolled in the specified course in Moodle.
     */
    public function find_late_enrolments_and_withdrawals($course, Batch $batch) {
        $marksatpresent = $this->gradecalculationservice->get_calculated_marks_raw_overrides($course, array(), array(), $batch);
        $batchmarks = $batch->get_marks();
        
        $late = new stdClass();
        $late->enrolments = array_udiff($marksatpresent, $batchmarks, 'Mark::compare_by_person_and_unit');
        $late->withdrawals = array_udiff($batchmarks, $marksatpresent, 'Mark::compare_by_person_and_unit');
        
        if($late->enrolments == null) {
            $late->enrolments = array();
        }
        if($late->withdrawals == null) {
            $late->withdrawals = array();
        }
        
        return $late;
    }
    
    /**
     * Finds the most recent batch for the given course and changes its status to prevent cron from sending it to Callista. Also
     * empties the time_when_queued field.
     * @param type $course Moodle's object for the course to find the batch for.
     * @return int One of BatchService's DEQUEUING constants.
     */
    public function remove_batch_from_cron_queue($course) {
        $batch = $this->batchdao->get_batch_without_marks_for_course($course->id);
        if($batch == null) {
            return BatchService::DEQUEUING_NON_EXISTENT_BATCH;
        }
        
        if($batch->get_status() != Batch::STATUS_QUEUED) {
            return BatchService::DEQUEUING_NON_QUEUED_STATUS;
        }
        
        try {
            $batch->set_status(Batch::STATUS_INITIAL);
            $batch->set_timewhenqueued(null);
            $this->batchdao->store_batch($batch, false);
            return BatchService::DEQUEUING_SUCCESSFUL;
        } catch(Exception $e) {
            return BatchService::DEQUEUING_DATABASE_ERROR;
        }
    }
    
    /**
     * Produces a stdClass containing five arrays.
     * The first, called enrolments, contains Marks for all the students that are enrolled in the course but do not have a Mark in 
     * the Batch.
     * The second, called withdrawals, contains Marks for all the students who have a Mark in the Batch but are not enrolled in the 
     * course.
     * The third, called successes, contains all the Marks that count as having been successfully transferred to Callista.
     * The fourth, called warnings, contains all the Marks that were uploaded to Callista but produced a response message.
     * The fifth, called errors, contains all the Marks that Callista rejected because of some error.
     * By default, if a Mark doesn't fit into the first four categories it is treated as an error.
     * @param type $course Moodle's object for the course whose marks we are dealing with.
     * @param Batch $batch The Batch of Marks we are separating into categories.
     * @return \stdClass A stdClass->enrolments  = array(Marks) of students who are currently enrolled in the specified course in 
     *                                             Moodle but do not have a Mark in the Batch.
     *                             ->withdrawals = array(Marks) of students who have a Mark in the Batch but are not currently
     *                                             enrolled in the specified course in Moodle.
     *                             ->successes   = array(Marks) of the students whose Marks were successfully uploaded to Callista
     *                                             without a response message.
     *                             ->warnings    = array(Marks) of the students whose Marks were uploaded to Callista but generated
     *                                             a response message.
     *                             ->errors      = array(Marks) of the students whose Marks generated an error when Callista was
     *                                             processing them.
     */
    public function separate_marks_into_categories($course, Batch $batch) {
        $late = $this->find_late_enrolments_and_withdrawals($course, $batch);
        $nonwithdrawals = array_udiff($batch->get_marks(), $late->withdrawals, 'Mark::compare_by_person_and_unit');
        
        $markcategories = new stdClass();
        $markcategories->enrolments = $late->enrolments;
        $markcategories->withdrawals = $late->withdrawals;
        $markcategories->successes = array();
        $markcategories->warnings = array();
        $markcategories->errors = array();
        foreach ($nonwithdrawals as $mark) {
            if($mark->response_was_successful()) {
                $markcategories->successes[] = $mark;
            } else if($mark->response_was_warning()) {
                $markcategories->warnings[] = $mark;
            } else if ($mark->get_outcomeloadmessagenumber() == Mark::QGRADE_ERRORNO) {
                $markcategories->warnings[] = $mark;
            } else {
                $markcategories->errors[] = $mark;
            }
        }
        
        return $markcategories;
    }
    
    /**
     * Creates a new batch for a course based on the last batch for the course in the database. The last batch must exist and have
     * been sent to Callista. The marks in the last batch are copied into the new batch including the response data from when they
     * were sent. This means that even though the mark has not been sent in this batch, it is possible to see if and why it failed
     * last time.
     * @param type $course Moodle's object for the course we are dealing with.
     * @param int $basebatchid The id of the batch to base the new batch on.
     * @return int One of BatchService's RESET contsants.
     */
    public function start_a_new_batch_based_on_another_batch($course, $basebatchid) {
        global $USER;
        $basebatch = $this->batchdao->get_batch_with_marks_by_id($basebatchid);
        
        if($basebatch != null) {
            $basestatus = $basebatch->get_status();
            if($basestatus == Batch::STATUS_SUCCESS 
                    || $basestatus == Batch::STATUS_DATA_ERROR 
                    || $basestatus == Batch::STATUS_GENERAL_ERROR) {
                $batch = new Batch();
                $batch->set_courseid($course->id);
                $batch->set_unitcode($course->shortname);
                $batch->set_status(Batch::STATUS_INITIAL);
                $batch->set_loadedbyusername($USER->username);

                $batch->set_teachingperiodalternatecode($basebatch->get_teachingperiodalternatecode());
                $batch->set_academicyear($basebatch->get_academicyear());

                //add the last batch's marks to the new batch. Clear the marks' ids so new records are inserted in the database
                //instead of updating the records for the marks in the last batch.
                $marks = $basebatch->get_marks();
                foreach ($marks as $mark) {
                    $mark->set_id(null);
                }
                $batch->set_marks($marks);

                try {
                    $this->batchdao->store_batch($batch);
                    return BatchService::RESET_SUCCESSFUL;
                } catch (Exception $e) {
                    return BatchService::RESET_DATABASE_ERROR;
                }
            } else {
                return BatchService::RESET_NON_RESET_STATUS;
            }
        } else {
            return BatchService::RESET_NON_EXISTENT_BATCH;
        }
    }
    
    /**
     * Retrieves all the batch records for the given course and all the mark records
     * for those batches.
     * @param int $course Moodle's course object for the course whose batches of marks we want.
     * @return stdClass a stdClass('batchrecords' => batch records
     *                             'markrecords'  => mark records) object.
     */
    public function get_debug_table_data($course) {
        return $this->batchdao->get_debug_table_data($course->id);
    }
}

?>
