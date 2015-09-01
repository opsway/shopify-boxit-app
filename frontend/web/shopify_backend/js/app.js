(function(){

    // initialize shopify app
    ShopifyApp.ready(function(){
        ShopifyApp.Bar.initialize({
            title: window.mainPageTitle,
            icon: window.boxItApp.appUrl + "/favicon.ico"
        });
    });

    var hideMessages = function ()
    {
        $('#alertError').css('display','none').css('opacity','0');
        $('#alertSuccess').css('display','none').css('opacity','0');
    };

    var showMessage = function(type,text)
    {
        $('#alert' + type).css('display','block');
        if(text != undefined)
            $('#alert' + type).find('.message').html(text);
        $('#alert' + type).animate({
            opacity :   1
        },2000);
    };

    $(function(){

        /**
         * function refresh webhooks
         */
        $('#hooks_update').click(function(e){

            e.preventDefault();

            var that = this;
            $(that).addClass('disabled');
            $(that).data('old_text', $(that).text()).text('Refreshing hooks...');
            hideMessages();

            $.ajax({
                type  :   'POST',
                url   :   window.boxItApp.updateHooksUrl,
                dataType : 'json',
                data  :   {
                    store : window.boxItApp.storeName,
                    hash : window.boxItApp.storeHash
                },
                success   : function(data)
                {
                    var message = "";

                    if(data.success)
                    {
                        showMessage('Success');

                    } else
                    {
                        $.each(data.errors, function(key,value){
                            if(message != "")
                                message += "<br />";
                            message += '<span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span><span class="sr-only">Error:</span>' + value;
                        });
                        showMessage('Error', message);
                    }
                    $(that).removeClass('disabled');
                    $(that).text($(that).data('old_text'));
                }
            });

        });


        /**
         * function refresh webhooks
         */
        $('.btn-install').click(function(e){

            e.preventDefault();

            if (confirm($(this).data('confirm'))){

                var that = this;

                $(that).addClass('disabled');
                $(that).data('old_text', $(that).text()).text('Update installation...');
                hideMessages();

                $.ajax({
                    type  :   'POST',
                    url   :   window.boxItApp.updateInstallUrl,
                    dataType : 'json',
                    data  :   {
                        store : window.boxItApp.storeName,
                        hash : window.boxItApp.storeHash,
                        method : $(that).data('what')
                    },
                    success   : function(data)
                    {
                        var message = "";

                        if(data.success)
                        {
                            showMessage('Success');

                        } else
                        {
                            $.each(data.errors, function(key,value){
                                if(message != "")
                                    message += "<br />";
                                message += '<span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span><span class="sr-only">Error:</span>' + value;
                            });
                            showMessage('Error', message);
                        }
                        $(that).removeClass('disabled');
                        $(that).text($(that).data('old_text'));

                        $(that).hide();
                        if ($(that).data('what') == 'install'){
                            $('.btn-what-uninstall').show();
                            $('#alertUninstalled').hide(100);
                            $('#boxit_api_key, #shopandcollect_api_key, #boxit_carrier_cost, #shopandcollect_carrier_cost, #btnSubmitSettings, #hooks_update, #checkout_button_id').removeClass('disabled').removeProp('disabled');
                        } else {
                            $('.btn-what-install').show();
                            $('#alertUninstalled').show(100);
                            $('#boxit_api_key, #shopandcollect_api_key, #boxit_carrier_cost, #shopandcollect_carrier_cost, #btnSubmitSettings, #hooks_update, #checkout_button_id').addClass('disabled').prop('disabled', 'disabled');
                        }
                    }
                });

            }

        });

        /**
         * save settings to the backend
         */
        $('#btnSubmitSettings').on('click',function(){
            var that = this;
            $(that).addClass('disabled');
            $(that).data('old_text', $(that).text()).text('Saving...');
            hideMessages();

            $.ajax({
                type  :   'POST',
                url   :   window.boxItApp.saveUrl,
                dataType : 'json',
                data  :   {
                    store : window.boxItApp.storeName,
                    hash : window.boxItApp.storeHash,
                    formData : $('#formData').serializeArray()
                },
                success   : function(data)
                {
                    var message = "";

                    if(data.success)
                    {
                        showMessage('Success');

                    } else
                    {
                        $.each(data.errors, function(key,value){
                            if(message != "")
                                message += "<br />";
                            message += '<span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span><span class="sr-only">Error:</span>' + value;
                        });
                        showMessage('Error', message);
                    }
                    $(that).removeClass('disabled');
                    $(that).text($(that).data('old_text'));
                }
            });
        });

        // check for current disabled status
        if ($('.btn-what-uninstall').css('display') == 'none'){
            $('#boxit_api_key, #shopandcollect_api_key, #boxit_carrier_cost, #shopandcollect_carrier_cost, #btnSubmitSettings, #hooks_update, #checkout_button_id').addClass('disabled').prop('disabled', 'disabled');
            $('#alertUninstalled').show();
        }

    });

})();