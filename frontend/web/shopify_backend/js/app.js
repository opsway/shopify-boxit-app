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
        $('.alert-success').css('display','none');
        $('.alert-success').css('opacity','0');
        $('.alert-danger').css('display','none');
        $('.alert-danger').css('opacity','0');
    };

    var showMessage = function(type,text)
    {
        $('.alert-' + type).css('display','block');
        if(text != undefined)
            $('.alert-' + type).find('.message').html(text);
        $('.alert-' + type).animate({
            opacity :   1
        },2000);
    };

    $(function(){

        $('#btnSubmitSettings').on('click',function(){
            var that = this;
            $(that).addClass('disabled');
            $(that).text('Saving...');
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
                        showMessage('success');

                    } else
                    {
                        $.each(data.errors, function(key,value){
                            if(message != "")
                                message += "<br />";
                            message += '<span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span><span class="sr-only">Error:</span>' + value;
                        });
                        showMessage('danger', message);
                    }
                    $(that).removeClass('disabled');
                    $(that).text('Save');
                }
            });
        });
    });

})();