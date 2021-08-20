<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateRetailerRatingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('retailer_ratings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('wholesaler_firm_id');
            $table->integer('rating');
            $table->string('pincode');
            $table->unsignedBigInteger('city_id');
            $table->text('name');
            $table->string('mobile');
            $table->text('review')->nullable();
            $table->string('image')->nullable();
            $table->enum('recommended', ['yes', 'no', 'none'])->default('none');

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
        Schema::dropIfExists('retailer_ratings');
    }
}
