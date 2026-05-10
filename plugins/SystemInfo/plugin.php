<?php

return [
    "slug" => "system-info",
    "name" => "System Info",
    "version" => "1.0.0",
    "namespace"=> "Plugins\\SystemInfo",
    "provider" => Plugins\SystemInfo\SystemInfoServiceProvider::class,
    
    // --- Each route of your plugin must be defined below ---
    "routes"=> [
        [
            "method" => "GET",
            "uri"=> "status",
            "core_middleware"=> ["public"]
        ]
    ]
];
