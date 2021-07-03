<?php

return [

    // Host to listen on (defaults to 127.0.0.1)
    # 'host' => '0.0.0.0',

    // Port for web interface to listen on (defaults to 8100)
    # 'port' => 8100,
    
    // Verbose logging
    'verbose' => true,
    
    // Tasks available 
    'tasks' => [
        [
            'name' => "Run webpack",
            'retries' => 0,
            'autostart' => true,
            'cmd' => 'sleep 10'
        ],
        [
            'name' => "Some process that keeps on exiting but needs to be running",
            'retries' => -1,
            'autostart' => true,
            'cmd' => 'ping -t 3 8.8.8.8 ; sleep 5'
        ],
        // Run multiple tasks back-to-back 
        [
            'name' => 'Jan-1',
            'after' => 'Jan-2',
            'autostart' => true,
            'cmd' => 'sleep 1',
        ],
        [
            'name' => 'Jan-2',
            'after' => 'Jan-3',
            'cmd' => 'sleep 1',
        ],
        [
            'name' => 'Jan-3',
            'after' => '',
            'cmd' => 'sleep 1',
        ]
    ]
];
