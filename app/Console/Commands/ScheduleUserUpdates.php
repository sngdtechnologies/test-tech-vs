<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Jobs\ProcessGroupUserBatch;
use App\Jobs\ProcessUserBatch;
use Log;

class ScheduleUserUpdates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:schedule-updates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Schedule updates for users whose attributes have changed';

    /**
     * Number of payload records per request group
     *
     * @var $numberPayloadGroup
     */
    private static $numberPayloadGroup = 1000;

    /**
     * Number of payload records per individual request
     *
     * @var $numberIndividualRequest
     */
    private static $numberIndividualRequest = 3600;

    /**
     * Number of request group per hour
     *
     * @var $numberRequestGroupPerHour
     */
    private static $numberRequestGroupPerHour = 50;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Recover users with modified attributes
        $users = User::where('attributes_changed', true)->get();

        // Group users into batches of 1000 and recovery of recordings for invidivual requests
        $userIndividualRequest = [];
        foreach ($users->chunk(self::$numberPayloadGroup) as $key => $userBatch) 
        {
            if ($key < self::$numberRequestGroupPerHour && $userBatch->count() >= self::$numberPayloadGroup) { // check that request batch number has not been exceeded 
                ProcessGroupUserBatch::dispatch($userBatch->toArray()); // dispatch a job for each batch
            } elseif (count($userIndividualRequest) < self::$numberIndividualRequest) { // checks if the number of records for individual requests has not been reached
                $userIndividualRequest = [...$userIndividualRequest, ...$userBatch->toArray()]; // recovery of recordings for invidivual requests 
            }
        }
        
        // dispatch a job for individual update batch
        foreach ($userIndividualRequest as $item) 
        {
            ProcessUserBatch::dispatch($item);   
        }

        // change value attributes_changed to false
        $users->each(function ($e) {
            $e->update(['attributes_changed' => false]);
        });

        $this->info('User update jobs have been scheduled.');
        return 0;
    }
}
