<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQuotationSummarySequenceToProductCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->integer('quotation_summary_sequence')->nullable()->after('excel_sheet_batch');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropColumn('quotation_summary_sequence');
        });
    }
}
