<?php

/**
 * The offerings dao retrieves a mapping of people enrolled in a course to the course offering they are enrolled in. The data is
 * sourced from the Callista Holding database, not Moodle's main database.
 *
 * @copyright  2012 onwards University of New England
 * @author anoble2
 */
require_once $CFG->dirroot . '/lib/adodb/adodb.inc.php';
require_once $CFG->dirroot . '/local/callista/classes/enrolment_database.php';

class OfferingsDao {
    
    /**
     * Finds the UNE courses (degrees) and offerings students in the Moodle course (unit) are enrolled in. An offering will be
     * 'ON' = on campus, 'OF' = off campus, 'OL' = online.
     * @param int $courseid The id number for the Moodle course whose students we want to get the course and offering data for.
     * @return array An array (student number => stdClass->coursecode = the UNE course code the student is enrolled in.
     *                                                   ->offering = the offering the student is part of.)
     */
    public function get_user_unit_course_and_offering_data($courseid) {
        $holding = new local_callista_enrolment_database();
        $mappings = $holding->get_course_sis_unit_offers($courseid);
        $courseandofferingdata = array();
        if ($mappings) {
            while (!$mappings->EOF) {
                $data = new stdClass();
                $data->unitcode = $mappings->fields['unit_code'];
                $data->coursecode = null;
                $data->offering = $mappings->fields['unit_class_code'];
                $students = $holding->get_students_for_unit_offer($mappings->fields['unit_offer_code']);
                while (!$students->EOF) {
                    $courseandofferingdata[$students->fields['sis_person_id']] = $data;
                    $students->MoveNext();
                }
                $students->Close();
                $mappings->MoveNext();
            }
            $mappings->Close();
        }

        return $courseandofferingdata;
    }
    
    /**
     * Finds the mapped offerings and returns the unit_offer_code's
     * @param int $courseid The id number for the Moodle course we want to get associated unit_offer_code's
     * @return array An array of unit_offer_code's
     */
    public function get_unit_offerings($courseid) {
        $holding = new local_callista_enrolment_database();
        $mappings = $holding->get_course_sis_unit_offers($courseid);
        $courseoffering = array();
        if ($mappings) {
            while (!$mappings->EOF) {
                $courseoffering[$mappings->fields['unit_offer_code']] = $mappings->fields['unit_code'];
                $mappings->MoveNext();
            }
            $mappings->Close();
        }
        return $courseoffering;
    }

    /**
     * Creates a connection to the Callista holding database.
     * @return ADONewConnection A connection to the Callista holding database. Remember to close it when you are finished with it.
     * @throws Exception If the connection could not be established.
     */
    private function get_callista_holding_db_connection() {
        if (!$config = get_config("local_callista")) {
            $config = new stdClass();
        }

        $database = ADONewConnection($config->holdingdbtype);
        if(!$database) {
            throw new Exception("Could not establish a connection to the Callista holding database.");
        }

        $database->Connect($config->holdingdbhost, $config->holdingdbuser, $config->holdingdbpass, $config->holdingdbname, true);
        $database->SetFetchMode(ADODB_FETCH_ASSOC);

        return $database;
    }
}

?>
