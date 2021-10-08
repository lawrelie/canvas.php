<?php
spl_autoload_register(
    function(string $className): void {
        $filename = sprintf('%s/src/%s.php', __DIR__, strtr($className, '\\', '/'));
        if (file_exists($filename)) {
            require_once $filename;
        }
    },
);
