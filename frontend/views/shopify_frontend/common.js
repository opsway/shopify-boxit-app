jQuery(function(){

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
        var address = jQuery('input[type=radio]:checked').parent().find('span').text();
        address = address.replace('Boxit pickup location chosen:','').replace('Shop&Collect pickup location chosen:','');


        jQuery.ajax({
            'type' : 'POST',
            'url'  : 'https://'+window.OwsBootstrap.getExternalAppDomain()+'/index.php?r=app/save',
            'dataType' : 'json',
            'crossDomain' : true,
            'data' : {
                'phone' : jQuery('#mobile_prefix option:selected').val() + jQuery('.pickup_mobile').val(),
                'locker_id' : jQuery('#pickup_location_id').val(),
                'customer_id' : jQuery('#customer_id').val(),
                'shop' : jQuery('#shop').val(),
                'type'	: jQuery('input[type="radio"]:checked').val(),
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

    // waiting for main app file
    syncEvent(function(){

        if (typeof console != 'undefined' && typeof console.log == 'function')
            console.log('Ows app initialized');

        jQuery('input[type="radio"]').change(function(){
            saveData();
        });
        jQuery('.method input').change(function(){
            if(jQuery(this).val() == 'other')
            {
                jQuery('#checkout').attr('disabled',false);
                jQuery('.inputField').hide();
            } else {
                jQuery('#checkout').attr('disabled',true);
                jQuery('.inputField').show();
            }
        });

        jQuery('.pickup_mobile').blur(function(){
            saveData();
            if(validatePhone())
            {
                jQuery('#checkout').attr('disabled',false);
                jQuery(this).removeClass('error');
                jQuery(this).addClass('success');
                jQuery('#phone-message').hide();
            } else {
                jQuery('#checkout').attr('disabled',true);
                jQuery(this).attr('id','inputWarning2');
                jQuery(this).removeClass('success');
                jQuery(this).addClass('error');
                jQuery('#phone-message').show();
            }
            if(validatePhone())
            {
                jQuery('.confirm').show();
            } else {
                jQuery('.confirm').hide();
            }
        });

        jQuery('.pickup_mobile').keyup(function(){
            saveData();
            if(validatePhone())
            {
                jQuery('#checkout').attr('disabled',false);
                jQuery(this).removeClass('error');
                jQuery(this).addClass('success');
                jQuery('#phone-message').hide();
            } else {
                jQuery('#checkout').attr('disabled',true);
                jQuery(this).attr('id','inputWarning2');
                jQuery(this).removeClass('success');
                jQuery(this).addClass('error');
                jQuery('#phone-message').show();
            }
            if(validatePhone())
            {
                jQuery('.confirm').show();
            } else {
                jQuery('.confirm').hide();
            }
        });

        jQuery('.confirm').on('click',function(){
            jQuery('.boxit-methods').html(
                '<div style="text-align:center;"><h1>Thank you for choose BoxIt locker</h1></div>'
            );
        });

        jQuery.ajax({
            'type' : 'POST',
            'url'  : 'https://'+window.OwsBootstrap.getExternalAppDomain()+'/index.php?r=app/cart&shop=' + jQuery('#shop').val(),
            'dataType' : 'json',
            'crossDomain' : true,
            'data' : {
                'shop' : jQuery('#shop').val(),
                'session' : window.OwsBootstrap.getSessionValue()
            },
            'success' : function(json) {
                //console.info(json.locker_id > 0);
                //console.info(json.phone.length > 0);
                console.info(json);

                // set session value
                if (json && json.session){
                    window.OwsBootstrap.setSessionValue(json.session);
                }

                if(json.locker_id > 0 && validatePhone())
                {
                    jQuery('.boxit-methods').parent().hide();
                    if(json.type != 'other')
                        jQuery('.inputField').show();
                    $('#mobile_prefix option:contains("' + json.phone.substring(0,3) + '")').attr('selected',true);
                }
                else
                {
                    jQuery('.boxit-methods').show();
                    jQuery('#pickup_location_id').val(json.locker_id);
                    jQuery('.pickup_mobile').val(substring(json.phone,3,10));
                    $('#mobile_prefix option:contains("' + json.phone.substring(0,3) + '")').attr('selected',true);
                    $('input').filter(function() { return this.value == json.type }).attr('checked',true);
                    if(json.type != 'other')
                    {
                        jQuery('.inputField').show();
                    }

                    if(validatePhone())
                    {
                        jQuery('.confirm').show();
                        jQuery('#checkout').attr('disabled',false);
                        jQuery('.pickup_mobile').removeClass('error');
                        jQuery('.pickup_mobile').addClass('success');
                        jQuery('#phone-message').hide();
                    } else {
                        jQuery('.confirm').hide();
                        jQuery('#checkout').attr('disabled',true);
                        jQuery('.pickup_mobile').attr('id','inputWarning2');
                        jQuery('.pickup_mobile').removeClass('success');
                        jQuery('.pickup_mobile').addClass('error');
                        jQuery('#phone-message').show();
                    }
                }
            }
        });

        if(jQuery('.method input:checked').val() == 'other')
        {
            jQuery('#checkout').attr('disabled',false);
        } else {
            console.info(validatePhone());
            if(validatePhone())
            {
                jQuery('#checkout').attr('disabled',false);
                jQuery(this).removeClass('error');
                jQuery(this).addClass('success');
                jQuery('#phone-message').hide();
            } else {
                jQuery('#checkout').attr('disabled',true);
                jQuery(this).attr('id','inputWarning2');
                jQuery(this).removeClass('success');
                jQuery(this).addClass('error');
                jQuery('#phone-message').show();
            }
        }

    }, 'typeof window.OwsBootstrap != "undefined"');

    if (typeof console != 'undefined' && typeof console.log == 'function')
        console.log('start search OwsBootstrap...');
});