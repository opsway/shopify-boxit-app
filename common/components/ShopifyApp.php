<?php
/**
 * ...
 *
 * Date: 14.08.15
 * Time: 12:39
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	14.08.2015/goshi 
 */

namespace common\components;

use Yii;
use common\components\ShopifyAPI;

class ShopifyApp extends \yii\base\Component
{

    /**
     * application settings
     * @var null|array
     */
    protected $_app_settings;

    public function init()
    {
        parent::init();

        if (!$this->_app_settings){
            $this->getAppSettings();
        }
    }

    /**
     * return array of application settings
     * @return array|bool|null
     */
    public function getAppSettings(){

        if (!$this->_app_settings){

            $command = Yii::$app->db->createCommand('SELECT * FROM app_settings');
            $this->_app_settings = $command->queryOne();

        }

        return $this->_app_settings;

    }

    /**
     * install webhooks
     * @param \common\components\ShopifyAPI $ShopifyAPI
     * @return $this
     */
    public function installWebHooks(ShopifyAPI $ShopifyAPI){

        $hooks = array();
        foreach (\Yii::$app->params['shopify_app']['webhooks'] as $hook){
            $hooks[$hook] = (\Yii::$app->params['base_api_url'] ? \Yii::$app->params['base_api_url'] : 'https://apps.opsway.com/shopify/boxit/frontend/web').'/index.php?r=' . $hook;
        }

        $ShopifyAPI->installWebhooks($hooks);

        unset($hooks);

        return $this;

    }

    /**
     * uninstall webhooks
     * @param \common\components\ShopifyAPI $ShopifyAPI
     * @return $this
     */
    public function uninstallWebHooks(ShopifyAPI $ShopifyAPI){

        $ShopifyAPI->uninstallWebhooks(\Yii::$app->params['shopify_app']['webhooks']);

        return $this;

    }

    /**
     * method install carrier services to the App
     * @param ShopifyAPI $ShopifyAPI
     * @return $this
     * @throws \Exception
     */
    public function installCarrierServices(ShopifyAPI $ShopifyAPI){

        /**
         * get list of the carrier services
         */
        $result = null;

        try {

            $result = $ShopifyAPI->getCarrierServicesList();

        } catch (\Exception $e){

            \Yii::error($e->getMessage(), 'shopifyApp/installCarrierServices');
            //throw new \Exception($e->getMessage(), $e->getCode(),$e);
        }

        \Yii::error($result);

        if (isset(\Yii::$app->params['shopify_app']['carrier_services'])){

            // try to find if carrier service always exists
            if ($result && is_array($result)){
                foreach ($result as $service){

                    if (mb_strtolower(trim($service['name'])) == 'boxit'){

                        // remove old carrier
                        $ShopifyAPI->deleteCarrierService($service['id']);

                    }

                }
            }

            // add carrier service
            \Yii::error('Install carrier: boxit');
            try {
                // always add timestamp to the URL to change carrier service
                $ShopifyAPI->addCarrierService([
                    "name" => 'BoxIt',
                    "callback_url" => (\Yii::$app->params['base_api_url'] ? \Yii::$app->params['base_api_url'] : 'https://apps.opsway.com/shopify/boxit/frontend/web').'/index.php?r=boxit/carrier&tstamp='.time(),
                    "format" => "json",
                    "service_discovery" => true
                ]);

            } catch (\Exception $e){
                \Yii::error($e->getMessage());
            }

            \Yii::error('End install carrier: boxit');

        }

        return $this;

    }

    /**
     * @param ShopifyAPI $ShopifyAPI
     * @return $this
     * @throws \Exception
     */
    public function uninstallCarrierServices(ShopifyAPI $ShopifyAPI){

        /**
         * get list of the carrier services
         */
        $result = null;

        try {

            $result = $ShopifyAPI->getCarrierServicesList();

        } catch (\Exception $e){

            \Yii::error($e->getMessage(), 'shopifyApp/installCarrierServices');
            //throw new \Exception($e->getMessage(), $e->getCode(),$e);
        }
        \Yii::error($result);

        if ($result && is_array($result) && isset(\Yii::$app->params['shopify_app']['carrier_services'])){

            // try to find if carrier service always exists
            if ($result && is_array($result)){
                foreach ($result as $service){

                    if (mb_strtolower(trim($service['name'])) == 'boxit'){

                        // remove old carrier
                        $ShopifyAPI->deleteCarrierService($service['id']);

                    }

                }
            }

        }

        return $this;

    }

    /**
     * method installes theme templates
     * @param ShopifyAPI $ShopifyAPI
     * @return string old cart template
     * @throws \yii\base\UserException
     * @throws \Exception
     */
    public function installTemplates(ShopifyAPI $ShopifyAPI){

        /**
         * get only main themes from themes list and patching it
         */
        $result = null;

        try {

            $result = $ShopifyAPI->getThemesList(['role' => 'main']);

        } catch (\Exception $e){

            \Yii::error($e->getMessage(), 'shopifyApp/installTemplates');
            throw new \Exception($e->getMessage(), $e->getCode(),$e);
        }


        /*
         * get concrete cart template
         */
        try {

            $asset = $ShopifyAPI->getThemeElement($result[0]['id'], ['asset[key]' => 'templates/cart.liquid','theme_id' => $result[0]['id']]);

            if (!$asset || empty($asset)){

                // loggin bad cart template
                \Yii::error('Cannot find cart template', 'shopifyApp/installTemplates');
                \Yii::error($asset, 'shopifyApp/installTemplates');

                throw new \yii\base\UserException('Cannot find cart template to apply App changes');

            }

        } catch (\yii\base\UserException $e){

            // rethrow excpetion
            throw new \yii\base\UserException($e->getMessage());

        } catch (\Exception $e){

            \Yii::error('New exception: '.$e->getMessage(), 'shopifyApp/installTemplates');
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }

        /*
         * check if we always have installed BoxIT plugin
         */
        if (strpos($asset['value'], '<!-- BOXIT-APP -->') !== false){

            \Yii::info('Found old BoxIT cart App template', 'shopifyApp/installTemplates');

            // remove old boxit-app
            $asset['value'] = preg_replace('#<!-- BOXIT-APP -->.*?<!-- END BOXIT-APP -->#is', '', $asset['value']);

        }

        /*
         * check if we have necessary code
         */
        if (!preg_match('|({%\s+if\s+additional_checkout_buttons\s+%})|sm', $asset['value'], $match)){

            // loggin bad cart template
            \Yii::error('Users cart template not compatible with App', 'shopifyApp/installTemplates');
            \Yii::error($asset['value'], 'shopifyApp/installTemplates');

            throw new \yii\base\UserException('Unfortunatelly, your cart not compatible with this App');


        }

        $content = preg_replace(
            '|({%\s+if\s+additional_checkout_buttons\s+%})|sm',
            \Yii::$app->view->renderFile('@app/views/shopify_frontend/include.php') . "\r\t\t" . '$1',
            $asset['value']);

        $ShopifyAPI->updateThemeElement($result[0]['id'], [
            'asset'	=>	[
                'key'	=>	'templates/cart.liquid',
                'value'	=>	$content
            ]
        ]);

        $ShopifyAPI->updateThemeElement($result[0]['id'], [
            'asset'	=>	[
                'key'	=>	'assets/boxitapp.bootstrap.js',
                'attachment'	=>	base64_encode(\Yii::$app->view->renderFile('@app/views/shopify_frontend/common.js'))
            ]
        ]);

        $ShopifyAPI->updateThemeElement($result[0]['id'], [
            'asset'	=>	[
                'key'	=>	'assets/boxitapp.jquery.js',
                'attachment'	=>	base64_encode(\Yii::$app->view->renderFile('@app/views/shopify_frontend/jquery.js'))
            ]
        ]);

        /*
         * push main boxit template
         */
        $content = \Yii::$app->view->renderFile('@app/views/shopify_frontend/cart.php');

        // replace path to the external items
        $content = str_replace('[[BASE_API_URL]]', \Yii::$app->params['base_api_url'], $content);

        $ShopifyAPI->updateThemeElement($result[0]['id'], [
            'asset'	=>	[
                'key'	=>	'snippets/boxitapp.liquid',
                'value'	=>	$content
            ]
        ]);


        // install script tags
        $ShopifyAPI->installScriptTagElement(\Yii::$app->params['base_api_url'].'/shopify_frontend/js/app.js');

        return $asset['value'];

    }

    /**
     * method uninstall template
     * @param ShopifyAPI $ShopifyAPI
     * @return $this
     * @throws \yii\base\UserException
     * @throws \Exception
     */
    public function uninstallTemplates(ShopifyAPI $ShopifyAPI){

        try {

            $result = $ShopifyAPI->getThemesList(['role' => 'main']);

        } catch (\Exception $e){

            \Yii::error('New exception: '.$e->getMessage(), 'shopifyApp/uninstallTemplates');
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }

        // install script tags
        try {
            $script_tags = $ShopifyAPI->getScriptTagsList();

            if (!empty($script_tags)){

                foreach ($script_tags as $tag){
                    if (strpos($tag['src'], '/shopify_frontend/js/app.js') !== false){
                        $ShopifyAPI->deleteScriptTagElement($tag['id']);
                    }
                }
            }

        } catch (\Exception $e) {
            //
        }

        /*
         * get concrete cart template
         */
        try {

            $asset = $ShopifyAPI->getThemeElement($result[0]['id'], ['asset[key]' => 'templates/cart.liquid','theme_id' => $result[0]['id']]);

        } catch (\Exception $e){

            \Yii::error('New exception: '.$e->getMessage(), 'shopifyApp/uninstallTemplates');
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }


        // if we have access to assets
        if (isset($asset) && $asset['value'])
            $asset['value'] = preg_replace('#<!-- BOXIT-APP -->.*?<!-- END BOXIT-APP -->#is', '', $asset['value']);

        // if we have access to themes
        if ($result){

            try {

                $ShopifyAPI->updateThemeElement($result[0]['id'], [
                    'asset'	=>	[
                        'key'	=>	'templates/cart.liquid',
                        'value'	=>	$asset['value']
                    ]
                ]);

                $ShopifyAPI->deleteThemeElement($result[0]['id'], [
                    'asset'	=>	[
                        'key'	=>	'snippets/boxitapp.liquid'
                    ]
                ]);

                $ShopifyAPI->deleteThemeElement($result[0]['id'], [
                    'asset'	=>	[
                        'key'	=>	'assets/boxitapp.jquery.js'
                    ]
                ]);

                $ShopifyAPI->deleteThemeElement($result[0]['id'], [
                    'asset'	=>	[
                        'key'	=>	'assets/boxitapp.bootstrap.js'
                    ]
                ]);

            } catch (\yii\base\UserException $e){

                // rethrow excpetion
                throw new \yii\base\UserException($e->getMessage());

            } catch (\Exception $e){

                \Yii::error('New exception: '.$e->getMessage(), 'shopifyApp/uninstallTemplates');
                throw new \Exception($e->getMessage(), $e->getCode(), $e);
            }

        }

        return $this;

    }

    /**
     * check if template always installed
     * @param ShopifyAPI $ShopifyAPI
     * @return bool
     * @throws \yii\base\UserException
     * @throws \Exception
     */
    public function isTemplateInstalled(ShopifyAPI $ShopifyAPI){


        try {

            $result = $ShopifyAPI->getThemesList(['role' => 'main']);

        } catch (\Exception $e){

            \Yii::error($e->getMessage(), 'shopifyApp/installTemplates');
            throw new \Exception($e->getMessage(), $e->getCode(),$e);
        }


        /*
         * get concrete cart template
         */
        try {

            $asset = $ShopifyAPI->getThemeElement($result[0]['id'], ['asset[key]' => 'templates/cart.liquid','theme_id' => $result[0]['id']]);

            if (!$asset || empty($asset)){

                // loggin bad cart template
                \Yii::error('Cannot find cart template', 'shopifyApp/installTemplates');
                \Yii::error($asset, 'shopifyApp/installTemplates');

                throw new \yii\base\UserException('Cannot find cart template to apply App changes');

            }

        } catch (\yii\base\UserException $e){

            // rethrow excpetion
            throw new \yii\base\UserException($e->getMessage());

        } catch (\Exception $e){

            \Yii::error('New exception: '.$e->getMessage(), 'shopifyApp/installTemplates');
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }

        /*
         * check if we always have installed BoxIT plugin
         */
        if (strpos($asset['value'], '<!-- BOXIT-APP -->') !== false){

            return true;

        } else {

            return false;

        }

    }

    /**
     * method checks if access token broken
     * @param ShopifyAPI $ShopifyAPI
     * @return bool
     */
    public function isAccessTokenValid(ShopifyAPI $ShopifyAPI){

        try {

            $result = $ShopifyAPI->getShop();

            if ($result){
                return true;
            } else {
                return false;
            }

        } catch (\Exception $e){

            \Yii::error($e->getMessage(), 'shopifyApp/checkAccessTokenValidation');
            return false;

        }


    }

}