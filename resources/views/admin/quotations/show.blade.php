@extends('layouts.admin')
@section('title', 'Quotation Summary')
@section('content')

    <div class="row mb-5">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <h4>Quotation Summary</h4>
                <a href="{{ route('admin.quotations.index') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-chevron-circle-left" aria-hidden="true"></i> Back To Quotation List
                </a>
            </div>
            
            <div class="card shadow border-0 quotation-box">
                <div class="card-body">
                    <div class="d-flex justify-content-between gap-3 mb-4 flex-wrap">
                        <div class="d-flex gap-3">
                            <h5 class="card-title fw-bold mb-0">{{ $category ? $category->category_name : 'No Category' }}</h5>
                            <span>({{ $date }})</span>
                        </div>
                        <button class="btn btn-sm btn-primary copy-to-clipboard">Copy</button>
                    </div>
                    @foreach($products as $category => $products)
                        <h6 class="category-title">{{ $category }}</h6>
                        <ul>
                            @foreach($products as $product)
                                <li>{{ $product->name }} - RM{{ $product->price }}</li>
                            @endforeach
                        </ul>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

@endsection
