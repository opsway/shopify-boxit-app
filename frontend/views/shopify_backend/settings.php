<html>
<head>
    <script src="//cdn.shopify.com/s/assets/external/app.js"></script>
    <link rel="stylesheet" type="text/css" href="<?=$app_url?>/components/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="<?=$app_url?>/components/Font-Awesome/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="<?=$app_url?>/shopify_backend/css/layout.css">
    <script src="<?=$app_url?>/components/jquery/dist/jquery.min.js"></script>
    <script type="text/javascript">
        ShopifyApp.init({
            apiKey: "<?=$store_settings['api_key']?>",  // Expects: 32 character API key string like ff9b1d04414785029e066f8fd0465d00
            shopOrigin: "https://<?=$user_settings['store_name']?>",  // Expects: https://exampleshop.myshopify.com
            debug: true
        });

        window.mainPageTitle = "Settings";

        /**
         * current backend app settings
         */
        window.boxItApp = {
            appUrl : '<?=$app_url?>',
            saveUrl : '<?=$app_url?>/index.php?r=site/saveconfig',
            storeName : '<?=$user_settings['store_name']?>',
            storeHash : '<?=$user_settings['access_token_hash']?>'
        };

        document.domain = '<?=$document_domain?>';

    </script>

    <script src="<?=$app_url?>/shopify_backend/js/app.js"></script>

</head>
<body>

    <div class="notification-container">
        <div class="alert alert-danger" role="alert" style="display:none;opacity:0;">
            <div class="message">
                <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
                <span class="sr-only">Error:</span>
                Enter a valid data
            </div>
        </div>
        <div class="alert alert-success" role="alert" style="display:none;opacity:0;">
            <div class="message">
                <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
                <span class="sr-only">OK!</span>
                Data was saved successful
            </div>
        </div>
    </div>

    <div class="page-header clearfix">
        <h1>API settings</h1>
        <div class="page-description"></div>
        <button id="btnSubmitSettings" class="btn btn-shopify green save"><i class="glyphicon glyphicon-ok"></i> Save</button>
    </div>

    <div class="section section-dashboard">

        <form id="formData" action="#">
            <div class="form-group">
                <label for="boxit_api_key">BoxIt API Key</label>
                <input type="text" class="form-control" name="boxit_api_key" id="boxit_api_key" placeholder="Enter BoxIt API key" value="<?=(empty($user_settings['boxit_api_key']) ? "" : htmlspecialchars($user_settings['boxit_api_key']))?>">
            </div>
            <div class="form-group">
                <label for="shopandcollect_api_key">Shop&amp;Collect API Key</label>
                <input type="text" class="form-control" name="shopandcollect_api_key" id="shopandcollect_api_key" placeholder="Enter Shop&amp;Collect API key" value="<?=(empty($user_settings['shopandcollect_api_key']) ? "" : htmlspecialchars($user_settings['shopandcollect_api_key']))?>">
            </div>

        </form>

    </div>

</body>
</html>