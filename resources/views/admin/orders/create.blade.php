@extends('layouts.admin')
@section('title', 'Manage Orders')
@section('content')

    <form method="POST" action="{{ route('admin.orders.store') }}" enctype="multipart/form-data" class="form-wrapper" id="order-form">
        @csrf
        <input type="hidden" id="id" name="customer_id" value="{{ isset($selected_customer) ? $selected_customer->id : '' }}" />
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow no-border mb-4">
                    <div class="card-body">
                        <h5 class="mb-4">Customer Details</h5>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-4">
                                    <label class="mb-2" for="order_customer">Customer</label>
                                    <span class="text-danger"> *</span>
                                    <select class="form-select" name="customer" id="order_customer" onchange="init_customer_details()">
                                        <option value="">Choose Customer...</option>
                                        @foreach($customers_list as $customer)
                                            <option value="{{ $customer->id }}"{{ (isset($selected_customer) && $selected_customer->id == $customer->id) ? ' selected' : (($input['customer'] ?? '') == $customer->id ? ' selected' : '') }}>
                                                {{ $customer->name }} - {{ $customer->sql_customer_code }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div id="customer_info" class="d-none">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-4">
                                        <label class="mb-2" for="pricing_date">Pricing Date</label>
                                        <span class="text-danger"> *</span>
                                        <input type="date" class="form-control" id="pricing_date" name="pricing_date" value="{{ $pricing_date ?? date('Y-m-d') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-4">
                                        <label class="mb-2" for="delivering_date">Delivering Date</label>
                                        <span class="text-danger"> *</span>
                                        <input type="date" class="form-control" id="delivering_date" name="delivering_date" value="{{ $delivering_date ?? date('Y-m-d', strtotime('+1 day')) }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-4">
                                        <label class="mb-2" for="attn_name">Attn. Name</label>
                                        <input type="text" class="form-control" name="attn_name" id="attn_name" value="{{ old('attn_name', isset($duplicate_from) ? $duplicate_from->attn_name : '') }}" placeholder="Enter Attn. Name (optional)">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-4">
                                        <label class="mb-2" for="attn_contact">Attn. Contact</label>
                                        <input type="text" class="form-control" name="attn_contact" id="attn_contact" value="{{ old('attn_contact', isset($duplicate_from) ? $duplicate_from->attn_contact : '') }}" placeholder="Enter Attn. Contact (optional)">
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
                                                <option value="{{ $area }}" {{ old('area', isset($duplicate_from) ? $duplicate_from->area : '') == $area ? 'selected' : '' }}>
                                                    {{ $area }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <!--<div class="col-md-6">-->
                                <!--    <div class="mb-4">-->
                                <!--        <label class="mb-2" for="billing_city">Billing City</label>-->
                                <!--        <span class="text-danger"> *</span>-->
                                <!--        <input type="text" class="form-control" name="billing_city" id="billing_city" value="{{ old('billing_city') }}" placeholder="Enter billing city">-->
                                <!--    </div>-->
                                <!--</div>-->
                                <!--<div class="col-md-6">-->
                                <!--    <div class="form-group mb-4">-->
                                <!--        <label class="mb-2" for="billing_postcode">Billing Postcode</label>-->
                                <!--        <span class="text-danger"> *</span>-->
                                <!--        <input id="billing_postcode" name="billing_postcode" value="{{ old('billing_postcode') }}" class="form-control col-4" placeholder="Enter your billing postcode" required/>-->
                                <!--    </div>-->
                                <!--</div>-->
                                <!--<div class="col-md-6">-->
                                <!--    <div class="form-group mb-4">-->
                                <!--        <label class="mb-2" for="billing_state">Billing State</label>-->
                                <!--        <span class="text-danger"> *</span>-->
                                <!--        <select id="billing_state" class="form-select" name="billing_state" required>-->
                                <!--            <option value="">Choose Billing State</option>-->
                                <!--            @foreach($shipping_state_options as $state)-->
                                <!--                <option value="{{ $state }}"{{ old('billing_state') == $state? " selected" : "" }}>{{ $state }}</option>-->
                                <!--            @endforeach-->
                                <!--        </select>-->
                                <!--    </div>-->
                                <!--</div>-->
                                <div class="col-md-12">
                                    <div class="form-group mb-4">
                                        <label class="mb-2" for="billing_address">Billing Address</label>
                                        <span class="text-danger"> *</span>
                                        <textarea id="billing_address" name="billing_address" class="form-control" rows="3" placeholder="Enter your billing address" required>{{ old('billing_address', isset($duplicate_from) ? $duplicate_from->billing_address : '') }}</textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                            </div>
                            <!--<div class="row">-->
                            <!--    <div class="col-md-6">-->
                            <!--        <div class="mb-4">-->
                            <!--            <label class="mb-2" for="shipping_city">Shipping City</label>-->
                            <!--            <input type="text" class="form-control" name="shipping_city" id="shipping_city" value="{{ old('shipping_city') }}" placeholder="Enter shipping city">-->
                            <!--        </div>-->
                            <!--    </div>-->
                            <!--    <div class="col-md-6">-->
                            <!--        <div class="form-group mb-4">-->
                            <!--            <label class="mb-2" for="shipping_postcode">Shipping Postcode</label>-->
                            <!--            <input id="shipping_postcode" name="shipping_postcode" value="{{ old('shipping_postcode') }}" class="form-control col-4" placeholder="Enter your shipping postcode"/>-->
                            <!--        </div>-->
                            <!--    </div>-->
                            <!--    <div class="col-md-6">-->
                            <!--        <div class="form-group mb-4">-->
                            <!--            <label class="mb-2" for="shipping_state">Shipping State</label>-->
                            <!--            <select id="shipping_state" class="form-select" name="shipping_state">-->
                            <!--                <option value="">Choose Shipping State</option>-->
                            <!--                @foreach($shipping_state_options as $state)-->
                            <!--                    <option value="{{ $state }}"{{ old('shipping_state') == $state? " selected" : "" }}>{{ $state }}</option>-->
                            <!--                @endforeach-->
                            <!--            </select>-->
                            <!--        </div>-->
                            <!--    </div>-->
                                <div class="col-md-12">
                                    <div class="form-group mb-4">
                                        <label class="mb-2" for="shipping_address">Shipping Address</label>
                                        <textarea id="shipping_address" name="shipping_address" class="form-control" rows="3" placeholder="Enter your shipping address">{{ old('shipping_address', isset($duplicate_from) ? $duplicate_from->shipping_address : '') }}</textarea>
                                    </div>
                                </div>
                            <!--</div>-->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-4">
                                        <label class="mb-2" for="payment_method">Payment Method</label>
                                        <!--<span class="text-danger"> *</span>-->
                                        <select id="payment_method" name="payment_method" class="form-select">
                                            <option value="" selected>-- Select Payment Method --</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-4" id="transferSlipGroup" style="display: none;">
                                        <label class="mb-2" for="transfer_slip">Upload Transfer Slip</label>
                                        <!--<span class="text-danger"> *</span>-->
                                        <input type="file" id="transfer_slip" name="transfer_slip" class="form-control" accept="image/*">
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Order Products Section -->
                <div class="card shadow no-border mb-4 {{ isset($selected_customer) ? '' : 'd-none' }}" id="order-products-section">
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
                                    @for($i = 0; $i < 50; $i++)
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
                                        <td class="price-cell">RM 0</td>
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
                <div class="card shadow no-border mb-4 {{ isset($selected_customer) ? '' : 'd-none' }}" id="submit-buttons-section">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Grand Total: RM <span id="total-price">0.00</span></strong>
                            </div>
                            <div>
                                <a href="{{ route('admin.orders') }}" class="btn btn-secondary me-2">Back</a>
                                <button type="submit" class="btn btn-primary">Create Order</button>
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
    <script>
        var payment_method_options = {!! json_encode($payment_method_options) !!};
        var allProducts = {!! json_encode($all_products) !!};
        var productsData = {};

        // Build productsData lookup object
        allProducts.forEach(function(p) {
            productsData[p.id] = p;
        });

        document.addEventListener('DOMContentLoaded', function () {
            toggleTransferSlip();
        });

        $(document).ready(function() {
            $('#order_customer').select2();

            $('#payment_method').select2({
                placeholder: 'Select a payment method'
            });

            $('#area').select2({
                placeholder: 'Select an area'
            });

            // Show/hide Order Products section based on customer selection
            $('#order_customer').on('change', function() {
                if ($(this).val()) {
                    $('#order-products-section').removeClass('d-none');
                    $('#submit-buttons-section').removeClass('d-none');
                } else {
                    $('#order-products-section').addClass('d-none');
                    $('#submit-buttons-section').addClass('d-none');
                }
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

            // Product select change handler (delegated)
            $(document).on('change', '.product-select', function() {
                onProductSelect($(this).closest('tr'));
            });

            // Form submission - filter out empty rows
            $('#order-form').on('submit', function(e) {
                // Check if at least one product is selected
                var hasProduct = false;
                $('.product-row').each(function() {
                    var productId = $(this).find('.product-select').val();
                    if (productId) {
                        hasProduct = true;
                    }
                });

                if (!hasProduct) {
                    e.preventDefault();
                    // Re-enable submit button (disabled by form-wrapper handler in script.js)
                    $(this).find('button[type="submit"]').prop('disabled', false);
                    $('*').css('cursor', '');
                    Swal.fire('Error', 'Please select at least one product.', 'error');
                    return false;
                }

                // Disable empty rows before submission
                $('.product-row').each(function() {
                    var productId = $(this).find('.product-select').val();
                    if (!productId) {
                        $(this).find('select, input').attr('disabled', true);
                    }
                });
            });

            @if(isset($selected_customer))
            // Auto-trigger customer details for duplicate order
            setTimeout(function() {
                init_customer_details();
                @if(isset($duplicate_from) && $duplicate_from->payment_method)
                setTimeout(function() {
                    $('#payment_method').val('{{ $duplicate_from->payment_method }}').trigger('change');
                }, 500);
                @endif
            }, 100);
            @endif

            @if(isset($duplicate_products) && count($duplicate_products) > 0)
            // Pre-populate products from duplicate order after products are loaded
            var duplicate_products = {!! json_encode($duplicate_products) !!};
            setTimeout(function() {
                populateDuplicateProducts(duplicate_products);
            }, 1000);
            @endif
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
        }

        function onProductSelect($row) {
            var $select = $row.find('.product-select');
            var productId = $select.val();

            if (productId && productsData[productId]) {
                var product = productsData[productId];
                var price = product.price || 0;

                $row.find('.uom-cell').text(product.uom_name || '');
                $row.find('.price-cell').text('RM ' + parseFloat(price).toFixed(2));
                $row.data('price', price);

                // Set default quantity to 1 if empty
                var $qty = $row.find('.quantity-input');
                if (!$qty.val()) {
                    $qty.val(1);
                }

                calculateRowTotal($row);
                calculateGrandTotal();
            } else {
                // Clear row data
                $row.find('.uom-cell').text('');
                $row.find('.price-cell').text('RM 0');
                $row.find('.total-cell').html('<strong>RM 0.00</strong>');
                $row.data('price', 0);
                calculateGrandTotal();
            }
        }

        function calculateRowTotal($row) {
            var price = parseFloat($row.data('price')) || 0;
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
                    <td class="price-cell">RM 0</td>
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
            // Clear the row instead of removing it
            $row.find('.product-select').val(null).trigger('change');
            $row.find('.quantity-input').val('');
            $row.find('.remark-input').val('');
            $row.find('.uom-cell').text('');
            $row.find('.price-cell').text('RM 0');
            $row.find('.total-cell').html('<strong>RM 0.00</strong>');
            $row.data('price', 0);
            $row.data('total', 0);
            calculateGrandTotal();
        }

        function populateDuplicateProducts(products) {
            products.forEach(function(p, index) {
                if (index < $('.product-row').length) {
                    var $row = $('.product-row').eq(index);
                    var $select = $row.find('.product-select');

                    // Store product data
                    productsData[p.product_id] = {
                        id: p.product_id,
                        name: p.product_name,
                        price: p.unit_price,
                        uom_name: ''
                    };

                    // Add option and select it
                    var option = new Option(p.product_name, p.product_id, true, true);
                    $select.append(option).trigger('change');

                    // Set quantity and remark
                    $row.find('.quantity-input').val(p.quantity || p.weight || 0);
                    $row.find('.remark-input').val(p.remark || '');
                    $row.find('.price-cell').text('RM ' + parseFloat(p.unit_price).toFixed(2));
                    $row.data('price', p.unit_price);

                    calculateRowTotal($row);
                }
            });
            calculateGrandTotal();
        }
    </script>

@endsection