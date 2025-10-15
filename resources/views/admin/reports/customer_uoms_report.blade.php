@extends('layouts.admin')
@section('title', 'Export Customer Uoms Report')
@section('css')

    <link rel="stylesheet" href="{{ asset('assets/css/select2.min.css') }}" />

@endsection
@section('content')

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow no-border">
                <div class="card-header bg-transparent">
                    <h5 class="card-title fw-bold my-2">Export Customer Uoms Report</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.export-customer-uoms-report') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-4">
                                    <label class="mb-2" for="status">Order Status</label>
                                    <div class="d-flex">
                                        <select class="form-control" name="status" id="status">
                                            <option value="">All</option>
                                            @foreach ($statuses as $key => $value)
                                                <option value="{{ $key }}">{{ $value }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-4">
                                    <label class="mb-2" for="fdate">Delivering Date</label>
                                    <input type="date" class="form-control" name="fdate" id="fdate" value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                        </div>
                        <div class="text-end mb-4">
                            <button type="submit" class="btn btn-outline-primary px-5">
                                Generate and Export Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
