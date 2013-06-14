<?php
    class LogEventController {
        private $pdo;
        private $selectMaxTime,
                $insertAccessDataStmt, $deleteAccessDataStmt,
                $insertAccessDataParsedStmt, $deleteAccessDataParsedStmt,
                $insertSearchTermsStmt, $deleteSearchTermsStmt,
                $selectCountry2ByIpStmt;
        
        function __construct (PDO $pdo) {
            $this->pdo = $pdo;
            $this->setupStatements();
        }
        
        
        function removeAll () {
            $this->deleteAccessDataParsedStmt->execute();
            $this->deleteSearchTermsStmt->execute();
            $this->deleteAccessDataStmt->execute();
        }
        
        
        function store (LogEvent $event) {
            
            // Create row in access_data
            
            $this->pdo->beginTransaction();
            $this->insertAccessDataStmt->execute(array(
                'time'      => $event->getDateTimeStr(),
                'ip'        => $event->getIP(),
                'site'      => $event->getRequestedURL(),
                'referrer'  => $event->getReferrer()
            ));
            $eventId = $this->pdo->lastInsertId();
            $event->setId($eventId);
            
            
            // Create rows in access_data_se_term
            
            $seReferrerData = $event->getSEReferrerData();
            if($seReferrerData) {
                foreach($seReferrerData->terms as $term) {
                    $this->insertSearchTermsStmt->execute(array(
                        'id'    => $eventId,
                        'term'  => $term
                    ));
                }
            }
            
            
            // Create row in access_data_parsed
            
            $parsedDate = getdate( $event->getTimestamp() );
            
            $this->insertAccessDataParsedStmt->execute(array(
                'id'                => $eventId,
                'day_of_week'       => $parsedDate['wday'],
                'hour'              => $parsedDate['hours'],
                'referrer_domain'   => $event->getReferrerDomain(),
                'referrer_se'       => $seReferrerData->engine,
                'country'           => $event->getCountry(),
                'ip_institution'    => NULL
            ));
            
            $this->pdo->commit();
        }
        
        function getMaxTimestamp () {
            $this->selectMaxTime->execute();
            $row = $this->selectMaxTime->fetch();
            return $row[0];
        }
        
        
        private function setupStatements () {
            $PDO = $this->pdo;
            
            // Set up table schema data:
            
            $accessDataCols = array(
                'time', 'ip', 'site', 'referrer'
            );
            $accessDataParsedCols = array(
                'id', 'day_of_week', 'hour', 'referrer_domain', 'referrer_se',
                'country', 'ip_institution'
            );
            $accessDataSETermsCols = array(
                'id', 'term'
            );
            
            
            // Prepare statements
            
            $this->selectMaxTime = $PDO->prepare("SELECT max(time) FROM access_data");
            
            $this->insertAccessDataStmt = $this->createInsertStatement('access_data', $accessDataCols);
            $this->deleteAccessDataStmt = $PDO->prepare("DELETE FROM access_data");
            $this->insertAccessDataParsedStmt = $this->createInsertStatement('access_data_parsed', $accessDataParsedCols);
            $this->deleteAccessDataParsedStmt = $PDO->prepare("DELETE FROM access_data_parsed");
            $this->insertSearchTermsStmt = $this->createInsertStatement('access_data_se_terms', $accessDataSETermsCols);
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
    }
?>
