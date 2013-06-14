<?php
    function __autoload ($className) {
        require_once dirname(__FILE__) . "/../classes/$className.class.php";
    }
?>
