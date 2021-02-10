<!DOCTYPE html>
<html>
<body>

<table>
    <tr>
        <td width="30"><b>Total Transaction</b></td>
        <td>: {{number_format($summary_fee['total_trx'])}}</td>
    </tr>
    <tr>
        <td width="30"><b>Total Gross Sales</b></td>
        <td>: {{(float)$summary_fee['total_sub_total']+abs((float)$summary_fee['total_discount_bundling'])}}</td>
    </tr>
    <tr>
        <td width="30"><b>Total Discount</b></td>
        <td>: {{abs($summary_fee['total_discount'])+$summary_fee['total_subscription']+abs($summary_fee['total_discount_delivery'])+abs((float)$summary_fee['total_discount_bundling'])}}</td>
    </tr>
    <tr>
        <td width="30"><b>Total Delivery</b></td>
        <td>: {{(float)$summary_fee['total_delivery']}}</td>
    </tr>
    <tr>
        <td width="30"><b>Sub Total (Gross Sales + delivery - discount/promo)</b></td>
        <td>: {{(float)$summary_fee['total_gross_sales']-$summary_fee['total_subscription']}}</td>
    </tr>
    <tr>
        <td width="30"><b>Total Fee Item</b></td>
        <td>: {{(float)$summary_fee['total_fee_item']}}</td>
    </tr>
    <tr>
        <td width="30"><b>Total MDR PG</b></td>
        <td>: {{(float)$summary_fee['total_fee_pg']}}</td>
    </tr>
    @if(isset($show_another_income) && $show_another_income == 1)
    <tr>
        <td width="30"><b>Total Income Promo</b></td>
        <td>: {{(float)$summary_fee['total_income_promo']}}</td>
    </tr>
    <tr>
        <td width="30"><b>Total Income Subscription</b></td>
        <td>: {{(float)$summary_fee['total_income_subscription']}}</td>
    </tr>
    <tr>
        <td width="30"><b>Total Income Bundling Product</b></td>
        <td>: {{(float)$summary_fee['total_income_bundling_product']??0}}</td>
    </tr>
    @endif
    <tr>
        <td width="30"><b>Total Income Outlet</b></td>
        <td>: {{(float)$summary_fee['total_income_outlet']}}</td>
    </tr>
</table>
<br>

@if(!empty($summary_product))
<table style="border: 1px solid black">
    <thead>
    <tr>
        <th style="background-color: #dcdcdc;"> Name </th>
        <th style="background-color: #dcdcdc;" width="20"> Type </th>
        <th style="background-color: #dcdcdc;" width="20"> Total Sold Out </th>
    </tr>
    </thead>
    <tbody>
    @if(!empty($summary_product))
        @foreach($summary_product as $val)
            <tr>
                <td style="text-align: left">{{$val['name']}}</td>
                <td style="text-align: left">{{$val['type']}}</td>
                <td style="text-align: left">{{$val['total_qty']}}</td>
            </tr>
        @endforeach
    @else
        <tr><td colspan="10" style="text-align: center">Data Not Available</td></tr>
    @endif
    </tbody>
</table>
@endif
</body>
</html>

