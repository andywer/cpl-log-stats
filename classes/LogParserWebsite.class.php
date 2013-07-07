<?php
    class LogParserWebsite extends AbstractLogParser {
        private $selectStmt;

        
        function __construct (PDO $pdo) {
            parent::__construct($pdo);
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
                    $event->parseSiteUrl();
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
            
            $this->controller->flushInsertBuffers();
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
    }
?>
