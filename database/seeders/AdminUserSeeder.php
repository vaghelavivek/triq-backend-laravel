<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserBusiness;
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
        $user1=User::create([
            'name'=>'Admin',
            'email'=>'admin@admin.com',
            'phone'=>9106659763,
            'email_verified_at'=> now(),
            'role_id'=>1,
            'password'=>Hash::make('admin'),
            'visible_password'=>'admin',
        ]);
        if ($user1) {
            $userb1 = new UserBusiness();
            $userb1->user_id = $user1->id;
            $userb1->country = 'india';
            $userb1->save();
        }
        
        $user2=User::create([
            'name'=>'Demo Admin',
            'email'=>'demo@admin.com',
            'phone'=>9624680752,
            'email_verified_at'=> now(),
            'role_id'=>1,
            'password'=>Hash::make('admin'),
            'visible_password'=>'admin',
        ]);
        if ($user2) {
            $userb2 = new UserBusiness();
            $userb2->user_id = $user2->id;
            $userb2->country = 'india';
            $userb2->save();
        }

        $user3=User::create([
            'name'=>'Test User',
            'email'=>'test@user.com',
            'phone'=>7894561230,
            'email_verified_at'=> now(),
            'role_id'=>3,
            'password'=>Hash::make('user'),
            'visible_password'=> 'user',
        ]);
        if ($user3) {
            $userb3 = new UserBusiness();
            $userb3->user_id = $user3->id;
            $userb3->country = 'india';
            $userb3->save();
        }
    }
}
