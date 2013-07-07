<?php
    /**
     *  Class to speed up insertion by managing a queue of insert operations
     *  and doing bulk insertions where possible.
     */
    
    class SQLInsertQueue {
        private $pdo;
        private $tableName, $columnNames;
        private $singleInsertStmt, $bulkInsertStmt;
        private $queue, $size;
        
        function __construct (PDO $pdo, $tableName, $columnNames) {
            $this->pdo = $pdo;
            $this->tableName = $tableName;
            $this->columnNames = $columnNames;
            $this->queue = array();
            $this->size = 25;
            $this->singleInsertStmt = $this->createSingleInsertStmt($tableName, $columnNames);
            $this->bulkInsertStmt = $this->createBulkInsertStmt($tableName, $columnNames, $this->size);
        }
        
        /// @param $values  Associative array storing the values of one row to insert.
        function queueInsertOperation ($values) {
            array_push($this->queue, $values);
            if(count($this->queue) == $this->size) {
                $this->flush();
                $this->queue = array();
            }
        }
        
        
        function flush () {
            if(count($this->queue) == $this->size) {
                $params = array();
                $rowId = 0;
                foreach($this->queue as $rowValues) {
                    foreach($rowValues as $colName=>$colValue) {
                        $params["{$colName}__{$rowId}"] = $colValue;
                    }
                    $rowId++;
                }
                $this->bulkInsertStmt->execute($params);
            } else {
                foreach($this->queue as $rowValues) {
                    $this->singleInsertStmt->execute($rowValues);
                }
            }
        }
        
        
        private function createSingleInsertStmt ($tableName, $colNames) {
            $_colNames = "(" . implode(', ', $colNames) . ")";
            $_colValues = "(:" . implode(', :', $colNames) . ")";
            
            return $this->pdo->prepare("INSERT INTO `$tableName` $_colNames VALUES $_colValues");
        }
        
        private function createBulkInsertStmt ($tableName, $colNames, $bulkSize) {
            $_colNames = "(" . implode(', ', $colNames) . ")";
            
            $colValues = array();
            for($i=0; $i<$bulkSize; $i++) {
                $_values = array();
                foreach($colNames as $colName) {
                    array_push($_values, "{$colName}__{$i}");
                }
                array_push($colValues, "(:" . implode(', :', $_values) . ")");
            }
            
            $_colValues = implode(', ', $colValues);
            return $this->pdo->prepare("INSERT INTO `$tableName` $_colNames VALUES $_colValues");
        }
    }
?>
