@extends('layouts.admin')
@section('title', 'Manage Products')
@section('content')

    <div class="row mb-5">
        <div class="col-md-12">
            <div class="card shadow no-border mb-0">
                <div class="card-body">
                    <h5 class="mb-4">Filter Products</h5>
                    <form method="GET" class="form-wrapper">
                        <input type="hidden" name="price_range" id="priceRangeInput">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-4">
                                    <label class="mb-2" for="uom_id">Select UOM</label>
                                    <select class="form-select"  id="uom_id" name="uom_id">
                                        <option value="">Choose...</option>
                                        @foreach ($uoms as $uom)
                                            <option value="{{ $uom->id }}" {{ request('uom_id') == $uom->id ? 'selected' : '' }}>
                                                {{ $uom->uom_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-4">
                                    <label class="mb-2" for="product_category_id">Select Category</label>
                                    <select class="form-select" id="product_category_id" name="product_category_id">
                                        <option value="">Choose...</option>
                                        @foreach ($product_categories as $category)
                                            <option value="{{ $category->id }}" {{ request('product_category_id') == $category->id ? 'selected' : '' }}>
                                                {{ $category->category_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-4">
                                    <label class="mb-2" for="filterSku">SKU</label>
                                    <input type="text" class="form-control" name="sku" id="filterSku" value="{{ $input['sku'] ?? '' }}" placeholder="SKU">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-4">
                                    <label class="mb-2" for="filterName">Name</label>
                                    <input type="text" class="form-control" name="name" id="filterName" value="{{ $input['name'] ?? '' }}" placeholder="Name">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-4">
                                    <label class="mb-2" for="filterStatus">Status</label>
                                    <select class="form-select" name="status" id="filterStatus">
                                        <option value="">All</option>
                                        <option value="active"{{ ($input['status'] ?? '') == 'active'? " selected" : "" }}>Active</option>
                                        <option value="inactive"{{ ($input['status'] ?? '') == 'inactive'? " selected" : "" }}>Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-4">
                                    <div id="priceRange" class="row col-12 g-2">
                                        <div class="form-group col-md-6">
                                            <label class="mb-2" for="filterPriceFrom">Price Range From</label>
                                            <input type="number" class="form-control" name="min_price" id="filterPriceFrom" value="{{ $input['min_price'] }}" step="0.01" placeholder="Min">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label class="mb-2" for="filterPriceTo">Price Range To</label>
                                            <input type="number" class="form-control" name="max_price" id="filterPriceTo" value="{{ $input['max_price'] }}" step="0.01" placeholder="Max">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-4">
                                    <label class="mb-2" for="filterFromDate">Date From</label>
                                    <input type="date" class="form-control" name="fdate" id="filterFromDate" value="{{ $input['fdate'] ?? '' }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-4">
                                    <label class="mb-2" for="filterToDate">Date To</label>
                                    <input type="date" class="form-control" name="tdate" id="filterToDate" value="{{ $input['tdate'] ?? '' }}">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-outline-primary">Search</button>
                                <a href="{{ route('admin.products') }}" class="btn btn-outline-secondary">Clear Search</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <p class="mb-0">Showing {{ $products->firstItem() ?? 0 }} to {{ $products->lastItem() ?? 0 }} of {{ $products->total() ?? 0 }}  results</p>
                <div class="d-flex justify-content-end align-items-center flex-wrap gap-1">
                    <a href="{{ route('admin.product-receive-qty.index') }}" class="btn btn-outline-primary">
                        Set Receive Qty
                    </a>
                    <a href="{{ route('admin.product-balance-qty.index') }}" class="btn btn-outline-primary">
                        Set Balance Qty
                    </a>
                    <a href="{{ route('admin.product-daily-prices.index') }}" class="btn btn-outline-primary">
                        Set Daily Price
                    </a>
                    <a href="{{ route('admin.products.create') }}" class="btn btn-outline-primary">
                        Add New Product
                    </a>
                    <a href="{{ route('admin.products-import.index') }}" class="btn btn-outline-success">
                        <i class="fa fa-file-excel-o" aria-hidden="true"></i> Import Products
                    </a>
                    <a href="{{ route('admin.products.export') . $query_params }}" class="btn btn-outline-success">
                        <i class="fa fa-file-excel-o" aria-hidden="true"></i> Export to Excel
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-md-12">
            <div class="card shadow no-border mb-0">
                <div class="card-body">
                    <h5 class="mb-4">Products</h5>
                    <div class="table-responsive">
                        <table id="productTable" class="table table-bordered w-100">
                            <thead>
                                <tr>
                                    <th>Option</th>
                                    <th>Image</th>
                                    <th>SKU</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>UOM</th>
                                    <th>Description</th>
                                    <th>Price</th>
                                    <th>Weight</th>
                                    <th>Status</th>
                                    <th>Last Updated At</th>
                                    <th>Added At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($products as $index => $product)
                                    @php
                                        $image = json_decode($product->images, true);
                                        if (isset($image[0])) {
                                            $image_url = url('/') . '/' . $product_path . "/" . $product->id . "/" . $image[0];
                                        } else {
                                            $image_url = asset('assets/images/product-default.jpg');
                                        }
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="{{ route('admin.products.edit', encrypt($product->id)) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                @if (Auth::user()->id == 1)
                                                    <a href="#" class="btn btn-sm btn-outline-danger" onclick="confirmRemove('{{ $product->id }}')" title="Remove">
                                                        <i class="fa fa-trash"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                        <td><img src="{{ $image_url }}" width="80px" /></td>
                                        <td>{{ $product->sku }}</td>
                                        <td>{{ $product->name }}</td>
                                        <td>{{ $product->category_name }}</td>
                                        <td>{{ $product->uom_name }}</td>
                                        <td>{{ $product->description }}</td>
                                        <td>{{ $product->price }}</td>
                                        <td>{{ $product->weight ?? 0 }} KG</td>
                                        <td>{{ __('product.status.'.$product->status) }}</td>
                                        <td>{{ $product->updated_at }}</td>
                                        <td>{{ $product->created_at }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="12">
                                        {{ $products->appends(request()->query())->links('pagination::bootstrap-4') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
@section('script')

    <script src="{{ asset('assets/js/sweetalert.min.js') }}"></script>
    <script>
        $(document).ready(function() {            
            // Update range values dynamically
            // var min_price = parseFloat("{{ $input['min_price'] }}");
            // var max_price = parseFloat("{{ $input['max_price'] }}");
            // var from_price = parseFloat("{{ $input['from_price'] }}");
            // var to_price = parseFloat("{{ $input['to_price'] }}");
            // $("#priceRangeSlider").slider({
            //     range: true, // Enable two handles
            //     min: min_price,
            //     max: max_price,
            //     values: [from_price, to_price], // Initial range values
            //     slide: function (event, ui) {
            //         $("#priceRangeValue").text(ui.values[0] + " - " + ui.values[1]);
            //         $("#priceRangeInput").val(ui.values[0] + "," + ui.values[1]);
            //     }
            // });

            // // Initialize the displayed range and input field
            // var initialRange = $("#priceRangeSlider").slider("option", "values");
            // $("#priceRangeValue").text(initialRange[0] + " - " + initialRange[1]);
            // $("#priceRangeInput").val(initialRange[0] + "," + initialRange[1]);

            $("#filterPriceFrom,#filterPriceTo").change(function(e){
                $("#priceRangeInput").val($("#filterPriceFrom").val() + "," + $("#filterPriceTo").val());
            });
        });

        function confirmRemove(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'You won\'t be able to revert this!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, remove it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // If the user clicks "Yes," redirect to the removeUrl
                    window.location.href = "{{ url('/admin/product/remove/') }}/" + id;
                }
            });
        }
    </script>

@endsection