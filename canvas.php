<?php
spl_autoload_register(
    function(string $className): void {
        $filename = __DIR__ . "/src/$className.php";
        if (file_exists($filename)) {
            require_once $filename;
        }
    },
);
