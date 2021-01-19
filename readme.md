<p align="center">
<?xml version="1.0" encoding="UTF-8"?>
<svg width="" height="" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1">
 <g id="group">
  <g id="group-1">
   <path id="Path" d="M494.932 271.274 L464.647 248.564 488.813 192.17 C490.181 188.981 489.589 185.293 487.3 182.692 485.002 180.087 481.405 179.047 478.089 179.995 L423.01 195.732 408.058 158.346 C406.935 155.543 404.479 153.491 401.523 152.885 398.562 152.297 395.501 153.201 393.365 155.332 L334.335 214.362 363.572 82.789 C364.305 79.499 363.133 76.078 360.537 73.925 357.945 71.771 354.362 71.258 351.265 72.587 L297.121 95.788 264.441 5.912 C263.149 2.364 259.776 0 256 0 252.224 0 248.851 2.364 247.557 5.912 L214.877 95.788 160.733 72.587 C157.632 71.258 154.058 71.771 151.461 73.925 148.864 76.079 147.693 79.499 148.426 82.789 L177.663 214.362 118.633 155.332 C116.501 153.2 113.449 152.297 110.475 152.885 107.519 153.49 105.063 155.543 103.94 158.346 L88.988 195.732 33.91 179.994 C30.577 179.047 26.998 180.086 24.699 182.691 22.41 185.292 21.817 188.98 23.186 192.169 L47.352 248.563 17.067 271.273 C14.878 272.913 13.558 275.457 13.479 278.194 13.4 280.931 14.567 283.545 16.654 285.317 L129.381 380.702 121.438 420.412 C120.859 423.307 121.736 426.307 123.793 428.421 125.845 430.544 128.236 431.491 131.161 431.035 L227.928 414.816 227.928 485 C227.928 499.886 240.016 512 254.875 512 269.735 512 281.822 499.886 281.822 485 L281.822 414.816 379.712 431.035 C382.629 431.491 385.87 430.544 387.922 428.421 389.979 426.307 390.997 423.307 390.418 420.412 L382.545 380.702 495.307 285.317 C497.395 283.545 498.596 280.931 498.517 278.194 498.441 275.458 497.121 272.914 494.932 271.274 Z M366.968 370.405 C364.472 372.519 363.323 375.817 363.964 379.028 L370.394 411.177 275.443 395.353 C272.838 394.914 269.614 395.651 267.597 397.362 265.584 399.064 263.86 401.573 263.86 404.213 L263.86 485 C263.86 489.983 259.829 494.035 254.878 494.035 249.926 494.035 245.896 489.983 245.896 485 L245.896 404.211 C245.896 401.571 245.295 399.062 243.282 397.36 241.646 395.974 239.866 395.228 237.756 395.228 237.265 395.228 236.909 395.272 236.418 395.351 L141.537 411.175 148.002 379.026 C148.642 375.815 147.511 372.517 145.015 370.403 L36.857 278.879 63.773 258.695 C67.084 256.213 68.271 251.783 66.639 247.976 L47.136 202.459 91.847 215.231 C96.316 216.52 100.948 214.209 102.658 209.933 L115.561 177.666 186.771 248.88 C189.595 251.705 193.942 252.327 197.442 250.406 200.946 248.485 202.758 244.476 201.889 240.582 L169.764 95.999 216.531 116.043 C218.807 117.021 221.373 117.025 223.641 116.03 225.904 115.052 227.667 113.179 228.514 110.859 L256 35.267 283.487 110.858 C284.334 113.178 286.097 115.051 288.36 116.029 290.632 117.016 293.198 117.011 295.47 116.042 L342.237 95.998 310.11 240.58 C309.242 244.475 311.053 248.483 314.557 250.404 318.048 252.325 322.408 251.702 325.228 248.878 L396.438 177.664 409.341 209.931 C411.052 214.207 415.687 216.51 420.152 215.229 L464.862 202.457 445.358 247.974 C443.727 251.781 444.911 256.211 448.222 258.693 L475.134 278.877 Z" fill="#000" fill-opacity="1" stroke="none"/>
  </g>
 </g>
</svg>
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
