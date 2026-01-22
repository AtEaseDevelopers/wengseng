@extends('layouts.admin')
@section('title', 'Edit Order')
@section('css')

    <link rel="stylesheet" href="{{ asset('assets/css/select2.min.css') }}" />

@endsection
@section('content')

    <form method="POST" action="{{ route('admin.orders.update', encrypt($order->id)) }}" enctype="multipart/form-data" class="form-wrapper" id="order-form">
        @csrf
        @method('PUT')
        <input type="hidden" id="order_id" name="order_id" value="{{ encrypt($order->id) }}" />
        <input type="hidden" id="customer_id" name="customer_id" value="{{ encrypt($order->user_id) }}" />
        <div class="row">
            <div class="col-md-12">
                @if ($errors->has('date'))
                    <span class="text-danger" role="alert">
                        <strong>{{ $errors->first('date') }}</strong>
                    </span>
                @endif
                <div class="card shadow no-border mb-4">
                    <div class="card-body">
                        <h5 class="mb-4">Customer Details</h5>
                    
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-4">
                                    <label class="mb-2" for="order_customer">Customer</label>
                                    <span class="text-danger"> *</span>
                                    <select class="form-select" name="customer" id="order_customer">
                                        <option value="{{ $customer->id }}" selected>
                                            {{ $customer->name }} - {{ $customer->sql_customer_code }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div id="customer_info">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-4">
                                        <label class="mb-2" for="pricing_date">Pricing Date</label>
                                        <span class="text-danger"> *</span>
                                        <input type="date" class="form-control" id="pricing_date" name="pricing_date" value="{{ $order->pricing_date }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-4">
                                        <label class="mb-2" for="delivering_date">Delivering Date</label>
                                        <span class="text-danger"> *</span>
                                        <input type="date" class="form-control" id="delivering_date" name="delivering_date" value="{{ $order->delivering_date }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-4">
                                        <label class="mb-2" for="attn_name">Attn. Name</label>
                                        <input type="text" class="form-control" name="attn_name" id="attn_name" value="{{ $order->attn_name }}" placeholder="Enter Attn. Name (optional)">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-4">
                                        <label class="mb-2" for="attn_contact">Attn. Contact</label>
                                        <input type="text" class="form-control" name="attn_contact" id="attn_contact" value="{{ $order->attn_contact }}" placeholder="Enter Attn. Contact (optional)">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-4">
                                        <label class="mb-2" for="area">Select Area</label>
                                        <select class="form-select @error('area') is-invalid @enderror"  id="area" name="area">
                                            <option value="">Choose...</option>
                                            @foreach ($areaList as $area)
                                                <option value="{{ $area }}" {{ $order->area == $area ? 'selected' : '' }}>
                                                    {{ $area }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group mb-4">
                                        <label class="mb-2" for="billing_address">Billing Address</label>
                                        <span class="text-danger"> *</span>
                                        <textarea id="billing_address" name="billing_address" value="{{ $order->billing_address }}" class="form-control" rows="3" placeholder="Enter your billing address" required>{{ $order->billing_address }}</textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group mb-4">
                                        <label class="mb-2" for="shipping_address">Shipping Address</label>
                                        <textarea id="shipping_address" name="shipping_address" value="{{ $order->shipping_address }}" class="form-control" rows="3" placeholder="Enter your shipping address">{{ $order->shipping_address }}</textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-4">
                                        <label class="mb-2" for="payment_method">Payment Method</label>
                                        <select id="payment_method" name="payment_method" class="form-select" data-selected="{{ $order->payment_method }}">
                                            <option value="" selected>-- Select Payment Method --</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6" id="transferSlipGroup" style="display: none;">
                                    <div class="form-group mb-4">
                                        <label class="mb-2" for="transfer_slip">Upload Transfer Slip</label>
                                        <span class="text-danger"> *</span>
                                        <input type="file" id="transfer_slip" name="transfer_slip" class="form-control" accept="image/*">
                                        @if($order->transfer_slip_url)
                                            <div class="card p-3">
                                                <img style="width: 70%;" src="{{ $order->transfer_slip_url }}" />
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Order Products Section -->
                <div class="card shadow no-border mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Order Products</h5>
                            <div>
                                <button type="button" class="btn btn-warning" id="btn-add-line">
                                    <i class="fa fa-plus"></i> Add Line
                                </button>
                            </div>
                        </div>
                        <hr>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="products-table">
                                <thead>
                                    <tr>
                                        <th style="width: 30%;">Product</th>
                                        <th style="width: 10%;">Quantity</th>
                                        <th style="width: 10%;">UOM</th>
                                        <th style="width: 12%;">Price</th>
                                        <th style="width: 13%;">Total</th>
                                        <th style="width: 20%;">Remark</th>
                                        <th style="width: 5%;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="products-tbody">
                                    @foreach($products as $index => $product)
                                    <tr class="product-row" data-price="{{ $product->unit_price }}" data-total="{{ $product->unit_price * ($product->quantity ?? $product->weight ?? 0) }}">
                                        <td>
                                            <select class="form-select product-select" name="product_id[]">
                                                <option value="">Choose product</option>
                                                <option value="{{ $product->product_id }}" selected>{{ $product->product_name }}</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control quantity-input" name="quantity[]" step="0.01" min="0" value="{{ $product->quantity ?? $product->weight ?? 0 }}">
                                        </td>
                                        <td class="uom-cell">{{ $product->uom_name ?? '' }}</td>
                                        <td><input type="number" class="form-control price-input" name="unit_price[]" step="0.01" min="0" value="{{ number_format($product->unit_price, 2, '.', '') }}"></td>
                                        <td class="total-cell"><strong>RM {{ number_format($product->unit_price * ($product->quantity ?? $product->weight ?? 0), 2) }}</strong></td>
                                        <td>
                                            <input type="text" class="form-control remark-input" name="remark[]" placeholder="Remarks" value="{{ $product->remark ?? '' }}">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                    @for($i = count($products); $i < 50; $i++)
                                    <tr class="product-row">
                                        <td>
                                            <select class="form-select product-select" name="product_id[]">
                                                <option value="">Choose product</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control quantity-input" name="quantity[]" step="0.01" min="0">
                                        </td>
                                        <td class="uom-cell"></td>
                                        <td><input type="number" class="form-control price-input" name="unit_price[]" step="0.01" min="0" value="0"></td>
                                        <td class="total-cell"><strong>RM 0.00</strong></td>
                                        <td>
                                            <input type="text" class="form-control remark-input" name="remark[]" placeholder="Remarks">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endfor
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="card shadow no-border mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Grand Total: RM <span id="total-price">0.00</span></strong>
                            </div>
                            <div>
                                <a href="{{ route('admin.orders') }}" class="btn btn-secondary me-2">Back</a>
                                <button type="submit" class="btn btn-primary">Update Order</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

@endsection
@section('script')

    <script src="{{ asset('assets/js/sweetalert.min.js') }}"></script>
    <script src="{{ asset('assets/js/select2.min.js') }}"></script>
    <script>
        var payment_method_options = {!! json_encode($payment_method_options) !!};
        var allProducts = {!! json_encode($all_products) !!};
        var existingProducts = {!! json_encode($products) !!};
        var productsData = {};

        // Build productsData lookup object from all products
        allProducts.forEach(function(p) {
            productsData[p.id] = p;
        });

        // Override with existing order product prices
        existingProducts.forEach(function(p) {
            if (productsData[p.product_id]) {
                productsData[p.product_id].price = p.unit_price;
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            // Populate payment methods
            if (payment_method_options) {
                var $paymentSelect = $('#payment_method');
                var selectedMethod = $paymentSelect.data('selected');
                $.each(payment_method_options, function(key, value) {
                    var selected = (key === selectedMethod) ? ' selected' : '';
                    $paymentSelect.append('<option value="' + key + '"' + selected + '>' + value + '</option>');
                });
                $paymentSelect.trigger('change');

                // Show transfer slip if bank transfer selected
                if (selectedMethod === 'bank-transfer') {
                    $('#transferSlipGroup').css('display', 'block');
                }
            }
        });

        $(document).ready(function() {
            $('#payment_method').select2({
                placeholder: 'Select a payment method'
            });

            $('#area').select2({
                placeholder: 'Select an area'
            });

            $('#payment_method').on('change', function() {
                let val = $(this).val();
                if (val == 'bank-transfer') {
                    $('#transferSlipGroup').css('display', 'block');
                } else {
                    $('#transferSlipGroup').css('display', 'none');
                }
            });

            // Initialize Select2 on all product dropdowns
            initAllProductSelect2();

            // Auto-open product Select2 dropdown on focus
            $(document).on('focus', '.select2-selection--single', function() {
                $(this).closest('.select2-container').siblings('select.product-select').select2('open');
            });

            // Fetch customer-specific prices on page load
            fetchCustomerPrices({{ $customer->id }});

            // Calculate initial grand total
            calculateGrandTotal();

            // Add Line button
            $('#btn-add-line').on('click', function() {
                addProductRow();
            });

            // Remove row button (delegated)
            $(document).on('click', '.btn-remove-row', function() {
                clearProductRow($(this).closest('tr'));
            });

            // Quantity change handler (delegated)
            $(document).on('change keyup', '.quantity-input', function() {
                calculateRowTotal($(this).closest('tr'));
                calculateGrandTotal();
            });

            // Price change handler (delegated)
            $(document).on('change keyup', '.price-input', function() {
                calculateRowTotal($(this).closest('tr'));
                calculateGrandTotal();
            });

            // Product select change handler (delegated)
            $(document).on('change', '.product-select', function() {
                onProductSelect($(this).closest('tr'));
            });

            // Form submission - filter out empty rows
            $('#order-form').on('submit', function(e) {
                $('.product-row').each(function() {
                    var productId = $(this).find('.product-select').val();
                    if (!productId) {
                        $(this).find('select, input').attr('disabled', true);
                    }
                });
            });
        });

        function initAllProductSelect2() {
            $('.product-select').each(function() {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    initProductSelect2($(this));
                }
            });
        }

        function initProductSelect2($select) {
            // Prepare data for Select2
            var selectData = allProducts.map(function(product) {
                return {
                    id: product.id,
                    text: product.name + (product.sku ? ' (' + product.sku + ')' : '')
                };
            });

            $select.select2({
                placeholder: 'Choose product',
                allowClear: true,
                data: selectData
            });

            // Auto-focus search field when Select2 is opened
            $select.on('select2:open', function() {
                document.querySelector('.select2-container--open .select2-search__field').focus();
            });
        }

        function onProductSelect($row) {
            var $select = $row.find('.product-select');
            var productId = $select.val();

            if (productId && productsData[productId]) {
                var product = productsData[productId];
                var price = product.price || 0;

                $row.find('.uom-cell').text(product.uom_name || '');
                $row.find('.price-input').val(parseFloat(price).toFixed(2));

                var $qty = $row.find('.quantity-input');
                if (!$qty.val()) {
                    $qty.val(1);
                }

                calculateRowTotal($row);
                calculateGrandTotal();
            } else {
                $row.find('.uom-cell').text('');
                $row.find('.price-input').val(0);
                $row.find('.total-cell').html('<strong>RM 0.00</strong>');
                calculateGrandTotal();
            }
        }

        function calculateRowTotal($row) {
            var price = parseFloat($row.find('.price-input').val()) || 0;
            var qty = parseFloat($row.find('.quantity-input').val()) || 0;
            var total = price * qty;
            $row.find('.total-cell').html('<strong>RM ' + total.toFixed(2) + '</strong>');
            $row.data('total', total);
        }

        function calculateGrandTotal() {
            var grandTotal = 0;
            $('.product-row').each(function() {
                grandTotal += parseFloat($(this).data('total')) || 0;
            });
            $('#grand-total').text(grandTotal.toFixed(2));
            $('#total-price').text(grandTotal.toFixed(2));
        }

        function addProductRow() {
            var $newRow = $(`
                <tr class="product-row">
                    <td>
                        <select class="form-select product-select" name="product_id[]">
                            <option value="">Choose product</option>
                        </select>
                    </td>
                    <td>
                        <input type="number" class="form-control quantity-input" name="quantity[]" step="0.01" min="0">
                    </td>
                    <td class="uom-cell"></td>
                    <td><input type="number" class="form-control price-input" name="unit_price[]" step="0.01" min="0" value="0"></td>
                    <td class="total-cell"><strong>RM 0.00</strong></td>
                    <td>
                        <input type="text" class="form-control remark-input" name="remark[]" placeholder="Remarks">
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `);

            $('#products-tbody').append($newRow);
            initProductSelect2($newRow.find('.product-select'));
        }

        function clearProductRow($row) {
            $row.find('.product-select').val('').trigger('change');
            $row.find('.quantity-input').val('');
            $row.find('.remark-input').val('');
            $row.find('.uom-cell').text('');
            $row.find('.price-input').val(0);
            $row.find('.total-cell').html('<strong>RM 0.00</strong>');
            $row.data('total', 0);
            calculateGrandTotal();
        }

        function fetchCustomerPrices(customerId) {
            $.get('/admin/order/get-products/' + customerId, function(response) {
                var data = JSON.parse(response);
                if (data.success && data.products) {
                    // Update productsData with customer-specific prices
                    Object.keys(data.products).forEach(function(id) {
                        if (productsData[id]) {
                            productsData[id].price = data.products[id].price;
                        }
                    });
                    // Only refresh prices for rows that don't already have a price set
                    // (i.e., newly added rows, not existing order products)
                    $('.product-row').each(function() {
                        var productId = $(this).find('.product-select').val();
                        var currentPrice = parseFloat($(this).find('.price-input').val()) || 0;
                        // Only update if no price is set (new row)
                        if (productId && currentPrice === 0) {
                            onProductSelect($(this));
                        }
                    });
                }
            });
        }
    </script>

@endsection