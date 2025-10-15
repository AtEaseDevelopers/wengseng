@extends('layouts.admin')
@section('title', 'Edit Customer')
@section('css')

    <link rel="stylesheet" href="{{ asset('assets/css/select2.min.css') }}" />

@endsection
@section('content')

    <form action="{{ route('admin.customers.update', encrypt($customer->id)) }}" method="POST" enctype="multipart/form-data" class="form-wrapper">
        @csrf
        <div class="row">
            <div class="col-md-8">
                <div class="card shadow no-border mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Basic Info</h5>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label class="mb-2" for="name">Customer Name</label>
                                    <span class="text-danger"> *</span>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="name" value="{{ old('name') ?: $customer->name }}" placeholder="Enter customer name" required>
                                    @error('name')
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label class="mb-2" for="email">Customer Email</label>
                                    <input type="text" class="form-control @error('email') is-invalid @enderror" name="email" id="email"
                                        value="{{ old('email') ?: $customer->email }}" placeholder="Enter customer email">
                                    @error('email')
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label class="mb-2" for="attn_name">Attn. Name</label>
                                    <input type="text" class="form-control @error('attn_name') is-invalid @enderror" name="attn_name" id="attn_name" value="{{ old('attn_name') ?: $customer->attn_name }}" placeholder="Enter Attn. Name (optional)">
                                    @error('attn_name')
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label class="mb-2" for="attn_contact">Attn. Contact</label>
                                    <input type="text" class="form-control @error('attn_contact') is-invalid @enderror" name="attn_contact" id="attn_contact" value="{{ old('attn_contact') ?: $customer->attn_contact }}" placeholder="Enter Attn. Contact (optional)">
                                    @error('attn_contact')
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label class="mb-2" for="fax_no">Fax No.</label>
                                    <input type="text" class="form-control @error('fax_no') is-invalid @enderror" name="fax_no" id="fax_no" value="{{ old('fax_no') ?: $customer->fax_no }}" placeholder="Enter Fax Number (optional)">
                                    @error('fax_no')
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label class="mb-2" for="sql_customer_code">SQL Customer Code</label>
                                    <input type="text" class="form-control @error('sql_customer_code') is-invalid @enderror" name="sql_customer_code" id="sql_customer_code" value="{{ old('sql_customer_code') ?: $customer->sql_customer_code }}" placeholder="Enter SQL customer code">
                                    @error('sql_customer_code')
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-4">
                                    <label class="mb-2" for="remark">Remark</label>
                                    <textarea class="form-control @error('remark') is-invalid @enderror" name="remark" id="remark" placeholder="Enter customer remark">{{ old('remark') ?: $customer->remark }}</textarea>
                                    @error('remark')
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label class="mb-2" for="billing_address">Billing Address</label>
                                    <span class="text-danger"> *</span>
                                    <textarea class="form-control @error('billing_address') is-invalid @enderror" name="billing_address" id="billing_address" rows="3" placeholder="Enter billing address" required>{{ old('billing_address') ?: $customer->billing_address }}</textarea>
                                    @error('billing_address')
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label class="mb-2" for="shipping_address">Shipping Address</label>
                                    <textarea class="form-control @error('shipping_address') is-invalid @enderror" name="shipping_address" id="shipping_address" rows="3" placeholder="Enter shipping address">{{ old('shipping_address') ?: $customer->shipping_address }}</textarea>
                                    @error('shipping_address')
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <br>

                        <h5 class="card-title">Advance Info</h5>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label class="mb-2" for="customer_category_id">Customer Category</label>
                                    <select class="form-select" name="customer_category_id" id="customer_category_id">
                                        <option value="">Choose...</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}"{{ $customer->customer_category_id == $category->id ? ' selected' : '' }}>
                                                {{ $category->category_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label class="mb-2" for="default_driver_id">Select Lorry</label>
                                    <select id="default_driver_id" class="form-select @error('default_driver_id') is-invalid @enderror" name="default_driver_id">
                                        <option value="">Choose...</option>
                                        @foreach ($drivers as $driver)
                                            <option value="{{ $driver->id }}" {{ $customer->default_driver_id == $driver->id ? 'selected' : '' }}>
                                                {{ $driver->lorry_number }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('default_driver_id')
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label class="mb-2" for="area_id">Select Area</label>
                                    <select class="form-select @error('area_id') is-invalid @enderror" id="area_id" name="area_id">
                                        <option value="">Choose...</option>
                                        @foreach ($areas as $area)
                                            <option value="{{ $area->id }}" {{ $customer->area == $area->id ? 'selected' : '' }}>
                                                {{ $area->area_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <br>

                        <h5 class="card-title">Visibility & Permissions</h5>
                        <hr>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-4">
                                    <label for="">Products Visibility</label>
                                    <a type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                                        <i class="fa fa-plus" aria-hidden="true"></i> Add Customer Products
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-4">
                                <label class="mb-2" for="attn_contact">Product Price Permission</label>
                                <div class="form-check me-3 mb-1">
                                    <label class="form-check-label" for="hide">
                                        <input class="form-check-input" type="radio" name="price_permission" id="hide" value="0" {{ $customer->price_permission == 0 ? 'checked' : '' }}>
                                        Hide Price
                                    </label>
                                </div>
                                <div class="form-check me-3 mb-1">
                                    <label class="form-check-label" for="unhide">
                                        <input class="form-check-input" type="radio" name="price_permission" id="unhide" value="1" {{ $customer->price_permission == 1 ? 'checked' : '' }}>
                                        Unhide Price
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4 mb-4">
                                <label class="mb-2" for="attn_contact">Invoice Visibility</label>
                                <div class="form-check me-3 mb-1">
                                    <label class="form-check-label" for="hide_invoice">
                                        <input class="form-check-input" type="radio" name="invoice_visibility" id="hide_invoice" value="0" {{ $customer->invoice_visibility == 0 ? 'checked' : '' }}>
                                        Hide Invoice
                                    </label>
                                </div>
                                <div class="form-check me-3 mb-1">
                                    <label class="form-check-label" for="unhide_invoice">
                                        <input class="form-check-input" type="radio" name="invoice_visibility" id="unhide_invoice" value="1" {{ $customer->invoice_visibility == 1 ? 'checked' : '' }}>
                                        Unhide Invoice
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4 mb-4">
                                <label class="mb-2" for="attn_contact">Invoice Product Price Visibility</label>
                                <div class="form-check me-3 mb-1">
                                    <label class="form-check-label" for="invoice_price_hide">
                                        <input class="form-check-input" type="radio" name="invoice_price_permission" id="invoice_price_hide" value="0" {{ $customer->invoice_price_permission == 0 ? 'checked' : '' }}>
                                        Hide Product Price
                                    </label>
                                </div>
                                <div class="form-check me-3 mb-1">
                                    <label class="form-check-label" for="invoice_price_unhide">
                                        <input class="form-check-input" type="radio" name="invoice_price_permission" id="invoice_price_unhide" value="1" {{ $customer->invoice_price_permission == 1 ? 'checked' : '' }}>
                                        Unhide Product Price
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow no-border mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Customer Products</h5>
                        <hr>
                        <div id="product_bag-item"></div>
                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('admin.customers') }}" class="btn btn-outline-secondary me-1">Back</a>
                            <button type="submit" class="btn btn-outline-primary">
                                Save
                                <div class="spinner-border spinner-border-sm d-none" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow no-border">
                <div class="card-body">
                    <div class="mb-4">
                        <h5 class="card-title">Reset Password</h5>
                    </div>
                    <form action="{{ route('admin.customer.update-password') }}" method="POST" class="form-wrapper">
                        @csrf
                        <input type="hidden" name="id" value="{{ encrypt($customer->id) }}">
                        <div class="mb-4">
                            <label class="mb-2" for="new_password">New Password</label>
                            <span class="text-danger"> *</span>
                            <input type="password" class="form-control @error('new_password') is-invalid @enderror" name="new_password" id="new_password" placeholder="Enter new password" required>
                            @error('new_password')
                                <span class="text-danger" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-outline-primary">
                                Reset Password
                                <div class="spinner-border spinner-border-sm d-none" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @include('admin.includes.add_products_modal')

@endsection
@section('script')

    <script src="{{ asset('assets/js/select2.min.js') }}"></script>
    <script src="{{ asset('assets/js/sweetalert.min.js') }}"></script>
    <script>
        var selected_products = {!! json_encode($product_visibilities) !!};        
        const productIds = selected_products.map(product => product.product_id);

        $(document).ready(function() {
            $('#payment_method').select2({
                placeholder: 'Select a payment method'
            });

            $('#default_driver_id').select2({
                placeholder: 'Select a default driver'
            });

            $('#area').select2({
                placeholder: 'Select an area'
            });
        });
        
        document.addEventListener('DOMContentLoaded', function () {
            display_selected_products();
        });
    </script>

@endsection
