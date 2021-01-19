<?php

return [
    [
        'name' => "Some long running thing",
        'retries' => 3,
        'autostart' => true
    ],
    [
        'name' => "Some process that keeps on exiting but needs to be running",
        'reties' => -1,
        'autostart' => true
    ]
];