<?php

namespace App\Http\Controllers\Admin;

use App\Models\Slider;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class SliderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->search;

        // Filtra los sliders por el user_id del usuario autenticado
        $sliders = Slider::where("user_id", auth()->id())
                        ->where("title", "like", "%".$search."%")
                        ->orderBy("id", "desc")
                        ->paginate(25);

        return response()->json([
            "total" => $sliders->total(),
            "sliders" => $sliders->map(function($slider){
                return [
                    "id" => $slider->id,
                    "title" => $slider->title,
                    "subtitle" => $slider->subtitle,
                    "label" => $slider->label,
                    "link" => $slider->link,
                    "state" => $slider->state,
                    "type_slider" => $slider->type_slider,
                    "price_original" => $slider->price_original,
                    "price_campaing" => $slider->price_campaing,
                    "color" => $slider->color,
                    "imagen" => env("APP_URL")."storage/".$slider->imagen,
                ];
            }),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if($request->hasFile("image")){
            $path = Storage::putFile("sliders", $request->file("image"));
            $request->request->add(["imagen" => $path]);
        }

        // Asigna el user_id del usuario autenticado
        $request->request->add(["user_id" => auth()->id()]);

        $slider = Slider::create($request->all());

        return response()->json(["message" => 200]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $slider = Slider::findOrFail($id);

        // Verifica que el slider pertenezca al usuario autenticado
        if ($slider->user_id !== auth()->id()) {
            return response()->json(["message" => 403, "message_text" => "No tienes permiso para ver este slider"]);
        }

        return response()->json(["slider" => [
            "id" => $slider->id,
            "title" => $slider->title,
            "subtitle" => $slider->subtitle,
            "label" => $slider->label,
            "link" => $slider->link,
            "state" => $slider->state,
            "color" => $slider->color,
            "type_slider" => $slider->type_slider,
            "price_original" => $slider->price_original,
            "price_campaing" => $slider->price_campaing,
            "imagen" => env("APP_URL")."storage/".$slider->imagen,
        ]]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $slider = Slider::findOrFail($id);

        // Verifica que el slider pertenezca al usuario autenticado
        if ($slider->user_id !== auth()->id()) {
            return response()->json(["message" => 403, "message_text" => "No tienes permiso para actualizar este slider"]);
        }

        if($request->hasFile("image")){
            if($slider->imagen){
                Storage::delete($slider->imagen);
            }
            $path = Storage::putFile("sliders", $request->file("image"));
            $request->request->add(["imagen" => $path]);
        }

        $slider->update($request->all());

        return response()->json(["message" => 200]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $slider = Slider::findOrFail($id);

        // Verifica que el slider pertenezca al usuario autenticado
        if ($slider->user_id !== auth()->id()) {
            return response()->json(["message" => 403, "message_text" => "No tienes permiso para eliminar este slider"]);
        }

        $slider->delete();

        return response()->json(["message" => 200]);
    }
}
