<?php

namespace Tests\Unit;

use App\Jobs\ProcessGroupUserBatch;
use App\Jobs\ProcessUserBatch;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProcessUserBatchTest extends TestCase
{
    /**
     * A test update group user job
     */
    #[Test]
    public function it_logs_the_group_user_updates()
    {
        // Simulate logging
        Log::shouldReceive('info') 
            ->twice() // 2 users are expected to be logged in
            ->withArgs(function ($message) {
                return strpos($message, 'firstname') !== false;
            });

        // Create a batch of fictitious users
        $users = [
            ['id' => 1, 'name' => 'Alex', 'timezone' => 'Europe/Amsterdam'],
            ['id' => 2, 'name' => 'Helen', 'timezone' => 'America/Los_Angeles']
        ];

        // Esecute le job
        $job = new ProcessGroupUserBatch($users);
        $job->handle();
    }

    /**
     * A test update user job
     */
    #[Test]
    public function it_logs_the_user_updates()
    {
        // Simulate logging
        Log::shouldReceive('info') 
            ->once() // 1 users are expected to be logged in
            ->withArgs(function ($message) {
                $expectedLog = "[1] firstname: Alex, timezone: 'Europe/Amsterdam'";
                return $message === $expectedLog;
            });

        // Create a batch of fictitious users
        $user = ['id' => 1, 'name' => 'Alex', 'timezone' => 'Europe/Amsterdam'];

        // Exécuter job
        $job = new ProcessUserBatch($user);
        $job->handle();
    }


}
