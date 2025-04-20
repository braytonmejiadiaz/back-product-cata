<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_marketing_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('campaign_name');
            $table->text('campaign_description');
            $table->json('target_audience')->nullable(); // { age: "25-35", interests: ["fashion", "tech"] }
            $table->json('ad_copy')->nullable(); // ["Special offer!", "20% discount"]
            $table->json('visual_recommendations')->nullable(); // { style: "minimalist", colors: ["#FF5733", "#FFFFFF"] }
            $table->string('call_to_action')->nullable();
            $table->string('status')->default('draft'); // draft, published, archived
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_marketing_campaigns');
    }
};
