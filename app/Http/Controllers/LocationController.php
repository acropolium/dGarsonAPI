<?php

namespace App\Http\Controllers;

use App\Company;
use App\Location;
use App\MenuItem;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LocationController extends Controller
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
        try{
            $company = Company::findOrFail($company);
        }catch (ModelNotFoundException $e){
            return response(['error'=>[trans('messages.company_not_found')]], 404);
        }
        $locations = Location::where('company_id', $company->id);
        return response()->json($locations->get());
    }

    /**
     * Get one location by id.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $location
     * @return \Illuminate\Http\Response
     */
    public function item(Request $request, $location)
    {
        $location = Location::where('id', $location);
        return response()->json($location->get());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try{
            $company = Company::findOrFail($request->input('company_id'));
        }catch (ModelNotFoundException $e){
            return response(['error'=>[trans('messages.company_not_found')]], 404);
        }

        if($request->user()->cant('update-companies', $company)){
            return response(['error' => [trans('messages.permission_denied')]], 403);
        }

        $location = Location::create([
            'company_id' => $request->input('company_id'),
            'address' => $request->input('address'),
            'phone' => $request->input('phone'),
            'lat' => $request->input('lat'),
            'lng' => $request->input('lng'),
        ]);

        return response()->json($location);
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
        try{
            $location = Location::findOrFail($id);
        }catch (ModelNotFoundException $e){
            return response(['error'=>[trans('messages.company_not_found')]], 404);
        }

        if($request->user()->cant('update-companies', $location->company)){
            return response(['error' => [trans('messages.permission_denied')]], 403);
        }

        $location->address = $request->input('address', $location->address);
        $location->phone = $request->input('phone', $location->phone);
        $location->lat = $request->input('lat', $location->lat);
        $location->lng = $request->input('lng', $location->lng);
        $location->save();

        return response()->json($location);
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
            $location = Location::findOrFail($id);
        }catch (ModelNotFoundException $e){
            return response(['error'=>[trans('messages.company_not_found')]], 404);
        }

        if(Auth::user()->cant('update-companies', $location->company)){
            return response(['error' => [trans('messages.permission_denied')]], 403);
        }

        MenuItem::where('location_id', $location->id)->delete();
        $location->delete();

        return response()->json(['success'=>'ok']);
    }
}
