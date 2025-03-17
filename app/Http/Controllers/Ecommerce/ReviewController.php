<?php

namespace App\Http\Controllers\Ecommerce;

use App\Models\Sale\Review;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ReviewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->request->add(["user_id" => auth("api")->user()->id]);
        $review = Review::create($request->all());

        return response()->json([
            "review" => $review,
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
        $review = Review::findOrFail($id);
        $review->update($request->all());
        return response()->json([
            "review" => $review,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
