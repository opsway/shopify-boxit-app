jQuery(function(){

    // flag determines, if any of API exists
    var isAPIExists = null;
    var appSettings = null;

    /**
     * method get value from loaded application settings (if no value default_value returned)
     * @param setting
     * @param default_value
     * @returns {*}
     */
    var getAppSetting = function(setting, default_value){

        if (appSettings && typeof appSettings[setting] != 'undefined' && appSettings[setting] != ''){
            return appSettings[setting];
        } else {
            return default_value;
        }

    };

    var syncEvent = function(k, condition){

        (function(){
            try{
                if (eval(condition) == true) {
                    k();
                } else {
                    setTimeout(arguments.callee,10);
                }
            } catch(e) {
                setTimeout(arguments.callee,10);
            }}
            )();
    };

    /**
     * validate locker id value
     * @returns {boolean}
     */
    var validateLockerId = function(){

        var selected = jQuery('.b-boxit-container input[type="radio"]:checked').val();
        if (selected != '' && selected != 'other')
        {
            return parseInt(jQuery('#pickup_location_id').val()) > 0;
        } else {
            return true;
        }

    };

    /**
     * validate phone number
     * @returns {*|Array|{index: number, input: string}|boolean}
     */
    var validatePhone = function ()
    {
        var regexpNum = /\d+/;
        var regexChar = /[a-zA-Z]/;
        var phone = jQuery('.pickup_mobile').val();
        return phone.match(regexpNum) && !phone.match(regexChar) && phone.length == 7;
    };

    var substring = function (text,start,end)
    {
        var str = '';
        if (text != null){
            for(i = start; i != end; i = i + 1)
            {
                if(text[i] != undefined)
                    str += text[i];
            }
        }
        return str;
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
                'shop' : jQuery('#shop').val(),
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

    /**
     * method apply validation on delivery form
     */
    var applyValidation = function(is_valid){
        if(is_valid)
        {
            jQuery('#'+getAppSetting('checkout_button_id', 'checkout')).attr('disabled',false);
            jQuery('.b-boxit-container .pickup_mobile').removeClass('error')
                .addClass('success');
            jQuery('#phone-message').hide();
        } else {
            jQuery('#'+getAppSetting('checkout_button_id', 'checkout')).attr('disabled',true);
            jQuery('.b-boxit-container .pickup_mobile').attr('id','inputWarning2')
                .removeClass('success')
                .addClass('error');
            jQuery('#phone-message').show();
        }

    };

    // waiting for main app core initialization
    syncEvent(function(){

        if (typeof console != 'undefined' && typeof console.log == 'function')
            console.log('Ows app initialized');

        jQuery('.b-boxit-container input[type="radio"]').change(function(){
            saveData();
            if(jQuery('.b-boxit-container .method input:checked').val() == 'other')
            {
                jQuery('.b-boxit-container .inputField').hide();
                applyValidation(true);
            } else {
                jQuery('.b-boxit-container .inputField').show();
                applyValidation(validateLockerId() && validatePhone());
            }
        });

        jQuery('.b-boxit-container .method input').change(function(){
            if(jQuery(this).val() == 'other')
            {
                jQuery('#'+getAppSetting('checkout_button_id', 'checkout')).attr('disabled',false);
                jQuery('.b-boxit-container .inputField').hide();
            } else {
                //jQuery('#'+getAppSetting('checkout_button_id', 'checkout')).attr('disabled',true);
                jQuery('.b-boxit-container .inputField').show();
                applyValidation(validateLockerId() && validatePhone());
            }
        });

        jQuery('.b-boxit-container .pickup_mobile').on('blur keyup', function(){
            saveData();
            applyValidation(validateLockerId() && validatePhone());

        });

        // temporary block checkout button until we will get info about API keys and oldcart data
        jQuery('#'+getAppSetting('checkout_button_id', 'checkout')).attr('disabled',true);
        // show preloader
        jQuery('.b-boxit-preloader').show(50);

        // get info about api keys and cart data
        jQuery.ajax({
            'type' : 'POST',
            'url'  : 'https://'+window.OwsBootstrap.getExternalAppPath()+'/index.php?r=app/cart&shop=' + jQuery('#shop').val(),
            'dataType' : 'json',
            'crossDomain' : true,
            'data' : {
                'shop' : jQuery('#shop').val(),
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
                    appSettings = json.app_settings;
                }

                // check if api keys exists
                if (json.api_exists){
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
                    // if any error - then we cannot show block to the user
                    $('.b-boxit-container').hide();
                    isAPIExists = false;
                }

                // unblock checkout button
                jQuery('#'+getAppSetting('checkout_button_id', 'checkout')).removeAttr('disabled');

                if (isAPIExists){
                    if(json.locker_id > 0 && validatePhone())
                    {
                        if(json.type != 'other')
                            jQuery('.b-boxit-container .inputField').show();
                        $('#mobile_prefix option:contains("' + json.phone.substring(0,3) + '")').attr('selected',true);
                    }
                    else
                    {
                        jQuery('#pickup_location_id').val(json.locker_id);

                        if (json.phone){
                            jQuery('.b-boxit-container .pickup_mobile').val(substring(json.phone,3,10));
                            $('#mobile_prefix option:contains("' + json.phone.substring(0,3) + '")').attr('selected',true);
                        }

                        if (json.type && isAPIExists[json.type])
                            $('input').filter(function() { return this.value == json.type }).attr('checked',true);

                        if(json.type != 'other')
                        {
                            jQuery('.b-boxit-container .inputField').show();
                        }

                        applyValidation(validateLockerId() && validatePhone());
                    }

                    // set timeout on change locker_id
                    setInterval(function(){
                        if(jQuery('.b-boxit-container .method input:checked').val() == 'other')
                        {
                            applyValidation(true);
                        } else {
                            applyValidation(validateLockerId() && validatePhone());
                        }
                    }, 500);
                }
            }
        });

        /*if(jQuery('.b-boxit-container .method input:checked').val() == 'other')
        {
            jQuery('#'+getAppSetting('checkout_button_id', 'checkout')).attr('disabled',false);
        } else {
            console.info(validatePhone());
            applyValidation(validatePhone());
        }*/

    }, 'typeof window.OwsBootstrap != "undefined"');

    if (typeof console != 'undefined' && typeof console.log == 'function')
        console.log('start search OwsBootstrap...');
});