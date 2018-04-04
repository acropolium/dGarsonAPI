<?php
namespace App\Http\Controllers;

use App\MenuItem;
use App\MenuItemOption;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Validator;

class MenuOptionController extends Controller
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
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
        if ($validator->fails()) {
            return response($validator->errors(), 400);
        }

        try {
            $menu = MenuItem::findOrFail($request->input('menu_item_id'));
        } catch (ModelNotFoundException $e) {
            return response([
                'error' => [trans('messages.menu_item_not_found')]
            ], 404);
        }

        if ($request->user()->cant('update-menu', $menu->company_id)) {
            return response([
                'error' => [trans('messages.permission_denied')]
            ], 403);
        }

        $data = [
            'menu_item_id' => $menu->id,
            'uk' => ['name' => $request->input('name_uk')],
            'price' => $request->input('price'),
            'count' => $request->input('count', 0)
        ];

        if ($request->has('name_en')) {
            $data['en'] = ['name' => $request->input('name_en')];
        }

        $item = MenuItemOption::create($data);

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
        try {
            $option = MenuItemOption::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response([
                'error' => [trans('messages.option_not_found')]
            ], 404);
        }

        if (
            $request->user()->cant(
                'update-menu',
                $option->menu_item->company_id
            )
        ) {
            return response([
                'error' => [trans('messages.permission_denied')]
            ], 403);
        }

        $validator = $this->validator($request->all());
        if ($validator->fails()) {
            return response($validator->errors(), 400);
        }

        $translation = $option->translateOrNew('uk');
        $translation->name = $request->input('name_uk');
        if ($request->has('name_en')) {
            $translation = $option->translateOrNew('en');
            $translation->name = $request->input('name_en');
        }
        $option->price = $request->input('price', $option->price);
        $option->count = $request->input('count', $option->count);
        $option->save();

        return response()->json($option);
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
            $option = MenuItemOption::with('menu_item')->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response([
                'error' => [trans('messages.option_not_found')]
            ], 404);
        }
        if (Auth::user()->cant('update-menu', $option->menu_item->company_id)) {
            return response([
                'error' => [trans('messages.permission_denied')]
            ], 403);
        }
        $option->delete();
        return response()->json(['success' => 'ok']);
    }

    protected function validator(array $data)
    {
        return Validator::make($data, [
            'menu_item_id' => 'integer',
            'name_uk' => 'required|max:255',
            'price' => 'required|numeric',
            'count' => 'integer'
        ]);
    }
}
