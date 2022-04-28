<?php

namespace App\Console\Commands;

use App\Support\Api\Signature\AuthSignature;
use Illuminate\Console\Command;

class DailyCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shareg:dailycheck';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'daily check';

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
     * @return mixed
     */
    public function handle()
    {

    }
}
