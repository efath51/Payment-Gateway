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
        $tableName = config('payment-gateway.table_prefix', 'pg_') . 'gateway_currencies';
        $gatewaysTable = config('payment-gateway.table_prefix', 'pg_') . 'gateways';

        Schema::create($tableName, function (Blueprint $table) use ($gatewaysTable) {
            $table->id();
            $table->foreignId('gateway_id')->constrained($gatewaysTable)->cascadeOnDelete();
            $table->string('name');
            $table->string('currency');
            $table->integer('method_code');
            $table->string('symbol')->default('');
            $table->decimal('rate', 18, 8);
            $table->decimal('min_amount', 18, 8)->default(0);
            $table->decimal('max_amount', 18, 8)->default(0);
            $table->decimal('fixed_charge', 18, 8)->default(0);
            $table->decimal('percent_charge', 8, 4)->default(0);
            $table->text('gateway_parameter')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('payment-gateway.table_prefix', 'pg_') . 'gateway_currencies';

        Schema::dropIfExists($tableName);
    }
};
