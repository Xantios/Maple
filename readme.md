<p align="center">
    <img src="https://raw.githubusercontent.com/Xantios/Maple/master/src/ui/logo.svg" alt="Maple Logo">
</p>

## Welcome to Maple!

Maple providers the task runner you always wanted but never dared to ask for. 

## NOTICE
Maple is current in active development (say alpha state) production use is **NOT** yet recommend

## Configuration

The configuration file is a plain PHP array

```php
<?php

return [
    // Port for web interface to listen on
    'port' => 8100,
    
    // Verbose logging
    'verbose' => true,
    
    // Tasks available 
    'tasks' => [
        [
            'name' => "Run webpack",
            'retries' => 0,
            'autostart' => true,
            'cmd' => 'npm run dev'
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
```

## Running

```bash
./maple
```

## Webinterface

There is a dashboard available at `http://localhost:8100`

<img src="src/ui/screenshot.png" alt="Screenshot" style="float:left; margin-right: 10px; "/>

## Contributing in code

Thank you for considering contributing, feel free to PR anything you like or create an issue if you think you have a great idea!

## Contributing in other means

Thank you emails, donations, photos of how your life changed by a piece of software and bags of coffee beans are welcome at <git@xantios.nl>

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Xantios Krugor <git@xantios.nl> All security vulnerabilities will be promptly addressed.

## License

Maple is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
