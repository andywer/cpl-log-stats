<?php
    $mysqlOptions = array(
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
    );
    
    $PDOS = array(
        'cpl-website'
            => new PDO('mysql:dbname=cpl-website;host=localhost', 'cpl', 'cpl', $mysqlOptions),
        'cpl-linkedhistory'
            => new PDO('mysql:dbname=cpl-linkedhistory;host=localhost', 'cpl', 'cpl', $mysqlOptions)
    );
?>
