<?php

namespace Database\Seeders;

use App\Models\Sale\Sale;
use Illuminate\Support\Str;
use App\Models\Cupone\Cupone;
use App\Models\Product\Product;
use App\Models\Sale\SaleAddres;
use App\Models\Sale\SaleDetail;
use Illuminate\Database\Seeder;
use App\Models\Discount\Discount;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Sale::factory()->count(1000)->create()->each(function($p) {
            $faker = \Faker\Factory::create();

            SaleAddres::create([
                "sale_id" => $p->id,
                "name" => $faker->name(),
                "surname" => $faker->lastName(),
                "company" =>  $faker->word(),
                "country_region" =>  $faker->randomElement(['Perú','Argentina','Bolivia','Brasil','Chile','Costa Rica','Cuba','Ecuador','El Salvador','Uruguay','Venezuela','México']),
                "address" =>  $faker->word(),
                "street" =>  $faker->word(),
                "city" =>  $faker->word(),
                "postcode_zip" => Str::random(4),
                "phone" => $faker->phoneNumber(),
                "email" => $faker->unique()->safeEmail(),
            ]);

            $num_items = $faker->randomElement([1,2,3,4,5]);

            $sum_total_sale = 0;
            for ($i=0; $i < $num_items; $i++) { 
                $quantity = $faker->randomElement([1,2,3,4,5,6,7,8,9,10]);
                $product = Product::inRandomOrder()->first();
                $is_cupon_discount = $faker->randomElement([1,2,3]);
                $discount_cupone = $this->getDiscountCupone($is_cupon_discount);
                $sale_detail = SaleDetail::create([
                    "sale_id" => $p->id,
                    "product_id" => $product->id,
                    "type_discount" => $discount_cupone ? $discount_cupone->type_discount : NULL,
                    "discount" => $discount_cupone ? $discount_cupone->discount : NULL,
                    "type_campaing" => $is_cupon_discount == 2 ? $discount_cupone->type_campaing : NULL,
                    "code_cupon" => $is_cupon_discount == 1 ? $discount_cupone->code : NULL,
                    "code_discount" => $is_cupon_discount == 2 ? $discount_cupone->code : NULL,
                    "product_variation_id" => NULL,
                    "quantity" => $quantity,
                    "price_unit" => $p->currency_total == 'PEN' ? $product->price_pen : $product->price_usd,
                    "subtotal" => $this->getTotalProduct($discount_cupone,$product,$p->currency_total),
                    "total" => $this->getTotalProduct($discount_cupone,$product,$p->currency_total) * $quantity,
                    "currency" => $p->currency_total,
                    "created_at" => $p->created_at,
                    "updated_at" => $p->updated_at,
                ]);
                $sum_total_sale += $sale_detail->total;
            }

            $sale = Sale::findOrFail($p->id);
            
            if($p->currency_total != $p->currency_payment){
                $sum_total_sale = round(($sum_total_sale/3.85),2);
                $sale->update([
                    "subtotal" => $sum_total_sale,
                    "total" => $sum_total_sale,
                ]);
            }else{
                $sale->update([
                    "subtotal" => $sum_total_sale,
                    "total" => $sum_total_sale,
                ]);
            }
            
        });
        // php artisan db:seed --class=SaleSeeder
    }

    public function getDiscountCupone($is_cupon_discount){
        if($is_cupon_discount != 3){
            if($is_cupon_discount == 1){
                $cupone = Cupone::inRandomOrder()->first();
                return $cupone;
            }else{
                $discount = Discount::inRandomOrder()->first();
                return $discount;
            }
        }
        return null;
    }

    public function getTotalProduct($discount_cupone,$product,$currency) {
        if($discount_cupone){
            if($currency == "PEN"){
                $price = $product->price_pen;
            }else{
                $price = $product->price_usd;
            }
            if($discount_cupone->type_discount == 1) {
                $price = $price - $discount_cupone->discount*0.01*$price;
            }else{
                $price = $price - $discount_cupone->discount;
            }
            return $price;
        }
        if($currency == "PEN"){
            return $product->price_pen;
        }else{
            return $product->price_usd;
        }
    }
}
