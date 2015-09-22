jQuery(function(){

    // flag determines, if any of API exists
    var isAPIExists = null;

    // special app identifier
    var appId = 'BoxIt';

    var syncEvent = function(k, condition){

        (function(){
            try{
                if (eval(condition) == true) {
                    try {
                        k();
                    } catch (e){
                        console.log(e);
                    }
                } else {
                    setTimeout(arguments.callee,10);
                }
            } catch(e) {
                setTimeout(arguments.callee,10);
            }}
            )();
    };

    var saveData = function ()
    {
        var address = jQuery('.b-boxit-container input[type=radio]:checked').parent().find('span').text();
        address = address.replace('Boxit pickup location chosen:','').replace('Shop&Collect pickup location chosen:','');


        jQuery.ajax({
            'type' : 'POST',
            'url'  : 'https://'+window.OwsBootstrap.getExternalAppPath()+'/index.php?r=app/save',
            'dataType' : 'json',
            'crossDomain' : true,
            'data' : {
                'phone' : jQuery('#mobile_prefix option:selected').val() + jQuery('.pickup_mobile').val(),
                'locker_id' : jQuery('#pickup_location_id').val(),
                'customer_id' : jQuery('#customer_id').val(),
                'shop' : window.OwsBootstrap.getShopDomains().join(','),
                'type'	: jQuery('.b-boxit-container input[type="radio"]:checked').val(),
                'address' : address.trim(),
                'session' : window.OwsBootstrap.getSessionValue()
            },
            'success' : function(data) {
                //console.info('OK');

                if (data && data.session){
                    window.OwsBootstrap.setSessionValue(data.session);
                }

            }
        });
    }

    // waiting for main app core initialization
    syncEvent(function(){

        if (typeof console != 'undefined' && typeof console.log == 'function')
            console.log('Ows app initialized');

        jQuery('.b-boxit-container input[type="radio"]').change(function(){
            saveData();
            if(jQuery('.b-boxit-container .method input:checked').val() == 'other')
            {
                jQuery('.b-boxit-container .inputField').hide();
                window.BoxitApp.applyValidation(true);
            } else {
                jQuery('.b-boxit-container .inputField').show();
                window.BoxitApp.applyValidation(window.BoxitApp.validateLockerId(jQuery('.b-boxit-container input[type="radio"]:checked').val()) && window.BoxitApp.validatePhone(jQuery('.pickup_mobile').val()));
            }
        });

        jQuery('.b-boxit-container .method input').change(function(){
            if(jQuery(this).val() == 'other')
            {
                jQuery('#'+window.BoxitApp.getAppSetting('checkout_button_id', 'checkout')).attr('disabled',false);
                jQuery('.b-boxit-container .inputField').hide();
            } else {
                //jQuery('#'+window.BoxitApp.getAppSetting('checkout_button_id', 'checkout')).attr('disabled',true);
                jQuery('.b-boxit-container .inputField').show();
                window.BoxitApp.applyValidation(window.BoxitApp.validateLockerId(jQuery('.b-boxit-container input[type="radio"]:checked').val()) && window.BoxitApp.validatePhone(jQuery('.pickup_mobile').val()));
            }
        });

        jQuery('.b-boxit-container .pickup_mobile').on('blur keyup', function(){
            saveData();
            window.BoxitApp.applyValidation(window.BoxitApp.validateLockerId(jQuery('.b-boxit-container input[type="radio"]:checked').val()) && window.BoxitApp.validatePhone(jQuery('.pickup_mobile').val()));

        });

        // get hook on pre get cart
        var hook = window.OwsBootstrap.getShopHooks(appId, 'beforeGetSavedUserCart', {'store' : window.OwsBootstrap.getShopDomains().join(','), 'session' : window.OwsBootstrap.getSessionValue()});
        var hookResult = null;
        if (typeof hook == 'function'){
            hookResult = hook({'store' : window.OwsBootstrap.getShopDomains().join(','), 'session' : window.OwsBootstrap.getSessionValue()});
        }

        // get info about api keys and cart data
        if (!hook || (hook && hookResult)){

            // temporary block checkout button until we will get info about API keys and oldcart data
            jQuery('#'+window.BoxitApp.getAppSetting('checkout_button_id', 'checkout')).attr('disabled',true);
            // show preloader
            jQuery('.b-boxit-preloader').show(50);

            jQuery.ajax({
                'type' : 'POST',
                'url'  : 'https://'+window.OwsBootstrap.getExternalAppPath()+'/index.php?r=app/cart&shop=' + window.OwsBootstrap.getShopDomains().join(','),
                'dataType' : 'json',
                'crossDomain' : true,
                'data' : {
                    'shop' : window.OwsBootstrap.getShopDomains().join(','),
                    'session' : window.OwsBootstrap.getSessionValue()
                },
                'success' : function(json) {
                    //console.info(json.locker_id > 0);
                    //console.info(json.phone.length > 0);
                    //console.info(json);

                    // hide preloader
                    jQuery('.b-boxit-preloader').stop().hide(50);

                    // set session value
                    if (json && json.session){
                        window.OwsBootstrap.setSessionValue(json.session);
                    }

                    // get app settings
                    if (json.app_settings){
                        window.BoxitApp.setAppSettings(json.app_settings);
                    }

                    // check if api keys exists
                    if (json.api_exists && window.BoxitApp.getAppSetting('is_show_on_checkout', '1') == '1'){
                        var found = false;
                        for (var key in json.api_exists){
                            if (json.api_exists[key]){
                                found = true;
                            } else {
                                jQuery('#boxit-delivery-radio-'+key).hide();
                            }
                        }

                        // oops - any API keys - then hide whole block
                        if (!found){
                            $('.b-boxit-container').hide();
                            isAPIExists = false;
                        } else {
                            isAPIExists = json.api_exists;
                            $('.b-boxit-container').show(50);
                        }

                    } else {
                        // if any error or show on checkout page turned off - then we cannot show block to the user
                        $('.b-boxit-container').hide();
                        isAPIExists = false;
                    }

                    // unblock checkout button
                    jQuery('#'+window.BoxitApp.getAppSetting('checkout_button_id', 'checkout')).removeAttr('disabled');

                    if (isAPIExists){
                        if(json.locker_id > 0 && window.BoxitApp.validatePhone(jQuery('.pickup_mobile').val()))
                        {
                            if(json.type != 'other')
                                jQuery('.b-boxit-container .inputField').show();
                            $('#mobile_prefix option:contains("' + json.phone.substring(0,3) + '")').attr('selected',true);
                        }
                        else
                        {
                            jQuery('#pickup_location_id').val(json.locker_id);

                            if (json.phone){
                                jQuery('.b-boxit-container .pickup_mobile').val(window.BoxitApp.substring(json.phone,3,10));
                                $('#mobile_prefix option:contains("' + json.phone.substring(0,3) + '")').attr('selected',true);
                            }

                            if (json.type && isAPIExists[json.type])
                                $('input').filter(function() { return this.value == json.type }).attr('checked',true);

                            if(json.type != 'other')
                            {
                                jQuery('.b-boxit-container .inputField').show();
                            }

                            window.BoxitApp.applyValidation(window.BoxitApp.validateLockerId(jQuery('.b-boxit-container input[type="radio"]:checked').val()) && window.BoxitApp.validatePhone(jQuery('.pickup_mobile').val()));
                        }

                        // set timeout on change locker_id
                        setInterval(function(){
                            if(jQuery('.b-boxit-container .method input:checked').val() == 'other')
                            {
                                window.BoxitApp.applyValidation(true);
                            } else {
                                window.BoxitApp.applyValidation(window.BoxitApp.validateLockerId(jQuery('.b-boxit-container input[type="radio"]:checked').val()) && window.BoxitApp.validatePhone(jQuery('.pickup_mobile').val()));
                            }
                        }, 500);
                    }
                }
            });
        }

        /*if(jQuery('.b-boxit-container .method input:checked').val() == 'other')
        {
            jQuery('#'+window.BoxitApp.getAppSetting('checkout_button_id', 'checkout')).attr('disabled',false);
        } else {
            console.info(window.BoxitApp.validatePhone(jQuery('.pickup_mobile').val()));
         window.BoxitApp.applyValidation(window.BoxitApp.validatePhone(jQuery('.pickup_mobile').val()));
        }*/

    }, 'typeof window.OwsBootstrap != "undefined" && typeof window.BoxitApp != "undefined"');

    if (typeof console != 'undefined' && typeof console.log == 'function')
        console.log('start search OwsBootstrap...');
});