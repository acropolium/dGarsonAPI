<?php

namespace App\Http\Controllers;

use App\Exceptions\MenuChangedException;
use App\MenuItem;
use App\Notifications\NewOrder;
use App\Notifications\OrderStateChanged;
use App\Order;
use App\User;
use Carbon\Carbon;
use \Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Validator;

class OrderController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
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
        $order = Order::with('user')
            //->active()
            ->where(function ($query){
                $query->active()
                    ->orWhereDate('created_at', '>=', Carbon::today()->subDay()->toDateString());
            });
        $order->with('company');
        if($request->has('after')){
            $order->where('updated_at', '>', Carbon::parse($request->input('after'))->toDateTimeString());
        }

        if($request->has('state')){
            $order->whereIn('state', explode(',', $request->input('state')));
        }

        switch ($request->user()->role){
            case 'admin':
                if($request->has('company_id')){
                    $order->whereIn('company_id', explode(',', $request->input('company_id')));
                }
                break;
            case 'owner':
            case 'worker':
                $order->where('company_id', $request->user()->company_id);
                if($request->has('location_id')){
                    $order->where('location_id', $request->input('location_id'));
                }
                break;
            default:
                $order->where('user_id', $request->user()->user_id);
        }

        $orderBy = $request->input('order', 'created_at');
        if($orderBy === 'take_away_time'){
            $order->orderByRaw('DATE_ADD(`created_at`, INTERVAL COALESCE(`desired_time`,0) MINUTE) DESC');
        }else{
            $order->latest();
        }


        return response()->json($order->simplePaginate($per_page));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = $this->validator($request->all());
        if($validator->fails()){
            return response($validator->errors(), 400);
        };

        $order = Order::where('company_id', $request->input('company_id'))
            ->active()
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if($order){
            return response($order, 302);
        }

        $cost = 0;
        $items = $request->input('items');
        $item_ids = array_pluck($items, 'id');
        $menuitems = MenuItem::with('options')->find($item_ids)->keyBy('id');
        if($menuitems->count() !== count(array_unique(array_pluck($items, 'id')), SORT_NUMERIC)){
            throw new MenuChangedException();
        }
        foreach ($items as &$item){
            $menuitem = $menuitems->get($item['id']);
            if($menuitem && $menuitem['price'] == $item['price'] && $menuitem['name'] === $item['name']){
                $cost += $menuitem['price'];

                $orderedOptions = [];
                if(isset($item['options']) && is_array($item['options'])){
                    $menuitem['options'] = $menuitem['options']->keyBy('id');
                    for($i=0;$i<count($item['options']);$i++){
                        $menuOption = $menuitem['options']->get($item['options'][$i]['id']);

                        if(!$menuOption || $item['options'][$i]['name'] !== $menuOption['name'] || floatval($item['options'][$i]['price']) != floatval($menuOption['price'])){
                            throw new MenuChangedException();
                        }

                        if($item['options'][$i]['count'] > 0){
                            $cost += $menuOption['price'];// * $item['options'][$i]['count'];
                            $orderedOptions[] = $item['options'][$i];
                        }
                    }
                }
                $item['options'] = $orderedOptions;

            }else{
                throw new MenuChangedException();
            }

        }

        $order = new Order();
        $order->company_id = $request->input('company_id');
        $order->location_id = $request->input('location_id');
        $order->user_id = $request->user()->id;
        $order->state = Order::STATE_PENDING;
        $order->items = $items;
        $order->cost = $cost;
        $order->desired_time = $request->input('desired_time');
        $order->save();

        $users = User::with('device_tokens')
            ->where('company_id', $order->company_id)
            ->where('role', '<>', User::ROLE_CLIENT)
            ->whereHas('device_tokens', function($query) use ($order){
                $query->where('location_id', $order->location_id);
            })->get();
        try{
            Notification::send($users, new NewOrder($order));
        }catch (\Exception $e){
            Log::debug($e->getMessage());
        }

        return response()->json($order);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try{
            $order = Order::with('user')->findOrFail($id);
        }catch (ModelNotFoundException $e){
            return response(['error'=>[trans('messages.order_not_found')]], 404);
        }

        return response()->json($order);
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
        $this->validate($request, [
            'state' => 'in:'.implode(',', Order::getStates()),
        ]);

        try{
            $order = Order::with('user')->findOrFail($id);
        }catch (ModelNotFoundException $e){
            return response(['error'=>[trans('messages.order_not_found')]], 404);
        }

        if($request->user()->cant('update-order', $order)){
            return response(['error' => [trans('messages.permission_denied')]], 403);
        }

        if($order->state !== Order::STATE_CANCEL) {

            if($request->user()->id == $order->user_id
                && !in_array($order->state, [Order::STATE_PENDING, Order::STATE_RECIEVED])
                && $request->input('state') !== Order::STATE_CANCEL){
                return response(['error' => [trans('messages.permission_denied')]], 403);
            }

            if(array_search($order->state, Order::getStates()) >= array_search($request->input('state'), Order::getStates())){
                return response(['error'=>[trans("messages.error_change_state",["state" => $request->input('state')])]], 403);
            }

            $order->state = $request->input('state');
            $order->save();

            try{
                $users = User::with('device_tokens')
                    ->where(function ($query) use ($order) {
                        $query->where(function ($query) use ($order) {
                            $query->where('company_id', $order->company_id)
                                ->where('role', '<>', User::ROLE_CLIENT)
                                ->whereHas('device_tokens', function($query) use ($order){
                                    $query->where('location_id', $order->location_id);
                                });
                        })
                        ->orWhere(function ($query) use ($order) {
                            $query->where('id', $order->user_id)
                                ->where('role', User::ROLE_CLIENT)
                                ->has('device_tokens');
                        });
                    })
                    ->where('id', '<>', $request->user()->id)
                    ->get();

                Notification::send($users, new OrderStateChanged($order));

            }catch (\Exception $e){
                Log::debug($e->getMessage());
            }
        }

        return response()->json($order);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function lastOrder(Request $request)
    {
        $order = Order::where('user_id', $request->user()->id)->orderBy('created_at', 'desc');
        if($request->has('company_id')){
            $order->where('company_id', $request->input('company_id'));
        }
        if($request->has('location_id')){
            $order->where('location_id', $request->input('location_id'));
        }
        try{
            return response()->json($order->firstOrFail());
        }catch (ModelNotFoundException $e){
            return response(['error'=>[trans('messages.order_not_found')]], 404);
        }

    }

    protected function validator(array $data)
    {
        return Validator::make($data, [
            'company_id' => 'required|integer|exists:companies,id',
            'location_id' => 'required|integer|exists:locations,id',
            'items' => 'required',
        ]);
    }

}
