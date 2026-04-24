<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('products', function (Blueprint $table) {
        $table->string('brand')->after('category')->nullable();
        $table->string('supplier')->after('brand')->nullable();
        $table->string('batch_number')->after('supplier')->nullable();
        $table->decimal('cost_price', 10, 2)->after('batch_number')->nullable();
        $table->decimal('selling_price', 10, 2)->after('cost_price')->nullable();
        $table->string('storage_location')->after('selling_price')->nullable();
    });
}

public function down()
{
    Schema::table('products', function (Blueprint $table) {
        $table->dropColumn([
            'brand', 'supplier', 'batch_number',
            'cost_price', 'selling_price', 'storage_location'
        ]);
    });
}
};
