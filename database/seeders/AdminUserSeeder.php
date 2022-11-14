<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user=User::create([
            'name'=>'Admin',
            'email'=>'admin@admin.com',
            'phone'=>9106659763,
            'email_verified_at'=> now(),
            'role_id'=>1,
            'password'=>Hash::make('admin'),
            'visible_password'=>'admin',
        ]);
        
        $user=User::create([
            'name'=>'Demo Admin',
            'email'=>'demo@admin.com',
            'phone'=>9624680752,
            'email_verified_at'=> now(),
            'role_id'=>1,
            'password'=>Hash::make('admin'),
            'visible_password'=>'admin',
        ]);
    }
}
