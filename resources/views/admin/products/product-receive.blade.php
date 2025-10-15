@extends('layouts.admin')
@section('title', 'Receive Quantity')
@section('content')

    <div class="row mb-5">
        <div class="col-md-12">
            <div class="card shadow no-border mb-0">
                <div class="card-body">
                    <h5 class="mb-4">Filter Receive Quantity</h5>
                    <form method="GET" class="form-wrapper">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-4">
                                    <label for="filterFromDate">Effective Date From</label>
                                    <input type="date" class="form-control" name="fdate" id="filterFromDate" value="{{ $input['fdate'] ?? '' }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-4">
                                    <label for="filterToDate">Effective Date To</label>
                                    <input type="date" class="form-control" name="tdate" id="filterToDate" value="{{ $input['tdate'] ?? '' }}">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-outline-primary">Search</button>
                                <a href="{{ route('admin.orders') }}" class="btn btn-outline-secondary">Clear Search</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-md-12">
            <div class="d-flex justify-content-end">
                <a href="{{ url('/admin/product-receive-qty/add/' . Carbon\Carbon::now()->format('Y-m-d')) }}" class="btn btn-outline-primary">
                    Set Receive Quantity
                </a>
            </div>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-md-12">
            <div class="card shadow no-border mb-0">
                <div class="card-body">
                    <h5 class="mb-4">Receive Quantity</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered w-100">
                            <thead>
                                <tr>
                                    <th>Action</th>
                                    <th>Date</th>
                                    <th>Last Updated At</th>
                                    <th>Added At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($receive_qtys as $index => $receive_qty)
                                <tr>
                                    <td>
                                        <a href="{{ url('/admin/product-receive-qty/add/' . $receive_qty->date) }}" class="btn btn-sm btn-outline-primary" title="View">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                    </td>
                                    <td>{{ $receive_qty->date }}</td>
                                    <td>{{ $receive_qty->updated_at }}</td>
                                    <td>{{ $receive_qty->created_at }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <div>{{ $receive_qtys->links('pagination::bootstrap-4') }}</div>
                </div>
            </div>
        </div>
    </div>

@endsection
@section('script')

    <script src="{{ asset('assets/js/sweetalert.min.js') }}"></script>
    <script>
        $(document).ready(function(){
            function handleRemoveAction(dailyPriceId) {
                // Display SweetAlert confirmation
                Swal.fire({
                    title: 'Confirm',
                    text: 'Are you sure you want to remove this daily price?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, remove it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                    // If user confirms, redirect to remove URL
                        window.location.href = "{{ url('/admin/product-daily-price/remove/') }}" + dailyPriceId;
                    }
                });
            }

            // Attach click event to remove links
            $('a[data-action="remove"]').on('click', function(e) {
                e.preventDefault();
                var dailyPriceId = $(this).data('id');
                handleRemoveAction(dailyPriceId);
            });
        });
    </script>

@endsection