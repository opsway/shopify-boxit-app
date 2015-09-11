/**
 * Shopify BoxIt frontend app
 * @author goshi
 * @version 0.1
 */
(function(){

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
            console.log(p);
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
                console.log(shopDomains);
                if (shopDomains === null){
                    if (typeof jQuery != "undefined" && jQuery('#shop').length && jQuery('#shop').val() != document.location.hostname){
                        shopDomains.push(jQuery('#shop').val());
                    }
                    shopDomains.push(document.location.hostname);
                }

                return shopDomains;

            }

        }

    };

    // initialize main OwsApp
    // make it global to use with script tags
    window.OwsBootstrap = new OwsApp();

    console.log(Shopify, window.OwsBootstrap);

    // check for checkout data and update backed
    if (typeof Shopify != 'undefined' &&
        typeof Shopify.checkout != 'undefined' &&
        typeof Shopify.checkout.order_id != 'undefined' &&
        Shopify.checkout.order_id &&
        typeof window.OwsBootstrap != 'undefined' &&
        window.OwsBootstrap){


        var updateOrder = function(){
            jQuery.ajax({
                'type' : 'POST',
                'url'  : 'https://'+window.OwsBootstrap.getExternalAppPath()+'/index.php?r=app/updateorder',
                'dataType' : 'json',
                'crossDomain' : true,
                'data' : {
                    'shop' : Shopify.shop,
                    'order_id' : Shopify.checkout.order_id,
                    'session' : window.OwsBootstrap.getSessionValue() ? window.OwsBootstrap.getSessionValue() : c.getCookie('_session_id')
                },
                'success' : function(data) {
                    //console.info('OK');

                    if (data && data.error && typeof console != 'undefined' && typeof console.log == 'function'){
                        console.log(data);
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

})();