<?php
    class LogEvent {
        private $pdo, $id, $yetStored;
        private $timestamp, $ip, $url, $referrer;
        private $country, $requestedSiteData, $seReferrerData;
        
        private $insertAccessDataStmt, $insertAccessDataParsedStmt,
                $insertSearchTermsStmt;
        
        
        function __construct (PDO $pdo, $timestamp, $ip, $url, $referrer) {
            $this->pdo = $pdo;
            $this->yetStored = false;
            
            $this->timestamp = $timestamp;
            $this->ip = self::ensureValidIP($ip);
            $this->url = $url;
            $this->referrer = $referrer;
            
            $parsed = new stdClass;
            $parsed->prof = NULL;
            $parsed->faculty = NULL;
            $parsed->timeFrom = NULL;
            $parsed->timeTo = NULL;
            $parsed->epocheRequest = NULL;
            $this->requestedSiteData = $parsed;
            
            $parsed = new stdClass;
            $parsed->engine = NULL;
            $parsed->terms = array();
            $this->seReferrerData = $parsed;
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
        
        function getUrlData () {
            return $this->requestedSiteData;
        }
        
        /// @return stdClass:{ engine: "google|...", terms: Array }
        function getSEReferrerData () {
            return $this->seReferrerData;
        }
        
        function parseSiteUrl () {
            $parsed = $this->requestedSiteData;
            if(preg_match('/\/unigeschichte\/professorenkatalog\/leipzig\/([^\/]+)/', $this->url, $matches)) {
                $id = preg_replace('/\.html$/', '', $matches[1]);
                $parsed->prof = $id;
            } else if(preg_match('/\/unigeschichte\/professorenkatalog\/fak\/([^\/]+)/', $this->url, $matches)) {
                $parsed->faculty = $matches[1];
            } else if(preg_match('/\/unigeschichte\/professorenkatalog\/(epoche|Zeitraum)\/([^\/]+)/', $this->url, $matches)) {
                $period = $matches[2];
                $x = strpos($period, '-');
                $timeFrom = intval(substr($period, 0, $x));
                $timeTo   = intval(substr($period, $x+1));
                if($timeFrom > 1000 && $timeTo > 1000) {
                    $parsed->timeFrom = $timeFrom;
                    $parsed->timeTo = $timeTo;
                    $parsed->epocheRequest = strtolower($matches[1])=="epoche" ? true : false;
                }
            }
        }
        
        function parseReferrerData () {
            $parsed = $this->seReferrerData;
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
        }
        
        
        private static function ensureValidIP ($ip) {
            return (substr_count($ip, '.') == 2)
                 ? "$ip.0"
                 : $ip ;
        }
    }
?>
