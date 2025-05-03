<?php

namespace App\Console\Commands;

use App\Models\Center;
use App\Models\User;
use Illuminate\Console\Command;

class UpdateAdminCenter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:update-center';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the admin user with a center ID';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $admin = User::where('email', 'admin@rahati.com')->first();
        
        if (!$admin) {
            $this->error('Admin user not found');
            return 1;
        }
        
        $center = Center::first();
        
        if (!$center) {
            $this->error('No centers found');
            return 1;
        }
        
        $admin->center_id = $center->id;
        $admin->save();
        
        $this->info("Admin user updated with center_id: {$center->id}");
        
        return 0;
    }
}
