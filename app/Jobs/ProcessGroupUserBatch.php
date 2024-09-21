<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;
use Exception;

class ProcessGroupUserBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $users;

    // Maximum number of attempts before marking job as failed
    public $tries = 3;
    
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
        try {
            foreach ($this->users as $item) {
                // Simulation of API call to supplier
                Log::info("[{$item['id']}] firstname: {$item['name']}, timezone: '{$item['timezone']}'");
            }
        } catch (Exception $e) {
            // In the event of an error, the job will restart automatically.
            throw $e;
        }
    }
}
