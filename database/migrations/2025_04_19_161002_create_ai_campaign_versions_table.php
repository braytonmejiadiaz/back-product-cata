<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_campaign_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')
                  ->constrained('ai_marketing_campaigns')
                  ->onDelete('cascade');
            $table->integer('version')->default(1);
            $table->json('data'); // Full campaign snapshot
            $table->text('change_description')->nullable();
            $table->timestamps();

            $table->index('campaign_id');
            $table->index(['campaign_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_campaign_versions');
    }
};
