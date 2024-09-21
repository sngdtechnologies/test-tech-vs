<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UpdateUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update users firstname, lastname, and timezone with new random values';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $timezones = ['CET', 'CST', 'GMT+1'];

        User::all()->each(function ($user) use ($timezones) {
            $user->update([
                'name'      => fake()->name(),
                'timezone'  => $timezones[array_rand($timezones)],
            ]);
        });
        $this->info('Users have been updated successfully!');
    }
}
