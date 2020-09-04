<?php

/**
 * The grade calculation service reads Moodle's grade book for a given course and puts together a 2-dimensional array of data to be
 * displayed in the marks table of the override page (views/OverridePage.php).
 *
 * @copyright  2012 onwards University of New England
 * @author anoble2
 */

require_once $CFG->dirroot . '/grade/export/callista/daos/GradebookDao.php';
require_once $CFG->dirroot . '/grade/export/callista/daos/OfferingsDao.php';

class GradeCalculationService {

    //The GradebookDao that will give the service the data on students' marks and grades.
    private $gradebookdao;
    
    //The OfferingsDao that determines which offering students are enrolled in.
    private $offeringsdao;
    
    function __construct() {
        $this->gradebookdao = new GradebookDao();
        
        $this->offeringsdao = new OfferingsDao();
    }
    
    /**
     * Gets an array of Mark objects holding the marks and grades the graded users of a course have achieved. The offerings the 
     * graded users are enrolled in can optionally be loaded. Overrides are then applied.
     * @param type $course Moodle's object for the course whose marks we want.
     * @param array $markoverrides An array (student number => overriding mark) of marks to use instead of the calculated marks from
     *                             Moodle's gradebook.
     * @param array $gradeoverrides An array (student number => overriding grade) of grades to use instead of the grade derived from
     *                              the mark calculated by Moodle's gradebook.
     * @return Array(Mark) An array of marks for the course. 
     */
    public function get_calculated_marks_raw_overrides($course, array $markoverrides = array(), array $gradeoverrides = array(), Batch $batch = null) {
        $marks = $this->gradebookdao->get_marks_for_course($course);
        $batchmarks = new StdClass;
        // Grab batch overrides if they exist to make the auto grading work better
        if (!empty($batch)) {
            $batchdao = new BatchDao();
            $batchmarks = $batchdao->get_marks_in_batch($batch);
        }
        
        $unitscoursesandofferings = $this->offeringsdao->get_user_unit_course_and_offering_data($course->id);
        foreach ($marks as $mark) {
            $studentnumber = $mark->get_studentnumber();
            if(array_key_exists($studentnumber, $unitscoursesandofferings)) {
                $mark->set_unitcode($unitscoursesandofferings[$studentnumber]->unitcode);
                $mark->set_coursecode($unitscoursesandofferings[$studentnumber]->coursecode);
                $mark->set_offering($unitscoursesandofferings[$studentnumber]->offering);
            }
            $batchmarkexists = false;
            foreach ($batchmarks as $batchmark) {
                if ($mark->is_for_same_person_and_unit($batchmark)) {
                    $batchmarkexists = true;

                    $mark->set_markoverride($batchmark->get_markoverride());
                    $mark->set_gradeoverride($batchmark->get_gradeoverride());
                    $mark->set_markoverridemanual($batchmark->get_markoverridemanual());
                    $mark->set_gradeoverridemanual($batchmark->get_gradeoverridemanual());
                    if (isset($markoverrides[$studentnumber]) && strlen($markoverrides[$studentnumber]) > 0) {
                        // Handle forcing of mark overrides based off the different in batch and gradebook marks
                        $mark->set_markoverride($markoverrides[$studentnumber]);
                        $mark->calculate_automatic_overrides();
                        if (!$mark->get_markoverridemanual() && (($mark->get_calculatedmark() != $batchmark->get_calculatedmark()) ||
                            ($mark->get_calculatedmark() == $mark->get_roundedmark()))) {
                            $mark->set_markoverridemanual(false);
                            if ($mark->get_calculatedmark() != $batchmark->get_calculatedmark()) {
                                $mark->set_markoverride($batchmark->get_calculatedmark());
                            }
                        } else {
                            $mark->set_markoverridemanual(true);
                        }
                    } else {
                        if (isset($markoverrides[$studentnumber]) && strlen($markoverrides[$studentnumber]) == 0) {
                            $mark->set_markoverride($markoverrides[$studentnumber]);
                            $mark->set_markoverridemanual(false);
                        }
                    }

                    if (isset($gradeoverrides[$studentnumber]) && strlen($gradeoverrides[$studentnumber]) > 0) {
                        if (!$mark->get_gradeoverridemanual() && (($mark->get_derivedgrade() != $batchmark->get_derivedgrade()) ||
                            ($mark->get_derivedgrade() == $mark->get_roundedgrade()))) {
                            if ($mark->get_derivedgrade() != $gradeoverrides[$studentnumber]) {
                                $mark->set_gradeoverridemanual(true);
                                $mark->set_gradeoverride($gradeoverrides[$studentnumber]);
                            } else {
                                $mark->set_gradeoverridemanual(false);
                                $mark->set_gradeoverride('');
                            }
                        } else {
                            $mark->set_gradeoverridemanual(true);
                            $mark->set_gradeoverride($gradeoverrides[$studentnumber]);
                        }
                    } else {
                        if (isset($gradeoverrides[$studentnumber]) && strlen($gradeoverrides[$studentnumber]) == 0) {
                            $mark->set_gradeoverride($gradeoverrides[$studentnumber]);
                            $mark->set_gradeoverridemanual(false);
                        } else if (!$mark->get_gradeoverridemanual() && $mark->get_derivedgrade() == $batchmark->get_derivedgrade()) {
                            $mark->set_gradeoverridemanual(false);
                            $mark->set_gradeoverride('');
                        }
                    }

                    $mark->calculate_automatic_overrides();
                    // If the overriding mark is the different to the calculated mark, check if manually or automatically overriden.
                    if ($mark->get_calculatedmark() != $mark->get_roundedmark()) {
                        if (!$mark->get_markoverridemanual() || empty($mark->get_markoverride())) {
                            $mark->set_markoverridemanual(false);
                            $mark->set_markoverride($mark->get_roundedmark());
                        }
                    } else if ($mark->get_calculatedmark() != $batchmark->get_calculatedmark()) {
                        // If automatic, use markoverride based on gradebook rounded grade then previous gradebook grade.
                        if (!$mark->get_markoverridemanual()) {
                            if ($mark->get_calculatedmark() == $mark->get_roundedmark()) {
                                $mark->set_markoverride('');
                            } else {
                                $mark->set_markoverride($mark->get_roundedmark());
                            }
                        }
                    }
                    $mark->calculate_automatic_overrides();
                    // If the overriding grade is the different to the derived grade, check if manually or automatically overriden.
                    if ($mark->get_derivedgrade() != $mark->get_roundedgrade()) {
                        if ((!$mark->get_markoverridemanual() && strlen($mark->get_markoverride()) > 0) && (!$mark->get_gradeoverridemanual() || empty($mark->get_gradeoverride()))) {
                            if ($mark->get_derivedgrade() == $mark->get_roundedgrade()) {
                                $mark->set_gradeoverride('');
                            } else {
                                $mark->set_gradeoverride($mark->get_roundedgrade());
                            }
                        } else {
                            if (!$mark->get_gradeoverridemanual()) {
                                $mark->set_gradeoverride('');
                            }
                        }
                    } else if ($mark->get_derivedgrade() != $batchmark->get_derivedgrade()) {
                        // If automatic, use gradeoverride based on gradebook rounded grade then previous gradebook grade.
                        if (!$mark->get_markoverridemanual() && (!$mark->get_gradeoverridemanual() || empty($mark->get_gradeoverride()))) {
                            $mark->set_gradeoverridemanual(false);
                            $mark->set_gradeoverride('');
                        } else {
                            if (!$mark->get_gradeoverridemanual()) {
                                $mark->set_gradeoverride('');
                            }
                        }
                    }
                }
            }
            
            if (!$batchmarkexists) {
                if(isset($markoverrides[$studentnumber]) && $markoverrides[$studentnumber] != '') {
                    $mark->set_markoverride($markoverrides[$studentnumber]);
                }

                if(isset($gradeoverrides[$studentnumber]) && $gradeoverrides[$studentnumber] != '') {
                    $mark->set_gradeoverride($gradeoverrides[$studentnumber]);
                }
                $mark->calculate_automatic_overrides();
                // If the overriding grade is the different to the derived grade, check if manually or automatically overriden.
                if ($mark->get_markoverride() == '' && $mark->get_gradeoverride() == '') {
                    if ($mark->get_derivedgrade() != $mark->get_roundedgrade()) {
                        $mark->set_gradeoverridemanual(false);
                        $mark->set_gradeoverride($mark->get_roundedgrade());
                    }
                }
                // If the overriding mark is the different to the calculated mark, check if manually or automatically overriden.
                if ($mark->get_markoverride() == '') {
                    if ($mark->get_calculatedmark() != $mark->get_roundedmark()) {
                        $mark->set_markoverridemanual(false);
                        $mark->set_markoverride($mark->get_roundedmark());
                    }
                }
            }
        }
        unset($mark);
        return $marks;
    }
    
    public function get_calculated_marks_copy_overrides($course, array $markswithoverrides, Batch $batch) {
        $calculatedmarks = $this->get_calculated_marks_raw_overrides($course, array(), array(), $batch);
        
        foreach ($calculatedmarks as $calculatedmark) {
            foreach ($markswithoverrides as $overridemark) {
                if($calculatedmark->is_for_same_person_and_unit($overridemark)) {
                    $calculatedmark->calculate_automatic_overrides();
                    if (!$overridemark->get_markoverridemanual() && $calculatedmark->get_markoverride() == '') {
                        $overridemark->set_markoverride('');
                    }
                    // If the overriding mark is the different to the calculated mark, check if manually or automatically overriden.
                    if ($overridemark->get_markoverride() != '' && $calculatedmark->get_calculatedmark() != $overridemark->get_markoverride()) {
                        if ($overridemark->get_markoverridemanual()) {
                            $calculatedmark->set_markoverridemanual(true);
                        } else {
                            if ($calculatedmark->get_markoverride() != $overridemark->get_markoverride()) {
                                $overridemark->set_markoverride($calculatedmark->get_roundedmark());
                            }
                            if (!$calculatedmark->get_markoverrideforced()) {
                                $calculatedmark->set_markoverridemanual(false);
                            }
                        }
                    }
                    if (!$overridemark->get_gradeoverridemanual() && $calculatedmark->get_gradeoverride() == '') {
                        $overridemark->set_gradeoverride('');
                    }
                    // If the overriding grade is the different to the derived grade, check if manually or automatically overriden.
                    if (!$overridemark->get_markoverridemanual() && $overridemark->get_gradeoverride() != '' && $calculatedmark->get_derivedgrade() != $overridemark->get_gradeoverride()) {
                        if ($overridemark->get_gradeoverridemanual()) {
                            $calculatedmark->set_gradeoverridemanual(true);
                        } else {
                            if ($calculatedmark->get_gradeoverride() != $overridemark->get_gradeoverride()) {
                                $overridemark->set_gradeoverride($calculatedmark->get_roundedgrade());
                            }
                            $calculatedmark->set_gradeoverridemanual(false);
                        }
                    }
                    $calculatedmark->set_outcomeloadedflag($overridemark->get_outcomeloadedflag());
                    $calculatedmark->set_outcomeloadmessage($overridemark->get_outcomeloadmessage());
                    $calculatedmark->set_outcomeloadmessagenumber($overridemark->get_outcomeloadmessagenumber());
                    break;
                }
            }
            unset($overridemark);
        }
        
        return $calculatedmarks;
    }
    
}
