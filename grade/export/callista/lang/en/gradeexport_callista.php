<?php

/**
 * Strings for component 'gradeexport_callista', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   gradeexport_callista
 * @copyright  2012 onwards University of New England
 */

$string['pluginname'] = 'Callista Grade Export';
$string['callista:publish'] = 'Publish grade export to Callista';
$string['callista:view'] = 'Use grade export to Callista';
$string['callista:viewdebugdata'] = 'Show extra Callista debugging information';
$string['defaultroleid'] = 'Default Role for notifications';
$string['defaultroleid_help'] = 'Default Role for notifications once an export to Callista has completed for a course';
$string['currentwdsl'] = 'Which WSDL to use';
$string['currentwdsl_help'] = 'Please pick which WSDL for Prod / Stage or Default for all or Test / Dev environments';
$string['adminnotificationemail'] = 'Admin notification email';
$string['adminnotificationemail_help'] = 'Also send notifications to this email once an export to Callista has completed for a course';

$string['webserviceusername'] = 'Web service username';
$string['webserviceusernamedescription'] = 'The username to authenticate to the web service as.';
$string['webservicepassword'] = 'Web service password';
$string['webservicepassworddescription'] = 'The password to authenticate to the web service as.';
$string['webserviceauthenticationnamespace'] = 'Web service authentication XML namespace';
$string['webserviceauthenticationnamespacedescription'] = 'The namespace to give to the authentication XML nodes when calling the web service.';

$string['exporttitle'] = 'Export Grades to Callista';
$string['unit'] = 'Unit';
$string['instructionheading'] = 'Instructions';
$string['instructionexpandalttext'] = 'Expand instructions';
$string['instructioncollapsealttext'] = 'Collapse instructions';
$string['marksandgradesheading'] = 'Marks and Grades';

$string['errorstillsending'] = 'Export batches that haven\'t sent successfully have been marked as failed';

$string['errorcoursetotal'] = '<br /><br />\'Callista Grade Export\' cannot be used as the Gradebook Course Total does not equal 100.<br /><br />Please return to the Gradebook and correct the Gradebook set up.';

//Text for the entries in the colour coding key
$string['keytitle'] = 'Key';
$string['keymalformed'] = 'Incorrect data format';
$string['keymalformed_help'] = 'An override highlighted with this will not be able to be stored in the database.<br />'
                             . 'A unit mark or grade highlighted with this will need an overriding mark or grade to be given.';
$string['keyoverridden'] = 'Overridden';
$string['keyoverridden_help'] = 'This mark or grade is being overriden.';
$string['keymanuallyoverridden'] = 'Manually overridden';
$string['keymanuallyoverridden_help'] = 'This mark or grade is being manually overriden.';
$string['keyautooverridden'] = 'Automatically overridden';
$string['keyautooverridden_help'] = 'This mark or grade is being automatically overriden.';
$string['keyenrolment'] = 'Enrolled after marks set';
$string['keyenrolment_help'] = 'This student enrolled after the marks and grades were set. They will not be part of the transfer.';
$string['keyunenrolment'] = 'Unenrolled after marks set';
$string['keyunenrolment_help'] = 'This student became unenrolled after the marks and grades were set. However they will be part of '
                               . 'the transfer. It is likely Callista will reject this entry at that time.';
$string['keysuccessful'] = 'Successful';
$string['keysuccessful_help'] = 'This student\'s mark and grade were accepted by Callista without any error.';
$string['keywarning'] = 'Warning';
$string['keywarning_help'] = 'This student\'s mark and grade were accepted by Callista, but it raised a problem.';
$string['keyfailed'] = 'Failed';
$string['keyfailed_help'] = 'This student\'s mark and grade were rejected by Callista.';
$string['keyrejectedlasttime'] = 'Rejected last time';
$string['keyrejectedlasttime_help'] = 'If this unit\'s marks have been sent before, there was an error the last time this student\'s '
                                    . 'mark and grade were sent.';

//Text for the table of marks
$string['studentnumber'] = 'Student<br />number';
$string['firstnamecolumn'] = 'First<br />name';
$string['unitcode'] = 'Unit';
$string['offering'] = 'Offering';
$string['mark'] = 'Mark';
$string['markoverride'] = 'Mark<br />override';
$string['gradeoverride'] = 'Grade<br />override';
$string['gradeglossary'] = 'Glossary';
$string['errormessagecolumn'] = 'Error<br />message';
$string['gradeglossary_help'] = '<table>'
                              . '<tr><td>Normal</td><td>HD</td>'
                              . '<td>High Distinction (80-100%)</td></tr>'
                              . '<tr><td>&nbsp;</td><td>D</td>'
                              . '<td>Distinction (70-79%)</td></tr>'
                              . '<tr><td>&nbsp;</td><td>C</td>'
                              . '<td>Credit (60-69%)</td></tr>'
                              . '<tr><td>&nbsp;</td><td>P</td>'
                              . '<td>Pass (50-59%)</td></tr>'
                              . '<tr><td>&nbsp;</td><td>N</td>'
                              . '<td>Fail (0-49%)</td></tr>'
                              . '<tr><td colspan=3>&nbsp;</td></tr>'
                              . '<tr><td>Deferred</td><td>SA</td>'
                              . '<td>Supplementary Assessment</td></tr>'
                              . '<tr><td>&nbsp;</td><td>SX</td>'
                              . '<td>Supplementary Exam</td></tr>'
                              . '<tr><td>&nbsp;</td><td>NA</td>'
                              . '<td>Not Available</td></tr>'
                              . '<tr><td>&nbsp;</td><td colspan=2>For Q grade overrides use NA</td></tr>'
                              . '<tr><td colspan=5>&nbsp;</td></tr>'
                              . '<tr><td>Other</td><td>DNS</td>'
                              . '<td>Fail, Did Not Submit</td></tr>'
                              . '</table>';

$string['archive'] = 'batch history';
$string['debugtabledata'] = 'debug table data';


//OverridePage
$string['overridepagesavedatabaseerror'] = 'There was an error storing the results in the database. No changes have been saved.';
$string['overridepagesavestatuserror'] = 'The marks could not be saved because the batch was not in a state that allows saving. '
                                       . 'For instance, somebody else may have queued the marks to be sent while you have been working on them.';
$string['overridepagemalformederror'] = 'The Save could not be performed as the \'Mark override\' column '
                                      . 'contains one or more invalid entries. The entries with an \'Incorrect '
                                      . 'data format\' have been highlighted.';
$string['overridepagequeuingdatabaseerror'] = 'There was an error when queuing the marks to be sent. The marks have been saved, but '
                                            . 'are not in the queue. Please try again, and if it fails, contact the BAS.';
$string['overridepagequeuestatuserror'] = 'The marks could not be queued for sending because the batch was not in a state that allows '
                                        . 'queuing. For instance, somebody else may have queued the marks already while you have '
                                        . 'been working on them.';
$string['overridepageemptymarkserror'] = 'There is at least one student who does not have a mark from the Gradebook and has not been '
                                      . 'given an overriding mark.';
$string['overridepageemptygradeserror'] = 'There is at least one student who does not have a grade derived from the Gradebook and '
                                       . 'has not been given an overriding grade.';
$string['overridepageemptymarksandgradeserror'] = 'There is at least one student  who does not have a mark from the Gradebook and '
                                                . 'has not been given an overriding mark, and at least one student who does not have '
                                                . 'a grade derived from the Gradebook and has not been given an overriding grade.';
$string['overridepagenonexistentbatcherror'] = 'The batch of marks you are trying to queue should exist, but doesn\'t. Please try '
                                             . 'saving or saving and sending again.';
$string['overridepageinstructions'] = '\n\n<span style="text-decoration: underline">Introduction...</span>\n\n'
                                    . 'This screen is the ‘Grades Transfer Input screen’. This screen allows marks and '
                                    . 'grades for {$a->unitname} to be sent from Moodle to Callista. Once sent, the Results '
                                    . 'Office will check the marks and grades before they are formally approved.\n\n'
                                    . 'The marks from Gradebook are automatically loaded into the ‘Mark’ column on this '
                                    . 'screen. The values in the ‘Grade’ column are derived from those in the ‘Mark’ '
                                    . 'column. The ‘Grade’ column will hold one of HD, D, C, P or N. If the Gradebook mark '
                                    . 'is missing for a student then both the Mark & Grade columns will be highlighted. In '
                                    . 'such cases a mark and / or grade override must be entered - see further below.\n\n'
                                    . '<span style="text-decoration: underline">Q grades...</span>\n\n'
                                    . 'If a student has a Q grade recorded on Callista it will automatically be shown on '
                                    . 'this screen. The Q grade will not be able to be overridden. It will not be '
                                    . 'transferred back to Callista, it will be shown on this screen for information '
                                    . 'purposes only.\n\n'
                                    . 'If it is believed a student should have a Q grade but one is not shown then the '
                                    . 'Unit Co-Ordinator should enter the grade as NA and the student concerned should '
                                    . 'contact the Results Office.\n\n'
                                    . '<span style="text-decoration: underline">Automatic overrides...</span>\n\n'
                                    . 'If a mark from Gradebook has a non-integer value it will be automatically rounded '
                                    . 'up or down. Any automatic rounding up of a mark may infer an automatic changing of '
                                    . 'grade. Any automatically changed marks / grades will be shown in the ‘Mark '
                                    . 'override’ & ‘Grade override’ columns and will be highlighted in green. The original '
                                    . 'Gradebook marks & grades will still be shown in the ‘Mark’ & ‘Grade’ columns. Any '
                                    . 'automatic overrides can subsequently be manually overridden if required.\n\n'
                                    . '<span style="text-decoration: underline">Manual overrides...</span>\n\n'
                                    . 'If it is necessary to award a different mark for a student then the ‘Mark '
                                    . 'override’ column can be used to do that by either selecting the \'No mark\' '
                                    . 'value in the drop-down box in that column or by entering an integer in the '
                                    . 'range 0 to 100.\n\n'
                                    . 'If it is necessary to award a different grade for a student then the ‘Grade '
                                    . 'override’ column can be used to do that by selecting the desired override grade from '
                                    . 'the drop-down box in that column. The override grades available are HD, D, C, P, N, '
                                    . 'SA, SX, NA & DNS.\n\n'
                                    . 'Note that the two override columns, ‘Mark override’ and ‘Grade override’, are '
                                    . 'independent of each other and giving a ‘Mark override’ will not infer a matching '
                                    . '‘Grade override’.\n\n'
                                    . '<span style="text-decoration: underline">‘Save’ & ‘Cancel’...</span>\n\n'
                                    . 'Clicking the ‘Save’ button at the top or bottom of this screen will save the '
                                    . 'current marks, grades and any overrides. Further handling of the entries can be '
                                    . 'performed by visiting this same screen at a later time / date.\n\n'
                                    . 'Clicking the ‘Cancel’ button at the top or bottom of this screen will discard any '
                                    . 'changes since the last ‘Save’ and return control to the unit home page.\n\n'
                                    . '<span style="text-decoration: underline">Transfer to Callista...</span>\n\n'
                                    . 'In order to actually transfer the marks and grades to Callista there is a two '
                                    . 'stage process… Confirmation then Submission. When the marks and grades are ready to '
                                    . 'be confirmed it is necessary to click the ‘Save and Confirm’ button at the top or '
                                    . 'bottom of this screen. On successfully confirming the marks the ‘Grades Transfer '
                                    . 'Submission screen’ will appear allowing for the submission of the marks and grades '
                                    . 'to Callista – that screen has its own set of instructions.\n\n'
                                    . '<span style="text-decoration: underline">Sorting...</span>\n\n'
                                    . 'The data on this screen can be sorted in ascending or descending order by '
                                    . 'clicking on any column heading except ‘Glossary’. The order alternates with repeated '
                                    . 'clicks.';
$string['overridepagesave'] = 'Save';
$string['overridepagesend'] = 'Save and send';

//QueuedPage
$string['queuedpageinstructions'] = 'This screen is the &lsquo;Grades Transfer Submission screen&rsquo;. This screen '
                                  . 'shows the marks and grades which have been confirmed and are '
                                  . 'therefore ready for submission / transfer to Callista.\n'
                                  . 'Wherever an overriding mark or grade has been entered for a student '
                                  . 'that override will be highlighted as per the key at the top right '
                                  . 'corner of this screen.\n'
                                  . 'Clicking the &lsquo;Cancel&rsquo; button at the top or bottom of this screen '
                                  . 'will reject the confirmation of the marks and return control to the '
                                  . '&lsquo;Grades Transfer Input screen&rsquo; where marks and grades can be '
                                  . 'overridden.\n'
                                  . 'Clicking the &lsquo;Submit to Callista&rsquo; button at the top or bottom of '
                                  . 'this screen will transfer the marks and grades to Callista and '
                                  . 'control will pass to the &lsquo;Grades Transfer Results screen&rsquo;.\n'
                                  . 'Also, the data on this screen can be sorted in ascending or '
                                  . 'descending order by clicking on any column heading except '
                                  . '&lsquo;Glossary&rsquo;. The order alternates with repeated clicks.\n';
$string['queuedpagedequeuedatabaseerror'] = 'There was an error removing the results from the queue. They are still queued.';
$string['queuedpagedequeuestatuserror'] = 'The marks could not be removed from the queue because they are no longer in the queue. '
                                        . 'It is likely they are being sent or have just been sent.';
$string['queuedpagenonexistentbatcherror'] = 'The batch of marks you are trying to remove from the queue should exist, but doesn\'t. '
                                           . 'Nothing will be sent to Callista because it doesn\'t exist.';
$string['queuedpagedequeue'] = 'Cancel sending';
$string['queuedpageemulatecron'] = 'Emulate cron run for this unit';

//TransferResultsPage
$string['markstransferrednone'] = 'There were no marks to transfer for {$a}.';
$string['markstransferrednoerrorssingle'] = '1 mark was transferred to Callista for {$a}.';
$string['markstransferrednoerrorsmultiple'] = '{$a->successful} marks were transferred to Callista for {$a->unitname}.';
$string['markstransferrednosuccesses'] = 'No marks were successfully transferred.';
$string['markstransferredonesuccess'] = '1 mark was successfully transferred.';
$string['markstransferredmultiplesuccesses'] = '{$a} marks were successfully transferred.';
$string['markstransferrednowarnings'] = 'There were no warnings.';
$string['markstransferredonewarning'] = 'There was 1 warning.';
$string['markstransferredmultiplewarnings'] = 'There were {$a} warnings.';
$string['markstransferrednoerrors'] = 'There were no errors.';
$string['markstransferredoneerror'] = 'There was 1 error.';
$string['markstransferredmultipleerrors'] = 'There were {$a} errors.';
$string['transferpageinstructions'] = 'This screen is the &lsquo;Grades Transfer Results screen&rsquo;. This screen '
                                    . 'shows whether the marks and grades submitted to Callista were '
                                    . 'accepted or not.\n'
                                    . 'The success or otherwise of each individual mark and grade '
                                    . 'submission can be determined by using the colour coding and the key '
                                    . 'in the top right corner of this screen. Any erroneous submissions '
                                    . 'will be highlighted in red and will by default be listed at the top '
                                    . 'of the screen with an appropriate entry in the &lsquo;Error message&rsquo; '
                                    . 'column.\n'
                                    . 'If it is necessary to correct any marks and/or grades and submit '
                                    . 'those corrections to Callista then that can be achieved by clicking '
                                    . 'the &lsquo;New Grades Transfer&rsquo; button – doing so will return control to '
                                    . 'the &lsquo;Grades Transfer Input screen&rsquo;. Any overrides which were '
                                    . 'entered in this latest submission to Callista will be retained and '
                                    . 'can be modified on the input screen.\n'
                                    . 'Also, the data on this screen can be sorted in ascending or '
                                    . 'descending order by clicking on any column heading except '
                                    . '&lsquo;Glossary&rsquo;. The order alternates with repeated clicks.\n';
$string['transferpageresetdatabaseerror'] = 'There was an error creating the new batch of marks. No changes have been made.';
$string['transferpageresetstatuserror'] = 'A new set of marks can only be based on an old set of marks where an attempt has been made '
                                        . 'to send them to Callista.';
$string['transferpagenonexistentbatcherror'] = 'The batch of marks you are trying to duplicate should exist, but doesn\'t. The new '
                                             . 'batch has not been created.';
$string['transferpagestartnewbatch'] = 'Start a new batch based on this batch';
$string['webserviceerrorstartnewbatch'] = 'Start a new batch based on this batch';

//WebServiceErrorPage
$string['wsepageintro'] = 'There was an error when sending the marks to Callista. The error message was:';
$string['wsebatchloaded'] = 'Some marks were transferred';
$string['wsebatchnotloaded'] = 'No marks were transferred.';

//UnknownErrorPage
$string['unknownerrormessagewithoutbatch'] = 'There was an unknown error while sending the marks for {$a} to Callista. The batch '
                                           . 'number for the marks could not be determined. Please contact the BAS so the '
                                           . 'error can be investigated.';
$string['unknownerrormessagewithbatch'] = 'There was an unknown error while sending the marks for {$a->shortname} to Callista. The '
                                        . 'batch number for the marks is {$a->batchid}. Please provide the batch number the Service '
                                        . 'Desk so the error can be investigated.';
$string['servicedesk'] = 'BAS';
$string['servicedeskphonelabel'] = 'Phone:';
$string['servicedeskphone'] = 'XT 2000';
$string['servicedeskemaillabel'] = 'Email:';
$string['servicedeskemail'] = 'ITSLMSTech@murdoch.edu.au';

//TransferredBatchesPage
$string['batchtableheading'] = 'Batches of Marks';
$string['columnheadinglastsaved'] = 'Time of last save';
$string['columnheadingstatus'] = 'Status';
$string['columnheadinggotolink'] = 'Link';
$string['batchstatusinitial'] = 'The batch has been saved but not queued to be transferred.';
$string['batchstatusqueued'] = 'The batch has been saved and queued for transfer to Callista.';
$string['batchstatussending'] = 'The batch of marks is being sent to Callista now.';
$string['batchstatussuccessful'] = 'All the marks in the batch have been successfully transferred.';
$string['batchstatusdataerror'] = 'The batch was transferred, but at least some of the marks caused errors.';
$string['batchstatusgeneralerror'] = 'The batch failed. No marks were transferred.';
$string['batchstatusunknown'] = 'An unknown error occurred. No marks were transferred.';
$string['batchview'] = 'View batch';
$string['batchlinkalttext'] = 'View this batch';

//Batch transfer error strings for when the sent marks don't match the marks in the response
$string['transfererrormarksmissingfromresponse'] = 'Callista has indicated it did not receive all of the marks sent by Moodle. There were {$a} missing marks. Please contact Results so this can be sorted out.';
$string['transfererrorextramarksinresponse'] = 'Callista has indicated it received marks that were not sent by Moodle. There were {$a} extra marks. Please contact Results so this can be sorted out.';
$string['transfererroralteredmarks'] = 'The details of some marks appear to have changed during transmission. There were {$a} altered details. Please contact Results so this can be sorted out.';

//DebugTableDataPage
$string['debugtabledatapageinstructions'] = 'You can expand and contract rows by clicking on the + or - in the columns on the left or right.';
$string['debugtabledatapagebatchesheading'] = 'Batch Records';
$string['debugtabledatapagenobatches'] = 'There are no batch records for this unit.';
$string['debugtabledatapagemarksheading'] = 'Mark Records';
$string['debugtabledatapagenomarks'] = 'You can expand and contract rows by clicking on the + or - in the columns on the left or right.';
