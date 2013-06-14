<?php
    class LogEvent {
        private $pdo, $id, $yetStored;
        private $timestamp, $ip, $url, $referrer;
        private $country, $seReferrerData;
        
        private $insertAccessDataStmt, $insertAccessDataParsedStmt,
                $insertSearchTermsStmt;
        
        
        function __construct (PDO $pdo, $timestamp, $ip, $url, $referrer) {
            $this->pdo = $pdo;
            $this->yetStored = false;
            
            $this->timestamp = $timestamp;
            $this->ip = self::ensureValidIP($ip);
            $this->url = $url;
            $this->referrer = $referrer;
        }
        
        function getId () {
            return $this->id;
        }
        
        function setId ($id) {
            $this->id = $id;
        }
        
        function getTimestamp () {
            return $this->timestamp;
        }
        
        function getDateTimeStr () {
            return date("Y-m-d H:i:s", $this->timestamp);
        }
        
        function getIP () {
            return $this->ip;
        }
        
        function getRequestedURL () {
            return $this->url;
        }
        
        function getReferrer () {
            return $this->referrer;
        }
        
        function getReferrerDomain () {
            return preg_match('/(http:\/\/)?(www\.)?([^\/]+)/', $this->referrer, $matches)
                 ? $matches[3]
                 : $this->referrer ;
        }
        
        /// @return String. 2-letter country code.
        function getCountry () {
            return $this->country;
        }
        
        function lookupCountry (LogEventController $controller) {
            $this->country = $controller->getCountryByIP($this->ip);
        }
        
        /// @return stdClass:{ engine: "google|...", terms: Array }
        function getSEReferrerData () {
            return $this->seReferrerData;
        }
        
        function parseReferrerData () {
            $parsed = new stdClass;
            $parsed->engine = NULL;
            $parsed->terms = array();
            
            if(preg_match('/[\/\.]google\.[a-z]+ .* [\?&]q=([^&]+)$/ix', $this->referrer, $matches)) {
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
            
            $this->seReferrerData = $parsed;
        }
        
        
        private static function ensureValidIP ($ip) {
            return (substr_count($ip, '.') == 2)
                 ? "$ip.0"
                 : $ip ;
        }
    }
?>
