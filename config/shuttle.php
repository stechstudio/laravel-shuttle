<?php

declare(strict_types=1);

return [

    /**
     * The URL endpoint used to trigger the
     * upload process.
     */
    'url_prefix' => '/uploader',

    /**
     * The disk you want to use for file uploads. This
     * must be a disk that uses the S3 driver.
     */
    'disk'       => 's3',

    /**
     * The authentication guard used for authorization.
     */
    'guard'      => 'web',

    /**
     * The background colors used for the file uploader UI.
     * You can customise the color for each state. You can
     * use any valid Tailwind background class. If you
     * need to specify a custom HEX value, create a
     * new color variable in your Tailwind config
     * file. Custom HEX values are not compiled
     * at run time.
     */
    'colors'     => [

        'details-panel' => [
            'uploading'       => env(key: 'DETAILS_PANEL_UPLOADING', default: 'bg-blue-500'),

            'upload-success'  => env(key: 'DETAILS_PANEL_UPLOAD_SUCCESS', default: 'bg-green-500'),

            'upload-error'    => env(key: 'DETAILS_PANEL_UPLOAD_ERROR', default: 'bg-red-500'),

            'connection-lost' => env(key: 'DETAILS_PANEL_CONNECTION_LOST', default: 'bg-blue-700'),
        ],

    ],

];
