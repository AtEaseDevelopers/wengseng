@extends('layouts.admin')
@section('title', 'Order Report')
@section('content')

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow no-border">
                <div class="card-body">
                    <h5 class="card-title">Filter</h5>
                    <hr>
                    <form method="GET" class="form-wrapper">
                        @php
                            $req = request();
                        @endphp
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-4">
                                    <label class="mb-2" for="product_category_id">Select Category</label>
                                    <select class="form-select"  id="product_category_id" name="product_category_id">
                                        <option value="">Choose...</option>
                                        @foreach ($product_categories as $category)
                                            <option value="{{ $category->id }}" {{ $req['product_category_id'] == $category->id ? 'selected' : '' }}>
                                                {{ $category->category_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                             <div class="col-md-4">
                                <div class="form-group mb-4">
                                    <label class="mb-2" for="status">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="">Choose...</option>
                                        @foreach ($statuses as $key => $value)
                                            <option value="{{ $key }}" {{ $req['status'] == $key ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>                            
                            <div class="col-md-4">
                                <div class="form-group mb-4">
                                    <label class="mb-2" for="fdate">Date Range</label>
                                    <div class="d-flex">
                                        <input type="date" class="form-control mb-1 me-2" name="fdate" id="fdate" value="{{ $req['fdate'] }}">
                                        <input type="date" class="form-control mb-1" name="tdate" id="tdate" value="{{ $req['tdate'] }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary me-1">Search</button>
                            <a href="" class="btn btn-outline-primary">Clear Search</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow no-border">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-row mb-4">
                        <h5 class="card-title">Order Stock Report</h5>
                        <div class="d-flex gap-2">
                            @if (count($orders))
                                <form action="{{ route('admin.print-order-stock-report') }}"  method="POST" target="_blank">
                                    @csrf
                                    <input type="hidden" value="{{ $req['product_category_id'] }}" name="product_category_id">
                                      @foreach(($req['user_id'] ?? []) as $uid)
                                        <input type="hidden" name="user_id[]" value="{{ $uid }}">
                                    @endforeach
                                    <input type="hidden"  value="{{ $req['area_id'] }}" name="area_id">
                                    <input type="hidden"  value="{{ $req['fdate'] }}" name="fdate">
                                    <input type="hidden"  value="{{ $req['tdate'] }}" name="tdate">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="fa fa-print" aria-hidden="true"></i> Print
                                    </button>
                                </form>
                                <form action="{{ route('admin.export-order-stock-report') }}" method="POST">
                                    @csrf
                                    <input type="hidden" value="{{ $req['product_category_id'] }}" name="product_category_id">
                                      @foreach(($req['user_id'] ?? []) as $uid)
                                        <input type="hidden" name="user_id[]" value="{{ $uid }}">
                                    @endforeach
                                    <input type="hidden"  value="{{ $req['area_id'] }}" name="area_id">
                                    <input type="hidden"  value="{{ $req['fdate'] }}" name="fdate">
                                    <input type="hidden"  value="{{ $req['tdate'] }}" name="tdate">
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="fa fa-file-excel-o" aria-hidden="true"></i> Export
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                    <hr>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Item Name</th>
                                    <th>Customer Name</th>
                                    <th style="text-align:right;">Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (count($orders))
                                    @php
                                        $sno = 1;
                                        $uom = "";
                                    @endphp
                                    @foreach ($orders as $product_name => $order_products)
                                        <tr>
                                            <td style="background-color:#f1f1f1;">{{ $sno++ }}</td>
                                            <td colspan="2" style="background-color:#f1f1f1; font-size:14px; font-weight: bold;">{{ $product_name }}</td>
                                            <td style="background-color:#f1f1f1; font-size:14px; font-weight: bold;  " align="right">{{ $order_products[0]->uom_name }}</td>
                                        </tr>
                                        @php
                                            $total_quantities = 0;
                                        @endphp
                                        @foreach ($order_products as $product)
                                            @php
                                                $total_quantities += $product->quantity;
                                            @endphp
                                            <tr>
                                                <td colspan="2"></td>
                                                <td id="{{ $product->order_product_id }}-u">{{ $product->user_name }}</td>
                                                <td align="right">
                                                    @if ($product->status != 'completed')
                                                        <button type="button" class="btn btn-sm btn-outline-primary btn-e-stock-quantity" data-bs-toggle="modal" data-bs-target="#edit-qty" data-id="{{ $product->order_product_id }}" data-qty="{{ $product->quantity }}" data-sno="{{ $sno }}" data-product="{{ $product_name }}">
                                                            <i class="fa fa-pencil"></i>
                                                        </button>
                                                    @endif
                                                    <span class="quantities-{{ $sno }}" id="{{ $product->order_product_id }}-qty">{{ $product->quantity }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr>
                                            <td colspan="3" style="font-weight: bold; text-align:right; font-size:14px;">{{ $product_name }}</td>
                                            <td style="font-weight: bold; text-align:right; font-size:14px;" id="total-quantities-{{ $sno }}">{{ number_format($total_quantities,2) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="4"></td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="4">No record found.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="edit-qty" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Quantity</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST" id="e-quantity-form">
                    @csrf
                    <input type="hidden" id="order_product_id" name="id">
                    <div class="modal-body">
                        <div class="mb-4">
                            <label for="i_name">Product</label>
                            <input type="text" class="form-control" id="i_name" disabled>
                        </div>
                        <div class="mb-4">
                            <label for="c_name">Customer Name</label>
                            <input type="text" class="form-control" id="c_name" disabled>
                        </div>
                        <div>
                            <label for="e_quantity">Quantity</label>
                            <span class="text-danger"> *</span>
                            <input type="number" step="0.01" class="form-control" id="e_quantity" name="qty" required>
                        </div>
                        <div class="alert alert-warning mt-3 mb-0 d-none">
                            <strong>Warning!</strong> <span></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close">Close</button>
                        <button type="submit" class="btn btn-primary">
                            Submit
                            <div class="spinner-border spinner-border-sm d-none" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
