<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\JobMetadata;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class UserUpdateSchedulingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_fetches_users_updated_since_last_job()
    {
        // Simulate a job last run an hour ago
        $lastCompletedAt = Carbon::now()->subHour();
        JobMetadata::create([
            'job_name' => 'process_group_user_batch',
            'last_completed_at' => $lastCompletedAt,
        ]);

        // Create users with different updated_at
        User::factory()->create(['updated_at' => Carbon::now()->subHours(2)]); // Out of interval
        $recentUser = User::factory()->create(['updated_at' => Carbon::now()->subMinutes(30)]); // In the meantime

        // Recover users modified since last run
        $users = User::where('updated_at', '>', $lastCompletedAt)->get();

        // Ensure that only recent users are recovered
        $this->assertCount(1, $users);
        $this->assertTrue($users->contains($recentUser));
    }
}
