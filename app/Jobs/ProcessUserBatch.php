<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;
use Exception;

class ProcessUserBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;

    // Maximum number of attempts before marking job as failed
    public $tries = 3;

    /**
     * Create a new job instance.
     * @Param $users
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Simulation of API call to supplier
            Log::info("[{$this->user['id']}] firstname: {$this->user['name']}, timezone: '{$this->user['timezone']}'");
        } catch (Exception $e) {
            // In the event of an error, the job will restart automatically.
            throw $e;
        }
    }
}
