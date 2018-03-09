<?php

namespace App\Http\Controllers;

use App\Company;
use App\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
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
        $company = Company::query();

        if($request->has('locations')){
            $company->with('locations');
        }

        if($request->user()->role === User::ROLE_CLIENT && $request->has('last_order')){
            $company->with(['latestOrder' => function ($query) use ($request) {
                $query->where('user_id', $request->user()->id)->active();
                    //->latest();
            }]);
        }
        //echo $company->toSql();

        return response()->json($company->simplePaginate($per_page));
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

        $company = new Company();

        if($request->user()->cant('update-companies', $company)) {
            return response(['error' => [trans('messages.permission_denied')]], 403);
        }

        $translations_uk = $company->translateOrNew('uk');
        $translations_uk->name = $request->input('name_uk');
        $translations_uk->address = $request->input('address_uk');
        if($request->has('name_en')){
            $translations_en = $company->translateOrNew('en');
            $translations_en->name = $request->input('name_en');
            $translations_en->address = $request->input('address_en');
        }
        $company->phone = $request->input('phone');
        $company->email = $request->input('email');
        $company->currency = $request->input('currency');


        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            $company->logo = $request->file('logo')->store('logos', 'public');
        }
        $company->save();

        return response()->json($company);
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
            $company = Company::findOrFail($id);
        }catch (ModelNotFoundException $e){
            return response(['error'=>[trans('messages.company_not_found')]], 404);
        }
        return response()->json($company);
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
        try{
            $company = Company::findOrFail($id);
        }catch (ModelNotFoundException $e){
            return response(['error'=>[trans('messages.company_not_found')]], 404);
        }

        if($request->user()->cant('update-companies', $company)){
            return response(['error' => [trans('messages.permission_denied')]], 403);
        }

        $validator = $this->validator($request->all());
        if($validator->fails()){
            return response($validator->errors(), 400);
        };

        $company->translate('uk')->name = $request->input('name_uk', $company->name_uk);
        $company->translate('uk')->address = $request->input('address_uk', $company->address_uk);
        if($request->has('address_en') || $request->has('name_en')) {
            $translation = $company->translateOrNew('en');
            $translation->name = $request->input('name_en');
            $translation->address = $request->input('address_en');
        }
        $company->phone = $request->input('phone', $company->phone);
        $company->email = $request->input('email', $company->email);
        $company->currency = $request->input('currency', $company->currency);
        $company->save();

        return response()->json($company);
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
            $company = Company::findOrFail($id);
        }catch (ModelNotFoundException $e){
            return response(['error'=>[trans('messages.company_not_found')]], 404);
        }

        if(Auth::user()->role != User::ROLE_ADMIN){
            return response(['error' => [trans('messages.permission_denied')]], 403);
        }
        $company->delete();
        return response()->json(['success'=>'ok']);
    }

    public function logo(Request $request, $id){

        try{
            $company = Company::findOrFail($id);
        }catch (ModelNotFoundException $e){
            return response(['error'=>[trans('messages.company_not_found')]], 404);
        }

        if($request->user()->cant('update-companies', $company)){
            return response(['error' => [trans('messages.permission_denied')]], 403);
        }
        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            $company->logo = $request->file('logo')->store('logos', 'public');
            $company->save();
        }

        return response()->json($company);
    }

    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name_uk' => 'required|max:255',
            'email' => 'email|max:255',
            'phone' => 'required',
            'currency' => ['required', Rule::in(Company::getAvailableCurrencies())]
        ]);
    }
}
