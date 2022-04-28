<?php

use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (config('migrate.seeds.skip_admin')) {
            return;
        }
        
        //add default admin
        $admin = new Admin();
        $admin->fill([
            'name' => '山田 太郎',
            'email' => 'admin@shareg.com',
            'password' => bcrypt('pa@123456'),
            'api_token' => sha1(time() . Str::random(60)),
            'api_token_expires_at' => now()->addMonths(2),
        ]);
        $admin->save();

        //add test 30 admins
        //factory(Admin::class, 30)->create();
    }
}
