<?php

namespace App\Http\Controllers\Admin\Sale;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Sale\Sale;
use App\Exports\SaleExport;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Resources\Ecommerce\Sale\SaleCollection;

class SalesController extends Controller
{
    
    
    public function list(Request $request) {

        $search = $request->search;
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $brand_id = $request->brand_id;

        $categorie_first_id = $request->categorie_first_id;
        $categorie_second_id = $request->categorie_second_id;
        $categorie_third_id = $request->categorie_third_id;

        $method_payment = $request->method_payment;

        $sales = Sale::filterAdvanceAdmin($search,$start_date,$end_date,$brand_id,$categorie_first_id,
                        $categorie_second_id,$categorie_third_id,$method_payment)
                        ->orderBy("id","desc")->paginate(25);

        return response()->json([
            "total" => $sales->total(),
            "sales" => SaleCollection::make($sales),
        ]);
    }

    public function list_excel(Request $request){

        $search = $request->search;
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $brand_id = $request->brand_id;

        $categorie_first_id = $request->categorie_first_id;
        $categorie_second_id = $request->categorie_second_id;
        $categorie_third_id = $request->categorie_third_id;

        $method_payment = $request->method_payment;

        $sales = Sale::filterAdvanceAdmin($search,$start_date,$end_date,$brand_id,$categorie_first_id,
                        $categorie_second_id,$categorie_third_id,$method_payment)
                        ->orderBy("id","desc")->get();

        return Excel::download(new SaleExport($sales),"sales_export.xlsx");
    }

    public function report_pdf($id){
        $sale = Sale::findOrFail($id);

        $pdf = PDF::loadView("sale.sale_pdf",compact("sale"));

        return $pdf->stream("venta_pdf".$sale->id.".pdf");
    }
}
