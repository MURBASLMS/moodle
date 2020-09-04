<?php

/**
 * The transfer results page is displayed after the resultLoad web service has been called
 * for the batch. It displays a table of the marks and grades similar to the queued page,
 * with colour coding and a column indicating if Callista accepted or rejected the mark.
 *
 * @copyright  2012 onwards University of New England
 * @author anoble2
 */
require_once $CFG->dirroot . '/grade/export/callista/views/ExportCallistaPage.php';
require_once $CFG->dirroot . '/grade/export/callista/views/ErrorTranslator.php';

class TransferResultsPage extends ExportCallistaPage {
    
    //The batch of marks that has been transferred and will be displayed by this page.
    private $batch;
    
    //An array holding the names and descriptions of css classes that are used for colour coding elements of the page.
    //array(stdClass('description' => the description of the colour coding that appears in the key (string),
    //               'cssclass' => the css class that gives the colour coding (string)))
    private $colourkeyentries;
    
    //An array of student numbers for students whose marks were transferred successfully
    private $successfultransfers;
    
    //An array of student numbers for students whose marks raised warnings from the web service
    private $warnedtransfers;
    
    //An array of student numbers for students whose marks raised errors from the web service
    private $failedtransfers;
    
    //An array of student numbers for students who enrolled after the marks were queued.
    private $studentsenrolledafterqueuing;
    
    //An array of student numbers for students who unenrolled after the marks were queued.
    private $studentswithdrawnafterqueueing;
    
    //Used to indicate a particular error message should be displayed on the page. No error message is displayed if this is null.
    private $errorcode;
    
    
    function __construct(Batch $batch,
                         array $successfultransfers = array(),
                         array $warnedtransfers = array(),
                         array $failedtransfers = array(),
                         array $studentsenrolledafterqueuing = array(),
                         array $studentswithdrawnafterqueuing = array(),
                         $errorcode = null) { 
        $this->batch = $batch;
        
        $this->colourkeyentries = array(new stdClass(),
                                        new stdClass(),
                                        new stdClass(),
                                        new stdClass(),
                                        new stdClass());
        $this->colourkeyentries[0]->languagepackkey = 'keysuccessful';
        $this->colourkeyentries[0]->cssclass = 'transferSuccessful';
        $this->colourkeyentries[1]->languagepackkey = 'keywarning';
        $this->colourkeyentries[1]->cssclass = 'transferWarning';
        $this->colourkeyentries[2]->languagepackkey = 'keyfailed';
        $this->colourkeyentries[2]->cssclass = 'transferFailed';
        $this->colourkeyentries[3]->languagepackkey = 'keyenrolment';
        $this->colourkeyentries[3]->cssclass = 'enrolment';
        $this->colourkeyentries[4]->languagepackkey = 'keyunenrolment';
        $this->colourkeyentries[4]->cssclass = 'unenrolment';
        
        $this->successfultransfers = $successfultransfers;
        $this->warnedtransfers = $warnedtransfers;
        $this->failedtransfers = $failedtransfers;
        $this->studentsenrolledafterqueuing = $studentsenrolledafterqueuing;
        $this->studentswithdrawnafterqueueing = $studentswithdrawnafterqueuing;
        
        $this->errorcode = $errorcode;
    }
    
    /**
     * Creates the html that forms the content of the page.
     * @global type $OUTPUT The Moodle core_renderer.
     * @return string The html code for the page content.
     */
    public function get_page_html() {
        global $OUTPUT, $CFG;
        
        $html = '';
        $html .= '<link rel="stylesheet" type="text/css" href="' . $CFG->wwwroot . '/grade/export/callista/views/MarksTableStyles.css">';
        $html .= html_writer::script('', $CFG->wwwroot . '/grade/export/callista/views/js/TransferResultsPage.js');
        $html .= html_writer::script("
            YUI().use('node', 'event', 'stylesheet', 'async-queue', function(Y) {
                Y.on('domready', function() {
                    transferResultsPageJs.initialise(Y);
                });
            });
            ");
        
        $html .= $OUTPUT->box_start() . "\n";
        $html .= $this->get_message() . "\n";
        $html .= $this->get_marks_table_heading($this->colourkeyentries) . "\n";
        $html .= html_writer::empty_tag('br'); // spacing
        $html .= $this->get_buttons() . "\n";
        $html .= $this->get_marks_table() . "\n";
        $html .= $this->get_buttons() . "\n";
        $html .= $OUTPUT->box_end() . "\n";
        
        $html .= '<form id="newBatchForm" name="newBatchForm" action="newBatchFromLastBatch.php" method="post">' . "\n";
        $html .= '<input type="hidden" name="id" value="' . $this->batch->get_courseid() . '"/>' . "\n";
        $html .= '<input type="hidden" name="basebatch" value="' . $this->batch->get_id() . '"/>' . "\n";
        $html .= '</form>' . "\n";
        
        return $html;
    }
    
    /**
     * Builds the message section that appears above the table of marks. Displays information about how many marks were successfully
     * sent to Callista, how many generated warnings and how many caused errors.
     * @return string The html code for the message section of the page.
     */
    private function get_message() {
        global $OUTPUT;
        
        $successful = count($this->successfultransfers);
        $warnings = count($this->warnedtransfers);
        $errors = count($this->failedtransfers) + count($this->studentswithdrawnafterqueueing);
        
        $html = '';
        $html .= $OUTPUT->container_start(null, 'pageMessage');
        
        $errormessage = null;
        switch($this->errorcode) {
            case BatchService::RESET_DATABASE_ERROR:
                $errormessage = get_string('transferpageresetdatabaseerror', 'gradeexport_callista');
                break;
            case BatchService::RESET_NON_RESET_STATUS:
                $errormessage = get_string('transferpageresetstatuserror', 'gradeexport_callista');
                break;
            case BatchService::RESET_NON_EXISTENT_BATCH:
                $errormessage = get_string('transferpagenonexistentbatcherror', 'gradeexport_callista');
                break;
            default:
        }
        if(!empty($errormessage)) {
            $html .= '<p id="errorMessage" class="error">' . $errormessage . "</p>\n";
        } else {
            $html .= '<p id="errorMessage" class="error" style="display: none;"></p>' . "\n";
        }
        
        $html .= '<p>';
        if($errors == 0 && $warnings == 0) {
            if($successful == 0) {
                $html .= get_string('markstransferrednone', 'gradeexport_callista', $this->batch->get_unitcode());
            } else if($successful == 1) {
                $html .= get_string('markstransferrednoerrorssingle', 'gradeexport_callista', $this->batch->get_unitcode());
            } else {
                $html .= get_string('markstransferrednoerrorsmultiple', 'gradeexport_callista', array('successful' => $successful, 'unitname' => $this->batch->get_unitcode()));
            }
        } else {
            if($successful == 0) {
                $html .= get_string('markstransferrednosuccesses', 'gradeexport_callista');
            } else if($successful == 1) {
                $html .= get_string('markstransferredonesuccess', 'gradeexport_callista');
            } else {
                $html .= get_string('markstransferredmultiplesuccesses', 'gradeexport_callista', $successful);
            }
            $html .= "<br/>";
            if($warnings == 0) {
                $html .= get_string('markstransferrednowarnings', 'gradeexport_callista');
            } else if($warnings == 1) {
                $html .= get_string('markstransferredonewarning', 'gradeexport_callista');
            } else {
                $html .= get_string('markstransferredmultiplewarnings', 'gradeexport_callista', $warnings);
            }
            $html .= "<br/>";
            if($errors == 0) {
                $html .= get_string('markstransferrednoerrors', 'gradeexport_callista');
            } else if($errors == 1) {
                $html .= get_string('markstransferredoneerror', 'gradeexport_callista');
            } else {
                $html .= get_string('markstransferredmultipleerrors', 'gradeexport_callista', $errors);
            }
        }
        $html .= "</p>\n";
        
        $html .= '<a id="instructionHeading" href="">' . get_string('instructionheading', 'gradeexport_callista') . ' ' 
                . $OUTPUT->pix_icon('t/collapsed', get_string('instructionexpandalttext', 'gradeexport_callista'))
                . $OUTPUT->pix_icon('t/expanded',
                                    get_string('instructioncollapsealttext', 'gradeexport_callista'), 
                                    'moodle', 
                                    array('style' => 'display: none;')) 
                . "</a>\n";
        $html .= '<div id="instructions" style="display: none;">' . "\n";
        $html .= '<p>' . str_replace('\n', "</p>\n<p>", get_string('transferpageinstructions', 'gradeexport_callista')) . "</p>\n";
        $html .= '</div>' . "\n";
        
        $html .= $OUTPUT->container_end();
        return $html;
    }
    
    /**
     * Builds the html for the table of marks.
     * Rows for students whose marks were transferred without error get the css class 'transferSuccessful'.
     * Rows for students whose marks generated a warning when transferred get the css class 'transferWarned'.
     * Rows for students whose marks generated an error when tranferred get the css class 'transferFailed'.
     * Rows for students who enrolled after the marks were queued get the css class 'enrolment'.
     * Rows for students who withdrew after the marks were queued get the css class 'unenrolment'.
     * @global type $OUTPUT Moodle's core_renderer object.
     * @return string The html code for the table of marks.
     */
    private function get_marks_table() {
        global $OUTPUT;
        
        $thereareerrors = count($this->failedtransfers) > 0 ||
                          count($this->warnedtransfers) > 0 ||
                          count($this->studentswithdrawnafterqueueing) > 0;
        
        $table = new html_table();
        $table->id = 'markstable';
        $table->head = array('<a class="sortingLink" id="c0" href="">' . get_string('studentnumber', 'gradeexport_callista') . '</a>',
                             '<a class="sortingLink" id="c1" href="">' . get_string('firstnamecolumn', 'gradeexport_callista') . '</a>',
                             '<a class="sortingLink" id="c2" href="">' . get_string('lastname') . '</a>',
                             '<a class="sortingLink" id="c3" href="">' . get_string('unitcode', 'gradeexport_callista') . '</a>',
                             '<a class="sortingLink" id="c4" href="">' . get_string('offering', 'gradeexport_callista') . '</a>',
                             '<a class="sortingLink" id="c5" href="">' . get_string('mark', 'gradeexport_callista') .  '</a>',
                             '<a class="sortingLink" id="c6" href="">' . get_string('grade') . '</a>',
                             get_string('gradeglossary', 'gradeexport_callista'));
        if($thereareerrors) {
            $table->head[] = '<a class="sortingLink" id="c8" href="">' . get_string('errormessagecolumn', 'gradeexport_callista') . '</a>';
        }
        
        foreach ($this->warnedtransfers as $mark) {
            $row = new html_table_row();
            $row->cells[] = new html_table_cell($mark->get_studentnumber());
            $row->cells[] = new html_table_cell($mark->get_studentfirstname());
            $row->cells[] = new html_table_cell($mark->get_studentsurname());
            $row->cells[] = new html_table_cell($mark->get_unitcode());
            $row->cells[] = new html_table_cell($mark->get_offering());
            $row->cells[] = new html_table_cell($mark->get_mark());
            $grade = $mark->get_grade();
            $row->cells[] = new html_table_cell($grade);
            if(!empty($grade)) {
                $row->cells[] = new html_table_cell($OUTPUT->help_icon('gradeglossary', 'gradeexport_callista'));
            } else {
                $row->cells[] = new html_table_cell();
            }
            
            $errormessage = ErrorTranslator::message_for_error($mark->get_outcomeloadmessagenumber(), $mark->get_outcomeloadmessage());
            $row->cells[] = new html_table_cell($errormessage);
            
            $row->attributes['class'] = 'transferWarning';
            $table->data[] = $row;
        }
        
        foreach ($this->failedtransfers as $mark) {
            $row = new html_table_row();
            $row->cells[] = new html_table_cell($mark->get_studentnumber());
            $row->cells[] = new html_table_cell($mark->get_studentfirstname());
            $row->cells[] = new html_table_cell($mark->get_studentsurname());
            $row->cells[] = new html_table_cell($mark->get_unitcode());
            $row->cells[] = new html_table_cell($mark->get_offering());
            $row->cells[] = new html_table_cell($mark->get_mark());
            $grade = $mark->get_grade();
            $row->cells[] = new html_table_cell($grade);
            if(!empty($grade)) {
                $row->cells[] = new html_table_cell($OUTPUT->help_icon('gradeglossary', 'gradeexport_callista'));
            } else {
                $row->cells[] = new html_table_cell();
            }
            
            $errormessage = ErrorTranslator::message_for_error($mark->get_outcomeloadmessagenumber(), $mark->get_outcomeloadmessage());
            $row->cells[] = new html_table_cell($errormessage);
            
            $row->attributes['class'] = 'transferFailed';
            $table->data[] = $row;
        }
        
        foreach ($this->studentsenrolledafterqueuing as $mark) {
            $row = new html_table_row();
            $row->cells[] = new html_table_cell($mark->get_studentnumber());
            $row->cells[] = new html_table_cell($mark->get_studentfirstname());
            $row->cells[] = new html_table_cell($mark->get_studentsurname());
            $row->cells[] = new html_table_cell($mark->get_unitcode());
            $row->cells[] = new html_table_cell($mark->get_offering());
            $row->cells[] = new html_table_cell($mark->get_mark());
            $grade = $mark->get_grade();
            $row->cells[] = new html_table_cell($grade);
            if(!empty($grade)) {
                $row->cells[] = new html_table_cell($OUTPUT->help_icon('gradeglossary', 'gradeexport_callista'));
            } else {
                $row->cells[] = new html_table_cell();
            }
            if($thereareerrors) {
                $row->cells[] = new html_table_cell();
            }
            
            $row->attributes['class'] = 'enrolment';
            $table->data[] = $row;
        }
        
        foreach ($this->studentswithdrawnafterqueueing as $mark) {
            $row = new html_table_row();
            $row->cells[] = new html_table_cell($mark->get_studentnumber());
            $row->cells[] = new html_table_cell($mark->get_studentfirstname());
            $row->cells[] = new html_table_cell($mark->get_studentsurname());
            $row->cells[] = new html_table_cell($mark->get_unitcode());
            $row->cells[] = new html_table_cell($mark->get_offering());
            $row->cells[] = new html_table_cell($mark->get_mark());
            $grade = $mark->get_grade();
            $row->cells[] = new html_table_cell($grade);
            if(!empty($grade)) {
                $row->cells[] = new html_table_cell($OUTPUT->help_icon('gradeglossary', 'gradeexport_callista'));
            } else {
                $row->cells[] = new html_table_cell();
            }
            
            $errormessage = ErrorTranslator::message_for_error($mark->get_outcomeloadmessagenumber(), $mark->get_outcomeloadmessage());
            $row->cells[] = new html_table_cell($errormessage);
            
            $row->attributes['class'] = 'unenrolment';
            $table->data[] = $row;
        }

        foreach ($this->successfultransfers as $mark) {
            $row = new html_table_row();
            $row->cells[] = new html_table_cell($mark->get_studentnumber());
            $row->cells[] = new html_table_cell($mark->get_studentfirstname());
            $row->cells[] = new html_table_cell($mark->get_studentsurname());
            $row->cells[] = new html_table_cell($mark->get_unitcode());
            $row->cells[] = new html_table_cell($mark->get_offering());
            $row->cells[] = new html_table_cell($mark->get_mark());
            $grade = $mark->get_grade();
            $row->cells[] = new html_table_cell($grade);
            if(!empty($grade)) {
                $row->cells[] = new html_table_cell($OUTPUT->help_icon('gradeglossary', 'gradeexport_callista'));
            } else {
                $row->cells[] = new html_table_cell();
            }
            if($thereareerrors) {
                $row->cells[] = new html_table_cell();
            }

            $row->attributes['class'] = 'transferSuccessful';
            $table->data[] = $row;
        }

        return html_writer::table($table);
    }
    
    /**
     * Creates the html for the buttons at the bottom of the page.
     * @global type $OUTPUT Moodle's core_renderer object.
     * @return string The html of the buttons for the bottom of the page.
     */
    private function get_buttons() {
        global $CFG;
        $courseid = $this->batch->get_courseid();
        
        $html = '';
        
        $gradebookurl = $CFG->wwwroot . '/grade/report/grader/index.php?id=' . $courseid;
        $gradebookstring = get_string('backto', '', get_string('gradebook', 'grades'));
        $html .= '<button class="gradebookButton" type="button" onClick="window.location=\'' . $gradebookurl . '\';">' . $gradebookstring . '</button>' . "\n";
        
        $courseurl = $CFG->wwwroot . '/course/view.php?id=' . $courseid;
        $coursestring = get_string('backto', '', $this->batch->get_unitcode());
        $html .= '<button class="courseButton" type="button" onClick="window.location=\'' . $courseurl . '\';">' . $coursestring . '</button>' . "\n";
        
        $archiveurl = $CFG->wwwroot . '/grade/export/callista/batchArchive.php?id=' . $courseid;
        $html .= '<button class="archiveButton" type="button" onClick="window.location=\'' . $archiveurl . '\';">' . get_string('archive', 'gradeexport_callista') . '</button>' . "\n";
        
        $html .= '<button class="newBatchFromThisBatch" type="button">' . get_string('transferpagestartnewbatch', 'gradeexport_callista') . '</button>' . "\n";
        
        return $html;
    }
}

?>
