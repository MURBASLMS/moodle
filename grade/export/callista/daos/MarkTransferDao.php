<?php

/**
 * MarkTransferDao is used to send a batch of marks to the Callista web service and to handle and interpret the response.
 *
 * @copyright  2012 onwards University of New England
 * @author anoble2
 */
class MarkTransferDao {
    //The URI for the wsdl file defining the web service.
    private $wsdllocation;
    
    //A regular expression for the SoapFault thrown when the wsdl file cannot be found.
    private $missingwsdlregex;
    
    function __construct() {
        global $CFG;
        
        $currentwdsl = get_config('gradeexport_callista', 'currentwdsl');
        $this->wsdllocation = $CFG->dirroot . "/grade/export/callista/localWsdl{$currentwdsl}.xml";
        
        $this->missingwsdlregex = '/^SOAP-ERROR: Parsing WSDL: Couldn\'t load from \'.*?\'/';
    }

    
    /**
     * Prepares and sends a batch of marks to the Callista web service using the SoapClient class. Afterwards, the response data
     * is stored in the batch.
     * @param Batch $batch The batch of marks to send.
     */
    public function transfer_batch(Batch $batch) {
        $prsltload = $this->get_soap_prsltload($batch);
        $prsltloadreturn_out = $this->get_soap_prsltloadreturn_out();
        
        try {
            $soapClient = new SoapClient($this->wsdllocation, array('trace' => true,
                                                                    'features' => SOAP_SINGLE_ELEMENT_ARRAYS));
            
            $username = get_config('gradeexport_callista', 'webserviceusername');
            $password = get_config('gradeexport_callista', 'webservicepassword');
            $authenticationNamespace = get_config('gradeexport_callista', 'webserviceauthenticationnamespace');
            //it's important to note that the authentication is basic auth within the soap header, not basic auth on the
            //http request.
            //if there's a username, include basic auth in the soap request. Otherwise omit it, even if there's a password
            if(isset($username) && is_string($username) && $username !== '') {
                if(!isset($password) || !is_string($password)) {
                    $password = '';
                }
                
                /* This structure assumes confidentiality is provided elsewhere,
                 * probably by using HTTPS.
                 * Desired xml structure for the soap header:
                 *  <SOAP-ENV:Header>
                 *      <wsse:Security>
                 *          <wsse:UsernameToken>
                 *              <wsse:Username>[username]</ns3:Username>
                 *              <wsse:Password>[password]</ns3:Password>
                 *          </wsse:UsernameToken>
                 *      </wsse:Security>
                 *  </SOAP-ENV:Header>
                 */
                $usernameSoapVar        = new SoapVar($username,                                    XSD_ANYTYPE,        null, null, 'Username',         $authenticationNamespace);
                $passwordSoapVar        = new SoapVar($password,                                    XSD_ANYTYPE,        null, null, 'Password',         $authenticationNamespace);
                $usernameTokenSoapVar   = new SoapVar(array($usernameSoapVar, $passwordSoapVar),    SOAP_ENC_OBJECT,    null, null, 'UsernameToken',    $authenticationNamespace);
                $securitySoapVar        = new SoapVar(array($usernameTokenSoapVar),                 SOAP_ENC_OBJECT,    null, null, 'Security',         $authenticationNamespace);
                
                $soapHeader = new SoapHeader($authenticationNamespace, 'Security', $securitySoapVar);
                $soapClient->__setSoapHeaders($soapHeader);
            }
        } catch (Exception $exception) {
            if(preg_match($this->missingwsdlregex, $exception->getMessage())) {
                $batch->set_generalerrormessage('The web service definition could not be found.');
            } else {
                $batch->set_generalerrormessage($exception->getMessage());
            }
            
            $batch->set_timewhensent(time());
            $batch->set_status(Batch::STATUS_GENERAL_ERROR);
            return;
        }
        
        try {
            $batch->set_timewhensent(time());
            //Make the call to Callista
            $responseobject = $soapClient->loadResult($prsltload, $prsltloadreturn_out);
            //extract the results
            $this->extract_results_from_response($responseobject, $batch);
            
        } catch (Exception $exception) {
            $batch->set_generalerrormessage($exception->getMessage());
            $batch->set_status(Batch::STATUS_GENERAL_ERROR);
        }
        
        $requestxml = $soapClient->__getLastRequest();
        $batch->set_generatedxml($requestxml);
        $responsexml = $soapClient->__getLastResponse();
        $batch->set_resultsxml($responsexml);
    }
    
    /**
     * Builds a stdClass object from the given batch that the SoapClient can convert into XML matching the schema in the wsdl file
     * for the web service.
     * @param Batch $batch The batch of marks that have to be uploaded to the Callista web service.
     * @return \stdClass A structure corresponding to the schema in the wsdl file.
     */
    private function get_soap_prsltload(Batch $batch) {
        global $CFG;

        $obj = new stdClass();
        $obj->teachAltCd = strtoupper($batch->get_teachingperiodalternatecode());
        $obj->loadedBy = strtoupper($batch->get_loadedbyusername());
        $obj->acadAltCd = strtoupper($batch->get_academicyear());
        $obj->teachCiSeqNumber = null;
        $obj->teachCalType = null;
        $obj->batchId = $batch->get_id();
        $obj->stdntUnitAtmptOutcome = array();
        foreach ($batch->get_marks() as $mark) {
            /* Send Q Grades through normally
            if ($mark->get_grade() == Mark::QGRADE_GRADE && $mark->get_outcomeloadmessagenumber() == Mark::QGRADE_ERRORNO) {
                if ($CFG->debugdeveloper) {
                    error_log("SKIP SENDING: " . $mark->get_studentnumber() . " with Q Grade (" . $mark->get_mark(false) . ")");
                }
                continue;
            }
            */
            $markobj = new stdClass();
            $markobj->courseCd = strtoupper($mark->get_coursecode());
            $markobj->grade = strtoupper($mark->get_grade());
            $markobj->surname = strtoupper($mark->get_studentsurname());
            $markobj->personId = $mark->get_studentnumber();
            $markobj->mark = $mark->get_mark(false, true);
            $markobj->outcomeId = $mark->get_outcomeid();
            $markobj->unitCd = strtoupper($mark->get_unitcode());
            $obj->stdntUnitAtmptOutcome[] = $markobj;
        }
        return $obj;
    }
    
    /**
     * Builds a stdclass object to match the second (unused) parameter the web service defines.
     * @return \stdClass 
     */
    private function get_soap_prsltloadreturn_out() {
        $obj = new stdClass();
        $obj->batchMessageText = null;
        $obj->batchLoadedFlag = null;
        $obj->batchId = null;
        $obj->suaoReturnMessages = array();
        return $obj;
    }
    
    /**
     * Checks the response object created by the SoapClient against the sent batch, and if they match extracts data from the response
     * object to complete the data fields in the batch.
     * @param type $responseobject The object generated by SoapClient's call to the web service.
     * @param Batch $batch The batch to compare the response against and to fill with data from the response.
     */
    private function extract_results_from_response($responseobject, Batch $batch) {
        global $CFG;

        //Check that the response we have received is for the batch we sent
        if($responseobject->batchId === $batch->get_id()) {
            //The batch id matches. Compare the details of the marks sent to the marks received. See if the response is missing any
            //that were sent, has any extra that weren't sent, or whether any have somehow changed.
            
            //Get the marks from the batch. Use the marks' outcome ids as the array key
            $marks = array();
            foreach ($batch->get_marks() as $mark) {
                $marks[$mark->get_outcomeid()] = $mark;
            }
            unset($mark);
            
            //Get the marks from the response. Use the marks' outcome ids as the array key
            $suaoreturnmessages = $responseobject->suaoReturnMessages;
            $markresponses = array();
            if($suaoreturnmessages != NULL) {
                foreach ($suaoreturnmessages->array as $markresponse) {
                    $markresponses[$markresponse->outcomeId] = $markresponse;
                }
                unset($markresponse);
            }
            
            //Separate into four possible groups. All matching is based on the outcome ids.
            //$matchedmarks - marks where all the available details match between the sent marks and marks in the response
            //$sentmarksthatdontmatchreceivedmarks - marks where the outcome id appears in both the sent marks
            //                      and response marks, but some other detail does not match
            //$sentmarksmissingfromresponse - marks that were sent but there is no mark in the response with the
            //                      same outcome id
            //$receivedmarksthatwerenotsent - marks that were in the response, but there was no mark that was sent
            //                      with that outcome id.
            $sentmarksmissingfromresponse = array_diff_key($marks, $markresponses);
            $receivedmarksthatwerenotsent = array_diff_key($markresponses, $marks);
            $marksincommon = array_intersect_key($marks, $markresponses);
            
            $sentmarksthatdontmatchreceivedmarks = array();
            foreach ($marksincommon as $outcomeid => $mark) {
                //Compare every attribute that the sent marks and received response marks have in common
                if($marks[$outcomeid]->get_studentnumber() != $markresponses[$outcomeid]->personId || 
                        strcasecmp($marks[$outcomeid]->get_unitcode(), $markresponses[$outcomeid]->unitCd) != 0 ||
                        strcasecmp($marks[$outcomeid]->get_coursecode(), $markresponses[$outcomeid]->courseCd) != 0) {
                    $sentmarksthatdontmatchreceivedmarks[$outcomeid] = $mark;
                }
            }
            unset($mark);
            $matchedmarks = array_diff_key($marks, $sentmarksthatdontmatchreceivedmarks);
            
            
            $status = Batch::STATUS_SUCCESS;
            $batch->set_batchmessagetext($responseobject->batchMessageText);
            $batch->set_batchloadedflag($responseobject->batchLoadedFlag);
            if($responseobject->batchLoadedFlag != 'TRUE') {
                $status = Batch::STATUS_GENERAL_ERROR;
            }
            
            //If the marks in the response aren't an exact match to the marks sent, record a batch error
            //so the sent and received xml can be examined by a person.
            $generalerror = array();
            if(count($sentmarksmissingfromresponse) > 0) {
                $status = Batch::STATUS_GENERAL_ERROR;
                $generalerror[] = get_string('transfererrormarksmissingfromresponse', 'gradeexport_callista');
            }
            if(count($receivedmarksthatwerenotsent) > 0) {
                $status = Batch::STATUS_GENERAL_ERROR;
                $generalerror[] = get_string('transfererrorextramarksinresponse', 'gradeexport_callista');
            }
            if(count($sentmarksthatdontmatchreceivedmarks) > 0) {
                $status = Batch::STATUS_GENERAL_ERROR;
                $generalerror[] = get_string('transfererroralteredmarks', 'gradeexport_callista');
            }
            $batch->set_generalerrormessage(implode("\n", $generalerror));
            
            foreach ($matchedmarks as $mark) {
                /* Send Q Grades through normally
                if ($mark->get_grade() == Mark::QGRADE_GRADE && $mark->get_outcomeloadmessagenumber() == Mark::QGRADE_ERRORNO) {
                    if ($CFG->debugdeveloper) {
                        error_log("SKIP PROCESSING: " . $mark->get_studentnumber() . " with Q Grade (" . $mark->get_mark(false) . ")");
                    }
                    continue;
                }
                */
                $markresponse = $markresponses[$mark->get_outcomeid()];
                if($markresponse->outcomeLoadedFlag != 'TRUE' && $status != Batch::STATUS_GENERAL_ERROR) {
                    $status = Batch::STATUS_DATA_ERROR;
                }
                $mark->set_outcomeloadedflag($markresponse->outcomeLoadedFlag);
                $mark->set_outcomeloadmessage($markresponse->outcomeLoadMessage);
                if ($mark->get_grade() == Mark::QGRADE_GRADE) {
                    $mark->set_outcomeloadmessagenumber(Mark::QGRADE_ERRORNO);
                } else {
                    $mark->set_outcomeloadmessagenumber($markresponse->outcomeLoadMessageNumber);
                }

            }
            unset($mark);
            
            //I expect a mismatch between what is sent and received to be rare.
            //If one occurs, the sent and received xml can be examined to find any differences.
            
            $batch->set_status($status);
            
        } else {
            //The batch ids don't match. The web service likely responded with a batch that is different to the one 
            //that was sent. Callista will need to be checked to see if the marks were uploaded or not.
            $batch->set_generalerrormessage('The web service\'s response was for a different batch. It\'s uncertain whether the marks were transferred to Callista or not. Please contact Exams and Results to find out.');
            $batch->set_status(Batch::STATUS_GENERAL_ERROR);
        }
    }
}

?>
