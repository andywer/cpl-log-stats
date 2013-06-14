<?php
    require_once dirname(__FILE__) . "/../config/settings.php";
    
    // Init DB connection
    
    $PDO = $PDOS['cpl-website'];
    $table = "ipv4_country";
    
    $insertStmt = $PDO->prepare("INSERT INTO `$table` (start_ip, end_ip, country2) VALUES (:start_ip, :end_ip, :country2)");
    $deleteStmt = $PDO->prepare("DELETE FROM `$table`");
    
    
    // Parse command line arguments
    
    if($argc != 2) {
        die("Usage: {$argv[0]} <path/to/IpToCountry.csv>\n");
    }
    
    $csvFilePath = $argv[1];
    
    echo "Clearing table...\n";
    $deleteStmt->execute();
    
    echo "Parsing file...\n";
    echo "> 0%";
    parseFile($csvFilePath, function($progress) {
        echo "\r> ".intval($progress*100)."%";
    });
    
    echo "\rDone.\n";
    exit;
    
    
    function parseFile ($csvFilePath, closure $progressCallback) {
        if(!is_file($csvFilePath)) {
            throw new Exception("File not found: $csvFilePath\n");
        }
        
        $fileHandle = fopen($csvFilePath, "r");
        $fileSize = filesize($csvFilePath);
        while( ! feof($fileHandle) ) {
            // Read line
            $line = fgets($fileHandle);
            if($line[0] == '#') {
                continue;
            }
            
            // Split line into columns & remove quotes
            $cols = explode(',', $line);
            if(count($cols) < 7) {
                continue;
            }
            
            array_walk($cols, function(&$col) {
                if($col[0] == '"') {
                    $col = substr($col, 1, -1);
                }
            });
            
            // Parse columns
            parseColumns($cols);
            
            // Progress callback
            $progressCallback( ftell($fileHandle) / $fileSize );
        }
        fclose($fileHandle);
    }
    
    function parseColumns ($cols) {
        global $PDO, $insertStmt;
        
        $ipStart = intval($cols[0]);
        $ipEnd   = intval($cols[1]);
        $country2 = $cols[4];
        
        if($country2 == "ZZ") {
            // Reserved IP range
            return;
        }
        
        $insertStmt->execute(array(
            'start_ip'  => $ipStart,
            'end_ip'    => $ipEnd,
            'country2'  => $country2
        ));
    }
?>
