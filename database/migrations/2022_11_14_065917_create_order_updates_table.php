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
        Schema::create('order_updates', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->integer('order_id')->nullable();
            $table->integer('parent_id')->nullable();
            $table->text('notes')->nullable();
            $table->tinyInteger('is_send_email')->nullable();
            $table->tinyInteger('is_personal_note')->nullable();
            $table->tinyInteger('add_to_profile')->nullable();
            $table->string('attachment')->nullable();
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
        Schema::dropIfExists('order_updates');
    }
};
