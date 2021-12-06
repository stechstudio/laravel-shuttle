<?php

namespace STS\Shuttle\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use STS\Shuttle\Facades\Shuttle;
use STS\Shuttle\Models\Contracts\InteractsWithUploads;

/**
 * @property int $id
 * @property-read string $uuid
 * @property string $key
 *
 * @property int $user_id
 * @property string $owner_type
 * @property int $owner_id
 *
 * @property string $name
 * @property string $extension
 * @property string $type
 * @property int $size
 *
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $completed_at
 */
class Upload extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public static function begin(array $attributes, Model & InteractsWithUploads $uploadable): array
    {
        $uuid = Str::uuid();

        $upload = $uploadable->uploads()->create([
            'user_id' => auth()->guard(config('shuttle.guard'))->id(),
            'uuid' => $uuid,
            'key' => $uuid . "." . strtolower(pathinfo($attributes['name'], PATHINFO_EXTENSION)),
            'name' => $attributes['name'],
            'extension' => strtolower(pathinfo($attributes['name'], PATHINFO_EXTENSION)),
            'type' => $attributes['type'],
            'size' => $attributes['size'],
        ]);

        $result = Shuttle::s3Client()->createMultipartUpload([
            'Bucket' => Shuttle::s3Bucket(),
            'Key' => $upload->key,
            'ACL' => 'private',
            'ContentType' => $attributes['type'],
            'Metadata' => $attributes,
            'Expires' => '+24 hours',
        ]);

        return ['key' => $result['Key'], 'uploadId' => $result['UploadId']];
    }

    public static function parts($key, $uploadId): array
    {
        $parts = [];
        $next = 0;

        do {
            $result = Shuttle::s3Client()->listParts([
                'Bucket' => Shuttle::s3Bucket(),
                'Key' => $key,
                'UploadId' => $uploadId,
                'PartNumberMarker' => $next,
            ]);

            $parts = array_merge($parts, $result['Parts']);
            $next = $result['NextPartNumberMarker'];
        } while ($result['IsTruncated']);

        return $parts;
    }

    public static function sign($key, $uploadId, $partNumber)
    {
        $signedRequest = Shuttle::s3Client()->createPresignedRequest(
            Shuttle::s3Client()->getCommand('uploadPart', [
                'Bucket' => Shuttle::s3Bucket(),
                'Key' => $key,
                'UploadId' => $uploadId,
                'PartNumber' => $partNumber,
                'Body' => '',
                'Expires' => '+24 hours',
            ]),
            '+24 hours'
        );

        return ['url' => (string)$signedRequest->getUri()];
    }

    public static function abort($key, $uploadId)
    {
        Shuttle::s3Client()->abortMultipartUpload([
            'Bucket' => Shuttle::s3Bucket(),
            'Key' => $key,
            'UploadId' => $uploadId,
        ]);

        return static::where('key', $key)->first()->delete();
    }

    public static function complete($key, $uploadId, $parts): array
    {
        $result = Shuttle::s3Client()->completeMultipartUpload([
            'Bucket' => Shuttle::s3Bucket(),
            'Key' => $key,
            'UploadId' => $uploadId,
            'MultipartUpload' => ['Parts' => $parts],
        ]);

        tap(static::where('key', $key)->first(), function ($upload) {
            $upload->update(['completed_at' => now()]);
            Shuttle::complete($upload);
        });

        return ['location' => $result['Location']];
    }
}
