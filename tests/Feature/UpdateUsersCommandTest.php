<?php

namespace Tests\Feature;

use App\Jobs\ProcessGroupUserBatch;
use App\Jobs\ProcessUserBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateUsersCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A test dispatches jobs for group user batches
     */
    #[Test]
    public function it_dispatches_jobs_for_group_user_batches()
    {
        //  Simulate a queue
        Queue::fake();

        // Creating fictitious users
        User::factory()->count(2001)->create();

        // Run the Artisan command
        $this->artisan('users:update');

        // Run the Artisan command
        $this->artisan('users:schedule-updates');

        // Check that jobs have been dispatched
        Queue::assertPushed(ProcessGroupUserBatch::class, 2); // 2 jobs for 2 batches of 1000 users

        // Check that jobs have been dispatched
        Queue::assertPushed(ProcessUserBatch::class, 1); // 1 jobs 1 user
    }
}
