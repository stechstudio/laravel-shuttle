<?php

namespace STS\Shuttle\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface InteractsWithUploads
{
    /** @return \Illuminate\Database\Eloquent\Relations\MorphMany<\STS\Shuttle\Models\Upload> */
    public function uploads(): MorphMany;
}
