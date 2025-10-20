<?php

use App\Http\Controllers\Admin\OrderPdfController;
use App\Http\Controllers\Admin\QuotationPdfController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\PdfHelper;
use App\Order;
use App\User;
use Illuminate\Support\Facades\Hash;

// Route::get('/test', function() {
//     User::where('id', '>', 1)->update([
//         'password' => Hash::make('password')
//     ]);
// });


// Route::namespace('Admin')->middleware(['admin_bootstrap'])->prefix(env('ADMIN_URL'))->group(function () {
Route::namespace('Admin')->middleware(['admin_bootstrap'])->prefix('admin')->group(
    function () {        
        Route::get('/login', 'LoginController@showForm')->name('admin.login');
        Route::post('/login', 'LoginController@login')->name('admin.login.submit');
        Route::get('/logout', 'LoginController@logout')->name('admin.logout');
    
        Route::group(
            ['middleware' => ['auth_admin', 'admin_role_check'], 'as' => 'admin.'],
            function () {
                Auth::setDefaultDriver('web_admin');

                // Admin routes
                Route::get(
                    '/', function () {
                        return redirect('dashboard');
                    }
                );
                Route::get('/dashboard', 'DashboardController@index')->name('dashboard');
                Route::get('/profile', 'DashboardController@profile')->name('profile');
                Route::post('/profile', 'DashboardController@profile_update')->name('profile-update');

                // product
                Route::controller(ProductController::class)->group(
                    function () {
                        Route::get('/products', 'index')->name('products');
                        Route::get('/products-export', 'export')->name('products.export');
                        Route::get('/product/add', 'create')->name('products.create');
                        Route::post('/product/add', 'store')->name('products.store');
                        Route::get('/product/edit/{product}', 'edit')->name('products.edit');
                        Route::post('/product/edit/{product}', 'update')->name('products.update');
                        Route::get('/product/remove/{product}', 'removeProduct');
                        Route::get('/products-import', 'import')->name('products-import.index');
                        Route::post('/products-import', 'import_store')->name('products-import.store');
                    }
                );

                // Product Daily Price
                Route::controller(ProductDailyPriceController::class)->group(
                    function () {
                        Route::get('/product-daily-prices', 'index')->name('product-daily-prices.index');
                        Route::get('/product-daily-price/remove/{product_daily_price}', 'removeProductDailyPrice');
                        Route::get('/product-daily-price/add', 'create');
                        Route::get('/product-daily-price/add/{product_daily_price_id}', 'create');
                        Route::get('/product-daily-price/add/{date}/{duplicate_to_date}', 'create');
                        Route::post('/product-daily-price/add/{date}', 'store_batch');
                        Route::post('/product-daily-price/add', 'store');
                    }
                );
                // Product Receive
                Route::controller(ProductReceiveController::class)->prefix('product-receive-qty')->group(function () {
                    Route::get('/', 'index')->name('product-receive-qty.index');
                    Route::get('/remove/{product_daily_price}', 'removeProductDailyPrice');
                    Route::get('/add', 'create');
                    Route::get('/add/{product_daily_price_id}', 'create');
                    Route::get('/add/{date}/{duplicate_to_date}', 'create');
                    Route::post('/add/{date}', 'store_batch');
                    Route::post('/add', 'store');
                });
                // Product Balance
                Route::controller(ProductBalanceController::class)->prefix('product-balance-qty')->group(function () {
                    Route::get('/', 'index')->name('product-balance-qty.index');
                    Route::get('/remove/{product_daily_price}', 'removeProductDailyPrice');
                    Route::get('/add', 'create');
                    Route::get('/add/{product_daily_price_id}', 'create');
                    Route::get('/add/{date}/{duplicate_to_date}', 'create');
                    Route::post('/add/{date}', 'store_batch');
                    Route::post('/add', 'store');
                });

                Route::resource('uom', UomController::class);
                Route::controller(UomController::class)->group(
                    function () {
                        Route::post('/fetch-uom', 'fetch_uom');
                    }
                );

                Route::resource('areas', AreasController::class);
                Route::controller(AreasController::class)->group(
                    function () {
                        Route::post('/fetch-areas', 'fetch_areas');
                    }
                );

                Route::resource('product-categories', ProductCategoriesController::class);
                Route::controller(ProductCategoriesController::class)->group(
                    function () {
                        Route::post('/fetch-product-categories', 'fetch_categories');
                    }
                );
                
                Route::resource('quotations', QuotationsController::class)->only(['index', 'show']);
                Route::controller(QuotationsController::class)->group(
                    function () {
                        Route::post('/quotations/copy', 'copy');
                    }
                );

                // Order PDF Routes
                Route::get('/orders/{id}/invoice', [OrderPdfController::class, 'invoice'])->name('order.invoice');
                Route::get('/orders/{id}/invoice2', [OrderPdfController::class, 'invoiceWithoutPrice'])->name('order.invoice2');
                Route::get('/orders/{id}/delivery-order', [OrderPdfController::class, 'deliveryOrder'])->name('order.delivery-order');

                // Order PDF Download Routes (optional)
                // Route::get('/orders/{id}/invoice/download', [OrderPdfController::class, 'downloadInvoice'])->name('order.invoice.download');
                // Route::get('/orders/{id}/invoice2/download', [OrderPdfController::class, 'downloadInvoiceWithoutPrice'])->name('order.invoice2.download');
                // Route::get('/orders/{id}/delivery-order/download', [OrderPdfController::class, 'downloadDeliveryOrder'])->name('order.delivery-order.download');

                // Quotation PDF Routes
                Route::get('/quotations/{id}/invoice', [QuotationPdfController::class, 'invoice'])->name('quotation.invoice');
                Route::get('/quotations/{id}/invoice2', [QuotationPdfController::class, 'invoiceWithoutPrice'])->name('quotation.invoice2');
                Route::get('/quotations/{id}/delivery-order', [QuotationPdfController::class, 'deliveryOrder'])->name('quotation.delivery-order');

                // Quotation PDF Download Routes (optional)
                // Route::get('/quotations/{id}/invoice/download', [QuotationPdfController::class, 'downloadInvoice'])->name('quotation.invoice.download');
                // Route::get('/quotations/{id}/invoice2/download', [QuotationPdfController::class, 'downloadInvoiceWithoutPrice'])->name('quotation.invoice2.download');
                // Route::get('/quotations/{id}/delivery-order/download', [QuotationPdfController::class, 'downloadDeliveryOrder'])->name('quotation.delivery-order.download');
                
                Route::resource('customer-categories', CustomerCategoriesController::class);
                Route::controller(CustomerCategoriesController::class)->group(
                    function () {
                        Route::post('/fetch-customer-categories', 'fetch_categories');
                    }
                );

                // customer
                Route::controller(CustomerController::class)->group(
                    function () {
                        Route::get('/customers', 'index')->name('customers');
                        Route::get('/customer/add', 'create')->name('customers.create');
                        Route::post('/customer/add', 'store')->name('customers.store');
                        Route::get('/customer/edit/{customer}', 'edit')->name('customers.edit');
                        Route::post('/customer/edit/{customer}', 'update')->name('customers.update');
                        Route::post('/customer/update-password', 'updatePassword')->name('customer.update-password');
                        Route::get('/customer/generate-new-login-link/{customer}', 'generateNewLoginLink')->name('customers.generate-new-login-link');
                        Route::get('/customers/export', 'export')->name('customers.export');
                        Route::get('/import-customers', 'import_customers')->name('import.customers');
                        Route::post('/import-customers', 'import_customers_submit')->name('import.customers.submit');
                        Route::post('/delete-customer-visibility-product', 'deleteCustomerProduct');
                    }
                );

                Route::resource('lorry', LorryController::class);
                Route::controller(LorryController::class)->group(
                    function () {
                        Route::post('/get-lorry', 'get_lorry');
                    }
                );
        
                // order
                Route::controller(OrderController::class)->group(
                    function () {
                        Route::get('/orders', 'index')->name('orders');
                        Route::get('/orders/export', 'export')->name('orders.export');
                        Route::post('/change-order-status', 'change_order_status')->name('change-order-status');
                        Route::post('/change-order-lorry', 'change_order_lorry')->name('change-order-lorry');
                        Route::post('/assign-order-driver', 'assign_order_driver')->name('assign-order-driver');
                        Route::get('/order/add', 'create')->name('orders.create');
                        Route::post('/order/add', 'store')->name('orders.store');
                        Route::post('/order/get-customer-info', 'getCustomerData')->name('orders.get-customer-info');
                        Route::get('/order/get-products/{customer}', 'getProducts')->name('orders.get-products');
                        Route::get('/order/summary/{order}', 'viewSummary')->name('orders.summary');
                        Route::get('/order/edit/{order}', 'edit')->name('orders.edit');
                        Route::put('/order/edit/{order}', 'update')->name('orders.update');
                        Route::get('/order/get-order-info/{order}', 'getOrderData')->name('orders.get-order-info');
                        Route::post('/order/update-status/{order}', 'update_order_status');
                        Route::post('/order/batch-update-status', 'batchUpdate');
                        Route::get('/order/batch-download-files', 'downloadInvoiceDoAsZip');
                        Route::post('/order-products-list', 'order_products_list');
                        Route::post('/update-order-products-weight', 'update_order_products_weight')->name('update-order-products-weight');
                        Route::get('/download_do_zip', 'download_do_zip');
                        Route::post('/sync-invoice', 'sync_invoice')->name('sync-invoice');
                    }
                );

                // report
                Route::controller(ReportsController::class)->group(
                    function () {
                        Route::get('/daily-sales-report', 'daily_sales_report')->name('daily-sales-report');
                        Route::get('/export-daily-sales-report', 'export_daily_sales_report')->name('export-daily-sales-report');
                        Route::get('/daily-summary-report', 'daily_summary_report')->name('daily-summary-report');
                        Route::get('/export-daily-summary-report', 'export_daily_summary_report')->name('export-daily-summary-report');
                        Route::get('/daily-summary-stock-report', 'daily_summary_stock_report')->name('daily-summary-stock-report');
                        Route::get('/export-daily-summary-stock-report', 'export_daily_summary_stock_report')->name('export-daily-summary-stock-report');
                        Route::get('/do-report', 'do_report')->name('do-report');
                        Route::get('/sql-do-export-report', 'sql_do_export_report')->name('sql-do-export-report');
                        Route::get('/sql-do-export-report-excel', 'sql_do_export_report_excel')->name('sql-do-export-report-excel');
                        Route::get('/customer-uoms-report', 'customer_uoms_report')->name('customer-uoms-report');
                        
                        Route::get('/order-report', 'order_report')->name('order-report');
                        Route::post('/update-stock-quantity', 'update_stock_quantity');
                        Route::post('/print-order-stock-report', 'print_order_stock_report')->name('print-order-stock-report');
                        Route::post('/export-order-stock-report', 'export_order_stock_report')->name('export-order-stock-report');
                        Route::post('/export-customer-uoms-report', 'export_customer_uoms_report')->name('export-customer-uoms-report');
                    }
                );
            }
        );
    }
);
