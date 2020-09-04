<?php

/**
 * The transferred batches page lists every batch for a course that has at some 
 * point been saved to the database and gives a link to view each one. It only 
 * shows the most recent saved version of a batch, not a row for each time a
 * given batch was saved.
 *
 * @copyright  2012 onwards University of New England
 * @author anoble2
 */
class TransferredBatchesPage {
    
    private $course;
    
    private $batches;
    
    function __construct($course, array $batches) {
        $this->course = $course;
        $this->batches = $batches;
    }
    
    /**
     * Creates the html that forms the content of the page.
     * @global type $OUTPUT The Moodle core_renderer.
     * @global type $CFG The Moodle config object.
     * @return string The html code for the page content.
     */
    public function get_page_html() {
        global $OUTPUT, $CFG;
        
        $html = '';
        
        $html .= '<link rel="stylesheet" type="text/css" href="' . $CFG->wwwroot . '/grade/export/callista/views/BatchesTableStyles.css">';
        
        $html .= $OUTPUT->box_start() . "\n";
        $html .= $this->get_batch_table_heading() . "\n";
        $html .= $this->get_batch_table() . "\n";
        $html .= $OUTPUT->box_end() . "\n";
        
        return $html;
    }
    
    /**
     * Gets the html for the heading for the table listing the unit's batches.
     * @global type $OUTPUT The Moodle core_renderer.
     * @return string The html code for the table heading.
     */
    private function get_batch_table_heading() {
        global $OUTPUT;
        return $OUTPUT->heading(get_string('batchtableheading', 'gradeexport_callista') . ' - ' . $this->course->shortname);
    }
    
    /**
     * Gets the html for the table listing the unit's batches.
     * @global type $CFG The Moodle config object.
     * @return string The html code for the table listing the batches.
     */
    private function get_batch_table() {
        global $CFG;
        
        $table = new html_table();
        $table->id = 'batchTable';
        $table->head = array(get_string('columnheadinglastsaved', 'gradeexport_callista'),
                             get_string('columnheadingstatus', 'gradeexport_callista'),
                             get_string('columnheadinggotolink', 'gradeexport_callista'));
        $table->data = array();
        foreach ($this->batches as $batch) {
            $row = new html_table_row();
            
            $row->cells[] = new html_table_cell(date('D, d M Y h:ia', $batch->get_timewhensaved()));
            
            $status = $batch->get_status();
            if($status == Batch::STATUS_INITIAL) {
                $statusmessage = get_string('batchstatusinitial', 'gradeexport_callista');
            } else if($status == Batch::STATUS_QUEUED) {
                $statusmessage = get_string('batchstatusqueued', 'gradeexport_callista');
            } else if($status == Batch::STATUS_SENDING) {
                $statusmessage = get_string('batchstatussending', 'gradeexport_callista');
            } else if($status == Batch::STATUS_SUCCESS) {
                $statusmessage = get_string('batchstatussuccessful', 'gradeexport_callista');
            } else if($status == Batch::STATUS_DATA_ERROR) {
                $statusmessage = get_string('batchstatusdataerror', 'gradeexport_callista');
            } else if($status == Batch::STATUS_GENERAL_ERROR) {
                $statusmessage = get_string('batchstatusgeneralerror', 'gradeexport_callista');
            } else {
                $statusmessage = get_string('batchstatusunknown', 'gradeexport_callista');
            }
            $row->cells[] = new html_table_cell($statusmessage);
            
            $linkurl = $CFG->wwwroot . '/grade/export/callista/index.php?id=' . $this->course->id . '&batchid=' . $batch->get_id();
            $linktext = get_string('batchview', 'gradeexport_callista');
            $imagehtml = '<img class="viewBatchImage" src="' . $CFG->wwwroot . '/grade/export/callista/views/images/forwd_16.png" '
                       . 'alt="' . get_string('batchlinkalttext', 'gradeexport_callista') . '"/>';
            $row->cells[] = new html_table_cell('<a href="' . $linkurl . '">' . $linktext . $imagehtml . '</a>');
            
            $table->data[] = $row;
        }
        
        return html_writer::table($table);
    }
}

?>
