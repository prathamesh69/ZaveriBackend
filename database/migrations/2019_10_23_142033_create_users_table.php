<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->enum('role', ['admin', 'retailer', 'wholesaler']);
            $table->string('name');
            $table->string('mobile')->nullable();
            $table->string('username', 191)->unique();
            $table->string('password');
            $table->unsignedBigInteger('wholesaler_firm_id')->nullable();
            $table->string('image')->nullable();
            $table->string('visiting_card')->nullable();
            $table->string('pincode')->nullable();
            $table->string('city_id')->nullable();
            $table->string('retailer_firm_name')->nullable();
            $table->string('fcm_token')->nullable();
            $table->boolean('approved')->default(true);
            $table->longText('extras')->nullable()->comment("{address,estd,gst}");

            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));

            $table->foreign('wholesaler_firm_id')->references('id')->on('wholesaler_firms')->onDelete('cascade');
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
