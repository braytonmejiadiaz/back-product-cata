<?php

namespace Database\Factories\Sale;

use App\Models\User;
use App\Models\Sale\Sale;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $method_payment = $this->faker->randomElement(["PAYPAL","MERCADOPAGO"]);
        // $date_sales = $this->faker->dateTimeBetween("2023-01-01 00:00:00", "2023-12-25 23:59:59");
        $date_sales = $this->faker->dateTimeBetween("2024-01-01 00:00:00", "2024-12-25 23:59:59");


        $currency_payment = $this->faker->randomElement(["USD","PEN"]);
        return [
            "user_id" => User::where("type_user",2)->inRandomOrder()->first()->id,
            "method_payment" => $method_payment,
            "currency_total" => $currency_payment == "USD" ? $this->faker->randomElement(["USD","PEN"]) : 'PEN',
            "currency_payment" => $currency_payment,
            "discount" => 0,
            "subtotal" => 0,
            "total" => 0,
            "price_dolar" => 0,
            "description" => $this->faker->text($maxNbChars = 300),
            "n_transaccion" =>  Str::random(6),
            "preference_id" =>  $method_payment == "MERCADOPAGO" ? Str::random(5) : NULL,
            // 
            "created_at" => $date_sales,
            "updated_at" => $date_sales,
        ];
    }
}
