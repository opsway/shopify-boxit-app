jQuery(function(){

    var box_cookie = 'boxitsess';
    var box_cookie_tout = 100*24*60*60*1000;

    /**
     * function set boxit session value
     * @param session
     */
    var setSessionValue = function(session){
        var d = new Date();
        d = d.setTime(d.getTime() + box_cookie_tout);
        if (d && d.toUTCString) {
            d = d.toUTCString();
        }

        ows_setcookie(box_cookie, session, d, '/');

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
        for(i = start; i != end; i = i + 1)
        {
            if(text[i] != undefined)
                str += text[i];
        }
        return str;
    };

    /**
     * cookies library
     *
     * @author goshi
     * @package javascript::share
     */
    var ows_setcookie = function (name, value, expires, path, domain, secure) {
        document.cookie = name + "=" + escape(value) +
            ((expires) ? "; expires=" + expires : "") +
            ((path) ? "; path=" + path : "") +
            ((domain) ? "; domain=" + domain : "") +
            ((secure) ? "; secure" : "");
    };

    var ows_getcookie = function (name) {
        var cookie = " " + document.cookie;
        var search = " " + name + "=";
        var setStr = null;
        var offset = 0;
        var end = 0;
        if (cookie.length > 0) {
            offset = cookie.indexOf(search);
            if (offset != -1) {
                offset += search.length;
                end = cookie.indexOf(";", offset);
                if (end == -1) {
                    end = cookie.length;
                }
                setStr = unescape(cookie.substring(offset, end));
            }
        }
        return(setStr);
    };

    var ows_deletecookie = function(name) {
        var d = new Date();
        d = d.setTime(d.getTime() + -1*1000);
        if (d && d.toUTCString) {
            d = d.toUTCString();
        }

        ows_setcookie(name, "", d, '/')
    };

    var saveData = function ()
    {
        var address = jQuery('input[type=radio]:checked').parent().find('span').text();
        address = address.replace('Boxit pickup location chosen:','').replace('Shop&Collect pickup location chosen:','');


        jQuery.ajax({
            'type' : 'POST',
            //'url'  : 'https://apps.opsway.com/shopify/boxit/frontend/web/index.php?r=app/save',
            'url'  : 'https://boxit-webt.pagekite.me/site/index.php?r=app/save',
            'dataType' : 'json',
            'crossDomain' : true,
            'data' : {
                'phone' : jQuery('#mobile_prefix option:selected').val() + jQuery('.pickup_mobile').val(),
                'locker_id' : jQuery('#pickup_location_id').val(),
                'customer_id' : jQuery('#customer_id').val(),
                'shop' : jQuery('#shop').val(),
                'type'	: jQuery('input[type="radio"]:checked').val(),
                'address' : address.trim(),
                'session' : ows_getcookie(box_cookie)
            },
            'success' : function(data) {
                //console.info('OK');

                if (data && data.session){
                    setSessionValue(data.session);
                }

            }
        });
    }


    // check if we have session
    // if not - get current shop session cookie
    if (!ows_getcookie(box_cookie) && ows_getcookie('_session_id')){
        var d = new Date();
        d = d.setTime(d.getTime() + box_cookie_tout);
        if (d && d.toUTCString) {
            d = d.toUTCString();
        }

        ows_setcookie(box_cookie, ows_getcookie('_session_id'), d, '/');
    }

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
        //'url'	:	'https://apps.opsway.com/shopify/boxit/frontend/web/index.php?r=app/cart&shop=' + jQuery('#shop').val(),
        'url'  : 'https://boxit-webt.pagekite.me/site/index.php?r=app/cart&shop=' + jQuery('#shop').val(),
        'dataType' : 'json',
        'crossDomain' : true,
        'data' : {
            'shop' : jQuery('#shop').val(),
            'session' : ows_getcookie(box_cookie)
        },
        'success' : function(json) {
            //console.info(json.locker_id > 0);
            //console.info(json.phone.length > 0);
            console.info(json);

            // set session value
            if (json && json.session){
                setSessionValue(json.session);
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
});