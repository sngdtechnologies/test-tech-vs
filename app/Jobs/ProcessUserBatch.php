<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;

class ProcessUserBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $users;

    /**
     * Create a new job instance.
     * @Param $users
     */
    public function __construct($users)
    {
        $this->users = $users;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach ($this->users as $user) {
            // Simulation of API call to supplier
            Log::info("[$user->id] firstname: {$user->name}, timezone: '{$user->timezone}'");
        }
    }
}
