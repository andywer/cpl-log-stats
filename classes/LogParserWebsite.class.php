<?php
    class LogParserWebsite extends AbstractLogParser {
        private $controller;
        private $selectStmt;

        
        function __construct (PDO $pdo) {
            parent::__construct($pdo);
            $this->controller = new LogEventController($pdo);
        }
        
        
        function clearParsedData () {
            $this->controller->removeAll();
        }
        
        /**
         *  @param {tableName}
         *      String. Name of the log data table to parse.
         *  @param {progressCallback}
         *      Closure. Optional.
         *      Signature: progressCallback($progress). $progress is a float of range 0..1.
         */
        function parse ($tableName, closure $progressCallback=NULL) {
            $this->setupStatements($tableName);
            
            $selectStmt = $this->selectStmt;
            $selectStmt->execute();
            
            $currentRowNo = 0;
            while($row = $selectStmt->fetch()) {
                // Parse row
                try {
                    $dateTime = $this->parseSQLDateTime($row['time']);
                    $event = new LogEvent(
                        $this->pdo,
                        $dateTime->getTimestamp(),
                        $row['ip'],
                        $row['site'],
                        $row['referrer']
                    );
                    $event->lookupCountry($this->controller);
                    $event->parseReferrerData();
                    $this->controller->store($event);
                } catch(PDOException $e) {
                    print_r($row);
                    throw $e;
                }
                
                // Print progess
                if(($currentRowNo % 400) == 0 && $progressCallback) {
                    $progressCallback($currentRowNo / $selectStmt->rowCount());
                }
                $currentRowNo++;
            }
            
            if($progressCallback) {
                $progressCallback(1);
            }
        }
        
        
        private function setupStatements ($tableName) {
            $minTime = $this->controller->getMaxTimestamp();
            $whereClause = $minTime
                         ? "WHERE time > '$minTime'"
                         : "" ;
            $this->selectStmt = $this->pdo->prepare("SELECT * FROM $tableName $whereClause ORDER BY time ASC");
        }
        
        private function getParsedMaxTimestamp () {
            $stmt = $this->pdo->prepare("SELECT max(time) FROM access_data");
        }
    }
?>
