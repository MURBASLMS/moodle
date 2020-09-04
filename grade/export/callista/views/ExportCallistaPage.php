<?php

/**
 * Superclass for all the views. Contains common functions such as putting together the colour-coded key.
 * Subclasses can call the protected member functions during their implementation of the abstract method get_page_html().
 * 
 * @copyright  2012 onwards University of New England
 * @author anoble2
 */
abstract class ExportCallistaPage {
    public abstract function get_page_html();
    
    /**
     * Creates the html for the colour-coded legend that appears on the page.
     * @global type $OUTPUT Moodle's core_renderer object.
     * @param array $keyentries An array(stdClass('displaystring' -> text for the key entry,
     *                                            'cssclass' -> the css class that will be given to everything matching this key. This
     *                                                          class is also given to a small div in the legend next to the displaystring.))
     * @return string The html code for the colour-coded legend.
     */
    protected function get_colour_key(array $keyentries) {
        global $OUTPUT;
        if(empty($keyentries)) {
            return '';
        }
        
        $html = $OUTPUT->box_start('generalbox', 'keybox') . "\n";
        $html .= $OUTPUT->heading(get_string('keytitle', 'gradeexport_callista'), 5) . "\n";
        $html .= "<table>\n";
        foreach($keyentries as $keydata) {
            $html .= "<tr>\n"
                   . "<td><div class=\"keycolour $keydata->cssclass\" style=\"width: 10px; height: 10px; margin: 5px auto;\"/></td>\n"
                   . "<td>" . get_string($keydata->languagepackkey, 'gradeexport_callista') . "</td>\n"
                   . "<td>" . $OUTPUT->help_icon($keydata->languagepackkey, 'gradeexport_callista') . "</td>\n"
                   . "</tr>\n";
        }
        unset($keydata);
        $html .= "</table>\n";
        $html .= $OUTPUT->box_end();
                
        return $html;
    }
    
    /**
     * Creates the html for the heading above the main table of marks and grades.
     * @global type $OUTPUT The Moodle core_renderer.
     * @param array $colourkeyentries An array(stdClass('displaystring' -> text for the key entry,
     *                                                  'cssclass' -> the css class that will be given to everything matching this key. This
     *                                                                class is also given to a small div in the legend next to the displaystring.))
     * @return string The html code for the heading.
     */
    protected function get_marks_table_heading(array $colourkeyentries) {
        global $OUTPUT;
        
        $html = '<table id="marksTableHeadingTable">' . "\n";
        $html .= "<tr>\n";
        $html .= '<td id="marksTableHeadingCell">' . $OUTPUT->heading(get_string('marksandgradesheading', 'gradeexport_callista'), 2) . "</td>\n";
        $html .= "<td>" . $this->get_colour_key($colourkeyentries) . "</td>\n";
        $html .= "</tr>\n";
        $html .= "</table>";
        return $html;
    }
}

?>
