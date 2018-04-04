<?php
namespace App\Http\Controllers;

use App\Company;
use App\DeviceToken;
use App\Order;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Validator;
use DateTime;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $per_page = $request->input('per_page', null);
        /*if(in_array($request->user()->role, [User::ROLE_CLIENT, User::ROLE_WORKER])){
            return response(['error'=>['You don\'t have rights']], 403);
        }*/

        $users = User::orderBy('created_at', 'desc');
        if ($request->has('keyword')) {
            $users->where(function ($query) use ($request) {
                $query->where(
                    'phone',
                    'like',
                    "%{$request->input('keyword')}%"
                )->orWhere('email', 'like', "%{$request->input('keyword')}%");
            });
        }

        switch ($request->user()->role) {
            case User::ROLE_ADMIN:
                if ($request->has('company_id')) {
                    $users->whereIn(
                        'company_id',
                        explode(',', $request->input('company_id'))
                    );
                }
                break;
            case User::ROLE_OWNER:
                $users->where(function ($query) use ($request) {
                    $query->where(function ($query) use ($request) {
                        $query->where(
                            'role',
                            User::ROLE_WORKER
                        )->where('company_id', $request->user()->company_id);
                    })->orWhere('id', $request->user()->id);
                });
                break;
            default:
                $users->where('id', $request->user()->id);
        }
        //echo($users->toSql());
        return response()->json($users->simplePaginate($per_page));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = $this->validator($request->all(), $request->user());
        if ($validator->fails()) {
            return response($validator->errors(), 400);
        }

        $user = new User();
        $user->name = $request->input('name');
        $user->email = (
            $request->has('email') ? $request->input('email') : null
        );
        $user->phone = (
            $request->has('phone') ? $request->input('phone') : null
        );
        $user->company_id = (
            $request->has('company_id') ? $request->input('company_id') : null
        );
        $user->location_id = (
            $request->has('location_id')
                ? intval($request->input('location_id'))
                : null
        );
        $user->password = bcrypt($request->input('password'));
        $user->api_token = Str::random(40);

        switch ($request->user()->role) {
            case User::ROLE_ADMIN:
                $user->role = $request->input('role', User::ROLE_WORKER);
                break;
            case User::ROLE_OWNER:
                $user->role = User::ROLE_WORKER;
                break;
        }

        if ($request->user()->cant('update-users', $user)) {
            return response([
                'error' => [trans('messages.permission_denied')]
            ], 403);
        }
        $user->save();

        return response()->json($user);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (empty($id)) {
            $id = $request->user()->id;
        }
        try {
            $user = User::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response([
                'error' => [trans('messages.user_not_found')]
            ], 404);
        }

        if ($request->user()->can('update-users', $user)) {
            $validator = $this->validator($request->all(), $user);
            if ($validator->fails()) {
                return response($validator->errors(), 400);
            }

            switch ($request->user()->role) {
                case User::ROLE_ADMIN:
                    $user->role = $request->input('role', $user->role);
                    $user->company_id = (
                        $request->has('company_id')
                            ? intval($request->input('company_id'))
                            : null
                    );
                    $user->location_id = (
                        $request->has('location_id')
                            ? intval($request->input('location_id'))
                            : null
                    );
                    break;
                case User::ROLE_OWNER:
                    if (
                        in_array($request->input('role'), [User::ROLE_WORKER])
                    ) {
                        $user->role = $request->input('role', $user->role);
                    }
                    $user->location_id = (
                        $request->has('location_id')
                            ? intval($request->input('location_id'))
                            : null
                    );
                    break;
            }

            $user->name = $request->input('name', $user->name);
            $user->email = (
                $request->has('email') ? $request->input('email') : null
            );
            $user->phone = (
                $request->has('phone') ? $request->input('phone') : null
            );
            $user->password = (
                $request->has('password')
                    ? bcrypt($request->input('password'))
                    : $user->password
            );
        }
        $user->save();

        return response()->json($user);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $item = User::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response([
                'error' => [trans('messages.user_not_found')]
            ], 404);
        }
        if (
            Auth::user()->cant('update-users', $item) ||
            Auth::user()->id == $item->id
        ) {
            return response([
                'error' => [trans('messages.permission_denied')]
            ], 403);
        }

        $item->delete();
        return response()->json(['success' => 'ok']);
    }

    public function refreshDeviceToken(Request $request)
    {
        if ($request->has('device_token')) {
            $request->user()->refreshDeviceToken(
                $request->input('device_token'),
                $request->all()
            );
        }

        return response()->json(['success' => 'ok']);
    }

    public function removeDeviceToken(Request $request)
    {
        if ($request->has('device_token')) {
            DeviceToken::where('token', $request->input('device_token'))
                ->where('user_id', $request->user()->id)
                ->delete();
        }

        return response()->json(['success' => 'ok']);
    }

    public function companies(Request $request)
    {
        $isNotAdmin = $request->user()->role !== User::ROLE_ADMIN;
        $company = Company::with(
            'locations'
        )->when($isNotAdmin, function ($query) use ($request) {
            return $query->where('id', $request->user()->company_id);
        });
        $company->with('translations');

        $companies = $company->get();

        $allowLocations = null;
        if ($request->has('locations_allow')) {
            $allowLocations = explode(',', $request->get('locations_allow'));
        }
        if ($request->has('statistics')) {
            foreach ($companies as $comp) {
                $range_start = new Carbon($request->get('range_start'));
                $range_end = new Carbon($request->get('range_end'));
                if (
                    $request->get('range_start') && $request->get('range_end')
                ) {
                    $start = $range_start->toDateString();
                    $end = $range_end->toDateString();
                } elseif ($request->get('range_start')) {
                    $start = $range_start->startOfWeek()->toDateString();
                    $end = $range_start->endOfWeek()->toDateString();
                } else {
                    $date = Carbon::now();
                    $start = $date->startOfWeek()->toDateString();
                    $end = $date->endOfWeek()->toDateString();
                }

                $query = DB::table('orders')
                    ->select(
                        DB::raw("DATE(created_at) as order_date"),
                        DB::raw(
                            "SUM(IF(`state` = '" .
                            Order::STATE_PAYED .
                            "', 1, 0)) as payed"
                        ),
                        DB::raw(
                            "SUM(IF(`state` = '" .
                            Order::STATE_NOTPICKED .
                            "', 1, 0)) as notpicked"
                        ),
                        DB::raw(
                            "SUM(IF(`state` = '" .
                            Order::STATE_CANCEL .
                            "', 1, 0)) as canceled"
                        ),
                        DB::raw(
                            "SUM(IF(`state` IN ('" .
                            Order::STATE_RECIEVED .
                            "', '" .
                            Order::STATE_INPROGRESS .
                            "', '" .
                            Order::STATE_PENDING .
                            "', '" .
                            Order::STATE_READY .
                            "'), 1, 0)) as active"
                        )
                    )
                    ->where('company_id', $comp->id)
                    ->whereBetween('created_at', [
                        $start . ' 00:00:00',
                        $end . ' 23:59:59'
                    ])
                    ->groupBy(DB::raw("DATE(created_at)"));
                if ($allowLocations) {
                    $query->whereIn('location_id', $allowLocations);
                }
                $statistics = $query->get();

                if (
                    $request->has('group_statistic_by') &&
                    $request->group_statistic_by == 'week'
                ) {
                    $statistics = $this->statisticGroupByWeek($statistics);
                } elseif (
                    $request->has('group_statistic_by') &&
                    $request->group_statistic_by == 'month'
                ) {
                    $statistics = $this->statisticGroupByMonth($statistics);
                } elseif (
                    $request->has('group_statistic_by') &&
                    $request->group_statistic_by == 'day'
                ) {
                    $statistics = $this->statisticGroupByDay($statistics);
                }
                $comp->statistics = $statistics;
            }
        }

        return response()->json($companies);
    }

    /**
     * @param $statistics
     * @return array|Collection|\stdClass
     */
    private function statisticGroupByMonth($statistics)
    {
        $statistics_arr = [];
        foreach ($statistics as $item) {
            $legend = Carbon::createFromFormat(
                'Y-m-d',
                $item->order_date
            )->format('Y-m');
            $key = Carbon::createFromFormat(
                'Y-m-d',
                $item->order_date
            )->format('Y-m');

            if (!array_key_exists($key, $statistics_arr)) {
                $item_new = $item;
                $item_new->legend = $legend;
            } else {
                $item_new = $statistics_arr[$key];
                if ($payed = $item->payed) {
                    $item_new->payed = $item_new->payed + $payed;
                }
                if ($active = $item->active) {
                    $item_new->active = $item_new->active + $active;
                }
                if ($canceled = $item->canceled) {
                    $item_new->canceled = $item_new->canceled + $canceled;
                }
                if ($notpicked = $item->notpicked) {
                    $item_new->notpicked = $item_new->notpicked + $notpicked;
                }
            }
            $statistics_arr[$key] = $item_new;
        }

        $statistics = new Collection();
        foreach ($statistics_arr as $item) {
            $statistics[] = $item;
        }
        return $statistics;
    }

    private function statisticGroupByWeek($statistics)
    {
        $statistics_arr = [];
        foreach ($statistics as $item) {
            $date = new DateTime($item->order_date);
            $key = $date->format("W");
            $start_legend = date('Y-m-d', strtotime('2017W' . $key));
            $end_legend = Carbon::createFromFormat('Y-m-d', $start_legend)
                ->addDay(6)
                ->format('Y-m-d');

            $legend = $start_legend . ' ' . $end_legend;
            if (!array_key_exists($key, $statistics_arr)) {
                $item_new = $item;
                $item_new->legend = $legend;
            } else {
                $item_new = $statistics_arr[$key];
                if ($payed = $item->payed) {
                    $item_new->payed = $item_new->payed + $payed;
                }
                if ($active = $item->active) {
                    $item_new->active = $item_new->active + $active;
                }
                if ($canceled = $item->canceled) {
                    $item_new->canceled = $item_new->canceled + $canceled;
                }
                if ($notpicked = $item->notpicked) {
                    $item_new->notpicked = $item_new->notpicked + $notpicked;
                }
            }
            $statistics_arr[$key] = $item_new;
        }

        $statistics = new Collection();
        foreach ($statistics_arr as $item) {
            $statistics[] = $item;
        }
        return $statistics;
    }

    private function statisticGroupByDay($statistics)
    {
        $statistics->each(function ($item) {
            $item->legend = $item->order_date;
        });
        return $statistics;
    }

    public function getClients(Request $request)
    {
        $per_page = $request->input('per_page', null);

        //        $users = User::orderBy('created_at', 'desc')->where('role', User::ROLE_CLIENT);
        $order_counter = Order::checkType($request->get('type_order'))
            ? $request->get('type_order')
            : Order::STATE_PAYED;
        $users = DB::table('users')
            ->select(
                'id',
                'name',
                'email',
                'phone',
                'role',
                'created_at',
                'updated_at',
                DB::raw(
                    "(SELECT SUM(IF(`state` = '" .
                    $order_counter .
                    "', 1, 0)) FROM `orders`
             WHERE users.id=orders.user_id) as counter"
                )
            )
            ->where('role', User::ROLE_CLIENT)
            ->orderBy('counter', 'DESC');
        if ($request->has('keyword')) {
            $users->where(function ($query) use ($request) {
                $query->where(
                    'phone',
                    'like',
                    "%{$request->input('keyword')}%"
                )->orWhere('email', 'like', "%{$request->input('keyword')}%");
            });
        }

        if ($request->has('company_id')) {
            $users->whereIn(
                'company_id',
                explode(',', $request->input('company_id'))
            );
        }

        $per_page = $per_page ? $per_page : 25;
        $clients = $users->simplePaginate($per_page);

        foreach ($clients as $client) {
            /*$date = Carbon::now();
            $start = $date->startOfYear()->toDateString();
            $end = $date->endOfYear()->toDateString();*/

            $statistics = //->whereBetween('created_at', [$start, $end])
            DB::table('orders')
                ->select(
                    DB::raw("DATE(created_at) as order_date"),
                    DB::raw(
                        "SUM(IF(`state` = '" .
                        Order::STATE_PAYED .
                        "', 1, 0)) as payed"
                    ),
                    DB::raw(
                        "SUM(IF(`state` = '" .
                        Order::STATE_NOTPICKED .
                        "', 1, 0)) as notpicked"
                    ),
                    DB::raw(
                        "SUM(IF(`state` = '" .
                        Order::STATE_CANCEL .
                        "', 1, 0)) as canceled"
                    ),
                    DB::raw(
                        "SUM(IF(`state` IN ('" .
                        Order::STATE_RECIEVED .
                        "', '" .
                        Order::STATE_INPROGRESS .
                        "', '" .
                        Order::STATE_PENDING .
                        "', '" .
                        Order::STATE_READY .
                        "'), 1, 0)) as active"
                    )
                )
                ->where('user_id', $client->id)
                ->groupBy(DB::raw("DATE(created_at)"))
                ->get();

            $statistics->each(function ($item) {
                $item->safety = $item->payed > $item->notpicked
                    ? 'safe'
                    : 'danger';
            });

            $client->statistics = $statistics;
        }

        return response()->json($clients);
    }

    public function getClient(Request $request, $id)
    {
        $range_start = new Carbon($request->get('range_start'));
        $range_end = new Carbon($request->get('range_end'));

        if ($request->get('range_start') && $request->get('range_end')) {
            $start = $range_start->toDateString();
            $end = $range_end->toDateString();
        } else {
            $date = Carbon::now();
            $start = $date->startOfYear()->toDateString();
            $end = $date->endOfYear()->toDateString();
        }

        $client = User::findOrFail($id);

        $statistics = DB::table('orders')
            ->select(
                DB::raw("DATE(created_at) as order_date"),
                DB::raw(
                    "SUM(IF(`state` = '" .
                    Order::STATE_PAYED .
                    "', 1, 0)) as payed"
                ),
                DB::raw(
                    "SUM(IF(`state` = '" .
                    Order::STATE_NOTPICKED .
                    "', 1, 0)) as notpicked"
                ),
                DB::raw(
                    "SUM(IF(`state` = '" .
                    Order::STATE_CANCEL .
                    "', 1, 0)) as canceled"
                ),
                DB::raw(
                    "SUM(IF(`state` IN ('" .
                    Order::STATE_RECIEVED .
                    "', '" .
                    Order::STATE_INPROGRESS .
                    "', '" .
                    Order::STATE_PENDING .
                    "', '" .
                    Order::STATE_READY .
                    "'), 1, 0)) as active"
                )
            )
            ->where('user_id', $client->id)
            ->whereBetween('created_at', [$start, $end])
            ->groupBy(DB::raw("DATE(created_at)"))
            ->get();

        $client->statistics = $statistics;

        return response()->json($client);
    }

    protected function validator(array $data, $user)
    {
        return Validator::make($data, [
            'name' => 'max:255',
            'email' => [
                'required_unless:role,client',
                Rule::unique('users')
                    ->ignore($user->id)
                    ->whereNotNull('email')
            ],
            'phone' => [
                'required_if:role,client',
                //'numeric',
                Rule::unique('users')
                    ->ignore($user->id)
                    ->whereNotNull('phone')
            ],
            'role' => 'required|in:' . implode(',', User::getRoles()),
            'password' => 'required_without:id'
        ], ['password' => ':attribute required']);
    }
}
