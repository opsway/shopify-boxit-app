<div class="b-boxit-container">
    <input type="hidden" id="customer_id" value="" />
    <input type="hidden" id="shop" value="" />
    <h3 class="title">Delivery options:</h3>

    <input id='pickup_location_id' readonly='readonly' type="hidden" />

    <div class='method'>
    <div class="h">
        <input id='delivery_method_boxit' type='radio' name='delivery_method' value='boxit' />
        <input id='delivery_method_shopandcollect' type='radio' name='delivery_method' value='shopandcollect' />
    </div>
    </div>

    <div class='inputField' >
        <select id="mobile_prefix" style="height: 29px;">
            <option value="050">050</option>
            <option value="052">052</option>
            <option value="053">053</option>
            <option value="054">054</option>
            <option value="055">055</option>
            <option value="057">057</option>
            <option value="050">058</option>
        </select>
- <input class='pickup_mobile' type="text" placeholder='Enter Phone number'  style="height: 29px;" /><br>
    <div id="phone-message" style="margin-left: 86px;font-size: 11px;color: red;">(Please enter 7-digit number)</div>

    <div class="b-location-selector"><a href="#">Change location</a></div>

    <div class="button-container">
        <button class="btn" id="boxit-apply-data" disabled="disabled">Apply</button>
    </div>
</div>
<script type='text/javascript' src='//service.box-it.co.il/plugin/Shopify/demo'></script>