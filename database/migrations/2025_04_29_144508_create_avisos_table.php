<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // En el archivo de migración
    public function up()
    {
        Schema::create('avisos', function (Blueprint $table) {
            $table->id();
            $table->text('contenido'); // Almacenará el texto con formato (HTML o Markdown)
            $table->json('estilos')->nullable(); // Para guardar los estilos personalizados
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avisos');
    }
};
