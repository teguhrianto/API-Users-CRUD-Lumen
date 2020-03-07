<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name' => 'Admin',
            'identity_id' => '12345678345',
            'gender' => 1,
            'address' => 'Cimahi',
            'photo' => '', //note: tidak ada gambar
            'email' => 'admin@daengweb.id',
            'password' => app('hash')->make('admin'),
            'phone_number' => '085343966997',
            'api_token' => Str::random(40),
            'role' => 0,
            'status' => 1
        ]);
    }
}
