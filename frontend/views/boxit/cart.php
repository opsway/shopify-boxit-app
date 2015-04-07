<style>

  body { margin: 20px; direction: ltr; }
  a { cursor: pointer; margin-left: 26px; color: #999; font-size: 12px; }
  a:hover { text-decoration: underline; color: #333; }
  .method {
	display: inline;
	height: 70px;
	float: left;
	margin-left: 14px;
  }
  .method span {
    margin: 15px;
    font-weight: bold; font-size: 12px;
    color: #0993a8;
  }
  .inputField{
	display: inline;
	margin-left: 14px;	
  }
</style>
<div>
  <h1>Delivery options:</h1>
</div>
<div class='method'>
  <input id='delivery_method_other' type='radio' name='delivery_method' value='other' />
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
  </input>
</div>
<div class='inputField'>
  <input id='pickup_email' placeholder='Enter Email'>
  </input>
</div>
<div class='inputField'>
  <input id='pickup_mobile' placeholder='Enter Phone number'>
  </input>
</div>
<script type='text/javascript' src='//service.box-it.co.il/plugin/Shopify/demo'></script>