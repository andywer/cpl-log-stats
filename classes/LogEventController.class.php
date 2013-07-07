<?php
    class LogEventController {
        private $pdo, $nextAccessDataId;
        private $selectMaxIdStmt,
                $selectMaxTimeStmt,
                $deleteAccessDataStmt,
                $deleteAccessDataParsedStmt,
                $deleteSearchTermsStmt,
                $selectCountry2ByIpStmt;
        
        private $insertAccessDataQueue,
                $insertAccessDataParsedQueue,
                $insertSearchTermsQueue;
        
        
        function __construct (PDO $pdo) {
            $this->pdo = $pdo;
            $this->setupStatements();
            $this->nextAccessDataId = $this->getMaxId()+1;
        }
        
        
        function removeAll () {
            $this->deleteAccessDataParsedStmt->execute();
            $this->deleteSearchTermsStmt->execute();
            $this->deleteAccessDataStmt->execute();
        }
        
        
        function store (LogEvent $event) {
            
            // Create row in access_data
            
            $this->insertAccessDataQueue->queueInsertOperation(array(
                'time'      => $event->getDateTimeStr(),
                'ip'        => $event->getIP(),
                'site'      => $event->getRequestedURL(),
                'referrer'  => $event->getReferrer()
            ));
            $eventId = $this->nextAccessDataId++;
            $event->setId($eventId);
            
            
            // Create rows in access_data_se_term
            
            $seReferrerData = $event->getSEReferrerData();
            if($seReferrerData) {
                foreach($seReferrerData->terms as $term) {
                    $this->insertSearchTermsQueue->queueInsertOperation(array(
                        'id'    => $eventId,
                        'term'  => $term
                    ));
                }
            }
            
            
            // Create row in access_data_parsed
            
            $parsedDate = getdate( $event->getTimestamp() );
            $urlData = $event->getUrlData();
            
            $this->insertAccessDataParsedQueue->queueInsertOperation(array(
                'id'                => $eventId,
                'day_of_week'       => $parsedDate['wday'],
                'hour'              => $parsedDate['hours'],
                'accessed_prof'     => $urlData->prof,
                'accessed_time_from'=> $urlData->timeFrom,
                'accessed_time_to'  => $urlData->timeTo,
                'epoche_request'    => $urlData->epocheRequest,
                'accessed_faculty'  => $urlData->faculty,
                'referrer_domain'   => $event->getReferrerDomain(),
                'referrer_se'       => $seReferrerData->engine,
                'country'           => $event->getCountry()
            ));
        }
        
        function flushInsertBuffers () {
            $this->insertAccessDataQueue->flush();
            $this->insertAccessDataParsedQueue->flush();
            $this->insertSearchTermsQueue->flush();
        }
        
        function getMaxId () {
            $this->selectMaxIdStmt->execute();
            $row = $this->selectMaxIdStmt->fetch();
            return $row[0];
        }
        
        function getMaxTimestamp () {
            $this->selectMaxTimeStmt->execute();
            $row = $this->selectMaxTimeStmt->fetch();
            return $row[0];
        }
        
        
        private function setupStatements () {
            $PDO = $this->pdo;
            
            // Set up table schema data:
            
            $accessDataCols = array(
                'time', 'ip', 'site', 'referrer'
            );
            $accessDataParsedCols = array(
                'id', 'day_of_week', 'hour', 'accessed_prof',
                'accessed_time_from', 'accessed_time_to', 'epoche_request',
                'accessed_faculty', 'referrer_domain', 'referrer_se',
                'country'
            );
            $accessDataSETermsCols = array(
                'id', 'term'
            );
            
            
            // Set up insert queues:
            
            $this->insertAccessDataQueue = new SQLInsertQueue($PDO, 'access_data', $accessDataCols);
            $this->insertAccessDataParsedQueue = new SQLInsertQueue($PDO, 'access_data_parsed', $accessDataParsedCols);
            $this->insertSearchTermsQueue = new SQLInsertQueue($PDO, 'access_data_se_terms', $accessDataSETermsCols);
            
            
            // Prepare statements
            $this->selectMaxIdStmt = $PDO->prepare("SELECT max(id) FROM access_data");
            $this->selectMaxTimeStmt = $PDO->prepare("SELECT max(time) FROM access_data");
            
            $this->deleteAccessDataStmt = $PDO->prepare("DELETE FROM access_data");
            $this->deleteAccessDataParsedStmt = $PDO->prepare("DELETE FROM access_data_parsed");
            $this->deleteSearchTermsStmt = $PDO->prepare("DELETE FROM access_data_se_terms");
            
            $this->selectCountry2ByIpStmt = $PDO->prepare("SELECT country2 FROM ipv4_country WHERE start_ip <= :ip AND end_ip >= :ip");
        }
        
        /// @return String. 2-letter country code.
        function getCountryByIP ($ip) {
            $longIP = ip2long($ip);
            
            $this->selectCountry2ByIpStmt->execute(array( 'ip' => $longIP ));
            $result = $this->selectCountry2ByIpStmt->fetch();
            return $result['country2'];
        }
        
        private function createInsertStatement ($tableName, $colNames) {
            $_colNames = "(" . implode(', ', $colNames) . ")";
            $_colValues = "(:" . implode(', :', $colNames) . ")";
            
            return $this->pdo->prepare("INSERT INTO `$tableName` $_colNames VALUES $_colValues");
        }
        
        private function createBulkInsertStatement ($tableName, $colNames, $bulkSize) {
            $_colNames = "(" . implode(', ', $colNames) . ")";
            
            $colValues = array();
            for($i=0; $i<$bulkSize; $i++) {
                $_values = array();
                foreach($colNames as $colName) {
                    array_push($_values, "{$colName}_{$i}");
                }
                array_push($colValues, "(:" . implode(', :', $_values) . ")");
            }
            
            $_colValues = implode(', ', $colValues);
            return $this->pdo->prepare("INSERT INTO `$tableName` $_colNames VALUES $_colValues");
        }
    }
?>
