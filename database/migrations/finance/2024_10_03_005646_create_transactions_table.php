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
        Schema::create('transaction', function (Blueprint $table) {
            $table->increments('id_transaction');
            $table->integer('id_kontak')->unsigned()->nullable();
            $table->integer('id_journal')->unsigned()->nullable();
            $table->integer('id_transaction_name')->unsigned()->nullable();
            $table->string('transaction_number', 50)->nullable();
            $table->string('from_or_to', 50)->nullable();
            $table->date('date')->nullable();
            $table->bigInteger('value')->nullable();
            $table->text('description')->nullable();
            $table->string('reference_number', 50)->nullable();
            $table->enum('in_ex', ['y', 'n'])->default('n')->description('y = exclude tax, n = include tax');
            $table->enum('status', ['valid', 'reversed', 'deleted'])->default('valid');

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_transaction_name')->references('id_transaction_name')->on('transaction_name')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('id_journal')->references('id_journal')->on('journal')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction');
    }
};
