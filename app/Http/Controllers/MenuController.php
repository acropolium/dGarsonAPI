<?php

namespace App\Http\Controllers;

use App\Company;
use App\MenuItem;
use App\Order;
use App\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;

class MenuController extends Controller
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
     * @param  int  $company
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $company)
    {
        $per_page = $request->input('per_page', null);

        try{
            $mCompany = Company::findOrFail($company);
        }catch (ModelNotFoundException $e){
            return response(['error'=>[trans('messages.company_not_found')]], 404);
        }

        $response = [];

        if($request->user()->role == User::ROLE_CLIENT){
            $order = Order::where('company_id', $mCompany->id)
                ->active()
                ->where('user_id', $request->user()->id)
                ->orderBy('created_at', 'desc')
                ->first();

            if($order){
                return response($order, 302);
            }

            $mCompany->load('locations');
            $response['company'] = $mCompany->toArray();
        }

        $menu = MenuItem::where('company_id', $mCompany->id);

        if($request->has('location_id')){
            $menu->where(function ($query) use ($request){
                $query->whereNull('location_id')->orWhere('location_id', $request->input('location_id'));
            });
        }

        if($request->user()->can('update-menu', $mCompany->id)){
            $menu->with('options.translations', 'translations');
        }else{
            $menu->with('options');
        }

        return response()->json(array_merge($menu->simplePaginate($per_page)->toArray(), $response));
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

        if($request->user()->cant('update-menu', $request->input('company_id'))){
            return response(['error' => [trans('messages.permission_denied')]], 403);
        }

        $data = [
            'company_id' => $request->input('company_id'),
            'location_id' => ($request->has('location_id') ? $request->input('location_id', null) : null),
            'uk' => [
                'name' => $request->input('name_uk'),
                'description' => $request->input('description_uk'),
            ],
            'volume' => $request->input('volume'),
            'price' => $request->input('price')
        ];

        if($request->has('name_en')){
            $data['en'] = [
                'name' => $request->input('name_en'),
                'description' => $request->input('description_en'),
            ];
        }

        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $item = MenuItem::create($data);

        return response()->json($item);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $item = MenuItem::with('options')->find($id);
        return response()->json($item);
    }

    /**
     * Update the specified resource in storage.
     *
     * @var $item MenuItem
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try{
            $item = MenuItem::with('options.translations')->findOrFail($id);
        }catch (ModelNotFoundException $e){
            return response(['error' => [trans('messages.menu_item_not_found')]], 404);
        }

        if($request->user()->cant('update-menu', $item->company_id)){
            return response(['error' => [trans('messages.permission_denied')]], 403);
        }

        $validator = $this->validator($request->all());
        if($validator->fails()){
            return response($validator->errors(), 400);
        };

        $translation = $item->translateOrNew('uk');
        $translation->name = $request->input('name_uk');
        $translation->description = $request->input('description_uk');
        if($request->has('name_en')){
            $translation = $item->translateOrNew('en');
            $translation->name = $request->input('name_en');
            $translation->description = $request->input('description_en');
        }
        $item->price = $request->input('price', $item->price);

        $location = null;
        if($request->has('location_id')){
            $location = $request->input('location_id');
        }

        $item->location_id = $location;
        $item->save();

        return response()->json($item);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try{
            $item = MenuItem::findOrFail($id);
        }catch (ModelNotFoundException $e){
            return response(['error' => [trans('messages.menu_item_not_found')]], 404);
        }

        if(Auth::user()->cant('update-menu', $item->company_id)){
            return response(['error' => [trans('messages.permission_denied')]], 403);
        }

        $item->delete();
        return response()->json(['success'=>'ok']);
    }

    public function logo(Request $request, $id){

        try{
            $item = MenuItem::with('options.translations')->findOrFail($id);
        }catch (ModelNotFoundException $e){
            return response(['error' => [trans('messages.menu_item_not_found')]], 404);
        }

        if($request->user()->cant('update-menu', $item->company_id)){
            return response(['error' => [trans('messages.permission_denied')]], 403);
        }

        if ($request->hasFile('logo') &&  $request->file('logo')->isValid()) {
            $item->logo = $request->file('logo')->store('logos', 'public');
            $item->save();
        }

        return response()->json($item);
    }

    protected function validator(array $data)
    {
        return Validator::make($data, [
            'company_id' => 'integer|exists:companies,id',
            'location_id' => 'integer|nullable|exists:locations,id',
            'name_uk' => 'required|max:255',
            'price' => 'required|numeric',
        ]);
    }
}
