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
        $tableName = config('payment-gateway.table_prefix', 'pg_') . 'gateways';

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('alias')->unique();
            $table->integer('code')->unique();
            $table->string('image')->nullable();
            $table->json('parameters')->nullable();
            $table->json('supported_currencies')->nullable();
            $table->text('description')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('payment-gateway.table_prefix', 'pg_') . 'gateways';

        Schema::dropIfExists($tableName);
    }
};
