@extends('layouts.admin')
@section('title', 'Manage Quotations')
@section('content')

    <div class="row mb-5">
        <div class="col-md-12">
            <div class="card shadow no-border mb-0">
                <div class="card-body">
                    <h5 class="mb-4">Filter Quotations</h5>
                    <form method="GET" class="form-wrapper">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-4">
                                    <label class="mb-2" for="date">Date</label>
                                    <input type="date" class="form-control" name="date" id="date" value="{{ $date }}">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-outline-primary">Search</button>
                                <a href="{{ route('admin.quotations.index') }}" class="btn btn-outline-secondary">Clear Search</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-md-12">
            <div class="card shadow no-border mb-0">
                <div class="card-body">
                    <h5 class="mb-4">Quotations</h5>
                    <div class="table-responsive">
                        <table id="orderTable" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Option</th>
                                    <th>Categroy</th>
                                    <th>Date</th>
                                    <th>Products</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($categories as $category)
                                    <tr>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                    Action
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('admin.quotations.show', encrypt($category->user_category)) }}?date={{ $date }}">
                                                            Quotation Summary
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item copy-to-clipboards" href="javascript:void(0);" data-user-category="{{ encrypt($category->user_category) }}" data-date="{{ $date }}">
                                                            Copy
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                        <td>{{ $category->category_name ?? 'No Category' }}</td>
                                        <td>{{ $date }}</td>
                                        <td>{{ $category->total_products ?? 0 }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
