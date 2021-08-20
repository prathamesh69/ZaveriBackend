<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateProductPriorityTrigger extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared("
            DROP TRIGGER IF EXISTS `trig_product_priority`;
            CREATE TRIGGER `trig_product_priority` BEFORE INSERT ON `products` FOR EACH ROW BEGIN
                SET NEW.priority = RAND()*(9999-1)+1;
            END
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared("DROP TRIGGER IF EXISTS `trig_product_priority`;");
    }
}
