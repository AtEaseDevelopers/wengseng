@extends('layouts.admin')
@section('title', 'Add Product Receive Quantity')
@section('content')

    <div class="row mb-5">
        <div class="col-md-12">
            <div class="card shadow no-border mb-4">
                <div class="card-body">
                    @if($duplicating)
                        <h5 class="mb-4">
                            <i class="fa fa-tags" aria-hidden="true"></i>
                            Duplicate Product Receive Quantity For {{ Carbon\Carbon::parse($setup_date)->format('d/m/Y') }} from {{ Carbon\Carbon::parse($duplicate_from_date)->format('d/m/Y') }}
                        </h5>
                    @else
                        <h5 class="mb-4">
                            <i class="fa fa-tags" aria-hidden="true"></i>
                            Manage Product Receive Quantity For
                        </h5>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="setup_date" class="mb-2">Setup Date</label>
                                    <input type="date" class="form-control d-inline col-3" id="setup_date" name="setup_date" value="{{ $setup_date }}">
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card shadow no-border mb-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0">Set Receive Quantity</h5>
                        <button type="button" class="btn btn-warning" id="add-line-btn">
                            <i class="fa fa-plus" aria-hidden="true"></i> Add Line
                        </button>
                    </div>
                    <form method="POST" action="{{ url('/admin/product-receive-qty/add') . '/' . $setup_date }}" enctype="multipart/form-data" class="form-wrapper" id="receive-qty-form">
                        @csrf
                        <table class="table table-bordered" id="items-table">
                            <thead>
                                <tr>
                                    <th style="width: 35%">Product</th>
                                    <th style="width: 25%">Qty</th>
                                    <th style="width: 25%">Remark</th>
                                    <th style="width: 15%">Action</th>
                                </tr>
                            </thead>
                            <tbody id="items-tbody">
                                {{-- Rows will be added by JavaScript --}}
                            </tbody>
                        </table>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="d-flex justify-content-end">
                                    <a href="{{ url('/admin/product-receive-qty') }}" class="btn btn-secondary me-2">Back</a>
                                    <button type="submit" class="btn btn-primary">
                                        Save
                                        <div class="spinner-border spinner-border-sm d-none" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
@section('script')

    <script src="{{ asset('assets/js/sweetalert.min.js') }}"></script>
    <script>
        var products = @json($products);
        var existingQuantities = @json($existing_quantities);
        var rowIndex = 0;

        $(document).ready(function(){
            // Date change handler
            $("#setup_date").change(function(){
                Swal.fire({
                    title: 'Fetching...',
                    text: 'The receive quantity is now fetching...',
                    icon: 'info',
                    showConfirmButton: false,
                    timer: 2000
                });
                window.location.href = "{{ url('/admin/product-receive-qty/add') }}/" + $(this).val();
            });

            // Add line button click
            $("#add-line-btn").click(function(){
                addRow();
            });

            // Delete row button click (delegated)
            $(document).on('click', '.delete-row-btn', function(){
                $(this).closest('tr').remove();
            });

            // Initialize: add existing quantities first, then fill up to 50 rows
            if (existingQuantities.length > 0) {
                existingQuantities.forEach(function(item) {
                    addRow(item.product_id, item.qty, item.remark);
                });
            }

            // Fill remaining rows to reach 50
            var remainingRows = 50 - existingQuantities.length;
            for (var i = 0; i < remainingRows; i++) {
                addRow();
            }
        });

        function addRow(productId = '', qty = '', remark = '') {
            if (remark === null) {
                remark = ''
            }
            var optionsHtml = '<option value="">Choose product</option>';
            products.forEach(function(product) {
                var selected = (productId && product.id == productId) ? ' selected' : '';
                var displayName = product.name + (product.sku ? ' (SKU: ' + product.sku + ')' : '');
                optionsHtml += '<option value="' + product.id + '"' + selected + '>' + displayName + '</option>';
            });

            var rowHtml = `
                <tr>
                    <td>
                        <select class="form-select product-select" name="items[${rowIndex}][product_id]">
                            ${optionsHtml}
                        </select>
                    </td>
                    <td>
                        <input type="text" class="form-control" name="items[${rowIndex}][qty]" value="${qty}" placeholder="Enter qty">
                    </td>
                    <td>
                        <input type="text" class="form-control" name="items[${rowIndex}][remark]" value="${remark}" placeholder="Enter remark">
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-outline-danger btn-sm delete-row-btn">
                            <i class="fa fa-trash" aria-hidden="true"></i>
                        </button>
                    </td>
                </tr>
            `;

            $('#items-tbody').append(rowHtml);

            // Initialize Select2 on the newly added row
            $('#items-tbody tr:last .product-select').select2({
                placeholder: 'Choose product',
                allowClear: true,
                width: '100%'
            });

            rowIndex++;
        }
    </script>

@endsection
