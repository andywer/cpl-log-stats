<?php
    abstract class AbstractLogParser {
        protected $pdo;
        
        function __construct (PDO $pdo) {
            $this->pdo = $pdo;
        }
        
        abstract function clearParsedData ();
        abstract function parse ($tableName);
        
        
        /// @return stdClass:{ engine: "google|...", terms: Array }
        function parseSearchEngineReferrer ($referrer) {
            $parsed = new stdClass;
            $parsed->engine = NULL;
            $parsed->terms = array();
            
            if(preg_match('/[\/\.]google\.[a-z]+ .* [\?&]q=([^&]+)$/ix', $referrer, $matches)) {
                $parsed->engine = "google";
                $queryString = urldecode($matches[1]);
                
                $searchTerms = explode(' ', $queryString);
                array_walk($searchTerms, function(&$term) {
                    $term = trim( preg_replace('/^[^\w]+ | [^\w]$/x', '', $term) );
                    $term = strtolower($term);
                });
                array_filter($searchTerms, function($term) {
                    return $term ? true : false;
                });
                $searchTerms = array_unique($searchTerms);
                
                if(count($searchTerms) == 1 && preg_match('/^http:\/\//i', $searchTerms[0])) {
                    $searchTerms = array();
                }
                $parsed->terms = $searchTerms;
            }
            
            return $parsed;
        }
        
        protected function createInsertStatement ($tableName, $colNames) {
            $_colNames = "(" . implode(', ', $colNames) . ")";
            $_colValues = "(:" . implode(', :', $colNames) . ")";
            
            return $this->pdo->prepare("INSERT INTO `$tableName` $_colNames VALUES $_colValues");
        }
        
        /// @return Instance of DateTime.
        protected function parseSQLDateTime ($dateTimeStr) {
            return DateTime::createFromFormat("Y-m-d H:i:s", $dateTimeStr);
        }
        
        protected function getDomainFromURL ($url) {
            return preg_match('/(http:\/\/)?(www\.)?([^\/]+)/', $url, $matches)
                 ? $matches[3]
                 : $url ;
        }
        
        protected function ensureValidIP ($ip) {
            return (substr_count($ip, '.') == 2)
                 ? "$ip.0"
                 : $ip ;
        }
    }
?>
