<?php

/**
 * The debug table data page shows the data that is in the two plugin tables for a
 * particular course. This will be useful for diagnosing problems with the plugin
 * when something is going wrong and manual database access is not available.
 * 
 * @copyright  2012 onwards University of New England
 * @author anoble2
 */
class DebugTableDataPage {
    
    private $tabledata;
    
    private $courseid;
    
    function __construct($tabledata, $courseid) {
        $this->tabledata = $tabledata;
        $this->courseid = $courseid;
    }
    /**
     * 
     * Creates the html that forms the content of the page.
     * @global type $OUTPUT The Moodle core_renderer.
     * @global type $CFG The Moodle config object.
     * @return string The html code for the page content.
     */
    public function get_page_html() {
        global $OUTPUT, $CFG;
        
        $html = '<link rel="stylesheet" type="text/css" href="' . $CFG->wwwroot . '/grade/export/callista/views/DebugTableDataStyles.css">';
        
        $html .= html_writer::script('', $CFG->wwwroot . '/grade/export/callista/views/js/DebugTableDataPage.js');
        $html .= html_writer::script("
            YUI().use('node', function(Y) {
                Y.on('domready', function() {
                    debugTableDataPageJs.initialise(Y);
                });
            });
            ");
        
        $html .= $OUTPUT->container(get_string('debugtabledatapageinstructions', 'gradeexport_callista'));
        $html .= $OUTPUT->container_start('horizontalOverflow');
        $html .= $OUTPUT->heading(get_string('debugtabledatapagebatchesheading', 'gradeexport_callista'), 3);
        if(empty($this->tabledata->batchrecords)) {
            $html .= "<p>" . get_string('debugtabledatapagenobatches', 'gradeexport_callista') . "</p>";
        } else {
            $html .= $this->create_batch_records_table($this->tabledata->batchrecords);
        }
        $html .= $OUTPUT->container_end();
        
        $html .= $OUTPUT->container_start('horizontalOverflow');
        $html .= $OUTPUT->heading(get_string('debugtabledatapagemarksheading', 'gradeexport_callista'), 3);
        if(empty($this->tabledata->markrecords)) {
            $html .= "<p>" . get_string('debugtabledatapagenomarks', 'gradeexport_callista') . "</p>";
        } else {
            $html .= $this->create_mark_records_table($this->tabledata->markrecords);
        }
        $html .= $OUTPUT->container_end();
        $html .= $OUTPUT->container('<a href="index.php?id=' . $this->courseid . '">Callista grade export page</a>',
                                    null,
                                    'gradeExportPageLink');
        
        return $html;
    }
    
    /**
     * Builds the html for the table displaying the batch records.
     * @param array $batchrecords The records to display in the table.
     * @return string The html for the batch records table.
     */
    private function create_batch_records_table($batchrecords) {
        $htmltable = new html_table();
        $htmltable->id = "batchRecords";
        $htmltable->head = array("+/-",
                                 "id",
                                 "course_id",
                                 "unit_code",
                                 "teach_pd_alt_code",
                                 "acad_year_alt_code",
                                 "status",
                                 "loaded_by_username",
                                 "time_when_saved",
                                 "time_when_queued",
                                 "generated_xml",
                                 "time_when_sent",
                                 "results_xml",
                                 "batch_loaded_flag",
                                 "batch_message_text",
                                 "general_error_message",
                                 "+/-");
        $htmltable->data = array();
        foreach ($batchrecords as $batchrecord) {
            $collapsecontrolcell = new html_table_cell('+');
            $collapsecontrolcell->attributes['class'] = 'collapseControl';
            
            $rowdata = array($batchrecord->id,
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
            foreach ($rowdata as &$value) {
                if($value == NULL) {
                    $value = new html_table_cell('<div class="shrinkable">null</div>');
                    $value->attributes['class'] = 'nullValue';
                } else {
                    $value = '<div class="shrinkable">' . nl2br(htmlspecialchars($value)) . '</div>';
                }
            }
            unset($value);
            array_unshift($rowdata, $collapsecontrolcell);
            $rowdata[] = $collapsecontrolcell;
            
            $htmltable->data[] = new html_table_row($rowdata);
        }
        unset($batchrecord);
        
        return html_writer::table($htmltable);
    }
    
    /**
     * Builds the html for the table displaying the mark records.
     * @param array $markrecords The records to display in the table.
     * @return string The html for the mark records table.
     */
    private function create_mark_records_table($markrecords) {
        $htmltable = new html_table();
        $htmltable->id = "markRecords";
        $htmltable->head = array("+/-",
                                 "id",
                                 "batch_id",
                                 "person_id",
                                 "person_first_name",
                                 "person_surname",
                                 "calculated_mark",
                                 "mark_override",
                                 "derived_grade",
                                 "grade_override",
                                 "outcome_id",
                                 "course_cd",
                                 "unit_cd",
                                 "offering",
                                 "outcome_loaded_flag",
                                 "outcome_load_message_number",
                                 "outcome_load_message",
                                 "+/-");
        $htmltable->data = array();
        $previousbatchid = null;
        $collapsecontrolcell = null;
        foreach ($markrecords as $markrecord) {
            $row = new html_table_row();
            $rowdata = array($markrecord->id,
                             $markrecord->batch_id,
                             $markrecord->person_id,
                             $markrecord->person_first_name,
                             $markrecord->person_surname,
                             $markrecord->calculated_mark,
                             Mark::convert_markoverride($markrecord->mark_override),
                             $markrecord->derived_grade,
                             $markrecord->grade_override,
                             $markrecord->outcome_id,
                             $markrecord->course_cd,
                             $markrecord->unit_cd,
                             $markrecord->offering,
                             $markrecord->outcome_loaded_flag,
                             $markrecord->outcome_load_message_number,
                             $markrecord->outcome_load_message);
            foreach ($rowdata as &$value) {
                if($value == NULL) {
                    $value = new html_table_cell('null');
                    $value->attributes['class'] = 'nullValue';
                } else {
                    $value = new html_table_cell(nl2br(htmlspecialchars($value)));
                }
            }
            unset($value);
            /* We can calculate the rowspan of the expand/collapse control cells like this because the 
             * BatchDAO orders the marks by the batch id when it retrieves them from the database.
             */
            if($markrecord->batch_id == $previousbatchid) {
                $collapsecontrolcell->rowspan++;
                $row->attributes['class'] = 'batchRow' . $markrecord->batch_id;
            } else {
                $previousbatchid = $markrecord->batch_id;
                $collapsecontrolcell = new html_table_cell('-');
                $collapsecontrolcell->attributes['class'] = 'collapseControl';
                $collapsecontrolcell->rowspan = 1;
                array_unshift($rowdata, $collapsecontrolcell);
                $rowdata[] = $collapsecontrolcell;
            }
            
            $row->cells = $rowdata;
            $htmltable->data[] = $row;
        }
        unset($markrecord);
        
        return html_writer::table($htmltable);
    }
}

?>