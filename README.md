# Laravel Shuttle

This package is designed to provide an amazingly simple way to get large-scale file uploading working in a TALL app, directly to S3.

- Simply integrate into your app using a single Blade component, that's it.
- UI ready. No need to implement in your UI at all. Users can drag-n-drop files anywhere on the page, and a fixed bottom-bar progress appears.
- Events are fired when files upload, so your app can react and update the UI accordingly.

## Installation

### 1. Composer install this package

```shell
composer require stechstudio/laravel-shuttle
```

### 2. Migrate the database

Shuttle sets up its own `uploads` table to store details of each file. Your app can then make use of these upload records, or you can move the file information to a different database table after the upload completes.

```shell
php artisan migrate
```

### 3. Add the routes

Shuttle will create all the necessary routes for multipart uploads, you just have to tell Shuttle where to define the routes. This way you can wrap any middleware, route prefix, or other requirements.

```php
Route::middleware(['auth'])->group(function() {
    Shuttle::routes();
});
```

### 4. Configure AWS

Make sure you have an `s3` disk properly configured in Laravel already. See the advanced config below if you want to change which disk or S3 client Shuttle uses.

The S3 bucket you decide to use needs CORS configured. Open the bucket in the AWS console, click on the permissions tab, scroll down to the CORS box, and paste this:

```
[
    {
        "AllowedHeaders": [
            "*"
        ],
        "AllowedMethods": [
            "POST",
            "PUT",
            "DELETE"
        ],
        "AllowedOrigins": [
            "*"
        ],
        "ExposeHeaders": [
            "ETag"
        ],
        "MaxAgeSeconds": 3000
    }
]
```

You can further restrict origins if desired.

> **Disclaimer**  
> The CORS Everywhere plugin does not play nicely with this package. Make sure that you disable it when developing locally.

## Implementation

Implementing Shuttle in your app is pretty simple, let's walk through it.

### 1. Implement the Blade component

It's time to drop Shuttle into your app. All you have to do is use the Blade component in your view where you want users to upload files. This should be a Livewire view file.

```html

<x-shuttle::uploader />
```

### 2. Add the upload context

You Livewire component needs to expose a `$uploadContext` array, with any metadata about the uploaded files that you will need later on to determine ownership.

For example, let's say you have a simple folder/file app. When users upload files to, you will need to add the folder ID to each file upload, so you can determine how to relate the files in the database. Your `Folder` Livewire component class might look something like this:

```php
class Folder extends Component {
    public $uploadContext = [];
    public $folder;

    public function mount(Folder $folder) {
        $this->folder = $folder;
        $this->uploadContext = ['folder_id' => $folder->id];    
    }
```

### 3. Tell Shuttle how to relate files

When a file is uploaded, Shuttle needs to know what the owner of the file is. We're not talking about the user that performed the upload, but rather _what model the file should be attached to._ This is commonly a folder, or a project.

In your `AppServiceProvider` `boot()` method, you will need to define a callback function that receives the file metadata (including the context information provided in the previous step). This will return a database model.

So for our folder example above, we might define a callback like this:

```php
Shuttle::resolveOwnerWith(function(array $metadata) {
    return Folder::findOrFail($metadata['folder_id']);
});
```

Shuttle will now ensure the uploaded files have an `owner` relationship set to this folder.

You also need to add the `HasUploads` trait on the owning model. In our example above, this would be the `Folder` model.

> **Warning**  
> This is a basic example to get things working. You will most likely want to perform some authorization check in that callback function to ensure the currently logged-in user has permission to upload files to the specified folder.

### 4. Add relationship to your owning models OR handle completed uploads yourself

If you'd like to make use of the `uploads` database records directly, there's nothing more to do. You can access `$folder->uploads` with our previous example setup, and retrieve all uploaded files.

Alternatively, if you prefer to handle uploaded file data on your own, you can wire up a callback that Shuttle will execute when an upload completes. Do this in your `AppServiceProvider`:

```php
Shuttle::whenComplete(function(Upload $upload) {
    // ...store the upload information somewhere else in your database
});
```

In this case, you probably want to treat `Upload` records as temporary, and execute a `$upload->delete()` in the above callback after handling.

## Uploading files

At this point you should be able to visit page in your app where you have implemented the Shuttle Blade component and provide upload context in your Livewire component.

Drag some files onto the page, and you should see a translucent overlay appear, letting you know you can drop your files.

Once you drop the files, you should see a bar at the bottom of the page showing the status of your uploads. You can expand this bar to view the individual status of each file as it uploads.

The bar will turn green when all dropped files are successfully uploaded. It will turn red and auto-expand if any uploads fail, showing the error messages.

# Events

We expose the following events that you can listen for.

| Event          	| Properties       	|
|----------------	|----------------	|
| fileAdded      	| $file           	|
| uploadProgress 	| $file, $progress 	|
| progress       	| $progress       	|
| uploadSuccess  	| $file           	|
| uploadError    	| $file           	|
| fileRemoved    	| $file           	|
| complete       	| $result         	|

# Customization

You are able to customise the colours of the UI. You just need to go ahead and publish the config file.

`php artisan vendor:publish --tag=shuttle-config`

```php
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
    'disk' => 's3',

    /**
     * The authentication guard used for authorization.
     */
    'guard' => 'web',

    /**
     * The background colors used for the file uploader UI.
     * You can customise the color for each state. You can
     * use any valid Tailwind background class. If you
     * need to specify a custom HEX value, create a
     * new color variable in your Tailwind config
     * file. Custom HEX values are not compiled
     * at run time.
     */
    'colors' => [

        'details-panel' => [
            'uploading' => env(key: 'DETAILS_PANEL_UPLOADING', default: 'bg-blue-500'),

            'upload-success' => env(key: 'DETAILS_PANEL_UPLOAD_SUCCESS', default: 'bg-green-500'),

            'upload-error' => env(key: 'DETAILS_PANEL_UPLOAD_ERROR', default: 'bg-red-500'),
        ],

    ],

];
```

# CSRF Token

You will need to add the following to your VerifyCsrfToken middleware to prevent a CSRF token mismatch error.

```php
    protected $except = [
        'upload/sign/*',
        '*uploader/s3/multipart*'
    ];
```
