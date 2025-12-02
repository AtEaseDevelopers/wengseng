const appUrl = document.querySelector('meta[name="app-url"]').getAttribute('content');
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

document.addEventListener('DOMContentLoaded', function () {
    if (document.querySelectorAll(".form-wrapper")) {
        const forms = document.querySelectorAll(".form-wrapper");
        forms.forEach(form => {
            form.addEventListener("submit", function (event) {
                var button = this.querySelector('button[type="submit"]');
                if (button) {
                    button.disabled = true;
                    var spinnerBorder = button.querySelector('.spinner-border');
                    if (spinnerBorder) {
                        spinnerBorder.classList.remove('d-none');
                    }
                }

                document.querySelectorAll("*").forEach(function (element) {
                    element.style.cursor = "wait";
                });
            });
        });
    }

    if (document.getElementById('product-search')) {
        const searchInput = document.getElementById('product-search');
        const productList = document.getElementById('productList');

        searchInput.addEventListener('input', function() {
            const searchValue = this.value.toLowerCase().trim();
            const productCards = productList.querySelectorAll('.products-card');
    
            if (searchValue === '') {
                productCards.forEach(card => card.classList.remove('d-none'));
            } else {
                productCards.forEach(card => {
                    const name = card.getAttribute('data-name').toLowerCase();
                    const sku = card.getAttribute('data-sku').toLowerCase();
                    if (name.includes(searchValue) || sku.includes(searchValue)) {
                        card.classList.remove('d-none');
                    } else {
                        card.classList.add('d-none');
                    }
                });
            }
        });
    }

    if (document.getElementById('addProductModal')) {
        document.getElementById("addProductModal").addEventListener('shown.bs.modal', function(e) {
            const productListElement = document.getElementById("productList");
            if (productListElement.innerHTML.trim() == '') {
                const pricing_date = document.getElementById('pricing_date');
                if (pricing_date.value == '') {
                    pricing_date.classList.add('is-invalid');
                    pricing_date.focus();
                    return;
                }
                pricing_date.classList.remove('is-invalid');

                const form = new FormData();
                if (document.getElementById('order_customer')) {
                    form.append('id', document.getElementById('order_customer').value);
                } else {
                    form.append('id', 'products_visibility');
                }
                form.append('pricing_date', pricing_date.value);
        
                fetch(appUrl + `/get-products-list`, {
                        method: 'POST',
                        body: form,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                        },
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById("productList").innerHTML = data.view;
                        } else {
                            Swal.fire('Error', 'An error occurred.', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error', 'An error occurred.', 'error');
                    });
            } else {
                init_pre_order_data();
            }
        });
    }

    if (document.getElementById('add-products')) {
        document.getElementById('add-products').addEventListener('click', function() {
            const checkboxes = document.querySelectorAll('.products:checked');
            var isValid = true;
            var isOrder = document.getElementById('order_customer');
            
            checkboxes.forEach(function(checkbox) {
                const product_id = checkbox.value;
                if (!document.getElementById('sel-product-' + product_id)) {
                    var productOptions = {};
                    const card = document.getElementById('product-card-' + product_id);
    
                    var inputs = card.querySelectorAll('select, input, textarea');
                    inputs.forEach(function(element) {
                        var optionName = element.name;
                        var selectedValue = element.value;
    
                        if (isOrder && element.hasAttribute('required') && !selectedValue) {
                            element.focus();
                            isValid = false;
                            return;
                        }
    
                        productOptions[optionName] = selectedValue;
                    });
    
                    if (isValid) {
                         var qtyWeightValue = (productOptions['quantity'] == '' || productOptions['quantity'] == undefined) ? productOptions['weight'] : productOptions['quantity'];
                        
                        productOptions['total_price'] = parseFloat(productOptions['price']) * parseFloat(qtyWeightValue);
                        selected_products.push(productOptions);
                    }
                }
            });

            if (!isValid) {
                Swal.fire({
                    title: 'Warning',
                    text: "Please fill all required fields.",
                    icon: 'warning',
                });
            } else if (selected_products.length != 0) {
                display_selected_products();
            } else {
                Swal.fire({
                    title: 'Warning',
                    text: "Please select any product to add to the bag.",
                    icon: 'warning',
                });
            }
        });
    }

    // if (document.getElementById('order_customer')) {
    //     document.getElementById('order_customer').addEventListener('change', function() {
    //         init_customer_details();
    //     });
    // }

    if (document.getElementById('payment_method')) {
        document.getElementById('payment_method').addEventListener('change', function() {
            toggleTransferSlip();
        });
    }

    if (document.querySelector(".btns-order-action")) {
        document.querySelectorAll(".btns-order-action").forEach(function(btn) {
            btn.addEventListener("click", function(e) {
                e.preventDefault();
        
                if (this.classList.contains('back')) {
                    if (step === 'select_products') {
                        document.getElementById("customer_info").classList.toggle('d-none');
                        document.getElementById("add-product-info").classList.toggle('d-none');
                        document.querySelector("button.back").classList.toggle('d-none');
                        document.getElementById("order_customer").disabled = false;
                        step = 'customer_info';
                    }
                } 
                
                if (this.classList.contains('next')) {
                    let allow_continue = true;
                    let requiredFields = document.querySelectorAll('form [required]');
        
                    requiredFields.forEach(function(itm) {
                        if (!itm.value && itm.getAttribute('name')) {
                            Swal.fire({
                                title: 'Warning',
                                text: "Please fill in all the required input before proceeding.",
                                icon: 'warning',
                            });
                            allow_continue = false;
                            return false;
                        }
                    });
        
                    if (!allow_continue) {
                        return false;
                    }
        
                    if (step === 'customer_info') {
                        document.getElementById("customer_info").classList.toggle('d-none');
                        document.getElementById("add-product-info").classList.toggle('d-none');
                        document.querySelector("button.back").classList.toggle('d-none');
                        document.getElementById("order_customer").disabled = true;
                        if (document.getElementById("user_id")) {
                            document.getElementById("user_id").value = document.getElementById("order_customer").value;
                        }
                        step = 'select_products';
                    } else if (step === 'select_products') {
                        if (!selected_products.length) {
                            Swal.fire({
                                title: 'Warning',
                                text: "Please click \"Add Product\" to add product into the bag to checkout.",
                                icon: 'warning',
                            });
                            return false;
                        }
        
                        Swal.fire({
                            title: order_text,
                            text: order_subtext,
                            icon: 'info',
                            showCancelButton: true,
                            confirmButtonColor: '#28a745',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Yes'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                document.querySelector('form').submit();
                            }
                        });
                    }
                }
            });
        });
    }

    if (document.querySelector('input[name="quantity[]"]')) {
        document.querySelectorAll('input[name="quantity[]"]').forEach(function(input) {
            input.addEventListener('change', function() {
                calculateTotal();
            });
        });
    }

    if (document.querySelector('.checkall')) {
        document.querySelector('.checkall').addEventListener('change', function () {
            const isChecked = this.checked;
        
            const checkboxes = document.querySelectorAll('.cs-checkbox');
        
            checkboxes.forEach(function (checkbox) {
                checkbox.checked = isChecked;
            });

            const statusActionButton = document.querySelector(".status-action-button");
            const downloadZip = document.querySelector(".download-zip");
            const changeOrderStatuses = document.getElementById('change-order-statuses');
            const changeOrderLorry = document.getElementById('change-order-lorry');
            const syncInvoice = document.getElementById('sync-invoice');

            if (document.querySelectorAll(".order-cbx-col input[type=checkbox]:checked").length) {
                if (statusActionButton) {
                    document.querySelector(".status-action-button[data-status='Completed']").style.display = "block";
                }

                if (downloadZip) {
                    document.querySelector(".download-zip").style.display = "block";
                }

                if (changeOrderStatuses) {
                    document.getElementById('change-order-statuses').classList.remove('d-none');
                }

                if (changeOrderLorry) {
                    document.getElementById('change-order-lorry').classList.remove('d-none');
                }
                if (syncInvoice) {
                    document.getElementById('sync-invoice').classList.remove('d-none');
                }
            } else {
                if (changeOrderLorry) {
                    document.getElementById('change-order-lorry').classList.add('d-none');
                }
                
                if (changeOrderStatuses) {
                    document.getElementById('change-order-statuses').classList.add('d-none');
                }

                if (downloadZip) {
                    document.querySelector(".download-zip").style.display = "none";
                }

                if (statusActionButton) {
                    document.querySelector(".status-action-button[data-status='Completed']").style.display = "none";
                }
                
                if (syncInvoice) {
                    document.getElementById('sync-invoice').classList.add('d-none');
                }
            }
        });
    }

    if (document.getElementById('change-order-statuses')) {
        document.getElementById('change-order-statuses').addEventListener('click', function() {
            var selectedOrders = [];
            document.querySelectorAll("input[name='selected_orders[]']:checked").forEach(function(checkbox) {
                selectedOrders.push(checkbox.value);
            });

            if (document.querySelector('#order-statuses')) {
                document.querySelector('#order-statuses .orders_id').value = selectedOrders;
            } else if (document.querySelector('.quotations_id')) {
                document.querySelectorAll('.quotations_id').forEach(element => {
                    element.value = selectedOrders;
                });
            }
        });
    }

    if (document.getElementById('change-order-lorry')) {
        document.getElementById('change-order-lorry').addEventListener('click', function() {
            var selectedOrders = [];
            document.querySelectorAll("input[name='selected_orders[]']:checked").forEach(function(checkbox) {
                selectedOrders.push(checkbox.value);
            });
            document.querySelector('#assign-lorry .orders_id').value = selectedOrders;
        });
    }

     if (document.getElementById('sync-invoice')) {
        document.getElementById('sync-invoice').addEventListener('click', function () {
            var selectedOrders = [];
            document.querySelectorAll("input[name='selected_orders[]']:checked").forEach(function (checkbox) {
                selectedOrders.push(checkbox.value);
            });
            document.querySelector('#sync-invoice .orders_id').value = selectedOrders;
        });
    }


    if (document.querySelector('.btn-change-lorry')) {
        document.querySelectorAll('.btn-change-lorry').forEach(function(button) {
            button.addEventListener('click', function() {
                document.querySelector('#change-lorry .orders_id').value = this.dataset.id;

                document.querySelector('#change-lorry #order_driver_id').value = this.dataset.lorry;
                document.querySelector('#change-lorry #order_driver_id').dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
    }

    if (document.querySelector('.btn-add-order-weight')) {
        document.querySelectorAll('.btn-add-order-weight').forEach(function(button) {
            button.addEventListener('click', function() {
                document.querySelector('#add-weight .orders_id').value = this.dataset.id;
                const modalBody = document.querySelector('#add-weight .modal-body');
                modalBody.innerHTML = `<div class="text-center p-4">
                    <div class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>`;

                const form = new FormData();
                form.append('id', this.dataset.id);

                fetch(appUrl + `/admin/order-products-list`, {
                    method: 'POST',
                    body: form,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    }
                })
                .then(response => response.json())
                .then(data => {
                    modalBody.innerHTML = data.view;
                })
                .catch(error => {
                    Swal.fire('Error', 'An error occurred.', 'error');
                    console.log(error);
                });
            });
        });
    }

    if (document.querySelector('.btn-add-to-cart')) {
        document.querySelectorAll('.btn-add-to-cart').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const modalBody = document.getElementById('add-to-cart-form').querySelector('.modal-body');
                modalBody.innerHTML = `
                    <div class="p-5 text-center">
                        <div class="spinner-border spinner-border-sm" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>`;

                document.getElementById('add-to-cart-form').setAttribute('action', btn.getAttribute('data-action'));
                
                const form = new FormData();
                form.append('id', btn.getAttribute('data-id'));

                fetch(appUrl + '/add-to-cart-product-info', {
                    method: "POST",
                    body: form,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    modalBody.innerHTML = data.view;
                })
                .catch(error => {
                    Swal.fire('Error', 'An error occurred.', 'error');
                });
            });
        });
    }

    if (document.getElementById('select-all')) {
        document.getElementById('select-all').addEventListener('click', function() {
            const checkboxes = document.querySelectorAll('.products-card input[type="checkbox"]');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = !checkbox.checked;
            });
        });        
    }

    if (document.getElementById('report-filters')) {
        const exportBtn = document.getElementById('export-excel-btn');
        const form = document.getElementById('report-filters');

        exportBtn.classList.add('disabled');

        form.addEventListener('input', function() {
            const hasValue = Array.from(form.elements).some(element => {
                return (element.tagName === 'INPUT' || element.tagName === 'SELECT') && element.value.trim() !== '';
            });

            if (hasValue) {
                exportBtn.classList.remove('disabled');
            } else {
                exportBtn.classList.add('disabled');
            }
        });

        form.addEventListener('change', function() {
            const hasValue = Array.from(form.elements).some(element => {
                return (element.tagName === 'INPUT' || element.tagName === 'SELECT') && element.value.trim() !== '';
            });

            if (hasValue) {
                exportBtn.classList.remove('disabled');
            } else {
                exportBtn.classList.add('disabled');
            }
        });
    }

    if (document.getElementById('export-excel-btn')) {
        document.getElementById('export-excel-btn').addEventListener('click', function(e) {
            e.preventDefault();
        
            // Get the form values
            var filterId = document.getElementById('filterId').value;
            var filterFromDate = document.getElementById('filterFromDate').value;
            var filterToDate = document.getElementById('filterToDate').value;
            var status = document.getElementById('status').value;
            var driver = document.getElementById('driver').value;
            var customer = document.querySelector('select[name="customer"]').value;
            var area = document.getElementById('area').value;
        
            // Construct the query string
            var queryString = `?id=${encodeURIComponent(filterId)}&fdate=${encodeURIComponent(filterFromDate)}&tdate=${encodeURIComponent(filterToDate)}&status=${encodeURIComponent(status)}&driver=${encodeURIComponent(driver)}&customer=${encodeURIComponent(customer)}&area=${encodeURIComponent(area)}`;
        
            // Get the export URL
            var baseUrl = this.getAttribute('href');
        
            // Redirect to the new URL with query parameters
            window.location.href = baseUrl + queryString;
        });
    }

    if (document.getElementById('productPrice')) {
        document.getElementById('productPrice').addEventListener('blur', function () {
            const newValue = this.value;
            document.querySelectorAll('.category-prices').forEach(input => {
                if (input.value === "0" || input.value === 0) {
                    input.value = newValue;
                }
            });
        });
    }

    if (document.getElementById('e-quantity-form')) {
        const form = document.getElementById('e-quantity-form');
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            
            form.querySelector('button[type="submit"]').disabled = true;
            form.querySelector(".spinner-border").classList.remove("d-none");

            const div_warning = form.querySelector(".alert");
            div_warning.classList.add('d-none');

            fetch(appUrl + '/admin/update-stock-quantity', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                body: new FormData(form)
            })
                .then(response => response.json())
                .then(data => {
                    form.querySelector('button[type="submit"]').disabled = false;
                    form.querySelector(".spinner-border").classList.add("d-none");

                    if (data.status) {
                        const elementID = document.getElementById('order_product_id').value + '-qty';
                        const el = document.getElementById(elementID);

                        // Update the quantity
                        el.innerHTML = document.getElementById('e_quantity').value;

                        let total = 0;

                        // Loop through all elements with the same class
                        document.querySelectorAll('.' + el.getAttribute('class')).forEach(element => {
                            let t = element.innerText.trim(); // remove spaces
                            total += parseFloat(t) || 0;      // safely parse as number
                        });

                        // Update the total
                        document.getElementById('total-' + el.getAttribute('class')).innerHTML = total;

                        let modal = bootstrap.Modal.getInstance(document.getElementById('edit-qty'));
                        if (modal) {
                            modal.hide();
                        }

                        form.reset();
                    } else {
                        div_warning.classList.remove('d-none');
                        div_warning.querySelector('span').innerHTML = data.message;
                    }
                })
                .catch(error => {
                    console.error('Upload error:', error);
                });
        });
    }
});
    
document.addEventListener('change', function(event) {
    if (event.target.matches('.toggle-product-options')) {
        if (document.getElementById('order_customer')) {
            const el = document.getElementById('product-option-' + event.target.value);
            if (event.target.checked) {
                el.classList.remove('d-none');
            } else {
                el.classList.add('d-none');
            }
        }
    }

    if (event.target.matches('.add-products-quantity')) {
        const input = event.target;

        if (document.getElementById('productQuantity_' + input.getAttribute('data-pid'))) {
            document.getElementById('productQuantity_' + input.getAttribute('data-pid')).value = input.value;  
        } else {
            const pid = input.getAttribute('data-pid');
            const price = parseFloat(input.getAttribute('data-price'));
            document.getElementById('product-' + pid + '-total').innerHTML = price * input.value;
        }

        calculateTotal();
    }
});

document.addEventListener('click', function(event) {
    if (event.target.closest('.duplicate-quoation')) {
        event.preventDefault();
        let duplicateEl = event.target.closest('.duplicate-quoation');            
        let id = duplicateEl.getAttribute('data-id');
        let button = document.getElementById('duplicate_quotations_id');
        if (button) {
            let url = button.getAttribute('data-url');
            let finalUrl = url + '?d=' + id;
            button.setAttribute('href', finalUrl);
        }
    }

    if (event.target.closest('.remove-from-bag')) {
        var index = Array.from(document.querySelectorAll('.remove-from-bag')).indexOf(event.target.closest('.remove-from-bag'));

        const sel = event.target.closest('.sel-product');
        if (sel.getAttribute('data-id')) {
            const form = new FormData();
            form.append('id', sel.getAttribute('data-id'));

            fetch(appUrl + '/admin/delete-customer-visibility-product', {
                method: "POST",
                body: form,
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                selected_products.splice(index, 1);
                sel.remove();
                display_selected_products();
            })
            .catch(error => {
                Swal.fire('Error', 'An error occurred.', 'error');
            });
        } else {
            selected_products.splice(index, 1);
            sel.remove();
            display_selected_products();
        }
    }
    
    if (event.target.closest('.btn-plus')) {
        const quantityInput = document.getElementById('quantity');
        let currentValue = parseInt(quantityInput.value);
        quantityInput.value = currentValue + 1;
        updateButtonState(currentValue + 1);
    }

    if (event.target.closest('.btn-minus')) {
        const quantityInput = document.getElementById('quantity');
        let currentValue = parseInt(quantityInput.value);
        if (currentValue > 1) {
            quantityInput.value = currentValue - 1;
            updateButtonState(currentValue - 1);
        }
    }
    
     if (event.target.closest('.btn-plus-weight')) {
        const quantityInput = document.getElementById('weight');
        let currentValue = parseInt(quantityInput.value);
        quantityInput.value = currentValue + 1;
        updateButtonState(currentValue + 1);
    }

    if (event.target.closest('.btn-minus-weight')) {
        const quantityInput = document.getElementById('weight');
        let currentValue = parseInt(quantityInput.value);
        if (currentValue > 1) {
            quantityInput.value = currentValue - 1;
            updateButtonState(currentValue - 1);
        }
    }

    if (event.target.closest('.btn-e-stock-quantity')) {
        document.getElementById('e-quantity-form').querySelector(".alert").classList.add('d-none');

        const btn = event.target.closest('.btn-e-stock-quantity');
        document.getElementById('order_product_id').value = btn.getAttribute('data-id');
        document.getElementById('i_name').value = btn.getAttribute('data-product');
        document.getElementById('c_name').value = document.getElementById(btn.getAttribute('data-id') + '-u').innerText;
        document.getElementById('e_quantity').value = btn.getAttribute('data-qty');
    }

    if (event.target.closest(".copy-to-clipboard")) {
        const box = event.target.closest(".quotation-box");

        // Main heading + date
        const title = box.querySelector("h5")?.innerText || "";
        const dateRaw = box.querySelector("span")?.innerText || "";

        // Format date for delivery date (extract date, add 1 day, format as DD/MM/YYYY)
        const dateMatch = dateRaw.match(/(\d{4})-(\d{2})-(\d{2})/);
        let formattedDate = dateRaw;
        if (dateMatch) {
            const deliveryDate = new Date(dateMatch[1], dateMatch[2] - 1, parseInt(dateMatch[3]) + 1);
            const dd = String(deliveryDate.getDate()).padStart(2, '0');
            const mm = String(deliveryDate.getMonth() + 1).padStart(2, '0');
            const yyyy = deliveryDate.getFullYear();
            formattedDate = `${yyyy}-${mm}-${dd}`;
        }

        let textToCopy = `${title} (${data.date})\nDelivery Date: ${formattedDate}\n\n`;

        // Loop through each category (h6) and its products (li)
        box.querySelectorAll(".category-title").forEach(category => {
            textToCopy += category.innerText + "\n";
            const ul = category.nextElementSibling; // should be the <ul>
            if (ul) {
                textToCopy += [...ul.querySelectorAll("li")]
                    .map(li => "- " + li.innerText)
                    .join("\n");
            }
            textToCopy += "\n\n"; // extra line after each category
        });

        // Copy to clipboard
        navigator.clipboard.writeText(textToCopy.trim()).then(() => {
            event.target.innerHTML = "Copied!";
            setTimeout(() => {
                event.target.innerHTML = "Copy";
            }, 2000);
        }).catch(err => {
            console.error("Failed to copy:", err);
        });
    }

    if (event.target.closest(".copy-to-clipboards")) {
        const btn = event.target.closest(".copy-to-clipboards");
        if (!btn) return;

        const userCategory = btn.dataset.userCategory;
        const date = btn.dataset.date;

        const form = new FormData();
        form.append('category', userCategory);
        form.append('date', date);

        fetch(appUrl + `/admin/quotations/copy`, {
                method: 'POST',
                body: form,
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(res => res.json())
            .then(data => {
                // Add 1 day to the date for delivery date
                const dateParts = data.date.split('-');
                const deliveryDate = new Date(dateParts[0], dateParts[1] - 1, parseInt(dateParts[2]) + 1);
                const dd = String(deliveryDate.getDate()).padStart(2, '0');
                const mm = String(deliveryDate.getMonth() + 1).padStart(2, '0');
                const yyyy = deliveryDate.getFullYear();
                const formattedDate = `${yyyy}-${mm}-${dd}`;

                let textToCopy = `${data.category_name} (${data.date})\n`;
                textToCopy += `Delivery Date: ${formattedDate}\n\n`;

                for (const [catName, products] of Object.entries(data.products)) {
                    textToCopy += catName + "\n";
                    textToCopy += products.map(p => `- ${p.name} - RM${p.price}`).join("\n");
                    textToCopy += "\n\n";
                }

                return navigator.clipboard.writeText(textToCopy.trim());
            })
            .then(() => {
                btn.innerHTML = "Copied!";
                setTimeout(() => btn.innerHTML = "Copy", 2000);
            })
            .catch(err => console.error("Copy failed:", err));
    }
});

function init_customer_details() {
    var order_customer = document.getElementById('order_customer');
    var customerInfo = document.getElementById('customer_info');

    if (customerInfo) customerInfo.classList.add('d-none');

    if (!order_customer.value) {
        return false;
    }

    const form = new FormData();
    form.append('id', order_customer.value);

    fetch(appUrl + `/admin/order/get-customer-info`, {
        method: 'POST',
        body: form,
        headers: {
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        Object.keys(data.customer).forEach(function(field) {
            if (field === 'payment_method') {
                var paymentMethodElement = document.getElementById('payment_method');
                paymentMethodElement.innerHTML = `<option value="" selected>-- Select Payment Method --</option>`;

                var pm = JSON.parse(data.customer[field]);
                if (pm != null) {
                    pm.forEach(function(pm_val) {
                        var newOption = document.createElement('option');
                        newOption.value = pm_val;
                        newOption.textContent = payment_method_options[pm_val];
                        paymentMethodElement.appendChild(newOption);
                    });
                }

                if (paymentMethodElement.getAttribute('data-selected')) {
                    paymentMethodElement.value = paymentMethodElement.getAttribute('data-selected');
                }
            } else {
                if (document.getElementById(field)) {
                    document.getElementById(field).value = data.customer[field];
                }
            }
        });

        if (document.getElementById('update-customer-category-price')) {
            fetch_customer_category_prices();
        }

        if (customerInfo) customerInfo.classList.remove('d-none');
        var nextButton = document.querySelector("form button.next");
        if (nextButton) nextButton.classList.remove('d-none');
        var transferSlipGroup = document.getElementById('transferSlipGroup');
        if (transferSlipGroup) transferSlipGroup.style.display = 'none';
    })
    .catch(error => {
        Swal.fire('Error', 'An error occurred.', 'error');
        console.log(error);
    });
}

function toggleTransferSlip() {
    var paymentMethod = document.getElementById('payment_method').value;
    var transferSlipGroup = document.getElementById('transferSlipGroup');
    var transferSlip = document.getElementById('transfer_slip');

    if (paymentMethod === 'bank-transfer') {
        transferSlipGroup.style.display = 'block';
        transferSlipGroup.setAttribute('required', true);
        transferSlip.setAttribute('required', true);
    } else {
        transferSlipGroup.style.display = 'none';
        transferSlipGroup.removeAttribute('required');
        transferSlip.removeAttribute('required');
    }
}

function display_selected_products() {
    var totalPrice = 0;
    var productHtml = '';
    
    selected_products.forEach(function(product, index) {
        var optionHtml = '';
        var optionHtml1 = '';
        
        if (document.getElementById('order_customer')) {
            for (var key in product) {
                if (product.hasOwnProperty(key) && !['product_id', 'product_name', 'price', 'quantity',  'weight', 'remark', 'total_price', ''].includes(key)) {
                    optionHtml += `<input type="hidden" name="product_options[${index}][${key}]" value="${product[key]}"/>`;
                    optionHtml1 += `<p class="mb-1">${key}: ${product[key]}</p>`;
                    
                    if (document.getElementById('productOption-' + key)) {
                        const selectElement = document.getElementById('productOption-' + key);
                        selectElement.value = product[key];
                    }
                }
            }
        }

        let totalPriceForProduct = parseFloat(product.total_price);
        productHtml += `<div class="sel-product mb-3" id="sel-product-${product.product_id}" ${product.id ? `data-id="${product.product_id}"` : ''}>
            <input type="hidden" name="product_id[]" value="${product.product_id}"/>
                <div class="card"><div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-2">
                        <h5>${product.product_name}</h5>
                        <div class="remove-from-bag">
                            <a role="button"><i class="fa fa-trash"></i> Remove</a>
                        </div>
                    </div>`;

        if (document.getElementById('order_customer')) {
            if (product.quantity == undefined || product.quantity == "") {
                 productHtml += `
                    ${optionHtml}
                    <div class="form-group mb-3">
                        <label class="mb-2">Weight/KG</label>
                        <span class="text-danger"> *</span>
                        <input type="number" class="form-control add-products-weight" name="quantity[]" value="${product.weight}" data-pid="${product.product_id}" data-price="${product.price}" min="1" step="0.1">
                    </div>
                    <div class="form-group mb-3">
                        <label class="mb-2">Remark</label>
                        <textarea class="form-control" name="remark[]">${product.remark}</textarea>
                    </div>
                `;
            } else {
                 productHtml += `
                    ${optionHtml}
                    <div class="form-group mb-3">
                        <label class="mb-2">Quantity</label>
                        <span class="text-danger"> *</span>
                        <input type="number" class="form-control add-products-quantity" name="quantity[]" value="${product.quantity}" data-pid="${product.product_id}" data-price="${product.price}" min="1" step="1">
                    </div>
                    <div class="form-group mb-3">
                        <label class="mb-2">Remark</label>
                        <textarea class="form-control" name="remark[]">${product.remark}</textarea>
                    </div>
                `;
            }

            productHtml += `
                ${optionHtml1}
                <p class="mb-1">Price: RM <span id="product-${product.product_id}-unit-price">${product.price}</span></p>
                <p class="mb-1">Total Price: <strong>RM <span id="product-${product.product_id}-total">${totalPriceForProduct.toFixed(2)}</span></strong></p>
            `;
        }
        
        productHtml += `</div></div></div>`;
        totalPrice += totalPriceForProduct;
        
    });

    document.getElementById('product_bag-item').innerHTML = productHtml;
    if (document.getElementById('total-price')) {
        document.getElementById('total-price').textContent = totalPrice.toFixed(2);
    }

    var modal = bootstrap.Modal.getInstance(document.getElementById('addProductModal'));
    if (modal) {
        modal.hide();
    }
}

function calculateTotal() {
    let total = 0;

    document.querySelectorAll('input[name="quantity[]"]').forEach(function(input) {
        let qty = parseFloat(input.value) || 0;
        let price = parseFloat(input.getAttribute('data-price')) || 0;
        let pid = input.getAttribute('data-pid');

        total += qty * price;
        document.getElementById(`product-${pid}-total`).innerHTML = (qty * price).toFixed(2);
    });

    document.getElementById('total-price').textContent = total.toFixed(2);
}

function updateButtonState(quantity) {
    const minusButton = document.querySelector('.btn-minus');
    const minusButtonWeight = document.querySelector('.btn-minus-weight');
   
    if(minusButton != undefined)
        minusButton.disabled = quantity <= 1;
    if(minusButtonWeight != undefined)
         minusButtonWeight.disabled = quantity <= 1;
}

function init_pre_order_data() {
    // select aleady chosed products
    if (typeof productIds !== 'undefined') {
        const checkboxes = document.querySelectorAll('.toggle-product-options');
        checkboxes.forEach(checkbox => {
            if (productIds.includes(parseInt(checkbox.value))) {
                checkbox.checked = true;
                const cardBody = checkbox.closest('.card-body');
                if (cardBody && cardBody.querySelector('.product-option-section')) {
                    cardBody.querySelector('.product-option-section').classList.remove('d-none');
                }
            }
        });
    }

    // select product options selected
    if (selected_products.length != 0) {
        selected_products.forEach(function(product, index) {
            for (var key in product) {                
                if (key == 'product_id') {
                    const quantityElement = document.getElementById('productQuantity_' + product['product_id']);
                    if (quantityElement) {
                        quantityElement.value = product['quantity'];
                    }
                    
                    const remarkElement = document.getElementById('productRemark_' + product['product_id']);
                    if (remarkElement) {
                        remarkElement.value = product['remark'];
                    }
                    
                    // const nosElement = document.getElementById('nos_' + product['product_id']);
                    // if (nosElement) {
                    //     nosElement.value = product['nos'];
                    // }
                } else if (product.hasOwnProperty(key) && !['product_id', 'product_name', 'price', 'quantity', 'remark', 'total_price', ''].includes(key)) {
                    if (document.getElementById('productOption-' + key)) {
                        const selectElement = document.getElementById('productOption-' + key);
                        selectElement.value = product[key];
                    }
                }
            }
        });
    }
}

function fetch_customer_category_prices() {
    const form = new FormData();
    form.append('customer_id', document.getElementById('order_customer').value);
    form.append('quotation_id', document.getElementById('quotation_id').value);

    fetch(appUrl + `/admin/get-customer-category-prices`, {
            method: 'POST',
            body: form,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
            },
        })
        .then(response => response.json())
        .then(data => {
            data.forEach(item => {
                for (const [productId, price] of Object.entries(item)) {
                    const numericPrice = parseFloat(price);

                    const qty = document.querySelector(`[data-pid="${productId}"]`).value;
                    const total = numericPrice * qty;

                    document.getElementById(`product-${productId}-unit-price`).innerHTML = numericPrice.toFixed(2);
                    document.getElementById(`product-${productId}-total`).innerHTML = total.toFixed(2);
                }
            });
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'An error occurred.', 'error');
        });
}