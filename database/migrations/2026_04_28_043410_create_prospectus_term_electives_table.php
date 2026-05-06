<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('prospectus_term_electives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospectus_term_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('display_order')->default(1);
            $table->timestamps();

            $table->unique(['prospectus_term_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prospectus_term_electives');
    }
};
