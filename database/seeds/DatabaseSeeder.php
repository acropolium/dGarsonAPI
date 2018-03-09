<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = array(
            ['name' => 'Administrator', 'email' => 'admin@a.c', 'password' => bcrypt('123456'), 'role' => \App\User::ROLE_ADMIN],
            ['name' => 'Owner', 'email' => 'owner@a.c', 'password' => bcrypt('123456'), 'role' => \App\User::ROLE_OWNER],
            ['name' => 'Worker', 'email' => 'worker@a.c', 'password' => bcrypt('123456'), 'role' => \App\User::ROLE_WORKER],
        );

        // Loop through each user above and create the record for them in the database
        foreach ($users as $user)
        {
            \App\User::create($user);
        }
    }
}
