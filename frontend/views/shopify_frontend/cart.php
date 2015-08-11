<style>
    .b-boxit-container {
        margin: 20px 0;
    }

        .b-boxit-container h3.title{
            font-size: 20px;
        }

        .b-boxit-container .method {
            float: left;
            height: 70px;
            margin-left: 14px;
        }

        .b-boxit-container .method span {
            margin: 15px;
            font-weight: bold;
            font-size: 12px;
            color: #0993a8;
        }

        .b-boxit-container .method a {
            cursor: pointer;
            margin-left: 26px;
            color: #999;
            font-size: 12px;
        }

        .b-boxit-container .inputField{
            display: inline;
            margin-left: 14px;
        }

        .b-boxit-container input[type=radio]{
            -webkit-appearance: radio;
        }

        .b-boxit-container input[type=checkbox]{
            -webkit-appearance: checkbox;
        }

</style>

<div class="b-boxit-container">

    <h3 class="title">Delivery options:</h3>

    <div class='method'>
        <input id='delivery_method_other' type='radio' checked="checked" name='delivery_method' value='other' />
        <label for='delivery_method_other'>
            Other shipping options
        </label>
    </div>

    <div class='method'>
        <input id='delivery_method_boxit' type='radio' name='delivery_method' value='boxit' />
        <label for='delivery_method_boxit'>
            Boxit delivery
        </label>
    </div>

    <div class='method'>
        <input id='delivery_method_shop_collect' type='radio' name='delivery_method' value='shop_collect' />
        <label for='delivery_method_shop_collect'>
            Shop&amp;Collect delivery
        </label>
    </div>
    <div class='clearfix'></div>
    <div class='inputField'>
        <input id='pickup_location_id' readonly='readonly' placeholder='Locker ID'>
    </div>
    <div class='inputField'>
        <input id='pickup_email' placeholder='Enter Email'>
    </div>
    <div class='inputField'>
        <input id='pickup_mobile' placeholder='Enter Phone number'>
    </div>
</div>
<script type="text/javascript" src="{{ 'boxitapp.jquery.js' | asset_url }}"></script>
<script type="text/javascript" src="{{ 'boxitapp.bootstrap.js' | asset_url }}"></script>
<script type='text/javascript' src='//service.box-it.co.il/plugin/Shopify/demo'></script>