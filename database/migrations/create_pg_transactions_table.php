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
        $tableName = config('payment-gateway.table_prefix', 'pg_') . 'transactions';

        $userModel = config('payment-gateway.user_model', 'App\Models\User');
        $userTable = (new $userModel)->getTable();

        Schema::create($tableName, function (Blueprint $table) use ($userTable) {
            $table->id();
            $table->foreignId('user_id')->constrained($userTable)->onDelete('cascade');
            $table->decimal('amount', 18, 8)->default(0);
            $table->decimal('charge', 18, 8)->default(0);
            $table->decimal('post_balance', 18, 8)->nullable();
            $table->enum('trx_type', ['+', '-']);
            $table->string('trx', 50)->unique();
            $table->text('details')->nullable();
            $table->string('remark', 50)->nullable();
            $table->timestamps();

            // Indexes for faster queries
            $table->index('user_id');
            $table->index('trx');
            $table->index('remark');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('payment-gateway.table_prefix', 'pg_') . 'transactions';

        Schema::dropIfExists($tableName);
    }
};
