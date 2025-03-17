<?php

namespace App\Http\Controllers\Admin\Sale;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class KpiSaleReportController extends Controller
{
    public function config(){
        $months_name = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
        return response()->json([
            "year" =>date("Y"),
            "month" =>date("m"),
            "meses" => $months_name,
        ]);
    }

    public function report_sales_country_for_year(Request $request) {

        $year = $request->year;
        $month = $request->month;

        $sales_for_year = DB::table("sales")->where("sales.deleted_at",NULL)
                                ->whereYear("sales.created_at",$year)
                                // ->whereMonth("sales.created_at",$month)
                                ->select(
                                    DB::raw("ROUND(SUM(IF(sales.currency_payment = 'USD',sales.total*3.85,sales.total)),2) as sales_total")
                                )
                                ->get()
                                ->sum("sales_total");

        // $month_last = Carbon::parse($year.'-'.$month.'-'.'01')->subMonth();

        $sales_for_year_last = DB::table("sales")->where("sales.deleted_at",NULL)
                                ->whereYear("sales.created_at",$year - 1)
                                // ->whereMonth("sales.created_at",$month_last->format("m"))
                                ->select(
                                    DB::raw("ROUND(SUM(IF(sales.currency_payment = 'USD',sales.total*3.85,sales.total)),2) as sales_total")
                                )
                                ->get()
                                ->sum("sales_total");

        $percentageV = 0;
        if($sales_for_year_last > 0){
            $percentageV = (($sales_for_year - $sales_for_year_last)/$sales_for_year_last)*100;
        }

        $query = DB::table("sales")->where("sales.deleted_at",NULL)
                    ->join("sale_addres","sale_addres.sale_id","=","sales.id")
                    ->whereYear("sales.created_at",$year);
        if($month){
            $query->whereMonth("sales.created_at",$month);
        }

        $query->select("sale_addres.country_region as country_region",
                DB::raw("ROUND(SUM(IF(sales.currency_payment = 'USD',sales.total*3.85,sales.total)),2) as total_sales"))
                ->groupBy("country_region")
                ->orderBy("total_sales","desc");

        $query = $query->take(6)->get();

        return response()->json([
            "sales_for_country" => $query,
            "percentageV" => round($percentageV,2),
            "sales_for_year" => round($sales_for_year,2)
        ]);
    }

    public function report_sales_week_categorias(){
        
        $start_week = Carbon::now()->startOfWeek();
        $end_week = Carbon::now()->endOfWeek();

        $start_week_last = Carbon::now()->subWeek()->startOfWeek();
        $end_week_last = Carbon::now()->subWeek()->endOfWeek();
        // dd($start_week_last,$end_week_last);
        $sales_week = DB::table("sales")->where("sales.deleted_at",NULL)
                    ->whereBetween("sales.created_at",[$start_week->format("Y-m-d")." 00:00:00",$end_week->format("Y-m-d")." 23:59:59"])
                    ->select(
                        DB::raw("ROUND(SUM(IF(sales.currency_payment = 'USD',sales.total*3.85,sales.total)),2) as sales_total")
                    )
                    ->get()
                    ->sum("sales_total");

        $sales_week_last = DB::table("sales")->where("sales.deleted_at",NULL)
                    ->whereBetween("sales.created_at",[$start_week_last->format("Y-m-d")." 00:00:00",$end_week_last->format("Y-m-d")." 23:59:59"])
                    ->select(
                        DB::raw("ROUND(SUM(IF(sales.currency_payment = 'USD',sales.total*3.85,sales.total)),2) as sales_total")
                    )
                    ->get()
                    ->sum("sales_total");
        
        $porcentageV = 0;

        if($sales_week_last > 0){
            $porcentageV = (($sales_week-$sales_week_last)/$sales_week_last)*100;
        }

        $sales_week_categories = DB::table("sales")->where("sales.deleted_at",NULL)
                    ->join("sale_details","sale_details.sale_id","=","sales.id")
                    ->join("products","sale_details.product_id","=","products.id")
                    ->join("categories","products.categorie_first_id","=","categories.id")
                    ->where("sale_details.deleted_at",NULL)
                    ->whereBetween("sales.created_at",[$start_week->format("Y-m-d")." 00:00:00",$end_week->format("Y-m-d")." 23:59:59"])
                    ->select("categories.name as categorie_name",DB::raw("ROUND(SUM(IF(sale_details.currency = 'USD',sale_details.total*3.85,sale_details.total)),2) as categorie_total"))
                    ->groupBy("categorie_name")
                    ->orderBy("categorie_total","desc")
                    ->take(3)
                    ->get();

        return response()->json([
            "sales_week" => round($sales_week,2),
            "porcentageV" => round($porcentageV,2),
            "sales_week_categories" => $sales_week_categories
        ]);
    }

    public function report_sales_week_discounts(){
        $start_week = Carbon::now()->startOfWeek();
        $end_week = Carbon::now()->endOfWeek();

        $start_week_last = Carbon::now()->subWeek()->startOfWeek();
        $end_week_last = Carbon::now()->subWeek()->endOfWeek();
        
        $sales_week_discounts = DB::table("sales")->where("sales.deleted_at",NULL)
                    ->join("sale_details","sale_details.sale_id","=","sales.id")
                    ->where("sale_details.deleted_at",NULL)
                    ->whereBetween("sales.created_at",[$start_week->format("Y-m-d")." 00:00:00",$end_week->format("Y-m-d")." 23:59:59"])
                    ->sum("sale_details.discount");

        $sales_week_discounts_last = DB::table("sales")->where("sales.deleted_at",NULL)
                    ->join("sale_details","sale_details.sale_id","=","sales.id")
                    ->where("sale_details.deleted_at",NULL)
                    ->whereBetween("sales.created_at",[$start_week_last->format("Y-m-d")." 00:00:00",$end_week_last->format("Y-m-d")." 23:59:59"])
                    ->sum("sale_details.discount");
        
        $porcentageV = 0;

        if($sales_week_discounts_last > 0){
            $porcentageV = (($sales_week_discounts-$sales_week_discounts_last)/$sales_week_discounts_last)*100;
        }

        $sales_week_discounts_for_day = DB::table("sales")->where("sales.deleted_at",NULL)
                    ->join("sale_details","sale_details.sale_id","=","sales.id")
                    ->where("sale_details.deleted_at",NULL)
                    ->whereBetween("sales.created_at",[$start_week->format("Y-m-d")." 00:00:00",$end_week->format("Y-m-d")." 23:59:59"])
                    ->select(
                        DB::raw("DATE_FORMAT(sales.created_at,'%Y-%m-%d') as date_format"),
                        DB::raw("ROUND(SUM(sale_details.discount),2) as discount_total")
                    )
                    ->groupBy("date_format")
                    ->get();
        
        $discount_for_days = collect([]);
        foreach ($sales_week_discounts_for_day as $key => $sales_week_discount) {
            $discount_for_days->push([
                "date" => $sales_week_discount->date_format,
                "percentage" => round((($sales_week_discount->discount_total)/$sales_week_discounts)*100,2)
            ]);
        }
        return response()->json([
            "discount_for_days" => $discount_for_days,
            "sales_week_discounts" => round($sales_week_discounts,2),
            "porcentageV" => round($porcentageV,2),
        ]);
    }

    public function report_sales_month_selected(Request $request) {

        $year = $request->year;
        $month = $request->month;

        $sales_for_day_of_month = DB::table("sales")->where("sales.deleted_at",NULL)
                ->whereYear("sales.created_at",$year)
                ->whereMonth("sales.created_at",$month)
                ->select(
                    DB::raw("DATE_FORMAT(sales.created_at,'%Y-%m-%d') as date_format"),
                    DB::raw("DATE_FORMAT(sales.created_at,'%m-%d') as date_format_day"),
                    DB::raw("ROUND(SUM(IF(sales.currency_payment = 'USD',sales.total*3.85,sales.total)),2) as sales_total")
                )
                ->groupBy("date_format","date_format_day")
                ->get();

        // Y-m-d
        $month_last = Carbon::parse($year.'-'.$month.'-'.'01')->subMonth();

        $sales_for_month_last = DB::table("sales")->where("sales.deleted_at",NULL)
                                ->whereYear("sales.created_at",$month_last->format("Y"))
                                ->whereMonth("sales.created_at",$month_last->format("m"))
                                ->select(
                                    DB::raw("ROUND(SUM(IF(sales.currency_payment = 'USD',sales.total*3.85,sales.total)),2) as sales_total")
                                )
                                ->get()
                                ->sum("sales_total");

        $percentageV = 0;
        if($sales_for_month_last){
            $percentageV = (($sales_for_day_of_month->sum("sales_total") - $sales_for_month_last)/$sales_for_month_last)*100;
        }
        return response()->json([
            "percentageV" => round($percentageV,2),
            // "sales_for_month_last" =>$sales_for_month_last,
            "total_sales_for_month" => round($sales_for_day_of_month->sum("sales_total"),2),
            "sales_for_day_of_month" => $sales_for_day_of_month,
        ]);
    }
    
    public function report_sales_for_month_year_selected(Request $request){

        $year = $request->year;

        $query = DB::table("sales")->where("sales.deleted_at",NULL)
                    ->whereYear("sales.created_at",$year)
                    ->select(
                        DB::raw("DATE_FORMAT(sales.created_at,'%Y-%m') as date_format_month"),
                        DB::raw("ROUND(SUM(IF(sales.currency_payment = 'USD',sales.total*3.85,sales.total)),2) as sale_total")
                    )
                    ->groupBy("date_format_month")
                    ->get();

        $query_last = DB::table("sales")->where("sales.deleted_at",NULL)
                    ->whereYear("sales.created_at",$year-1)
                    ->select(
                        DB::raw("DATE_FORMAT(sales.created_at,'%Y-%m') as date_format_month"),
                        DB::raw("ROUND(SUM(IF(sales.currency_payment = 'USD',sales.total*3.85,sales.total)),2) as sale_total")
                    )
                    ->groupBy("date_format_month")
                    ->get();

        
        $query_discount = DB::table("sales")->where("sales.deleted_at",NULL)
                            ->join("sale_details","sale_details.sale_id","=","sales.id")
                            ->where("sale_details.deleted_at",NULL)
                            ->whereYear("sales.created_at",$year)
                            ->where("sale_details.code_discount","<>",NULL)
                            ->select(
                                DB::raw("ROUND(SUM(IF(sale_details.currency = 'USD',sale_details.discount*3.85,sale_details.discount)),2) as discount_total"),
                                DB::raw("COUNT(*) as count_total")
                            )
                            ->get();

        $query_cupone = DB::table("sales")->where("sales.deleted_at",NULL)
                            ->join("sale_details","sale_details.sale_id","=","sales.id")
                            ->where("sale_details.deleted_at",NULL)
                            ->whereYear("sales.created_at",$year)
                            ->where("sale_details.code_cupon","<>",NULL)
                            ->select(
                                DB::raw("ROUND(SUM(IF(sale_details.currency = 'USD',sale_details.discount*3.85,sale_details.discount)),2) as discount_total"),
                                DB::raw("COUNT(*) as count_total")
                            )
                            ->get();
        return response()->json([
            "query_cupone" => $query_cupone,
            "query_discount" => $query_discount,
            "sales_for_month_year_last" => $query_last,
            "sales_form_month_year_total" => $query->sum("sale_total"),
            "sales_for_month_year" => $query,
        ]);
    }

    public function report_discount_cupone_year(Request $request) {

        $year = $request->year;

        $query_cupone = DB::table("sales")->where("sales.deleted_at",NULL)
                            ->join("sale_details","sale_details.sale_id","=","sales.id")
                            ->where("sale_details.deleted_at",NULL)
                            ->whereYear("sales.created_at",$year)
                            ->where("sale_details.code_cupon","<>",NULL)
                            ->select(
                                DB::raw("sale_details.code_cupon as cupone"),
                                DB::raw("COUNT(*) as count_total")
                            )
                            ->groupBy("cupone")
                            ->get();

        $query_discount = DB::table("sales")->where("sales.deleted_at",NULL)
                            ->join("sale_details","sale_details.sale_id","=","sales.id")
                            ->where("sale_details.deleted_at",NULL)
                            ->whereYear("sales.created_at",$year)
                            ->where("sale_details.code_discount","<>",NULL)
                            ->select(
                                DB::raw("sale_details.code_discount as code_discount"),
                                DB::raw("COUNT(*) as count_total")
                            )
                            ->groupBy("code_discount")
                            ->get();
        return response()->json([
            "uso_discount_year" => $query_discount,
            "canje_cupone_year" => $query_cupone,
        ]);
    }

    public function report_sales_for_categories(Request $request) {

        $year = $request->year;
        $month = $request->month;

        $sales_for_month = DB::table("sales")->where("sales.deleted_at",NULL)
                                ->whereYear("sales.created_at",$year)
                                ->whereMonth("sales.created_at",$month)
                                ->select(
                                    DB::raw("ROUND(SUM(IF(sales.currency_payment = 'USD',sales.total*3.85,sales.total)),2) as sales_total")
                                )
                                ->get()
                                ->sum("sales_total");

        $month_last = Carbon::parse($year.'-'.$month.'-'.'01')->subMonth();

        $sales_for_month_last = DB::table("sales")->where("sales.deleted_at",NULL)
                                ->whereYear("sales.created_at",$month_last->format("Y"))
                                ->whereMonth("sales.created_at",$month_last->format("m"))
                                ->select(
                                    DB::raw("ROUND(SUM(IF(sales.currency_payment = 'USD',sales.total*3.85,sales.total)),2) as sales_total")
                                )
                                ->get()
                                ->sum("sales_total");

        $percentageV = 0;
        if($sales_for_month_last){
            $percentageV = (($sales_for_month - $sales_for_month_last)/$sales_for_month_last)*100;
        }

        $query = DB::table('sales')->where("sales.deleted_at",NULL)
                    ->join("sale_details","sale_details.sale_id",'=',"sales.id")
                    ->where("sale_details.deleted_at",NULL)
                    ->whereYear("sales.created_at",$year)
                    ->whereMonth("sales.created_at",$month)
                    ->join("products","products.id",'=',"sale_details.product_id")
                    ->join("categories","categories.id",'=',"products.categorie_first_id")
                    ->select(
                        "categories.name as categorie_name",
                        DB::raw("ROUND(SUM(IF(sale_details.currency = 'USD',sale_details.total*3.85,sale_details.total)),2) as categories_total"),
                        DB::raw("ROUND(SUM(sale_details.quantity),2) as categories_quantity"),
                        DB::raw("ROUND(SUM(IF(sale_details.currency = 'USD',sale_details.total*3.85,sale_details.total)/30),2) as categories_avg"),
                    )
                    ->groupBy("categorie_name")
                    ->orderBy("categories_total","desc")
                    ->take(5)
                    ->get();
        
        return response()->json([
            "sale_for_categories" => $query,
            "percentageV" => round($percentageV,2),
            "sales_total" =>  round($sales_for_month,2),
        ]);
    }

    public function report_sales_for_categories_details(Request $request) {

        $year = $request->year;
        $month = $request->month;

        $sales_month_categories = DB::table("sales")->where("sales.deleted_at",NULL)
                    ->join("sale_details","sale_details.sale_id","=","sales.id")
                    ->join("products","sale_details.product_id","=","products.id")
                    ->join("categories","products.categorie_first_id","=","categories.id")
                    ->where("sale_details.deleted_at",NULL)
                    ->whereYear("sales.created_at",$year)
                    ->whereMonth("sales.created_at",$month)
                    ->select("categories.name as categorie_name",
                    "categories.id as categorie_id","categories.imagen as categorie_imagen",
                    DB::raw("ROUND(SUM(IF(sale_details.currency = 'USD',sale_details.total*3.85,sale_details.total)),2) as categorie_total"))
                    ->groupBy("categorie_name","categorie_id",'categorie_imagen')
                    ->orderBy("categorie_total","desc")
                    ->take(5)
                    ->get();

        $product_most_sales = collect([]);
        foreach ($sales_month_categories as $key => $sales_month_categ) {

            $query_product_most_sales = DB::table("sales")->where("sales.deleted_at",NULL)
                    ->join("sale_details","sale_details.sale_id","=","sales.id")
                    ->join("products","sale_details.product_id","=","products.id")
                    ->join("categories","products.categorie_first_id","=","categories.id")
                    ->where("sale_details.deleted_at",NULL)
                    ->whereYear("sales.created_at",$year)
                    ->whereMonth("sales.created_at",$month)
                    ->where("products.categorie_first_id",$sales_month_categ->categorie_id)
                    ->select("products.title as product_title","products.sku as product_sku",
                    "products.price_pen as product_price","products.imagen as product_imagen",
                    DB::raw("ROUND(SUM(IF(sale_details.currency = 'USD',sale_details.total*3.85,sale_details.total)),2) as product_total"),
                    DB::raw("ROUND(SUM(sale_details.quantity),2) as product_quantity_total")
                    )
                    ->groupBy("product_title","product_sku","product_price","product_imagen")
                    ->orderBy("product_total","desc")
                    ->take(3)
                    ->get();

            $product_most_sales->push([
                "categorie_id" => $sales_month_categ->categorie_id,
                "products" => $query_product_most_sales->map(function($item){
                    $item->imagen = env("APP_URL")."storage/".$item->product_imagen;
                    return $item;
                }),
            ]);
        }

        return response()->json([
            "product_most_sales" => $product_most_sales,
            "sale_month_categories" => $sales_month_categories->map(function($item) {
                $item->imagen = env("APP_URL")."storage/".$item->categorie_imagen;
                return $item;
            }),
        ]);
    }

    public function report_sales_for_brand(Request $request) {

        $year = $request->year;
        $month = $request->month;

        $query = DB::table("sales")->where("sales.deleted_at",NULL)
                    ->join("sale_details","sale_details.sale_id","=","sales.id")
                    ->join("products","sale_details.product_id","=","products.id")
                    ->join("brands","products.brand_id","=","brands.id")
                    ->where("sale_details.deleted_at",NULL)
                    ->whereYear("sales.created_at",$year)
                    ->whereMonth("sales.created_at",$month)
                    ->select("brands.name as brand_name",
                    "brands.id as brand_t_id",
                    DB::raw("ROUND(SUM(IF(sale_details.currency = 'USD',sale_details.total*3.85,sale_details.total)),2) as brand_total"),
                    DB::raw("ROUND(SUM(sale_details.quantity),2) as quantity_total")
                    )
                    ->groupBy("brand_name","brand_t_id")
                    ->get();
        return response()->json([
            "sales_for_brand" => $query,
        ]);
    }
}
