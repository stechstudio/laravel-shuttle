<?php

namespace STS\Shuttle\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use STS\Shuttle\Models\Upload;

trait HasUploads
{
    public function uploads(): MorphMany
    {
        return $this->morphMany(Upload::class, 'owner');
    }
}
