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
        Schema::table('users', function (Blueprint $table) {
            $table->string('id_number')->nullable()->unique()->after('id');
            $table->string('username')->nullable()->unique()->after('email');
            $table->string('first_name')->nullable()->after('username');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('last_name')->nullable()->after('middle_name');
            $table->string('name_suffix')->nullable()->after('last_name');
            $table->string('role')->default('student')->after('name_suffix');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('id_number')->nullable(false)->change();
            $table->string('username')->nullable(false)->change();
            $table->string('first_name')->nullable(false)->change();
            $table->string('last_name')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['id_number']);
            $table->dropUnique(['username']);
            $table->dropColumn([
                'id_number',
                'username',
                'first_name',
                'middle_name',
                'last_name',
                'name_suffix',
                'role',
            ]);
        });
    }
};
