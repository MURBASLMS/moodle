<?php

/**
 * ErrorTranslator translates Callista's error messages to a more user-friendly version.
 *
 * @copyright  2012 onwards University of New England
 * @author anoble2
 */
class ErrorTranslator {
    private static $translatedmessages = array(2550  => 'The student number is not in Callista.',
                                               2551  => 'The surname doesn\'t match the student number.',
                                               2552  => 'The student has withdrawn from the unit in Callista.',
                                               2553  => 'A mark has already been given to this student.',
                                               2555  => 'The unit has no grading schema in Callista for the grades to be validated against.',
                                               2556  => 'The student is not enrolled in the unit.',
                                               2557  => 'The student does not appear to be enrolled in a course.',
                                               2558  => 'The grade is not valid.',
                                               2559  => 'The mark was outside the range of the given grade.',
                                               2637  => 'The student\'s result for this unit has previously been finalised.',
                                               3533  => 'The grade must have an accompanying mark.',
                                               6068  => 'The student must be given either a mark or a grade.',
                                               6071  => 'The grade cannot be determined.',
                                               7983  => 'The grade cannot be given as a result for the unit.',
                                               8443  => 'The given grade can only be assigned by the Callista system.',
                                               16262 => 'Marks cannot be recorded for the given grade.',
                                               16282 => 'The mark was rejected because the grade cannot be paired with a mark.',
                                               17466 => 'The teaching period for the unit could not be determined.',
                                               17467 => 'The batch of marks was missing its id number.',
                                               17471 => 'The marks and grades were successfully transferred.',
                                               17472 => 'The web service aborted because of some invalid data. No marks were transferred.',
                                               //17473 can use the default error message
                                               17474 => 'Duplicate marks were found in the transferred data. The transfer was aborted.',
                                               17636 => 'The marks were missing the username of the person transferring the marks.',
                                               99999 => 'Q grade was automatically pre-populated from Callista'
        );
    
    /**
     * Retrieves the user-friendly version of an error message given an error number. If there is no user-friendly version, the default
     * message is returned.
     * @param int $number The error number to get the message for.
     * @param string $default The string to return if there is no custom message for the error number.
     * @return string A message for the error number.
     */
    public static function message_for_error($number, $default) {
        if(array_key_exists($number, ErrorTranslator::$translatedmessages)) {
            return ErrorTranslator::$translatedmessages[$number];
        } else {
            return $default;
        }
    }
}

?>
