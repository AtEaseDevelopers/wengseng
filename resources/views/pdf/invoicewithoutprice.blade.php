<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
</head>

    <body>
        <!-- Header -->
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 20%; text-align: center; vertical-align: middle;">
                    <img src="{{ asset('assets/images/logo.png') }}" alt="{{ env('APP_NAME') }}" class="app-logo">
                </td>
                <td style="text-align: center; width: 60%;">
                    <span style="font-size: 22px; font-weight: 700;">{{ env('APP_NAME') }}</span><br>
                    <span style="font-size: 16px;">
                        501, Lot 151536,<br />
                        Lorong Kledang Utara 15,<br />
                        31450 Menglembu,<br />
                        Ipoh, Perak.<br />
                        Phone: 017-5386991<br />
                        Email: wengseng_fresh@outlook.com
                    </span>
                </td>
                <td style="width: 10%; text-align: center; vertical-align: middle;">
                    {{-- <img src="{{ public_path('assets/images/mesti-logo2.jpg') }}" alt="" style="height: 62.5px; width: 62.5px;"> --}}
                </td>
                <td style="width: 10%; text-align: center; vertical-align: middle;">
                    {{-- <img src="{{ public_path('assets/images/mesti-logo.jpg') }}" alt="" style="height: 62.5px; width: 62.5px;"> --}}
                </td>
            </tr>
        </table>
        <!-- Sub Header -->
        <table style="width: 100%; border-collapse: collapse; font-family: sans-serif; margin: 20px 0 0 0;">
            <tr>
                <td style="width: 70%; vertical-align: text-top;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="width: 50%; vertical-align: text-top;">
                                <span style="font-size: 14px; font-weight: 700;">BILLING ADDRESS :</span><br><br>
                                <span style="font-size: 14px; font-weight: 700;">{{ $user->name }}</span><br>
                                <span style="font-size: 14px;">
                                    {{ $order->billing_address }}<br />
                                </span>
                            </td>
                            <td style="width: 50%; vertical-align: text-top;">
                                <span style="font-size: 14px; font-weight: 700;">DELIVERY ADDRESS :</span><br><br>
                                <span style="font-size: 14px; vertical-align: text-bottom;">
                                    {{ $order->shipping_address }}<br />
                                </span>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width: 30%; vertical-align: text-top;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td colspan="3">
                                <span style="font-size: 18px; font-weight: 700;">{{ $type == 'order' ? 'Invoice' : 'Quotation' }}</span><br>
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 30%;">
                                <span style="font-size: 14px; font-weight: 700;">NO.</span><br>
                            </td>
                            <td style="width: 5%;">:</td>
                            <td>
                                <span style="font-size: 14px; font-weight: 700;">{{ $invoice_number }}</span><br>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <span style="font-size: 14px;">DATE</span><br>
                            </td>
                            <td>:</td>
                            <td>
                                <span style="font-size: 14px;">{{ $date }}</span><br>
                            </td>
                        </tr>
                        @if ($type == 'order')
                            <tr>
                                <td>
                                    <span style="font-size: 14px;">AGENT</span><br>
                                </td>
                                <td>:</td>
                                <td>
                                    <span style="font-size: 14px;"></span><br>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <span style="font-size: 14px;">TERM</span><br>
                                </td>
                                <td>:</td>
                                <td>
                                    <span style="font-size: 14px;"></span><br>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <span style="font-size: 14px;">DO No.</span><br>
                                </td>
                                <td>:</td>
                                <td>
                                    <span style="font-size: 14px;"></span><br>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <span style="font-size: 14px;">PO No.</span><br>
                                </td>
                                <td>:</td>
                                <td>
                                    <span style="font-size: 14px;"></span><br>
                                </td>
                            </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>
        <!-- Sub Header 2 -->
        <table style="width: 100%; border-collapse: collapse; font-family: sans-serif; margin: 10px 0 0 0;">
            <tr>
                @if ($type == 'order')
                    <td style="width: 30%;">
                        <span style="font-size: 14px; font-weight: 700;">A/C NO : <span
                                style="font-weight: 100;">300-L0015</span></span><br>
                    </td>
                @endif
                <td style="width: {{ ($type == 'order') ? 70 : 100 }}%;">
                    <span style="font-size: 14px; font-weight: 700;">TEL : <span
                            style="font-weight: 100;">{{ $user->attn_contact }}</span></span><br>
                </td>
            </tr>
        </table>
        <!-- Items -->
        @php
            $total_weight = 0;
            $sub_total = 0;
            $total = 0;
        @endphp
        <table style="width: 100%; font-family: sans-serif; border-collapse: collapse; margin: 10px 0 0 0;">
            <tr>
                <td
                    style="font-size: 14px; border-top: solid 1px black; border-bottom: solid 1px black; font-weight: 700; padding: 5px 0 5px 0; width: 10%;">
                    NO.</td>
                <td
                    style="font-size: 14px; border-top: solid 1px black; border-bottom: solid 1px black; font-weight: 700; width: 30%; text-align: left;">
                    DESCRIPTION</td>
                <td
                    style="font-size: 14px; border-top: solid 1px black; border-bottom: solid 1px black; font-weight: 700; width: 10%; text-align: left;">
                    QTY</td>
                <td
                    style="font-size: 14px; border-top: solid 1px black; border-bottom: solid 1px black; font-weight: 700; width: 30%; text-align: left;">
                    REMARK</td>
                <td
                    style="font-size: 14px; border-top: solid 1px black; border-bottom: solid 1px black; font-weight: 700; width: 30%; text-align: center;">
                    WEIGHT</td>
            </tr>
            @foreach ($order_items as $key => $prod)
                <tr>
                    <td style="font-size: 14px; text-align: left; padding: 5px 0;">{{ $key + 1 }}</td>
                    <td style="font-size: 14px; text-align: left;">{{ $prod->name }}</td>
                    <td style="font-size: 14px; text-align: left;">
                        {{ $prod->show_qty == true ? $prod->quantity ?? 0 : '' }}</td>
                    <td style="font-size: 14px; text-align: left;">{{ $prod->remark }}</td>
                    <td style="font-size: 14px; text-align: center;">
                        {{ $prod->show_weight == true ? ($prod->quantity != null && $prod->product_weight != null ? $prod->product_weight * $prod->quantity : $prod->weight) . ' KG' : '' }}
                    </td>
                </tr>
                @php
                    if ($prod->show_weight == true) {
                        $total_weight += $prod->weight;
                    }
                @endphp
            @endforeach
        </table>
        <!-- Footer -->
        <table style="width: 100%; font-family: sans-serif; border-collapse: collapse; margin: 50px 0 0 0;">
            <tr>
                <td style="border-top: solid 1px black; border-bottom: solid 1px black;" colspan="2"></td>
                <td
                    style="font-size: 14px; border-top: solid 1px black; border-bottom: solid 1px black; font-weight: 700; text-align: right; padding: 5px 0 5px 0;">
                    Total Quantity : {{ $total_weight }}</td>
            </tr>
            <tr>
                <td colspan="3 padding: 5px 0;">
                    <span style="font-size: 12px;">BEFORE ACCEPTANCE , PLEASE INSPECT THE GOODS AS WE WILL NOT BE
                        RESPONSIBLE FOR ANY DEFECTS AFTER ACCEPTANCE NO CLAIMS OR WHATSOEVER WILL BE ENTERTAINED UNLESS WITH
                        OFFICIAL WRITTEN ADVICE TO US WITHIN 7 DAYS OF CHOP SIGN OF RECEIFT OF GOODS.</span>
                </td>
            </tr>
        </table>
    </body>

</html>
