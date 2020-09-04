<?php
/**
 * The override page is the first page displayed in the process of sending unit marks to Callista. A unit coordinator can view a
 * table of basic student details and the students' mark and grade in this unit. The unit coordinator can enter an override any mark
 * and grade that needs to be changed. If an enterred override is in the wrong format (e.g. an alphabetic mark instead of numeric),
 * the table cell will be highlighted. The unit's marks and grades can only be queued for sending once they are all in the correct
 * format.
 * The override page will only be displayed if no batch of marks has been queued or stored for the unit.
 * The correct format for a mark can be any number with up to 5 decimal places.
 * A correct grade can be any one of 'HD', 'D', 'C', 'P', 'N', 'SA', 'SX', 'NA' or 'DNS'.
 * Moodle calculates the initial mark and from this derives the grade. The overrides for marks and grades are independent of each other.
 * In particular, the grade override will not be derived from the mark override.
 *
 * @copyright  2012 onwards University of New England
 * @author anoble2
 */
require_once $CFG->dirroot . '/grade/export/callista/views/ExportCallistaPage.php';
require_once $CFG->dirroot . '/grade/export/callista/services/BatchService.php';
require_once $CFG->dirroot . '/grade/export/callista/views/ErrorTranslator.php';
require_once $CFG->dirroot . '/grade/export/callista/daos/RetrieveGradeDao.php';

class OverridePage extends ExportCallistaPage {
    
    //Moodle's id number for the course.
    private $courseid;
    
    //The short name for the course. E.g. STAT100
    private $courseshortname;
    
    //Used to indicate a particular error message should be displayed on the page. No error message is displayed if this is null.
    private $errorcode;
    
    //An array holding the names and descriptions of css classes that are used for colour coding elements of the page.
    //array(stdClass('description' => the description of the colour coding that appears in the key (string),
    //               'cssclass' => the css class that gives the colour coding (string)))
    private $colourkeyentries;
    
    //An array of Marks containing all the data for the marks and grades table, one Mark per row.
    private $marks;
    
    //An array of Q Grades if any students in associated UOO ID's have them.
    private $qgrades;

    //An array(string) listing the valid grades that can be used as overrides.
    private $allowedgrades;
    
    //Regex that matches either a simple integer (no sign) or a simple float (no sign)
    private $wellformedmarkregex;
    
    //Regex that matches either an empty string, simple integer or simple float.
    private $wellformedmarkoverrideregex;
    
    //Regex that matches one of the allowed letter grades.
    private $wellformedgraderegex;
    
    //Regex that matches the empty string or one of the allowed letter grades.
    private $wellformedgradeoverrideregex;
    
    function __construct($courseid,
                         $courseshortname,
                         array $marks,
                         $qgrades,
                         $errorcode = null) {
        $this->courseid = $courseid;
        $this->courseshortname = $courseshortname;
        $this->qgrades = $qgrades;
        $this->errorcode = $errorcode;
        
        $this->colourkeyentries = array(new stdClass(),
                                        new stdClass(),
                                        new stdClass(),
                                        new stdClass(),
                                        new stdClass());
        $this->colourkeyentries[0]->languagepackkey = 'keymalformed';
        $this->colourkeyentries[0]->cssclass = 'malformed';
        $this->colourkeyentries[1]->languagepackkey = 'keyautooverridden';
        $this->colourkeyentries[1]->cssclass = 'autooverridden';
        $this->colourkeyentries[2]->languagepackkey = 'keymanuallyoverridden';
        $this->colourkeyentries[2]->cssclass = 'manuallyoverridden';
        $this->colourkeyentries[3]->languagepackkey = 'keywarning';
        $this->colourkeyentries[3]->cssclass = 'transferWarning';
        $this->colourkeyentries[4]->languagepackkey = 'keyrejectedlasttime';
        $this->colourkeyentries[4]->cssclass = 'rejectedLastTime';
        
        $this->marks = $marks;
        
        $this->allowedgrades = array ('HD', 'D', 'C', 'P', 'N', 'SA', 'SX', 'NA', 'DNS');
        $this->wellformedmarkregex = '/(?:^[0-9]+$)|(?:^[0-9]*\.[0-9]+$)/';
        $this->wellformedmarkoverrideregex = '/' . Mark::NO_MARK_STRING . '|(?:^$)|(?:^[0-9]+$)|(?:^[0-9]*\.[0-9]+$)/';
        $this->wellformedgraderegex = '/(?:^' . implode('$)|(?:^', array_map('preg_quote', $this->allowedgrades)) . '$)/';
        $this->wellformedgradeoverrideregex = '/(?:^$)|(?:^' . implode('$)|(?:^', array_map('preg_quote', $this->allowedgrades)) . '$)/';
    }
    
    /**
     * Creates the html that forms the content of the page.
     * @global type $OUTPUT The Moodle core_renderer.
     * @return string The html code for the page content.
     */
    public function get_page_html() {
        global $OUTPUT, $PAGE, $CFG;
        
        $html = '';
        
        //Get the styles used on the page for colour coding and when sorting the marks table.
        $html .= '<link rel="stylesheet" type="text/css" href="' . $CFG->wwwroot . '/grade/export/callista/views/MarksTableStyles.css">';
        
        //Get the javascript code for sorting the marks table and validating mark and grade overrides.
        $html .= html_writer::script('', $CFG->wwwroot . '/grade/export/callista/views/js/OverridePage.js');
        $html .= html_writer::script("
            YUI().use('node', 'event', 'stylesheet', 'async-queue', function(Y) {
                Y.on('domready', function() {
                    overridePageJs.initialise(Y, '" . addslashes_js(get_string('overridepagemalformederror', 'gradeexport_callista')) . "',
                                                 '" . addslashes_js(Mark::NO_MARK_STRING) . "');
                });
            });
            ");
        
        $html .= '<form id="sendToCallista" name="sendToCallista" action="save.php" method="post">' . "\n";
        $html .= '<input type="hidden" name="id" value="' . $this->courseid . '"/>' . "\n";
        $html .= '<input type="hidden" id="buttonClicked" name="buttonClicked" value="saveButton"/>' . "\n";
        $html .= $OUTPUT->box_start() . "\n";
        $html .= $this->get_instructions() . "\n";
        $html .= $this->get_marks_table_heading($this->colourkeyentries) . "\n";
        $html .= html_writer::empty_tag('br'); // spacing
        $html .= $this->get_buttons() . "\n";
        $html .= $this->get_message() . "\n";
        $html .= $this->get_marks_table() . "\n";
        $html .= $this->get_message() . "\n";
        $html .= $this->get_buttons() . "\n";
        $html .= $OUTPUT->box_end() . "\n";
        $html .= "</form>\n";
        $PAGE->requires->yui_module('moodle-gradeexport_callista-autocomplete',
                                    'M.gradeexport_callista.autocomplete.init', array(Mark::NO_MARK_STRING));
        
        return $html;
    }
    
    /**
     * Builds the html that shows the instructions above the Marks and Grades
     * @global type $OUTPUT Moodle's core_renderer.
     * @return String The html for the message section.
     */
    private function get_instructions() {
        global $OUTPUT;
        
        $html = '';
        $html .= $OUTPUT->container_start(null, 'pageMessage');
        
        
        $html .= '<a id="instructionHeading" href="">' . get_string('instructionheading', 'gradeexport_callista') . ' ' 
                . $OUTPUT->pix_icon('t/collapsed', get_string('instructionexpandalttext', 'gradeexport_callista'))
                . $OUTPUT->pix_icon('t/expanded',
                                    get_string('instructioncollapsealttext', 'gradeexport_callista'), 
                                    'moodle', 
                                    array('style' => 'display: none;')) 
                . "</a>\n";
        $html .= '<div id="instructions" style="display: none;">' . "\n";
        $html .= '<p>' . str_replace('\n', "</p>\n<p>", get_string('overridepageinstructions', 
                                                                   'gradeexport_callista', 
                                                                   array('unitname'     => $this->courseshortname, 
                                                                         'helpiconhtml' => $OUTPUT->pix_icon('help', 'help')))) 
                . "</p>\n";
        $html .= '</div>' . "\n";
        
        $html .= $OUTPUT->container_end();
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
        
        //If there's an error code, display the error message for it
        $errormessage = null;
        switch($this->errorcode) {
            case BatchService::SAVING_DATABASE_ERROR:
                $errormessage = get_string('overridepagesavedatabaseerror', 'gradeexport_callista');
                break;
            case BatchService::SAVING_NON_SAVING_STATUS:
                $errormessage = get_string('overridepagesavestatuserror', 'gradeexport_callista');
                break;
            case BatchService::SAVING_MALFORMED_OVERRIDES:
                $errormessage = get_string('overridepagemalformederror', 'gradeexport_callista');
                break;
            case BatchService::QUEUING_DATABASE_ERROR:
                $errormessage = get_string('overridepagequeuingdatabaseerror', 'gradeexport_callista');
                break;
            case BatchService::QUEUING_NON_QUEUING_STATUS:
                $errormessage = get_string('overridepagequeuestatuserror', 'gradeexport_callista');
                break;
            case BatchService::QUEUING_EMPTY_MARKS:
                $errormessage = get_string('overridepageemptymarkserror', 'gradeexport_callista');
                break;
            case BatchService::QUEUING_EMPTY_GRADES:
                $errormessage = get_string('overridepageemptygradeserror', 'gradeexport_callista');
                break;
            case BatchService::QUEUING_EMPTY_MARKS_AND_GRADES:
                $errormessage = get_string('overridepageemptymarksandgradeserror', 'gradeexport_callista');
                break;
            case BatchService::QUEUING_NON_EXISTENT_BATCH:
                $errormessage = get_string('overridepagenonexistentbatcherror', 'gradeexport_callista');
                break;
            default:
        }
        if(!empty($errormessage)) {
            $html .= '<p id="errorMessage" class="alert alert-danger">' . $errormessage . "</p>\n";
        } else {
            $html .= '<p id="errorMessage" class="alert alert-danger" style="display: none;"></p>' . "\n";
        }
        
        // Show generic Q Grade error messages
        if (!empty($this->qgrades->errors) && count($this->qgrades->errors) > 0) {
            foreach ($this->qgrades->errors as $error) {
                $html .= '<p id="errorMessage" class="alert alert-danger">' . $error . "</p>\n";
            }
        }
        $html .= $OUTPUT->container_end();
        return $html;
    }
    
    /**
     * Builds the html for the table of marks. This includes validating the marks, mark overrides and grades against their corresponding
     * regular expressions and setting css classes if they fail the comparison.
     * @global type $OUTPUT Moodle's core_renderer.
     * @return string The html for the table.
     */
    private function get_marks_table() {
        global $OUTPUT;
        
        $previouserrors = false;
        foreach ($this->marks as $mark) {
            if($mark->response_was_error()) {
                $previouserrors = true;
                break;
            }
        }
        unset($mark);
        if (!empty($this->qgrades->data) && count($this->qgrades->data) > 0) {
            $previouserrors = true;
        }
        $table = new html_table();
        $table->id = 'markstable';
        //The links in the headings are used to trigger the sorting of the table by that column in ascending or descending order.
        $table->head = array('<a class="sortingLink" id="c0" href="">' . get_string('studentnumber', 'gradeexport_callista') . '</a>',
                             '<a class="sortingLink" id="c1" href="">' . get_string('firstnamecolumn', 'gradeexport_callista') . '</a>',
                             '<a class="sortingLink" id="c2" href="">' . get_string('lastname') . '</a>',
                             '<a class="sortingLink" id="c3" href="">' . get_string('unitcode', 'gradeexport_callista') . '</a>',
                             '<a class="sortingLink" id="c4" href="">' . get_string('offering', 'gradeexport_callista') . '</a>',
                             '<a class="sortingLink" id="c5" href="">' . get_string('mark', 'gradeexport_callista').  '</a>',
                             '<a class="sortingLink" id="c6" href="">' . get_string('markoverride', 'gradeexport_callista'). '</a>',
                             '<a class="sortingLink" id="c7" href="">' . get_string('grade'). '</a>',
                             '<a class="sortingLink" id="c8" href="">' . get_string('gradeoverride', 'gradeexport_callista'). '</a>',
                             get_string('gradeglossary', 'gradeexport_callista'));
        if($previouserrors) {
            $table->head[] = '<a class="sortingLink" id="c10" href="">' . get_string('errormessagecolumn', 'gradeexport_callista') . '</a>';
        }
        
        $table->data = array();
        foreach ($this->marks as $mark) {
            $row = new html_table_row();
            $qgrade = false;
            $inputattributes = '';
            $studentnumber = $mark->get_studentnumber();
            $unitcode = $mark->get_unitcode();
            $allowedgrades = $this->allowedgrades;
            if (!empty($this->qgrades->data[$unitcode]) && !empty($this->qgrades->data[$unitcode][$studentnumber])) {
                $qgrade = true;
                $inputattributes = ' DISABLED';
                if ($this->qgrades->data[$unitcode][$studentnumber]['MARK'] == null) {
                    $mark->set_markoverride(Mark::NO_MARK);
                } else {
                    $mark->set_markoverride($this->qgrades->data[$unitcode][$studentnumber]['MARK']);
                }
                $mark->set_gradeoverride(Mark::QGRADE_GRADE);
                $mark->set_outcomeloadmessagenumber(Mark::QGRADE_ERRORNO);
                $row->attributes['class'] = 'transferWarning';
                $this->allowedgrades[] .= 'Q';
            }
            $row->cells[] = new html_table_cell($studentnumber);
            $row->cells[] = new html_table_cell($mark->get_studentfirstname());
            $row->cells[] = new html_table_cell($mark->get_studentsurname());
            $row->cells[] = new html_table_cell($unitcode);
            $row->cells[] = new html_table_cell($mark->get_offering());
            
            $cell = new html_table_cell(floatval($mark->get_calculatedmark()));
            $cell->attributes['class'] = 'calculatedmark';
            //If the calculated mark isn't in the right format, highlight it.
            if(!preg_match($this->wellformedmarkregex, $mark->get_calculatedmark())) {
                $cell->attributes['class'] .= ' malformed';
            }
            $row->cells[] = $cell;
            
            //The mark override fields are sent as an array when the form is submitted.
            $cell = new html_table_cell('<input type="text" class="markoverridecombo markoverride" name="mark_overrides[' . $mark->get_studentnumber() . ']" value="' . $mark->get_markoverride() . '"' . $inputattributes . '/>');
            //If the overriding mark isn't in the right format, highlight it.
            if(!preg_match($this->wellformedmarkoverrideregex, $mark->get_markoverride())) {
                $cell->attributes['class'] .= ' malformed';
            }

            // Highlight depending on mark override & manual flag
            if (!empty($mark->get_markoverride())) {
                if ($mark->get_markoverridemanual()) {
                    $cell->attributes['class'] .= ' overridden';
                } else {
                    $cell->attributes['class'] .= ' autooverridden';
                }
            }

            $row->cells[] = $cell;
            
            $cell = new html_table_cell($mark->get_derivedgrade());
            $cell->attributes['class'] = 'derivedgrade';
            //If the derived grade isn't in the right format, highlight it.
            if(!preg_match($this->wellformedgraderegex, $mark->get_derivedgrade())) {
                $cell->attributes['class'] .= ' malformed';
            }
            $row->cells[] = $cell;
            
            
            //The grade override fields are sent as an array when thr form is submitted.
            $gradeoverrideselect = '<select class = "gradeoverride" name="grade_overrides[' . $mark->get_studentnumber() . ']"' . $inputattributes . '>';
            //If there is no grade override, select the emtpy string as the default value.
            if($mark->get_gradeoverride() === '') {
                $gradeoverrideselect .= ' <option value = "" selected = "selected"></option>';
            } else {
                $gradeoverrideselect .= ' <option value = ""></option>';
            }
            //Add all the possible grade overrides to the drop-down list, selecting the one matching the given grade override.
            foreach ($this->allowedgrades as $possiblegrade) {
                if($possiblegrade === $mark->get_gradeoverride()) {
                    $gradeoverrideselect .= "<option value=\"$possiblegrade\" selected = \"selected\">$possiblegrade</option>";
                } else {
                    $gradeoverrideselect .= "<option value=\"$possiblegrade\">$possiblegrade</option>";
                }
            }
            $gradeoverrideselect .= '</select>';
            // Add hidden override grade so this can be used in Javascript
            $value = $mark->get_gradeoverride();
            if ($qgrade) {
                $gradeoverrideselect .= '<input type="hidden" name="mark_overridden[' . $mark->get_studentnumber() . ']" value="' . $mark->get_markoverride() . '"/>';
                $gradeoverrideselect .= '<input type="hidden" name="grade_overridden[' . $mark->get_studentnumber() . ']" value="' . Mark::QGRADE . '"/>';
            }
            $gradeoverrideselect .= '<input type="hidden" class="gradeautooverride" name="gradeautooverride[' . $mark->get_studentnumber() . ']" value="' . $value . '"/>';
            $cell = new html_table_cell($gradeoverrideselect);
            
            // Highlight depending on grade override & manual flag
            if (!empty($mark->get_gradeoverride())) {
                if ($mark->get_gradeoverridemanual()) {
                    $cell->attributes['class'] .= ' overridden';
                } else {
                    $cell->attributes['class'] .= ' autooverridden';
                }
            }
            $row->cells[] = $cell;
            
            //The help icon describes what each of the grade abbreviations means.
            $row->cells[] = new html_table_cell($OUTPUT->help_icon('gradeglossary', 'gradeexport_callista'));
            
            if($previouserrors) {
                $errormessage = ErrorTranslator::message_for_error($mark->get_outcomeloadmessagenumber(), $mark->get_outcomeloadmessage());
                if(empty($errormessage)) {
                    $cell = new html_table_cell();
                } else {
                    $cell = new html_table_cell($errormessage);
                    $cell->attributes['class'] = 'rejectedLastTime';
                }
                $row->cells[] = $cell;
            }
            
            $table->data[] = $row;
            $this->allowedgrades = $allowedgrades; // Reset back after Q Grade has been processed;
        }
        
        return html_writer::table($table);
    }
    
    /**
     * Creates the html for the buttons at the bottom of the page. The export plugin requires javascript to work properly, so these
     * buttons don't work without it.
     * @global type $CFG The Moodle config object.
     * @return string The html of the buttons for the bottom of the page.
     */
    private function get_buttons() {
        global $CFG;
        
        $html = '';
        $html .= '<button class="saveButton" type="button">' . get_string('overridepagesave', 'gradeexport_callista') . '</button>' . "\n";
        $html .= '<button class="sendButton" type="button">' . get_string('overridepagesend', 'gradeexport_callista') . '</button>' . "\n";
        
        $cancelurl = $CFG->wwwroot . '/course/view.php?id=' . $this->courseid;
        $html .= '<button class="cancelButton" type="button" onClick="window.location=\'' . $cancelurl . '\';">' . get_string('cancel') . '</button>' . "\n";
        
        $archiveurl = $CFG->wwwroot . '/grade/export/callista/batchArchive.php?id=' . $this->courseid;
        $html .= '<button class="archiveButton" type="button" onClick="window.location=\'' . $archiveurl . '\';">' . get_string('archive', 'gradeexport_callista') . '</button>' . "\n";
        return $html;
    }
}

?>
