<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\FromCollection;

class SaleExport implements FromView
{
    protected $sales;
    public function __construct($sales) {
        $this->sales = $sales;
    } 
    /**
    * @return \Illuminate\Support\Collection
    */
    public function view(): View
    {
        return view("sale.sale_export",[
            "sales" => $this->sales,
        ]);
    }
}
