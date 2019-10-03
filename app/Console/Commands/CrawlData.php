<?php

namespace App\Console\Commands;

use App\Exceptions\InvalidRaceEmailException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Services\RaceMonitor\RaceMonitor\RaceMonitor;

class CrawlData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Email the top 10 table Race and ATR Index data, email:send {email?}';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     */
    public function handle(RaceMonitor $monitor)
    {
        try {
            $monitor->run();
        } catch (InvalidRaceEmailException $e) {
            Log::error($e);

            die();
        }

        Log::info($monitor->getMessage());

        return;
    }
}
