<?php

namespace App\Http\Controllers\Ecommerce;

use Illuminate\Http\Request;
use App\Models\Sale\UserAddres;
use App\Http\Controllers\Controller;

class UserAddressController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth('api')->user();
        $address = UserAddres::where("user_id",$user->id)->orderBy("id","desc")->get();

        return response()->json([
            "address" => $address,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->request->add(["user_id" => auth("api")->user()->id]);
        $addres = UserAddres::create($request->all());

        return response()->json([
            "addres" => $addres,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $addres = UserAddres::findOrFail($id);
        $addres->update($request->all());
        return response()->json([
            "addres" => $addres,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $addres = UserAddres::findOrFail($id);
        $addres->delete();

        return response()->json([
            "message" => 200
        ]);
    }
}
