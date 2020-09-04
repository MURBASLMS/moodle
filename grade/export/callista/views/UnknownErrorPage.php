<?php

/**
 * The unknown error page is displayed when the batch's status has an undefined value or when some other unpredicted situation arises.
 * It displays the contact information for the service desk along with the batch's id if available.
 *
 * @copyright  2012 onwards University of New England
 * @author anoble2
 */
require_once $CFG->dirroot . '/grade/export/callista/views/ExportCallistaPage.php';

class UnknownErrorPage extends ExportCallistaPage {
    //The batch that caused the unknown error.
    private $batch;
    
    function __construct(Batch $batch) {
        $this->batch = $batch;
    }
    
    /**
     * Creates the html that forms the content of the page.
     * @global type $OUTPUT The Moodle core_renderer.
     * @return string The html code for the page content.
     */
    public function get_page_html() {
        global $OUTPUT;
        
        $html = '';
        $html .= $OUTPUT->box_start();
        $html .= $this->get_message();
        
        $html .= "<br />\n<br />\n";
        $html .= $this->get_service_desk_info();
        $html .= $this->get_buttons();
        $html .= $OUTPUT->box_end();
        return $html;
    }
    
    /**
     * Creates the html to display the error message explaining why the web service failed.
     * @return string The html code for the error message section.
     */
    private function get_message() {
        $html = '';
        
        if($this->batch->get_id() == null) {
            $html .= '<p>' . get_string('unknownerrormessagewithoutbatch', 
                                        'gradeexport_callista', 
                                        $this->batch->get_unitcode()) 
                    . "</p>\n";
        } else {
            $html .= '<p>' . get_string('unknownerrormessagewithbatch', 
                                        'gradeexport_callista', 
                                        array('shortname' => $this->batch->get_unitcode(), 
                                              'batchid' => $this->batch->get_id()))
                    . "</p>\n";
        }
        return $html;
    }
    
    /**
     * Creates the html to display the contact details of the service desk.
     * @return string The html code for the service desk's contact details.
     */
    private function get_service_desk_info() {
        $table = new html_table();
        $table->data = array(array(get_string('servicedeskphonelabel', 'gradeexport_callista'),
                                   get_string('servicedeskphone', 'gradeexport_callista')),
                             array(get_string('servicedeskemaillabel', 'gradeexport_callista'),
                                   get_string('servicedeskemail', 'gradeexport_callista')));
        $html = '';
        $html .= '<p>' . get_string('servicedesk', 'gradeexport_callista') . "</p>\n";
        $html .= html_writer::table($table) . "\n";
        
        return $html;
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
        $html .= '<button id="gradebookButton" type="button" onClick="window.location=\'' . $gradebookurl . '\';">' . $gradebookstring . '</button>' . "\n";
        
        $courseurl = $CFG->wwwroot . '/course/view.php?id=' . $courseid;
        $coursestring = get_string('backto', '', $this->batch->get_unitcode());
        $html .= '<button id="courseButton" type="button" onClick="window.location=\'' . $courseurl . '\';">' . $coursestring . '</button>' . "\n";
        
        $archiveurl = $CFG->wwwroot . '/grade/export/callista/batchArchive.php?id=' . $courseid;
        $html .= '<button id="archiveButton" type="button" onClick="window.location=\'' . $archiveurl . '\';">' . get_string('archive', 'gradeexport_callista') . '</button>' . "\n";
        
        return $html;
    }
}

?>
