@foreach ($products as $product)
    @php
        $image = json_decode($product->images, true);
        if (isset($image[0])) {
            $image_url = $image_path . $product->id . "/" . $image[0];
        } else {
            $image_url = asset('assets/images/product-default.jpg');
        }

        $price = $product->price;
        if ($product->daily_price) {
            $price = $product->daily_price;
        } elseif ($id != 'products_visibility') {
            $price = $product->category_price ?? $price;
        }
    @endphp
    <div class="card products-card mb-3" id="product-card-{{ $product->id }}" data-id="{{ $product->id }}" data-name="{{ $product->name }}" data-sku="{{ $product->sku }}">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div class="d-flex">
                    <img src="{{ $image_url }}" alt="{{ $product->name }}" onError="this.onerror=null;this.src='{{ asset('assets/images/product-default.jpg') }}';" class="me-3" width="120">
                    <div>
                        <h5 style="margin-bottom: 5px; line-height: 1;">{{ $product->name }}</h5>
                        <p style="margin-bottom: 20px; color: gray; line-height: 1;">In {{ $product->uom_name }}</p>
                        <p>Sku: {{ $product->sku }}</p>
                        <p>RM {{ $price }}</p>
                    </div>
                </div>
                <div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input products toggle-product-options" id="product_{{ $product->id }}" value="{{ $product->id }}">
                        <label class="form-check-label" for="product_{{ $product->id }}">Select</label>
                    </div>
                </div>
            </div>
            <div class="product-option-section d-none" id="product-option-{{ $product->id }}">
                <input type="hidden" name="product_id" value="{{ $product->id }}" />
                <input type="hidden" name="product_name" value="{{ $product->name }}" />
                <input type="hidden" name="price" value="{{ $price }}" />
                @if ($product->product_options)
                    @php
                        $product_options = json_decode($product->product_options, true);
                    @endphp
                    @foreach ($product_options as $optionName => $optionData)
                        <div class="form-group mb-3">
                            <label class="mb-2" for="productOption-{{ $optionName }}">
                                {{ $optionName }}
                            </label>
                            @if ($optionData['mandatory'])
                                <span class="text-danger"> *</span>
                            @endif
                            @php
                                $option_items = $optionData['items'];
                            @endphp
                            <select id="productOption-{{ $optionName }}" class="form-select" name="{{ $optionName }}" {{ $optionData['mandatory'] ? 'required' : '' }}>
                                <option value="">Choose {{ $optionName }}...</option>
                                @foreach ($option_items as $item)
                                    <option value="{{ $item }}">{{ ucfirst($item) }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                @endif

                @if ($product->sell_in == 'qty')
                    <div class="form-group mb-3">
                        <label class="mb-2" for="productQuantity">Quantity</label>
                        <span class="text-danger"> *</span>
                        <input type="number" class="form-control col-3" id="productQuantity_{{ $product->id }}" name="quantity" value="1" min="0" step="0.10">
                    </div>
                @else
                    <div class="form-group mb-3">
                        <label class="mb-2" for="productQuantity">Weight/KG</label>
                        <span class="text-danger"> *</span>
                        <input type="number" class="form-control col-3" id="productWeight_{{ $product->id }}" name="weight" value="1" min="0" step="0.10">
                    </div>
                @endif
                <!--<div class="form-group mb-3">-->
                <!--    <label class="mb-2" for="nos">Nos</label>-->
                <!--    <textarea class="form-control" id="nos_{{ $product->id }}" name="nos"></textarea>-->
                <!--</div>-->
                <div class="form-group mb-3">
                    <label class="mb-2" for="productRemark">Remark</label>
                    <textarea class="form-control" id="productRemark_{{ $product->id }}" name="remark"></textarea>
                </div>
            </div>
        </div>
    </div>
@endforeach