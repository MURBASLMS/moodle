<?php

/**
 * The queued page is displayed after a unit coordinator has clicked 'send' on the override page and the batch of marks is successfully
 * stored in the database, but before the resultLoad web service has been executed for the batch. It displays a table of the marks
 * and grades for students in the course that will be sent to the web service, including any overrides specified on the override page.
 *
 * @copyright  2012 onwards University of New England
 * @author anoble2
 */
require_once $CFG->dirroot . '/grade/export/callista/views/ExportCallistaPage.php';

class QueuedPage extends ExportCallistaPage {
    //The batch of marks that has been queued and will be displayed by this page.
    private $batch;
    
    //An array holding the names and descriptions of css classes that are used for colour coding elements of the page.
    //array(stdClass('description' => the description of the colour coding that appears in the key (string),
    //               'cssclass' => the css class that gives the colour coding (string)))
    private $colourkeyentries;
    
    //An array of student numbers for students who enrolled after the marks were queued.
    private $studentsenrolledafterqueuing;
    
    //An array of student numbers for students who withdrew after the marks were queued.
    private $studentswithdrawnafterqueueing;
    
    //Used to indicate a particular error message should be displayed on the page. No error message is displayed if this is null.
    private $errorcode;
    
    function __construct(Batch $batch,
                         array $studentsenrolledafterqueuing = array(),
                         array $studentswithdrawnafterqueuing = array(),
                         $errorcode = null) {
        $this->batch = $batch;
        
        $this->colourkeyentries = array(new stdClass(),
                                        new stdClass(),
                                        new stdClass());
        $this->colourkeyentries[0]->languagepackkey = 'keyoverridden';
        $this->colourkeyentries[0]->cssclass = 'overridden';
        $this->colourkeyentries[1]->languagepackkey = 'keyenrolment';
        $this->colourkeyentries[1]->cssclass = 'enrolment';
        $this->colourkeyentries[2]->languagepackkey = 'keyunenrolment';
        $this->colourkeyentries[2]->cssclass = 'unenrolment';
        
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
        
        //Get the styles used on the page for colour coding and when sorting the marks table.
        $html .= '<link rel="stylesheet" type="text/css" href="' . $CFG->wwwroot . '/grade/export/callista/views/MarksTableStyles.css">';
        
        //Get the javascript code for sorting the marks table and validating mark and grade overrides.
        $html .= html_writer::script('', $CFG->wwwroot . '/grade/export/callista/views/js/QueuedPage.js');
        $html .= html_writer::script("
            YUI().use('node', 'event', 'stylesheet', 'async-queue', function(Y) {
                Y.on('domready', function() {
                    queuedPageJs.initialise(Y);
                });
            });
            ");
        
        $html .= '<form id="dequeueBatch" name="dequeueBatch">' . "\n";
        $html .= '<input type="hidden" name="id" value="' . $this->batch->get_courseid() . '"/>' . "\n";
        $html .= $OUTPUT->box_start() . "\n";
        $html .= $this->get_message() . "\n";
        $html .= $this->get_marks_table_heading($this->colourkeyentries) . "\n";
        $html .= html_writer::empty_tag('br'); // spacing
        $html .= $this->get_buttons() . "\n";
        $html .= $this->get_marks_table() . "\n";
        $html .= $this->get_buttons() . "\n";
        $html .= $OUTPUT->box_end() . "\n";
        $html .= '</form>' . "\n";
        
        //temporary
        $html .= '<form id="emulateForm" name="emulateForm" action="debugEmulateCron.php" method="post">' . "\n";
        $html .= '<input type="hidden" name="id" value="' . $this->batch->get_courseid() . '"/>' . "\n";
        $html .= '</form>' . "\n";
        
        return $html;
}
    
    /**
     * Builds the html that appears in the message section above the table of marks.
     * @global type $OUTPUT Moodle's core_renderer.
     * @return String The html for the message section.
     */
    private function get_message() {
        global $OUTPUT;
        
        $html = '';
        $html .= $OUTPUT->container_start(null, 'pageMessage');
        
        $errormessage = null;
        switch($this->errorcode) {
            case BatchService::DEQUEUING_DATABASE_ERROR:
                $errormessage = get_string('queuedpagedequeuedatabaseerror', 'gradeexport_callista');
                break;
            case BatchService::DEQUEUING_NON_QUEUED_STATUS:
                $errormessage = get_string('queuedpagedequeuestatuserror', 'gradeexport_callista');
                break;
            case BatchService::DEQUEUING_NON_EXISTENT_BATCH:
                $errormessage = get_string('queuedpagenonexistentbatcherror', 'gradeexport_callista');
                break;
            default:
        }
        if(!empty($errormessage)) {
            $html .= '<p id="errorMessage" class="error">' . $errormessage . "</p>\n";
        } else {
            $html .= '<p id="errorMessage" class="error" style="display: none;"></p>' . "\n";
        }
        
        $html .= '<a id="instructionHeading" href="">' . get_string('instructionheading', 'gradeexport_callista') . ' ' 
                . $OUTPUT->pix_icon('t/collapsed', get_string('instructionexpandalttext', 'gradeexport_callista'))
                . $OUTPUT->pix_icon('t/expanded',
                                    get_string('instructionexpandalttext', 'gradeexport_callista'), 
                                    'moodle', 
                                    array('style' => 'display: none;')) 
                . "</a>\n";
        $html .= '<div id="instructions" style="display: none;">' . "\n";
        $html .= '<p>' . str_replace('\n', "</p>\n<p>", get_string('queuedpageinstructions', 'gradeexport_callista')) . "</p>\n";
        $html .= '</div>' . "\n";
        $html .= $OUTPUT->container_end();
        return $html;
    }
    
    /**
     * Builds the html for the table of marks. Marks and grades that have been overridden are given the 'overridden' css class, students 
     * that have enrolled since the batch was queued are given the 'enrolment' class, and students that have unenrolled since the batch
     * was queued are given the 'unenrolment' class.
     * @global type $OUTPUT Moodle's core_renderer.
     * @return string The html for the table.
     */
    private function get_marks_table() {
        global $OUTPUT;
        
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
        $table->data = array();
        foreach ($this->batch->get_marks() as $mark) {
            $row = new html_table_row();
            if($this->mark_in_array_by_student_number($mark, $this->studentswithdrawnafterqueueing)) {
                $row->attributes['class'] = 'unenrolment';
            }
            $qgrade = false;
            if ($mark->get_outcomeloadmessagenumber() == Mark::QGRADE_ERRORNO) {
                $qgrade = true;
                $row->attributes['class'] = 'transferWarning';
            }
            $row->cells[] = new html_table_cell($mark->get_studentnumber());
            $row->cells[] = new html_table_cell($mark->get_studentfirstname());
            $row->cells[] = new html_table_cell($mark->get_studentsurname());
            $row->cells[] = new html_table_cell($mark->get_unitcode());
            $row->cells[] = new html_table_cell($mark->get_offering());
            
            $cell = new html_table_cell($mark->get_mark());
            if($mark->get_markoverride() != null) {
                $cell->attributes['class'] = 'overridden';
            }
            $row->cells[] = $cell;
            
            $grade = $mark->get_grade();
            $cell = new html_table_cell($grade);
            if($mark->get_gradeoverride() != null) {
                $cell->attributes['class'] = 'overridden';
            }
            $row->cells[] = $cell;
            
            
            if(!empty($grade)) {
                $row->cells[] = new html_table_cell($OUTPUT->help_icon('gradeglossary', 'gradeexport_callista'));
            } else {
                $row->cells[] = new html_table_cell();
            }

            $table->data[] = $row;
        }
        
        foreach ($this->studentsenrolledafterqueuing as $mark) {
            $row = new html_table_row();
            $row->attributes['class'] = 'enrolment';
            $qgrade = false;
            if ($mark->get_outcomeloadmessagenumber() == Mark::QGRADE_ERRORNO) {
                $qgrade = true;
                $row->attributes['class'] = 'transferWarning';
            }
            $row->cells[] = new html_table_cell($mark->get_studentnumber());
            $row->cells[] = new html_table_cell($mark->get_studentfirstname());
            $row->cells[] = new html_table_cell($mark->get_studentsurname());
            $row->cells[] = new html_table_cell($mark->get_offering());
            $row->cells[] = new html_table_cell($mark->get_mark());
            $grade = $mark->get_grade();
            $row->cells[] = new html_table_cell($grade);
            if(!empty($grade)) {
                $row->cells[] = new html_table_cell($OUTPUT->help_icon('gradeglossary', 'gradeexport_callista'));
            } else {
                $row->cells[] = new html_table_cell();
            }
            
            $table->data[] = $row;
        }
        
        return html_writer::table($table);
    }
    
    /**
     * Checks to see whether a mark exists in the array that has a student number equal
     * to the one in the given mark.
     * @param Mark $mark The mark with the student number to search for.
     * @param array $markarray The array of marks to search.
     * @return boolean True if a mark in the array has its student number equal to
     *                 the student number in the singular mark argument. False otherwise.
     */
    private function mark_in_array_by_student_number(Mark $mark, array $markarray) {
        foreach ($markarray as $markelement) {
            if($mark->get_studentnumber() == $markelement->get_studentnumber()) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Creates the html for the buttons at the bottom of the page.
     * @global type $CFG Moodle's configuration object.
     * @return string The html of the buttons for the bottom of the page.
     */
    private function get_buttons() {
        global $CFG;
        $courseid = $this->batch->get_courseid();
        
        $dequeueurl = $CFG->wwwroot . '/grade/export/callista/dequeue.php?id=' . $courseid;
        $dequeueurlstring = get_string('queuedpagedequeue', 'gradeexport_callista');
        $html = '<button class="dequeueButton" type="button" onClick="window.location=\'' . $dequeueurl . '\';">' . $dequeueurlstring . '</button>' . "\n";
        
        $gradebookurl = $CFG->wwwroot . '/grade/report/grader/index.php?id=' . $courseid;
        $gradebookstring = get_string('backto', '', get_string('gradebook', 'grades'));
        $html .= '<button class="gradebookButton" type="button" onClick="window.location=\'' . $gradebookurl . '\';">' . $gradebookstring . '</button>' . "\n";
        
        $courseurl = $CFG->wwwroot . '/course/view.php?id=' . $courseid;
        $coursestring = get_string('backto', '', $this->batch->get_unitcode());
        $html .= '<button class="courseButton" type="button" onClick="window.location=\'' . $courseurl . '\';">' . $coursestring . '</button>' . "\n";
        
        $archiveurl = $CFG->wwwroot . '/grade/export/callista/batchArchive.php?id=' . $courseid;
        $html .= '<button class="archiveButton" type="button" onClick="window.location=\'' . $archiveurl . '\';">' . get_string('archive', 'gradeexport_callista') . '</button>' . "\n";
        
        //This form and button is temporary to allow user testers to emulate a cron run for this batch.
        $html .= '<button class="emulateCron" type="button">' . get_string('queuedpageemulatecron', 'gradeexport_callista') . '</button>' . "\n";
        
        
        return $html;
    }
}

?>
