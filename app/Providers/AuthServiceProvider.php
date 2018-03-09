<?php

namespace App\Providers;

use App\Company;
use App\MenuItem;
use App\Order;
use App\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::define('update-users', function ($user, User $target_user) {

            if($user->role === User::ROLE_ADMIN || $user->id == $target_user->id){
                return true;
            }
            if($user->role === User::ROLE_OWNER && $user->company_id == $target_user->company_id && $target_user->role == User::ROLE_WORKER){
                return true;
            }

            return false;
        });

        Gate::define('update-companies', function ($user, Company $company) {

            if($user->role === User::ROLE_ADMIN){
                return true;
            }
            if($user->role === User::ROLE_OWNER && $company->id && $user->company_id == $company->id){
                return true;
            }

            return false;
        });

        Gate::define('update-menu', function ($user, $company_id) {

            if($user->role === User::ROLE_ADMIN){
                return true;
            }
            if(($user->role === User::ROLE_OWNER || $user->role === User::ROLE_WORKER) && $user->company_id == $company_id){
                return true;
            }

            return false;
        });

        Gate::define('update-order', function ($user, Order $order) {

            if($user->role === User::ROLE_ADMIN){
                return true;
            }
            if(($user->role === User::ROLE_OWNER || $user->role === User::ROLE_WORKER) && $user->company_id == $order->company_id){
                return true;
            }

            if($user->role === User::ROLE_CLIENT && $user->id == $order->user_id){
                return true;
            }

            return false;
        });
    }
}
