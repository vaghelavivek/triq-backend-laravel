<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('order_id');
            $table->string('transaction_id');
            $table->string('paytm_order_id')->nullable();
            $table->string('bank_transaction_id')->nullable();
            $table->float('amount',20,2);
            $table->string('currency',20);
            $table->string('payment_type',20);
            $table->text('transaction_details')->nullable();
            $table->string('status',20);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_transactions');
    }
};
