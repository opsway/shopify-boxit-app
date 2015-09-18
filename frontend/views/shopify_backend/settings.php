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
            updateHooksUrl : '<?=$app_url?>/index.php?r=site/updatehooks',
            updateInstallUrl : '<?=$app_url?>/index.php?r=site/updateinstall',
            storeName : '<?=$user_settings['store_name']?>',
            storeHash : '<?=$user_settings['access_token_hash']?>'
        };

        document.domain = '<?=$document_domain?>';

    </script>

    <script src="<?=$app_url?>/shopify_backend/js/app.js"></script>

</head>
<body>

    <div class="notification-container">
        <div class="alert alert-danger" role="alert" style="display:none;opacity:0;" id="alertError">
            <div class="message">
                <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
                <span class="sr-only">Error:</span>
                Enter a valid data
            </div>
        </div>
        <div class="alert alert-success" role="alert" style="display:none;opacity:0;" id="alertSuccess">
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

        <div id="alertUninstalled" class="alert alert-danger" role="alert" <?php if ($user_settings['is_uninstalled'] == 0): ?>style="display: none"<? endif; ?>>
            <strong>Warning!</strong> Your app is uninstalled. You need to click `Reinstall app` button to enable functionality
        </div>

        <div id="alertNoAPIKeys" class="alert alert-danger" role="alert" <?php if (trim($user_settings['boxit_api_key']) != '' || trim($user_settings['boxit_api_key']) != ''): ?>style="display: none"<? endif; ?>>
            <strong>Warning!</strong> You need to enter one of the API keys below to enable BoxIt carrier service.
        </div>

        <form id="formData" action="#">

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">API keys</h3>
                </div>
                <div class="panel-body">

                    <div class="form-group">
                        <label for="boxit_api_key">BoxIt API Key</label>
                        <input type="text" class="form-control" name="boxit_api_key" id="boxit_api_key" placeholder="Enter BoxIt API key" value="<?=(empty($user_settings['boxit_api_key']) ? "" : htmlspecialchars($user_settings['boxit_api_key']))?>">
                    </div>
                    <div class="form-group">
                        <label for="shopandcollect_api_key">Shop&amp;Collect API Key</label>
                        <input type="text" class="form-control" name="shopandcollect_api_key" id="shopandcollect_api_key" placeholder="Enter Shop&amp;Collect API key" value="<?=(empty($user_settings['shopandcollect_api_key']) ? "" : htmlspecialchars($user_settings['shopandcollect_api_key']))?>">
                    </div>

                </div>

            </div>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Carrier service settings</h3>
                </div>
                <div class="panel-body">

                    <div class="form-group">
                        <label for="boxit_api_key">BoxIt carrier delivery cost *</label>
                        <input type="text" class="form-control" name="boxit_carrier_cost" id="boxit_carrier_cost" placeholder="Enter BoxIt carrier cost" value="<?=(empty($user_settings['boxit_carrier_cost']) ? "" : htmlspecialchars($user_settings['boxit_carrier_cost']))?>">
                    </div>
                    <div class="form-group">
                        <label for="shopandcollect_api_key">Shop&amp;Collect carrier delivery cost *</label>
                        <input type="text" class="form-control" name="shopandcollect_carrier_cost" id="shopandcollect_carrier_cost" placeholder="Enter Shop&amp;Collect carrier cost" value="<?=(empty($user_settings['shopandcollect_carrier_cost']) ? "" : htmlspecialchars($user_settings['shopandcollect_carrier_cost']))?>">
                    </div>

                    <div class="alert alert-info" role="alert">
                        * Currency will be used from shop settings
                    </div>
                </div>

            </div>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Additional settings</h3>
                </div>
                <div class="panel-body">

                    <div class="form-group">
                        <label for="is_show_on_checkout_all">Show location select dialog on checkout page (then, dialog on the thankyou page will be shown only if user not input right data on the checkout page)</label>
                        <div class="radios-group">
                            <label for="is_show_on_checkout_1"><input type="radio" class="form-control" name="is_show_on_checkout" id="is_show_on_checkout_1" value="1" <?=($user_settings['is_show_on_checkout'] == 1) ? 'checked="checked"' : ''?>>Yes</label>
                            <label for="is_show_on_checkout_0"><input type="radio" class="form-control" name="is_show_on_checkout" id="is_show_on_checkout_0" value="0" <?=($user_settings['is_show_on_checkout'] == 0) ? 'checked="checked"' : ''?>>No</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="boxit_api_key">Checkout button ID (if you are using non-standard checkout template you need to define button ID to handle it on delivery change). Default value is "checkout"</label>
                        <input type="text" class="form-control" name="checkout_button_id" id="checkout_button_id" placeholder="Example: checkout" value="<?=(empty($user_settings['checkout_button_id']) ? "" : htmlspecialchars($user_settings['checkout_button_id']))?>">
                    </div>

                </div>

            </div>

        </form>

        <div class="alert alert-warning" role="alert">
            <strong>Warning!</strong> Before uninstalling app from the apps list - press the "Uninstall app" button below. Then the garbage from the App will be removed correctly.
        </div>

        <div class="form-group clear">
            <div class="float-right">
                <button class="btn btn-success" id="hooks_update">Refresh hooks</button>
            </div>

            <div class="float-right">
                <button class="btn btn-warning btn-install btn-what-install" data-confirm="Are you sure? Do you want to install again all hooks and templates?" data-what="install" <?php if ($user_settings['is_uninstalled'] == 0): ?>style="display: none"<?php endif; ?>>Reinstall app</button>
                <button class="btn btn-danger btn-install btn-what-uninstall" data-confirm="Are you sure? Do you want to uninstall all hooks and templates?" data-what="uninstall" <?php if ($user_settings['is_uninstalled'] == 1): ?>style="display: none"<?php endif; ?>>Uninstall app</button>
            </div>

        </div>

    </div>

</body>
</html>