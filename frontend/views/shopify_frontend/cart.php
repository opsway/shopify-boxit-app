<style>@import url('[[BASE_API_URL]]/shopify_frontend/css/layout.css');</style>

<!-- You can override all styles here -->

<div class="b-boxit-preloader" style="display: none">
    Please wait, while we loading additional delivery settings...
</div>

<div class="b-boxit-container" style="display: none">
    <input type="hidden" id="customer_id" value="{% if customer %}{{ customer.id }}{% endif %}" />
    <input type="hidden" id="shop" value="{{ shop.url }}" />
    <h3 class="title">Delivery options:</h3>

    <div class='method'>
        <input id='delivery_method_other' type='radio' checked="checked" name='delivery_method' value='other' />
        <label for='delivery_method_other'>
            Other shipping options
        </label>
    </div>

    <div class='method' id="boxit-delivery-radio-boxit">
        <input id='delivery_method_boxit' type='radio' name='delivery_method' value='boxit' />
        <label for='delivery_method_boxit'>
            Boxit delivery
        </label>
    </div>

    <div class='method' id="boxit-delivery-radio-shopandcollect">
        <input id='delivery_method_shopandcollect' type='radio' name='delivery_method' value='shopandcollect' />
        <label for='delivery_method_shopandcollect'>
            Shop&amp;Collect delivery
        </label>
    </div>
    <div class='clearfix'></div>
    <input id='pickup_location_id' readonly='readonly' type="hidden" />
    <div class='inputField' style="display:none;">
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
</div>
<!-- <script type="text/javascript" src="{{ 'boxitapp.jquery.js' | asset_url }}"></script> -->
<script type="text/javascript" src="{{ 'boxitapp.bootstrap.js' | asset_url }}"></script>
<script type='text/javascript' src='//service.box-it.co.il/plugin/Shopify/demo'></script>