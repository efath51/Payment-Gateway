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
        $tableName = config('payment-gateway.table_prefix', 'pg_') . 'deposits';

        $userModel = config('payment-gateway.user_model', 'App\Models\User');
        $userTable = (new $userModel)->getTable();

        Schema::create($tableName, function (Blueprint $table) use ($userTable) {
            $table->id();
            $table->foreignId('user_id')->constrained($userTable)->onDelete('cascade');
            $table->integer('plan_id')->default(0);
            $table->integer('order_id')->default(0);
            $table->string('trx')->unique();
            $table->integer('method_code');
            $table->string('method_currency');
            $table->decimal('amount', 28, 8);
            $table->decimal('charge', 28, 8);
            $table->decimal('rate', 28, 8);
            $table->decimal('final_amount', 28, 8);
            $table->text('detail')->nullable();
            $table->string('admin_feedback')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->string('success_url')->nullable();
            $table->string('failed_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('payment-gateway.table_prefix', 'pg_') . 'deposits';

        Schema::dropIfExists($tableName);
    }
};
