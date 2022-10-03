<?php

declare(strict_types=1);

namespace STS\Shuttle;

use Closure;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use STS\Shuttle\Models\Upload;

class ShuttleManager
{
    protected mixed $baseUrlResolver = null;

    protected mixed $ownerResolver = null;

    protected mixed $s3ClientResolver = null;

    protected mixed $s3BucketResolver = null;

    protected mixed $completeHandler = null;

    public function resolveBaseUrlWith(mixed $resolver): static
    {
        $this->baseUrlResolver = $resolver;

        return $this;
    }

    public function baseUrl(): string
    {
        return $this->baseUrlResolver
            ? call_user_func($this->baseUrlResolver, config('shuttle.url_prefix'))
            : config('shuttle.url_prefix');
    }

    public function resolveOwnerWith(Closure $resolver): static
    {
        $this->ownerResolver = $resolver;

        return $this;
    }

    public function owner($metadata): mixed
    {
        return call_user_func($this->ownerResolver, $metadata);
    }

    public function routes(): void
    {
        Route::name('uploader.')
            ->prefix(config('shuttle.url_prefix') . '/s3/multipart')
            ->group(function () {
                Route::post('/', fn() => Upload::begin(request('metadata'), $this->owner(request('metadata'))))
                    ->name('create');

                Route::get('/{uploadId}', fn() => Upload::parts(request('key'), request('uploadId')))
                    ->name('get-parts');

                Route::get('/{uploadId}/{partNumbers}', fn() => Upload::sign(request('key'), request('uploadId'), request('partNumbers')))
                    ->name('sign-part');

                Route::delete('/{uploadId}', fn() => Upload::abort(request('key'), request('uploadId')))
                    ->name('abort');

                Route::post('/{uploadId}/complete', fn() => Upload::complete(request('key'), request('uploadId'), request('parts')))
                    ->name('complete');
            });
    }

    public function resolveS3ClientWith(Closure $resolver): static
    {
        $this->s3ClientResolver = $resolver;

        return $this;
    }

    public function s3Client(): mixed
    {
        if ($this->s3ClientResolver) {
            return value($this->s3ClientResolver);
        }

        /** @phpstan-ignore-next-line */
        return Storage::disk(config('shuttle.disk'))->getClient();
    }

    public function resolveS3BucketWith(Closure $resolver): static
    {
        $this->s3BucketResolver = $resolver;

        return $this;
    }

    public function s3Bucket(): mixed
    {
        return $this->s3BucketResolver
            ? call_user_func($this->s3BucketResolver)
            : config('filesystems.disks.' . config('shuttle.disk') . '.bucket');
    }

    public function whenComplete(Closure $handler): static
    {
        $this->completeHandler = $handler;

        return $this;
    }

    public function complete(Upload $upload): void
    {
        value($this->completeHandler, $upload);
    }
}
