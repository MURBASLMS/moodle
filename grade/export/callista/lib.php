<?php
/**
 * @copyright  2012 onwards University of New England
 */

require_once $CFG->dirroot . '/grade/export/callista/services/GradeTransferService.php';

/**
 * A temporary function. Otherwise this code would run every time cron happens. 
 */
function dev_gradeexport_callista_cron() {
    mtrace('I am the callista export CRON in you lib');
    //currently this function is disabled so the cron job doesn't try and execute any actual exports.
}

/**
 * This is the function that the master cron script calls. It causes all queued 
 * batches to be transmitted to Callista.
 */
function gradeexport_callista_cron() {
    mtrace('Sending queued marks to Callista.');
    mtrace('Mark transfer to Callista finished.');
}