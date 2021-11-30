<?php

namespace STS\Shuttle;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

class ShuttleManager
{
    /** @var callable */
    protected $baseUrlResolver;

    /** @var callable */
    protected $ownerResolver;

    /** @var callable */
    protected $s3ClientResolver;

    /** @var callable */
    protected $s3BucketResolver;

    /** @var callable */
    protected $completeHandler;

    public function resolveBaseUrlWith($resolver)
    {
        $this->baseUrlResolver = $resolver;

        return $this;
    }

    public function baseUrl()
    {
        return $this->baseUrlResolver
            ? call_user_func($this->baseUrlResolver, config('shuttle.url_prefix'))
            : config('shuttle.url_prefix');
    }

    public function resolveOwnerWith($resolver)
    {
        $this->ownerResolver = $resolver;

        return $this;
    }

    public function owner($metadata)
    {
        return call_user_func($this->ownerResolver, $metadata);
    }

    public function routes()
    {
        Route::name('uploader.')->prefix(config('shuttle.url_prefix') . '/s3/multipart')->group(function () {
            Route::post('/', fn () => Upload::begin(request('metadata'), $this->owner(request('metadata'))))
                ->name('create');

            Route::get('/{uploadId}', fn () => Upload::parts(request('key'), request('uploadId')))
                ->name('get-parts');

            Route::get('/{uploadId}/{partNumber}', fn () => Upload::sign(request('key'), request('uploadId'), request('partNumber')))
                ->name('sign-part');

            Route::delete('/{uploadId}', fn () => Upload::abort(request('key'), request('uploadId')))
                ->name('abort');

            Route::post('/{uploadId}/complete', fn () => Upload::complete(request('key'), request('uploadId'), request('parts')))
                ->name('complete');
        });
    }

    public function resolveS3ClientWith($resolver)
    {
        $this->s3ClientResolver = $resolver;

        return $this;
    }

    public function s3Client()
    {
        return $this->s3ClientResolver
            ? call_user_func($this->s3ClientResolver)
            : Storage::disk(config('shuttle.disk'))->getAdapter()->getClient();
    }

    public function resolveS3BucketWith($resolver)
    {
        $this->s3BucketResolver = $resolver;

        return $this;
    }

    public function s3Bucket()
    {
        return $this->s3BucketResolver
            ? call_user_func($this->s3BucketResolver)
            : config('filesystems.disks.' . config('shuttle.disk') . '.bucket');
    }

    public function whenComplete($handler)
    {
        $this->completeHandler = $handler;

        return $this;
    }

    public function complete(Upload $upload)
    {
        call_user_func($this->completeHandler, $upload);
    }
}
