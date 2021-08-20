<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePreferencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('preferences', function (Blueprint $table) {
            $table->string('id', 191)->primary();
            $table->text('value')->nullable();

            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
        });

        $this->insertData();
    }

    private function insertData()
    {
        DB::table('preferences')->insert([
            // retailer app
            [
                'id' => 'retailer_app',
                'value' => json_encode([
                    'version' => '18',
                    'url' => 'https://zaveribazaar.co.in',
                    'whats_new' => 'Bug fixes and new features added!',
                    'product_whatsapp' => '9167656501',
                    'iOS' => [
                        'version' => '18',
                        'url' => 'https://apps.apple.com/us/app/id1524255777',
                        'whats_new' => 'Bug fixes and new features added!',
                    ],
                    'android' => [
                        'version' => '19',
                        'url' => 'https://play.google.com/store/apps/details?id=in.co.zaveribazaar.retailer',
                        'whats_new' => 'Bug fixes and new features added!',
                    ],
                ])
            ],

            // wholesaler app
            [
                'id' => 'wholesaler_app',
                'value' => json_encode([
                    'version' => '18',
                    'url' => 'https://zaveribazaar.co.in',
                    'whats_new' => 'Bug fixes and new features added!',
                    'iOS' => [
                        'version' => '18',
                        'url' => 'https://apps.apple.com/us/app/id1523073466',
                        'whats_new' => 'Bug fixes and new features added!',
                    ],
                    'android' => [
                        'version' => '19',
                        'url' => 'https://play.google.com/store/apps/details?id=in.co.zaveribazaar.wholesaler',
                        'whats_new' => 'Bug fixes and new features added!',
                    ],
                ])
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('preferences');
    }
}
