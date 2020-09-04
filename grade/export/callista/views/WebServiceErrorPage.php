<?php

/**
 * The web service error page is displayed when there is an error calling the web service, such as it not being available. It displays
 * an error message to the user along with an indication of whether the batch was loaded or not (usually not).
 *
 * @copyright  2012 onwards University of New England
 * @author anoble2
 */
require_once $CFG->dirroot . '/grade/export/callista/views/ExportCallistaPage.php';

class WebServiceErrorPage extends ExportCallistaPage {
    
    //The batch of marks that failed to be uploaded to Callista.
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
        global $CFG, $OUTPUT;
        
        $html = '';
        
        $html .= html_writer::script('', $CFG->wwwroot . '/grade/export/callista/views/js/WebServiceErrorPage.js');
        $html .= html_writer::script("
            YUI().use('node', 'event', 'stylesheet', 'async-queue', function(Y) {
                Y.on('domready', function() {
                    WebServiceErrorPageJs.initialise(Y);
                });
            });
            ");

        $html .= $OUTPUT->box_start() . "\n";
        $html .= '<p>' . get_string('wsepageintro', 'gradeexport_callista') . "</p>\n";
        $html .= $this->get_error_message() . "\n";
        $html .= $this->get_batch_loaded_message() . "\n";
        $html .= $this->get_buttons() . "\n";
        $html .= $OUTPUT->box_end() . "\n";
        
        $html .= '<form id="newBatchForm" name="newBatchForm" action="newBatchFromLastBatch.php" method="post">' . "\n";
        $html .= '<input type="hidden" name="id" value="' . $this->batch->get_courseid() . '"/>' . "\n";
        $html .= '<input type="hidden" name="basebatch" value="' . $this->batch->get_id() . '"/>' . "\n";
        $html .= '</form>' . "\n";

        return $html;
    }
    
    /**
     * Creates the html to display the error message explaining why the web service failed.
     * @return string The html code for the error message section.
     */
    private function get_error_message() {
        $errormessage = '';
        $batchmessage = $this->batch->get_batchmessagetext();
        if($batchmessage != null && $batchmessage != '') {
            $errormessage = "<p>$batchmessage</p>";
        }
        
        $generalerror = $this->batch->get_generalerrormessage();
        if($generalerror != null && $generalerror != '') {
            $generalerror = str_replace("\n", "<br/>", $generalerror);
            $errormessage .= "<p>$generalerror</p>";
        }
        
        return $errormessage;
    }
    
    /**
     * Creates the html showing whether the batch was loaded or not.
     * @return string The html code explaining whether the batch was loaded or not.
     */
    private function get_batch_loaded_message() {
        if($this->batch->get_batchloadedflag() == 'TRUE') {
            return '<p>' . get_string('wsebatchloaded', 'gradeexport_callista') . '</p>';
        } else {
            return '<p>' . get_string('wsebatchnotloaded', 'gradeexport_callista') . '</p>';
        }
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
        $html .= html_writer::script('', $CFG->wwwroot . '/grade/export/callista/views/js/WebServiceErrorPage.js');
        $gradebookurl = $CFG->wwwroot . '/grade/report/grader/index.php?id=' . $courseid;
        $gradebookstring = get_string('backto', '', get_string('gradebook', 'grades'));
        $html .= '<button id="gradebookButton" type="button" onClick="window.location=\'' . $gradebookurl . '\';">' . $gradebookstring . '</button>' . "\n";
        
        $courseurl = $CFG->wwwroot . '/course/view.php?id=' . $courseid;
        $coursestring = get_string('backto', '', $this->batch->get_unitcode());
        $html .= '<button id="courseButton" type="button" onClick="window.location=\'' . $courseurl . '\';">' . $coursestring . '</button>' . "\n";
        
        $archiveurl = $CFG->wwwroot . '/grade/export/callista/batchArchive.php?id=' . $courseid;
        $html .= '<button id="archiveButton" type="button" onClick="window.location=\'' . $archiveurl . '\';">' . get_string('archive', 'gradeexport_callista') . '</button>' . "\n";
        $html .= '<button id="newBatchFromThisBatch" type="button">' . get_string('webserviceerrorstartnewbatch', 'gradeexport_callista') . '</button>' . "\n";
        return $html;
    }
}

?>
