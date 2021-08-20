<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWholesalerFirmsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wholesaler_firms', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('mobile')->nullable();
            $table->string('image')->nullable();
            $table->string('qr_code')->nullable();
            $table->text('address')->nullable();
            $table->text('pincode')->nullable();
            $table->unsignedBigInteger('city_id');
            $table->string('gst')->nullable();
            $table->longText('marks')->nullable();
            $table->longText('meltings')->nullable();
            $table->longText('preferences')->nullable();

            $table->longText('email_addresses')->nullable()->comment('[string]');
            $table->longText('icom_numbers')->nullable()->comment('[string]');
            $table->longText('landline_numbers')->nullable()->comment('[string]');
            $table->longText('links')->nullable()->comment('{website, facebook, instagram}');

            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));

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
        Schema::dropIfExists('wholesaler_firms');
    }
}
