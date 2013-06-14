<?php
    require_once dirname(__FILE__) . "/common.php";
    require_once dirname(__FILE__) . "/../config/settings.php";
    
    $PDO = $PDOS['cpl-website'];
    $table = 'website_log';
    
    $PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $parser = new LogParserWebsite($PDO);
    
    
    // Commands
    
    if($argc == 2) {
        $cmd = $argv[1];
        if(strcasecmp($cmd, "help") == 0) {
            echo "Usage: {$argv[0]} [CLEAR|HELP]\n";
            echo "Commands:\n";
            echo "  HELP        Prints this help\n";
            echo "  CLEAR       Wipe all data created by this script from the database\n";
        } else if(strcasecmp($cmd, "clear") == 0) {
            echo "Clearing tables...\n";
            $parser->clearParsedData();
        } else {
            die("Unknown command: $cmd\n");
        }
        exit;
    }
    
    // Iterate over all logged events and parse them
    
    echo "Parsing data...\n";
    $parser->parse($table, function ($progress) {
        echo ($progress > 0 ? "\r" : "") . "> ".intval($progress*100)."%";
    });
        
    echo "\rDone.     \n";
    
?>
