<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('shipping_address');
            $table->string('shipping_postcode', 10)->nullable();
            $table->string('shipping_state', 30)->nullable();
            $table->text('payment_method')->nullable();
            $table->text('login_code')->comment('when user login with link must match this code');
            $table->text('remark')->nullable()->comment('remark for admin to refer');
            $table->string('status', 30);
            $table->boolean('price_permission')->default(1);
            $table->boolean('invoice_visibility')->default(1);
            $table->boolean('invoice_price_permission')->default(1);
            $table->integer('default_driver_id')->nullable();
            $table->string('attn_name')->nullable();
            $table->string('attn_contact')->nullable();
            $table->integer('customer_category_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
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
