<?php

declare(strict_types=1);

namespace STS\Shuttle\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface InteractsWithUploads
{
    public function uploads(): MorphMany;
}
