@extends('layouts.admin')
@section('title', 'Edit Order')
@section('css')

    <link rel="stylesheet" href="{{ asset('assets/css/select2.min.css') }}" />

@endsection
@section('content')

    <form method="POST" action="{{ route('admin.orders.update', encrypt($order->id)) }}" enctype="multipart/form-data" class="form-wrapper">
        @csrf
        @method('PUT')
        <input type="hidden" id="order_id" name="order_id" value="{{ encrypt($order->id) }}" />
        <input type="hidden" id="customer_id" name="customer_id" value="{{ encrypt($order->user_id) }}" />
        <div class="row">
            <div class="col-md-8">
                @if ($errors->has('date'))
                    <span class="text-danger" role="alert">
                        <strong>{{ $errors->first('date') }}</strong>
                    </span>
                @endif
                <div class="card shadow no-border">
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

                        <div class="mb-4 d-none" id="add-product-info">
                            <button type="button" class="btn btn-outline-primary mb-4" data-bs-toggle="modal" data-bs-target="#addProductModal">
                                <i class="fa fa-plus" aria-hidden="true"></i> Add Products
                            </button>
                            <div class="alert alert-info">Please add products to this order.</div>
                        </div>

                        <div class="row mb-2">
                            <div class="col-md-12">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                    <div>
                                        <button type="button" class="btn btn-outline-primary px-5 disabled" disabled>
                                            Grand Total: RM <span id="total-price">0.00</span>
                                        </button>
                                    </div>
                                    <div>
                                        <a href="{{ route('admin.orders') }}" class="btn btn-outline-secondary me-1">Back</a>
                                        <button type="submit" class="btns-order-action back d-none btn btn-outline-primary me-1">Back To Previous Step</button>
                                        <button type="submit" class="btns-order-action next btn btn-outline-primary">Next Step</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow no-border mb-0">
                    <div class="card-body">
                        <h5>Order Products</h5>
                        <hr>
                        <div id="product_bag-item"></div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    @include('admin.includes.add_products_modal')

@endsection
@section('script')

    <script src="{{ asset('assets/js/sweetalert.min.js') }}"></script>
    <script src="{{ asset('assets/js/select2.min.js') }}"></script>
    <script>
        var step = 'customer_info';
        var payment_method_options = {!! json_encode($payment_method_options) !!};
        var selected_products = {!! json_encode($products) !!};        
        const productIds = selected_products.map(product => product.product_id);
        var order_text = 'Update Order';
        var order_subtext = 'Confirm to update this order? Kindly double check on the order.';
        
        document.addEventListener('DOMContentLoaded', function () {
            display_selected_products();

            const customerSelect = document.getElementById('order_customer');
            if (customerSelect) {
                customerSelect.disabled = true;
                document.querySelector('select#order_customer').dispatchEvent(new Event('change', { 'bubbles': true }));
                document.getElementById('addProductModal').dispatchEvent(new Event('shown.bs.modal', { 'bubbles': true }));
            }
        });
        $(document).ready(function() {
            $('#payment_method').select2({
                placeholder: 'Select a payment method'
            });

            $('#area').select2({
                placeholder: 'Select an area'
            });
        });
    </script>

@endsection