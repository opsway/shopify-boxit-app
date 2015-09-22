/**
 * Shopify BoxIt frontend app
 * @author goshi
 * @version 0.1
 */
(function(){

    if (typeof window.isBoxItInitialize != 'undefined' && window.isBoxItInitialize){
        return;
    }

    window.isBoxItInitialize = true;

    // define base ows app
    var OwsApp = function(){

        var box_cookie = 'boxitsess';
        var box_cookie_tout = 100*24*60*60*1000;

        // current shop domains
        var shopDomains = null;

        /**
         * cookielibrary
         * @type {{setCookie: setCookie, getCookie: getCookie, deleteCookie: deleteCookie}}
         */
        var c = {

            setCookie : function (name, value, expires, path, domain, secure) {
                document.cookie = name + "=" + escape(value) +
                    ((expires) ? "; expires=" + expires : "") +
                    ((path) ? "; path=" + path : "") +
                    ((domain) ? "; domain=" + domain : "") +
                    ((secure) ? "; secure" : "");
            },

            getCookie : function (name) {
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
            },

            deleteCookie : function(name) {
                var d = new Date();
                d = d.setTime(d.getTime() + -1*1000);
                if (d && d.toUTCString) {
                    d = d.toUTCString();
                }

                this.setCookie(name, "", d, '/')
            }

        };

        var getScriptURL = (function() {
            var scripts = document.getElementsByTagName('script');
            var myScript = null;
            if (scripts && scripts.length){
                for (var i= 0,l=scripts.length; i<l; i++){
                    if (/shopify_frontend/.test(scripts[i].src)){
                        myScript = scripts[i];
                        break;
                    }
                }
            }

            return function() { return myScript ? myScript.src : myScript; };
        })();

        // method extract base path for app
        var getBasePath = function(){

            var p = getScriptURL().split('/shopify_frontend/');
            //console.log(p);
            return p[0];

        };

        // check if we have session
        // if not - get current shop session cookie
        if (/*!c.getCookie(box_cookie) && */c.getCookie('_shopify_s')){
            var d = new Date();
            d = d.setTime(d.getTime() + box_cookie_tout);
            if (d && d.toUTCString) {
                d = d.toUTCString();
            }

            c.setCookie(box_cookie, c.getCookie('_shopify_s'), d, '/');
        }

        return {

            /**
             * function set app session value
             * @param session
             */
            setSessionValue : function(session){
                var d = new Date();
                d = d.setTime(d.getTime() + box_cookie_tout);
                if (d && d.toUTCString) {
                    d = d.toUTCString();
                }

                c.setCookie(box_cookie, session, d, '/');

            },

            /**
             * function get app session value
             */
            getSessionValue : function(){

                return c.getCookie(box_cookie);

            },

            syncEvent : function (k, condition){

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
            },

            getExternalAppDomain: function(){

                var src = getScriptURL();
                var domain = null;

                if (src){
                    src = src.replace('https://', '').split('/');
                    domain = src[0];
                }
                return domain;

            },

            getExternalAppPath : function(){

                return getBasePath().replace('https://', '');

            },

            /**
             * method returns shop domains
             * @returns {*}
             */
            getShopDomains : function(){

                if (shopDomains === null){
                    shopDomains = [];
                    if (typeof jQuery != "undefined" && jQuery('#shop').length && jQuery('#shop').val() != document.location.hostname){
                        shopDomains.push(jQuery('#shop').val());
                    }
                    shopDomains.push(document.location.hostname);
                    if (typeof Shopify != 'undefined' && typeof Shopify.shop != 'undefined' && Shopify.shop)
                        shopDomains.push(Shopify.shop);
                }

                return shopDomains;

            },

            /**
             * method returns hooks function
             * @param app
             * @param hook
             * @param options
             * @returns {null}
             */
            getShopHooks: function(app, hook, options){

                return null;

            },

            /**
             * loading css
             * @param url
             * @returns {boolean}
             */
            loadCss : function(url){

                if (typeof url != 'undefined' && url !=''){
                    var fileref = document.createElement("link");
                    fileref.setAttribute("rel", "stylesheet");
                    fileref.setAttribute("type", "text/css");
                    // load css with prevent caching
                    fileref.setAttribute("href", url+'?'+(new Date()).getTime());
                    document.getElementsByTagName("head")[0].appendChild(fileref);
                    return true;
                } else {
                    return false;
                }
            }

        }

    };


    var BoxitApp = function(){

        var appSettings = null;

        /**
         * validate locker id value
         * @returns {boolean}
         */
        var validateLockerId = function(value){

            var selected = value;
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
        var validatePhone = function (value)
        {
            var regexpNum = /\d+/;
            var regexChar = /[a-zA-Z]/;
            var phone = value;
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

            return is_valid;

        };


        return {

            validateLockerId : function(value){

                return validateLockerId(value);

            },

            validatePhone : function(value){

                return validatePhone(value);

            },

            substring : function(text,start,end){

                return substring(text,start,end);

            },

            applyValidation : function(is_valid){

                return applyValidation(is_valid);

            },

            getAppSetting : function(setting, default_value){

                return getAppSetting(setting, default_value);

            },

            setAppSettings : function(appSettingsValue){

                appSettings = appSettingsValue;

            }

        }

    };

    // initialize main OwsApp
    // make it global to use with script tags
    window.OwsBootstrap = new OwsApp();

    // initialize current BoxIt app
    window.BoxitApp = new BoxitApp();

    //console.log(Shopify, window.OwsBootstrap);

    // check for checkout data and update backed
    if (typeof Shopify != 'undefined' &&
        typeof Shopify.checkout != 'undefined' &&
        typeof Shopify.checkout.order_id != 'undefined' &&
        Shopify.checkout.order_id &&
        typeof window.OwsBootstrap != 'undefined' &&
        window.OwsBootstrap){

        console.log(Shopify.checkout.shipping_rate.title);
        // check if we have one of the allowed delivery id
        if (typeof Shopify.checkout.shipping_rate != 'undefined' &&
            (Shopify.checkout.shipping_rate.title == 'BoxIt' || Shopify.checkout.shipping_rate.title == 'Shop&Collect')){

            // load styles
            window.OwsBootstrap.loadCss('https://'+window.OwsBootstrap.getExternalAppPath()+'/shopify_frontend/css/layout.css');

            // define html chunks
            var loader_html = '<div class="boxit-c"><div class="bg-boxit-popup-bg"></div><div class="b-boxit-strobber"><div class="b-boxit-strobber-inner"><div class="b-boxit-preloader">Please wait...</div></div></div></div>';
            var popup_html = '<div class="boxit-c"><div class="bg-boxit-popup-bg"></div><div class="b-boxit-popup"><div class="b-boxit-popup-inner"></div><span class="b-boxit-close">x</span></div></div>';

            // define html entities
            var loader_cont = null;
            var popup_cont = null;

            var updateOrder = function(){

                // show loader
                if (!loader_cont){
                    loader_cont = jQuery(loader_html);
                    loader_cont.find('.boxit-c').css('opacity', 0);
                    loader_cont.appendTo('body').animate({'opacity': 1}, 200);
                }

                var u_session = window.OwsBootstrap.getSessionValue() ? window.OwsBootstrap.getSessionValue() : c.getCookie('_session_id');

                // check if user input his location and phone
                jQuery.ajax({
                    'type' : 'POST',
                    'url'  : 'https://'+window.OwsBootstrap.getExternalAppPath()+'/index.php?r=app/cart&shop=' + window.OwsBootstrap.getShopDomains().join(','),
                    'dataType' : 'json',
                    'crossDomain' : true,
                    'data' : {
                        'shop' : window.OwsBootstrap.getShopDomains().join(','),
                        'session' : window.OwsBootstrap.getSessionValue(),
                        'order_id' : Shopify.checkout.order_id
                    },
                    'success' : function(json) {
                        //console.info(json.locker_id > 0);
                        //console.info(json.phone.length > 0);
                        console.info(json);

                        // get app settings
                        if (json.app_settings){
                            window.BoxitApp.setAppSettings(json.app_settings);
                        }

                        console.log(Shopify.checkout.shipping_rate);

                        // check if user always fill phone and location and shipping type is right
                        if (!(
                            typeof json.locker_id != 'undefined' &&
                            typeof json.type_title != 'undefined' &&
                            json.locker_id != '' &&
                            window.BoxitApp.validatePhone(window.BoxitApp.substring(json.phone,3,10)) &&
                            json.type_title == Shopify.checkout.shipping_rate.title)
                            && (typeof json.is_complete == 'undefined' || (typeof json.is_complete != 'undefined' && !json.is_complete))){

                            // we have some variants to move on
                            if (typeof json.locker_id != 'undefined' &&
                                json.locker_id != '' &&
                                window.BoxitApp.validatePhone(window.BoxitApp.substring(json.phone,3,10)) &&
                                typeof json.type_title != 'undefined' &&
                                json.type_title != Shopify.checkout.shipping_rate.title){

                                // update delivery method title
                                jQuery.ajax({
                                    'type' : 'POST',
                                    'url'  : 'https://'+window.OwsBootstrap.getExternalAppPath()+'/index.php?r=app/updateorder',
                                    'dataType' : 'json',
                                    'crossDomain' : true,
                                    'data' : {
                                        'shop' : Shopify.shop,
                                        'order_id' : Shopify.checkout.order_id,
                                        'session' : u_session,
                                        'type_title' : Shopify.checkout.shipping_rate.title
                                    },
                                    'success' : function(data) {
                                        //console.info('OK');

                                        // hide preloader
                                        loader_cont.stop().hide(50);

                                        if (data && data.error && typeof console != 'undefined' && typeof console.log == 'function'){
                                            console.log(data);
                                        }
                                    }
                                });

                            } else {

                                // add dialog template from backend
                                jQuery.ajax({
                                    'type' : 'get',
                                    'url'  : 'https://'+window.OwsBootstrap.getExternalAppPath()+'/index.php?r=app/uitemplate',
                                    'crossDomain' : true,
                                    'success' : function(data) {
                                        //console.info('OK');

                                        if (data && typeof console != 'undefined' && typeof console.log == 'function'){
                                            console.log(data);
                                        }

                                        // hide preloader
                                        loader_cont.stop().hide(50);

                                        // block closing window
                                        window.onbeforeunload = function confirmExit() {
                                            return "You have attempted to leave this page. Your order will not be saved. Are you sure?";
                                        };

                                        // in other case show dialog
                                        // show popup
                                        if (!popup_cont){
                                            popup_cont = jQuery(popup_html);
                                            popup_cont.find('.boxit-c').css('opacity', 0);
                                            popup_cont.appendTo('body').animate({'opacity': 1}, 200);
                                        }

                                        popup_cont.find('.b-boxit-popup-inner').html(data);

                                        if (typeof json.locker_id != 'undefined' &&
                                            json.locker_id != '' && window.BoxitApp.validatePhone(jQuery('.pickup_mobile').val()))
                                        {
                                            jQuery('#pickup_location_id').val(json.locker_id);

                                            jQuery('.b-boxit-container .inputField').show();
                                            jQuery('#mobile_prefix option:contains("' + window.BoxitApp.substring(json.phone, 0,3) + '")').attr('selected',true);
                                        }
                                        else
                                        {
                                            jQuery('#pickup_location_id').val(json.locker_id);

                                            if (json.phone){
                                                jQuery('.b-boxit-container .pickup_mobile').val(window.BoxitApp.substring(json.phone,3,10));
                                                jQuery('#mobile_prefix option:contains("' + json.phone.substring(0,3) + '")').attr('selected',true);
                                            }

                                            if (Shopify.checkout.shipping_rate.title){
                                                var services = window.BoxitApp.getAppSetting('carrier_services');
                                                if (services && services[Shopify.checkout.shipping_rate.title]){
                                                    jQuery('input').filter(function() { return this.value == services[Shopify.checkout.shipping_rate.title] }).attr('checked',true);
                                                }
                                            }

                                            if (window.BoxitApp.applyValidation(window.BoxitApp.validateLockerId(jQuery('.b-boxit-container input[type="radio"]:checked').val()) && window.BoxitApp.validatePhone(jQuery('.pickup_mobile').val()))){
                                                jQuery('#boxit-apply-data').attr('disabled', false).removeClass('disabled');
                                            } else {
                                                jQuery('#boxit-apply-data').attr('disabled', true).addClass('disabled');
                                            }

                                        }

                                        // bind events
                                        jQuery('.b-boxit-container .method input').change(function(){

                                            if (window.BoxitApp.applyValidation(window.BoxitApp.validateLockerId(jQuery('.b-boxit-container input[type="radio"]:checked').val()) && window.BoxitApp.validatePhone(jQuery('.pickup_mobile').val()))){
                                                jQuery('#boxit-apply-data').attr('disabled', false).removeClass('disabled');
                                            } else {
                                                jQuery('#boxit-apply-data').attr('disabled', true).addClass('disabled');
                                            }

                                        });

                                        jQuery('.b-boxit-container .pickup_mobile').on('blur keyup', function(){

                                            if (window.BoxitApp.applyValidation(window.BoxitApp.validateLockerId(jQuery('.b-boxit-container input[type="radio"]:checked').val()) && window.BoxitApp.validatePhone(jQuery('.pickup_mobile').val()))){
                                                jQuery('#boxit-apply-data').attr('disabled', false).removeClass('disabled');
                                            } else {
                                                jQuery('#boxit-apply-data').attr('disabled', true).addClass('disabled');
                                            }

                                        });

                                        var lockSelector = jQuery('.b-boxit-container .b-location-selector');

                                        lockSelector.find('a').on('click', function(e){
                                            e.preventDefault();
                                            /*if (jQuery('.b-boxit-container .method a').length){
                                                jQuery('.b-boxit-container .method a').eq(1).trigger('click');
                                            } else { */
                                                jQuery('.b-boxit-container input[type="radio"]:checked').trigger('click');

                                            //}
                                        });

                                        jQuery('#boxit-apply-data').click(function(e){
                                            e.preventDefault();

                                            if (window.BoxitApp.validateLockerId(jQuery('.b-boxit-container input[type="radio"]:checked').val()) && window.BoxitApp.validatePhone(jQuery('.pickup_mobile').val())){

                                                var address = jQuery('.b-boxit-container input[type=radio]:checked').closest('.method').find('span').text();
                                                address = address.replace('Boxit pickup location chosen:','').replace('Shop&Collect pickup location chosen:','');

                                                jQuery.ajax({
                                                    'type' : 'POST',
                                                    'url'  : 'https://'+window.OwsBootstrap.getExternalAppPath()+'/index.php?r=app/updateorder',
                                                    'dataType' : 'json',
                                                    'crossDomain' : true,
                                                    'data' : {
                                                        'shop' : Shopify.shop,
                                                        'order_id' : Shopify.checkout.order_id,
                                                        'session' : u_session,
                                                        'phone' : jQuery('#mobile_prefix option:selected').val() + jQuery('.pickup_mobile').val(),
                                                        'locker_id' : jQuery('#pickup_location_id').val(),
                                                        'type'	: jQuery('.b-boxit-container input[type="radio"]:checked').val(),
                                                        'type_title' : Shopify.checkout.shipping_rate.title,
                                                        'address' : address.trim()

                                                    },
                                                    'success' : function(data) {
                                                        //console.info('OK');

                                                        if (data && data.error && typeof console != 'undefined' && typeof console.log == 'function'){
                                                            console.log(data);
                                                        }

                                                        // freeup close event
                                                        window.onbeforeunload = null;

                                                        // hide popup
                                                        popup_cont.stop().hide(50);

                                                    }
                                                });
                                            } else {
                                                alert('Sorry, but you need to select location and enter right phone number');
                                            }

                                        });

                                        // set timeout on change locker_id
                                        setInterval(function(){
                                            if (window.BoxitApp.applyValidation(window.BoxitApp.validateLockerId(jQuery('.b-boxit-container input[type="radio"]:checked').val()) && window.BoxitApp.validatePhone(jQuery('.pickup_mobile').val()))){
                                                jQuery('#boxit-apply-data').attr('disabled', false).removeClass('disabled');
                                            } else {
                                                jQuery('#boxit-apply-data').attr('disabled', true).addClass('disabled');
                                            }

                                            if (jQuery('.b-boxit-container .method a').length){
                                                lockSelector.hide();
                                            } else {
                                                lockSelector.show();
                                            }
                                        }, 500);

                                    }
                                });

                            }

                        } else if (
                            typeof json.locker_id != 'undefined' &&
                                typeof json.type_title != 'undefined' &&
                                json.locker_id != '' &&
                                window.BoxitApp.validatePhone(window.BoxitApp.substring(json.phone,3,10)) &&
                                json.type_title == Shopify.checkout.shipping_rate.title
                            && typeof json.is_complete != 'undefined' && !json.is_complete){

                            // on this stage - simply update order to complete it
                            jQuery.ajax({
                                'type' : 'POST',
                                'url'  : 'https://'+window.OwsBootstrap.getExternalAppPath()+'/index.php?r=app/updateorder',
                                'dataType' : 'json',
                                'crossDomain' : true,
                                'data' : {
                                    'shop' : Shopify.shop,
                                    'order_id' : Shopify.checkout.order_id,
                                    'session' : u_session,
                                    'type_title' : Shopify.checkout.shipping_rate.title
                                },
                                'success' : function(data) {
                                    //console.info('OK');

                                    // hide preloader
                                    loader_cont.stop().hide(50);

                                    if (data && data.error && typeof console != 'undefined' && typeof console.log == 'function'){
                                        console.log(data);
                                    }
                                }
                            });

                        } else {

                            // hide preloader
                            loader_cont.stop().hide(50);

                        }

                    }
                });

            };

            // check if jquery exists
            if (typeof jQuery == 'undefined'){

                var s = document.createElement('script');
                s.type = 'text/javascript';
                s.src = 'https://'+window.OwsBootstrap.getExternalAppPath()+'/components/jquery/dist/jquery.min.js';
                var x = document.getElementsByTagName('script')[0];
                x.parentNode.insertBefore(s, x);

                window.OwsBootstrap.syncEvent(function(){
                    updateOrder();
                }, 'typeof jQuery != "undefined" && typeof jQuery.ajax == "function"');
            } else {
                updateOrder();
            }

        }



    }

})();