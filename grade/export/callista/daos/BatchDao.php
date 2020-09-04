<?php

/**
 * BatchDao reads and writes Batch and Mark objects to and from Moodle's database.
 *
 * @copyright  2012 onwards University of New England
 * @author anoble2
 */
require_once $CFG->dirroot . '/grade/export/callista/model/Batch.php';
require_once $CFG->dirroot . '/grade/export/callista/model/Mark.php';

class BatchDao {
    
    /**
     * Gets data on the most recent batch stored for a given Moodle course id. This includes retrieving all the marks that are part 
     * of the batch.
     * @param int $courseid The Moodle course id to find the most recent batch of marks for.
     * @return Batch If a batch is found for the course id.
     *               If a batch is not found, null.
     */
    public function get_batch_with_marks_for_course($courseid) {
        $batch = $this->get_batch_without_marks_for_course($courseid);
        if($batch != null) {
            $batch->set_marks($this->get_marks_in_batch($batch));
        }
        return $batch;
    }
    
    /**
     * Gets a specified batch and its marks from the database.
     * @global moodle_database $DB Moodle database object.
     * @param int $batchid The id number of the batch to retrieve.
     * @return Batch The batch with the given id, or null if it does not exist.
     */
    public function get_batch_with_marks_by_id($batchid) {
        global $DB;
        
        $batch = null;
        $columns = 'id, course_id, unit_code, teach_pd_alt_code, acad_year_alt_code, status, loaded_by_username, time_when_saved, ' .
                   'time_when_queued, generated_xml, time_when_sent, results_xml, batch_loaded_flag, batch_message_text, ' .
                   'general_error_message';
        $batchdata = $DB->get_records('callista_exp_batches', array('id' => $batchid), null, $columns);
        $batchdata = current($batchdata);
        if(!empty($batchdata)) {
            $batch = new Batch($batchdata->id,
                               $batchdata->course_id,
                               $batchdata->unit_code,
                               $batchdata->teach_pd_alt_code,
                               $batchdata->acad_year_alt_code,
                               $batchdata->status,
                               $batchdata->loaded_by_username,
                               $batchdata->time_when_saved,
                               $batchdata->time_when_queued,
                               $batchdata->generated_xml,
                               $batchdata->time_when_sent,
                               $batchdata->results_xml,
                               $batchdata->batch_loaded_flag,
                               $batchdata->batch_message_text,
                               $batchdata->general_error_message);
            $batch->set_marks($this->get_marks_in_batch($batch));
        }
        return $batch;
    }
        
    /**
     * Gets data on the most recent batch stored for a given Moodle course id. This does not include any marks that are part of the
     * batch.
     * @global moodle_database $DB Moodle database object.
     * @param int $courseid The Moodle course id to find the most recent batch of marks for.
     * @return Batch If a batch is found for the course id.
     *               If a batch is not found, null.
     */
    public function get_batch_without_marks_for_course($courseid) {
        global $DB;
        
        $batch = null;
        $columns = 'id, course_id, unit_code, teach_pd_alt_code, acad_year_alt_code, status, loaded_by_username, time_when_saved, ' .
                   'time_when_queued, generated_xml, time_when_sent, results_xml, batch_loaded_flag, batch_message_text, ' .
                   'general_error_message';
        $batchdata = $DB->get_records('callista_exp_batches', array('course_id' => $courseid), 'time_when_saved DESC', $columns, 0, 1);
        $batchdata = current($batchdata);
        if(!empty($batchdata)) {
            $batch = new Batch($batchdata->id,
                               $batchdata->course_id,
                               $batchdata->unit_code,
                               $batchdata->teach_pd_alt_code,
                               $batchdata->acad_year_alt_code,
                               $batchdata->status,
                               $batchdata->loaded_by_username,
                               $batchdata->time_when_saved,
                               $batchdata->time_when_queued,
                               $batchdata->generated_xml,
                               $batchdata->time_when_sent,
                               $batchdata->results_xml,
                               $batchdata->batch_loaded_flag,
                               $batchdata->batch_message_text,
                               $batchdata->general_error_message);
        }
        return $batch;
    }
    
    /**
     * Gets all the batches (without their marks) for a given course from the database, ordered by most recently saved first.
     * @global moodle_database $DB Moodle database object.
     * @param int $courseid The Moodle id for the course whose batches of marks we want.
     * @return array(Batch) An array of Batch objects of all the batches in the database for this course.
     */
    public function get_all_batches_without_marks_for_course($courseid) {
        global $DB;
        
        $batches = array();
        $columns = 'id, course_id, unit_code, teach_pd_alt_code, acad_year_alt_code, status, loaded_by_username, time_when_saved, ' .
                   'time_when_queued, generated_xml, time_when_sent, results_xml, batch_loaded_flag, batch_message_text, ' .
                   'general_error_message';
        $batchdata = $DB->get_records('callista_exp_batches', array('course_id' => $courseid), 'time_when_saved DESC', $columns);
        foreach ($batchdata as $batchrecord) {
            $batches[] = new Batch($batchrecord->id,
                                   $batchrecord->course_id,
                                   $batchrecord->unit_code,
                                   $batchrecord->teach_pd_alt_code,
                                   $batchrecord->acad_year_alt_code,
                                   $batchrecord->status,
                                   $batchrecord->loaded_by_username,
                                   $batchrecord->time_when_saved,
                                   $batchrecord->time_when_queued,
                                   $batchrecord->generated_xml,
                                   $batchrecord->time_when_sent,
                                   $batchrecord->results_xml,
                                   $batchrecord->batch_loaded_flag,
                                   $batchrecord->batch_message_text,
                                   $batchrecord->general_error_message);
        }
        return $batches;
    }
    
    /**
     * Retrieves all the marks that a given batch contains in the database.
     * @global moodle_database $DB Moodle database object.
     * @param Batch $batch The batch whose marks to get.
     * @return array An array of Marks objects.
     */
    public function get_marks_in_batch(Batch $batch) {
        global $DB;
        
        $columns = 'id, batch_id, person_id, person_first_name, person_surname, calculated_mark, mark_override, derived_grade, ' . 
                   'grade_override, outcome_id, course_cd, unit_cd, offering, outcome_loaded_flag, outcome_load_message_number, ' .
                   'outcome_load_message, mark_override_manual, grade_override_manual';
        $marksdata = $DB->get_records('callista_exp_marks', array('batch_id' => $batch->get_id()), 'person_surname', $columns);
        $marks = array();
        foreach ($marksdata as $markdata) {
            $marks[] = new Mark($markdata->id,
                                $markdata->batch_id,
                                $markdata->person_id,
                                $markdata->person_first_name,
                                $markdata->person_surname,
                                $markdata->calculated_mark,
                                $markdata->mark_override,
                                $markdata->derived_grade,
                                $markdata->grade_override,
                                $markdata->outcome_id,
                                $markdata->course_cd,
                                $markdata->unit_cd,
                                $markdata->offering,
                                $markdata->outcome_loaded_flag,
                                $markdata->outcome_load_message_number,
                                $markdata->outcome_load_message,
                                $markdata->mark_override_manual,
                                $markdata->grade_override_manual);
        }
        return $marks;
    }
    
    /**
     * Writes a batch object to the MDL_CALLISTA_EXP_BATCHES table, and optionally the batch's marks to the MDL_CALLISTA_EXP_MARKS
     * table. If the batch's id is null, this performs an insert operation, otherwise this does an update. If an insert is performed,
     * the id generated by the database is stored in the batch's id field.
     * If the marks are to be written to the database, any marks already in the database that are not in the batch (matching by id)
     * will be deleted. Other marks will be stored using the store_mark() method.
     * @global moodle_database $DB $DB Moodle's database object.
     * @param Batch $batch The batch to write to the database.
     * @param boolean $writemarks Set to true to also write the batch's marks to the database, or false to omit them. Default is true.
     */
    public function store_batch(Batch $batch, $writemarks = true) {
        global $DB;
        
        //Store any ids generated during the transaction and apply them to the in-memory object after the transaction succeeds. 
        //Then the in-memory objects don't have to be corrected if the transaction fails half way through.
        $newbatchid = null;
        $newmarkids = array();
        $transaction = $DB->start_delegated_transaction();
        
        try {
            //The batch id should be null only when we are queuing a new batch. The database creates the batch id from a primary-key
            //sequence.
            $params = array('course_id'             => $batch->get_courseid(),
                            'unit_code'             => $batch->get_unitcode(),
                            'teach_pd_alt_code'     => $batch->get_teachingperiodalternatecode(),
                            'acad_year_alt_code'    => $batch->get_academicyear(),
                            'status'                => $batch->get_status(),
                            'loaded_by_username'    => $batch->get_loadedbyusername(),
                            'time_when_saved'       => time(),
                            'time_when_queued'      => $batch->get_timewhenqueued(),
                            'generated_xml'         => $batch->get_generatedxml(),
                            'time_when_sent'        => $batch->get_timewhensent(),
                            'results_xml'           => $batch->get_resultsxml(),
                            'batch_loaded_flag'     => $batch->get_batchloadedflag(),
                            'batch_message_text'    => $batch->get_batchmessagetext(),
                            'general_error_message' => $batch->get_generalerrormessage()
                    );
                    
            if($batch->get_id() == null) {
                $newbatchid = $DB->insert_record('callista_exp_batches', $params);
            } else {
                //Update the already-existing record.
                $params['id'] = $batch->get_id();
                $DB->update_record('callista_exp_batches', $params);
                $newbatchid = $batch->get_id();
            }
            if($writemarks) {
                //Delete any marks in the database that are not part of the batch. Then insert or update all the marks in the batch.
                $existingstoredmarks = $this->get_marks_in_batch($batch);
                
                $existingmarks = array();
                foreach ($existingstoredmarks as $existingstoredmark) {
                    $existingmarks[$existingstoredmark->get_studentnumber()] = $existingstoredmark->get_markoverride();
                    $deletemark = true;
                    foreach ($batch->get_marks() as $batchmark) {
                        // Convert null mark override back to being stored as no mark.
                        if (!empty($existingmarks[$batchmark->get_studentnumber()]) && $existingmarks[$batchmark->get_studentnumber()] == Mark::NO_MARK_STRING && $batchmark->get_markoverride(false) == null) {
                            $batchmark->set_markoverride(Mark::NO_MARK);
                        }
                        if($existingstoredmark->is_for_same_person_and_unit($batchmark)) {
                            $deletemark = false;
                            $batchmark->set_id($existingstoredmark->get_id());
                            break;
                        }
                    }
                    unset($batchmark);
                    if($deletemark) {
                        $DB->delete_records('callista_exp_marks', array('id' => $existingstoredmark->get_id()));
                    }
                }
                unset($existingstoredmark);
                
                foreach ($batch->get_marks() as $markindex => $mark) {
                    $mark->set_batchid($newbatchid);
                    $newmarkids[$markindex] = $this->store_mark($mark);
                }
                unset($markindex);
                unset($mark);
            }
            $transaction->allow_commit();
            
            $batch->set_id($newbatchid);
            if($writemarks) {
                foreach ($batch->get_marks() as $markindex => $mark) {
                    // Convert null mark override back to being stored as no mark.
                    if (!empty($existingmarks[$mark->get_studentnumber()]) && $existingmarks[$mark->get_studentnumber()] == Mark::NO_MARK_STRING && $mark->get_markoverride(false) == null) {
                         $mark->set_markoverride(Mark::NO_MARK);
                    }
                    if($newmarkids[$markindex] != null) {
                        $mark->set_id($newmarkids[$markindex]);
                    }
                }
            }
            
        } catch (Exception $e) {
            $transaction->rollback($e);
        }
    }
    
    /**
     * Writes a Mark to the MDL_CALLISTA_EXP_MARKS table. If the mark id is null, the mark will be inserted into the database and
     * the table's primary-key sequence will generate the mark id. If the mark id is not null, an update is performed on the record
     * with the matching id. Exceptions generated by the database here will be caught in the store_batch() method and will caise the
     * transaction to roll back.
     * @global moodle_database $DB Moodle's database object.
     * @param Mark $mark The mark to store.
     * @return int The id field generated by the database if the mark was inserted as a new record, or null otherwise.
     */
    private function store_mark(Mark $mark) {
        global $DB;
        
        $mark->calculate_automatic_overrides();
        // If the overriding mark is the different to the calculated mark, check if manually or automatically overriden.
        if ($mark->get_markoverride(false) == '' || ($mark->get_markoverride() != '' && $mark->get_calculatedmark() != $mark->get_markoverride())) {
            // If the overriding mark is the same as rounded, set as automatically overriden.
            if ($mark->get_roundedmark() == $mark->get_markoverride()) {
                if (!$mark->get_markoverrideforced()) {
                    $mark->set_markoverridemanual(false);
                }
            } else {
                if (!$mark->get_markoverrideforced()) {
                    $mark->set_markoverridemanual(true);
                }
            }
        }

        $mark->calculate_automatic_overrides();
        // If the overriding grade is the different to the derived grade, check if manually or automatically overriden.
        if ($mark->get_gradeoverride() != '' && $mark->get_derivedgrade() != $mark->get_gradeoverride()) {
            // If the overriding grade is the same as rounded, set as automatically overriden.
            if (!$mark->get_markoverridemanual() && $mark->get_roundedgrade() == $mark->get_gradeoverride()) {
                if (!$mark->get_gradeoverrideforced()) {
                    $mark->set_gradeoverridemanual(false);
                }
            } else {
                if (!$mark->get_gradeoverrideforced()) {
                    $mark->set_gradeoverridemanual(true);
                }
            }
        }

        $newmarkid = null;
        $params = array(//id is included below if the record is being updated
                        'batch_id'                      => $mark->get_batchid(),
                        'person_id'                     => $mark->get_studentnumber(),
                        'person_first_name'             => $mark->get_studentfirstname(),
                        'person_surname'                => $mark->get_studentsurname(),
                        'calculated_mark'               => $mark->get_calculatedmark(),
                        'mark_override'                 => $mark->get_markoverride(false),
                        'derived_grade'                 => $mark->get_derivedgrade(),
                        'grade_override'                => $mark->get_gradeoverride(),
                        'outcome_id'                    => $mark->get_outcomeid(),
                        'course_cd'                     => $mark->get_coursecode(),
                        'unit_cd'                       => $mark->get_unitcode(),
                        'offering'                      => $mark->get_offering(),
                        'outcome_loaded_flag'           => $mark->get_outcomeloadedflag(),
                        'outcome_load_message_number'   => $mark->get_outcomeloadmessagenumber(),
                        'outcome_load_message'          => $mark->get_outcomeloadmessage(),
                        'mark_override_manual'          => $mark->get_markoverridemanual(),
                        'grade_override_manual'         => $mark->get_gradeoverridemanual()
        );
        if($mark->get_id() == null) {
            $newmarkid = $DB->insert_record('callista_exp_marks', $params);
        } else {
            $params['id'] = $mark->get_id();
            $DB->update_record('callista_exp_marks', $params);
        }
        return $newmarkid;
    }
    
    /**
     * Counts how many mark records in the database for the given batch have either no mark data or no grade data stored.
     * @global moodle_database $DB Moodle's database object.
     * @param Batch $batch The batch to check in the database.
     * @return array An array('numberwithoutmarks'  => the number of the batch's mark records without mark data,
     *                        'numberwithoutgrades' => the number of the batch's grade records without grade data)
     */
    public function check_batch_for_empty_marks_and_grades(Batch $batch) {
        global $DB;
        
        $numberwithoutmarks = $DB->count_records('callista_exp_marks', array('batch_id' => $batch->get_id(),
                                                                             'calculated_mark' => null,
                                                                             'mark_override' => null));
        $numberwithoutgrades = $DB->count_records('callista_exp_marks', array('batch_id' => $batch->get_id(),
                                                                              'derived_grade' => null,
                                                                              'grade_override' => null));
        return array('numberwithoutmarks' => $numberwithoutmarks,
                     'numberwithoutgrades' => $numberwithoutgrades);
    }
    
    /**
     * Get an array of all the Moodle course ids of batches that are queued but haven't been uploaded to Callista yet. Change their
     * status to being sent.
     * @global moodle_database $DB Moodle's database object.
     * @return array(string) An array of Moodle course ids. 
     */
    public function get_course_ids_of_queued_batches() {
        global $DB;
        
        //Get all the course records that have not been processed by Callista (status = 2).
        $DB->set_field('callista_exp_batches', 'status', Batch::STATUS_SENDING, array('status' => Batch::STATUS_QUEUED));
        
        $ids = $DB->get_fieldset_select('callista_exp_batches', 'course_id', 'status = ' . Batch::STATUS_SENDING);
        return $ids;
    }
    
    /**
     * If after attempting to send all the batches there are any that still have a status of being sent, something has gone wrong.
     * This method changes the status of these batches to that of a general error and gives them an appropriate error message.
     * @global moodle_database $DB 
     */
    public function clean_up_batches_still_considered_sending() {
        global $DB;
        
        $sql = "UPDATE {callista_exp_batches} \n"
             . "SET (status, general_error_message) = (?, ?) \n"
             . "WHERE status = ?";
        $parameters = array(Batch::STATUS_GENERAL_ERROR,
                            get_string('errorstillsending', 'gradeexport_callista'),
                            Batch::STATUS_SENDING);
        
        $DB->execute($sql, $parameters);
    }
    
    /**
     * Retrieves all the batch records for the given course and all the mark records
     * for those batches.
     * @global moodle_database $DB Moodle database object.
     * @param int $courseid The Moodle id for the course whose batches of marks we want.
     * @return stdClass a stdClass('batchrecords' => batch records
     *                             'markrecords'  => mark records) object.
     */
    public function get_debug_table_data($courseid) {
        global $DB;
        
        $DB->execute("SET TIME ZONE 'Australia/Perth'"); // Display in Perth time
        $sql = "SELECT *, to_timestamp(time_when_saved) as time_when_saved,\n"
             . "to_timestamp(time_when_queued) as time_when_queued, to_timestamp(time_when_sent) as time_when_sent \n"
             . "FROM {callista_exp_batches} \n"
             . "WHERE course_id = ? \n"
             . "ORDER BY id ASC";
        $batchrecords = $DB->get_records_sql($sql, array($courseid));
        $sql = "SELECT marks.* \n"
             . "FROM {callista_exp_marks} marks \n"
             . "JOIN {callista_exp_batches} batches \n"
             . "  ON marks.batch_id = batches.id \n"
             . "  AND batches.course_id = ? \n"
             . "ORDER BY marks.batch_id DESC, marks.id ASC";
        $markrecords = $DB->get_records_sql($sql, array($courseid));
        $DB->execute("SET TIME ZONE 'Australia/South'"); // Revert in South Australian time
        
        $tabledata = new stdClass();
        $tabledata->batchrecords = $batchrecords;
        $tabledata->markrecords = $markrecords;
        return $tabledata;
    }
}
