<?php

namespace STS\Shuttle\Commands;

use Illuminate\Console\Command;

class ShuttleCommand extends Command
{
    public $signature = 'laravel-shuttle';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
