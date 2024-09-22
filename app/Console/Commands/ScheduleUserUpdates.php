<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JobMetadata;
use App\Models\User;
use App\Jobs\ProcessGroupUserBatch;
use App\Jobs\ProcessUserBatch;
use Log;
use Carbon\Carbon;

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
     * JobMetadata for group user batch
     *
     * @var $jobMetadataGroupUserBatch
     */
    private $jobMetadataGroupUserBatch;

    /**
     * JobMetadata for group user batch
     *
     * @var $jobMetadataUserBatch
     */
    private $jobMetadataUserBatch;

    /**
     * Last job metadata group user batch complete time
     *
     * @var $lastJobGroupUserCompletedAt
     */
    private $lastJobGroupUserCompletedAt;

    /**
     * Last job metadata user batch complete time
     *
     * @var $lastJobUserCompletedAt
     */
    private $lastJobUserCompletedAt;

    /**
     * Create a new Shedule user update.
     */
    private function initialise () {
        $subhour = Carbon::now()->subHours();
        
        // Retrieve or create the first “process_group_user_batch"
        $jobMetadataGroupUserBatch = JobMetadata::where("job_name", "process_group_user_batch")
        ->where("last_completed_at", '>', $subhour)
        ->orderByDesc('id')
        ->first();

        if ($jobMetadataGroupUserBatch != null) {
            $this->jobMetadataGroupUserBatch = $jobMetadataGroupUserBatch;
        } else {
            $this->jobMetadataGroupUserBatch = JobMetadata::create([
                "job_name" => "process_group_user_batch", 
                "last_completed_at" => Carbon::now(),
                "number_execution" => 0
            ]);
        }

        // Retrieve or create the first “process_user_batch"
        $jobMetadataUserBatch = JobMetadata::where("job_name", "process_user_batch")
        ->where("last_completed_at", '>', $subhour)
        ->orderByDesc('id')
        ->first();

        if ($jobMetadataUserBatch != null) {
            $this->jobMetadataUserBatch = $jobMetadataUserBatch;
        } else {
            $this->jobMetadataUserBatch = JobMetadata::create([
                "job_name" => "process_user_batch", 
                "last_completed_at" => Carbon::now(),
                "number_execution" => 0
            ]);
        }

        // Retrieve the date of the last job completed
        $lastJobMetadataGroupUser = JobMetadata::where("job_name", "process_group_user_batch")
        ->where("last_completed_at", '>', $subhour)
        ->orderByDesc("id")
        ->first();

        $this->lastJobGroupUserCompletedAt = $lastJobMetadataGroupUser ? $lastJobMetadataGroupUser->last_completed_at : $subhour;

        $lastJobMetadataUser = JobMetadata::where("job_name", "process_user_batch")
        ->where("last_completed_at", '>', $subhour)
        ->orderByDesc("id")
        ->first();

        $this->lastJobUserCompletedAt = $lastJobMetadataUser ? $lastJobMetadataUser->last_completed_at : $subhour;
    } 

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Initialise data Command
        $this->initialise();

        // Retrieve users whose “updated_at” is later than this date
        $users = User::where("updated_at", '>', $this->lastJobGroupUserCompletedAt)
        ->orWhere("updated_at", '>', $this->lastJobUserCompletedAt)
        ->get();

        // Group users into batches of 1000 and recovery of recordings for invidivual requests
        $userIndividualRequest = [];
        $numberIRR = self::$numberIndividualRequest - $this->jobMetadataUserBatch->number_execution; // number of individual requests remaining
        $numberRBR = self::$numberRequestGroupPerHour - $this->jobMetadataGroupUserBatch->number_execution; // number of requests per batch remaining
        foreach ($users->chunk(self::$numberPayloadGroup) as $key => $userBatch) 
        {
            // check that request batch number has not been exceeded 
            if ($numberRBR > 0 && $userBatch->count() >= self::$numberPayloadGroup) { 
                // dispatch a job for each batch
                ProcessGroupUserBatch::dispatch($userBatch->toArray()); 
                // Update job execution date
                $this->jobMetadataGroupUserBatch->update([
                    "number_execution" => $this->jobMetadataGroupUserBatch->number_execution + 1,
                    "last_completed_at" => Carbon::now()
                ]); 
                $numberRBR--;
            } else {
                $temp = [...$userIndividualRequest, ...$userBatch->toArray()]; // recovery of recordings for invidivual requests 
                foreach ($temp as $item) {
                    // checks if the number of individual requests remaining has not been greater than 0
                    if ($numberIRR > 0) {
                        array_push($userIndividualRequest, $item);
                        $numberIRR--;
                    }
                }
            }
        }

        // dispatch a job for individual update batch
        foreach ($userIndividualRequest as $item) 
        {
            ProcessUserBatch::dispatch($item);   
        }

        // Update job execution date
        if (count($userIndividualRequest)) {
            $this->jobMetadataUserBatch->update([
                "number_execution" => $this->jobMetadataUserBatch->number_execution + count($userIndividualRequest),
                "last_completed_at" => Carbon::now()
            ]); 
        }

        // change value attributes_changed to false
        $users->each(function ($e) {
            $e->update(['attributes_changed' => false]);
        });

        $this->info('User update jobs have been scheduled.');
        return 0;
    }
}
