<?php
// database/migrations/xxxx_xx_xx_create_walkin_sales_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function colType(string $table, string $column): string
    {
        $row = DB::selectOne("
            SELECT COLUMN_TYPE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = ?
              AND COLUMN_NAME  = ?
        ", [$table, $column]);

        return $row ? strtolower($row->COLUMN_TYPE) : 'int unsigned';
    }

    private function addCol(Blueprint $table, string $colName, string $columnType): void
    {
        if (str_contains($columnType, 'bigint')) {
            $table->unsignedBigInteger($colName);
        } else {
            $table->unsignedInteger($colName);
        }
    }

    public function up(): void
    {
        // Detect actual types from existing tables
        $userIdType       = $this->colType('users',        'user_id');
        $productIdType    = $this->colType('products',     'product_id');
        $appointmentIdType= $this->colType('appointments', 'appointment_id');

        // ── walkin_sales ──────────────────────────────────────
        Schema::create('walkin_sales', function (Blueprint $table) use ($userIdType) {
            $table->id('sale_id');
            $this->addCol($table, 'user_id',  $userIdType);
            $this->addCol($table, 'staff_id', $userIdType);
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->enum('payment_method', ['cash', 'gcash', 'card', 'other']);
            $table->decimal('amount_tendered', 10, 2)->nullable();
            $table->enum('status', ['completed', 'voided'])->default('completed');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users');
            $table->foreign('staff_id')->references('user_id')->on('users');
        });

        // ── walkin_sale_items ─────────────────────────────────
        Schema::create('walkin_sale_items', function (Blueprint $table) use ($productIdType) {
            $table->id('item_id');
            $table->unsignedBigInteger('sale_id');
            $this->addCol($table, 'product_id', $productIdType);
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();

            $table->foreign('sale_id')->references('sale_id')->on('walkin_sales')->onDelete('cascade');
            $table->foreign('product_id')->references('product_id')->on('products');
        });

        // ── walkin_sale_services ──────────────────────────────
        Schema::create('walkin_sale_services', function (Blueprint $table) use ($appointmentIdType) {
            $table->id();
            $table->unsignedBigInteger('sale_id');
            $this->addCol($table, 'appointment_id', $appointmentIdType);
            $table->decimal('service_price', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('sale_id')->references('sale_id')->on('walkin_sales')->onDelete('cascade');
            $table->foreign('appointment_id')->references('appointment_id')->on('appointments')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('walkin_sale_services');
        Schema::dropIfExists('walkin_sale_items');
        Schema::dropIfExists('walkin_sales');
    }
};