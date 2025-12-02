<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title>{{ env('APP_NAME') }} - Order Stock Report</title>
        <style>
            @font-face {
                font-family: 'NotoSansCJKtc-Regular';
                src: url('{{ public_path('assets/fonts/NotoSansCJKtc-Regular.ttf') }}') format('truetype');
            }
            
            body {
                font-family: 'NotoSansCJKtc-Regular', sans-serif;
                font-size: 12px;
            }

            h1 {
                text-align: center;
                font-size: 20px;
                font-weight: bold;
                margin-bottom: 0;
            }
            
            h5 {
                text-align: center;
                font-size: 14px;
                font-weight: normal;
                margin-top: 4px;
            }
            
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
            }
            
            thead {
                background: #f1f1f1;
            }
            
            th, td {
                border: 1px solid #ddd;
                padding: 6px;
                text-align: left;
                vertical-align: top;
            }
            
            th {
                font-weight: bold;
            }

            tr:nth-child(even) {
                background: #fafafa;
            }
            
            table, tr, td, th, tbody, thead, tfoot {
                page-break-inside: avoid !important;
            }

            tr {
                page-break-inside: avoid !important;
                page-break-after: auto !important;
            }

            table {
                page-break-after: auto !important;
            }

            thead {
                display: table-header-group;
            }

            tfoot {
                display: table-footer-group;
            }
        </style>
    </head>
    <body>

        <div>
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Customer Code</th>
                        <th>Product Name</th>
                        <th>UOM</th>
                        <th style="text-align:right;">Qty</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $sno = 1;
                    @endphp
                    @foreach ($orders as $customer_code => $order_products)
                        <tr>
                            <td style="background-color:#f1f1f1;">{{ $sno++ }}</td>
                            <td colspan="4" style="background-color:#f1f1f1; font-size:14px; font-weight: bold;">{{ $customer_code }}</td>
                        </tr>
                        @foreach ($order_products as $product)
                            <tr>
                                <td></td>
                                <td></td>
                                <td>{{ $product->product_name }}</td>
                                <td>{{ $product->uom_name }}</td>
                                <td align="right">{{ $product->quantity ?? $product->weight }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>

    </body>
</html>
