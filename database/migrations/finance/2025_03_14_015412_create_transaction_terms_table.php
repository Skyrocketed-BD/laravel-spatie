<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // specific connection database
    protected $connection = 'finance';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaction_term', function (Blueprint $table) {
            $table->increments('id_transaction_term');
            $table->integer('id_transaction')->unsigned()->nullable();
            $table->integer('id_receipt')->unsigned()->nullable();
            $table->string('nama', 50)->nullable();
            $table->date('date')->nullable();
            $table->float('percent')->nullable();
            $table->bigInteger('value_ppn')->nullable();
            $table->bigInteger('value_pph')->nullable();
            $table->bigInteger('value_percent')->nullable();
            $table->bigInteger('value_deposit')->nullable();
            $table->enum('deposit', ['down_payment', 'advance_payment'])->nullable();
            $table->enum('final', ['0', '1'])->default('0');

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_transaction')->references('id_transaction')->on('transaction')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('id_receipt')->references('id_receipt')->on('receipts')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_term');
    }
};
