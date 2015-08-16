<?php
/**
 * ...
 *
 * Date: 14.08.15
 * Time: 10:22
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	14.08.2015/goshi 
 */

namespace common\components;

use Yii;
use phpish\shopify;

class ShopifyAPI extends \yii\base\Component{

    /**
     * current active clients
     * @var array
     */
    protected $_clients = array();

    /**
     * last active client
     * @var null
     */
    protected $_lastClient = null;


    /**
     * last handled request
     * @var null|\yii\web\Request
     */
    protected $_lastRequest = null;


    /**
     * method get request from shopify
     * @param null $request
     * @return $this
     */
    public function handleRequest($request = null){

        if ($request && $request instanceof \yii\web\Request){

            $this->_lastRequest = $request;

        } else {

            $this->_lastRequest = Yii::$app->request;

        }

        return $this;

    }

    /**
     * return verification code
     * @return array|null|string
     */
    public function getRequestVerifyCode(){

        if ($this->_lastRequest){

            return $this->_lastRequest->getHeaders()->get('x-shopify-hmac-sha256');

        } else {

            return null;
        }

    }

    /**
     * return shopify shop domain
     * @return array|null|string
     */
    public function getRequestShop(){

        if ($this->_lastRequest){

            return $this->_lastRequest->getHeaders()->get('x-shopify-shop-domain');

        } else {

            return null;
        }

    }

    /**
     * return shopify order id
     * @return array|null|string
     */
    public function getRequestOrderId(){

        if ($this->_lastRequest){

            return $this->_lastRequest->getHeaders()->get('x-shopify-order-id');

        } else {

            return null;
        }

    }

    /**
     * method requests access token from Shopify API
     * @param $shop
     * @param $api_key
     * @param $shared_secret
     * @param $code
     * @return mixed
     */
    public function requestAccessToken($shop, $api_key, $shared_secret, $code){

        return shopify\access_token(
            $shop, $api_key, $shared_secret, $code
        );

    }

    /**
     * method returns authorization url
     * @param $shop
     * @param $api_key
     * @param array $permissions
     * @param string $redirect_uri
     * @return string
     */
    public function getAuthorizationUrl($shop, $api_key, $permissions = array(), $redirect_uri = ''){

        return shopify\authorization_url($shop, $api_key, $permissions ? (is_string($permissions) ? array($permissions) : $permissions) : array(), $redirect_uri);

    }

    /**
     * activate client to the API
     * @param $shop
     * @param $api_key
     * @param $access_token
     * @return ShopifyAPI
     * @throws \Exception
     */
    public function activateClient($shop, $api_key, $access_token){

        $key = $shop.$api_key.$access_token;

        if (!isset($this->_clients[$key])){

            try {
                $this->_clients[$key] = shopify\client(
                    $shop, $api_key, $access_token
                );

            } catch (\Exception $e){
                \Yii::error($e->getMessage());
                throw new \Exception($e->getMessage(), $e->getCode());
            }
        }

        $this->_lastClient = $key;

        return $this;

    }


    public function makeApiCall($endpoint, $method = 'GET', $query='', $payload='', &$response_headers=array(), $request_headers=array(), $curl_opts=array()){

        if (!$this->_lastClient)
            throw new \Exception('ShopifyAPI: You need to activate client first');

        try {

            $response = $this->_clients[$this->_lastClient](strtoupper($method).' '.$endpoint, $query, $payload, $response_headers, $request_headers, $curl_opts);

        } catch (\yii\base\UserException $e){

            // rethrow exception
            throw new \yii\base\UserException($e->getMessage());

        } catch (\Exception $e){

            \Yii::error($e->getMessage(), 'ShopifyAPI:makeApiCall');
            throw new \Exception($e->getMessage());
        }

        return $response;

    }

    /**
     * get shop info
     * @return mixed
     * @throws \yii\base\UserException
     * @throws \Exception
     */
    public function getShop(){

        if (!$this->_lastClient)
            throw new \Exception('ShopifyAPI: You need to activate client first');

        try {

            $result = $this->makeApiCall('/admin/shop.json', 'GET');

        } catch (\yii\base\UserException $e){

            // rethrow exception
            throw new \yii\base\UserException($e->getMessage());

        } catch (\Exception $e){

            \Yii::error($e->getMessage(), 'ShopifyAPI:getShop');
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }

        return $result;

    }


    /**
     * get carrier services list
     * @return mixed
     * @throws \yii\base\UserException
     * @throws \Exception
     */
    public function getCarrierServicesList(){

        if (!$this->_lastClient)
            throw new \Exception('ShopifyAPI: You need to activate client first');

        try {

            $result = $this->makeApiCall('/admin/carrier_services.json', 'GET');

            if (!$result || empty($result)){

                // loggin bad themes list
                \Yii::error('Bad carrier services list', 'ShopifyAPI:getCarrierServicesList');
                \Yii::error($result, 'ShopifyAPI:getCarrierServicesList');

                throw new \yii\base\UserException('Bad carrier services list');

            }

        } catch (\yii\base\UserException $e){

            // rethrow exception
            throw new \yii\base\UserException($e->getMessage());

        } catch (\Exception $e){

            \Yii::error($e->getMessage(), 'ShopifyAPI:getCarrierServicesList');
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }

        return $result;

    }

    /**
     * add carrier service to Shopify
     * @param array $params
     * @return $this
     * @throws \yii\base\UserException
     * @throws \Exception
     */
    public function addCarrierService($params = array()){

        if (!$this->_lastClient)
            throw new \Exception('ShopifyAPI: You need to activate client first');

        if (empty($params))
            throw new \Exception('ShopifyAPI: No carrier data');

        try {

            // detect carrier services format
            if (!isset($params['carrier_service'])){
                $params = ['carrier_service' => $params];
            }

            \Yii::error($params);

            \Yii::error($this->makeApiCall('/admin/carrier_services.json', 'POST', array(), $params));


        } catch (\yii\base\UserException $e){

            // rethrow exception
            throw new \yii\base\UserException($e->getMessage());

        } catch (\Exception $e){

            \Yii::error($e->getMessage(), 'ShopifyAPI:addCarrierService');
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }

        return $this;

    }

    /**
     * method deletes carrier service
     * @param $carrier_id
     * @return $this
     * @throws \yii\base\UserException
     * @throws \Exception
     */
    public function deleteCarrierService($carrier_id){

        if (!$this->_lastClient)
            throw new \Exception('ShopifyAPI: You need to activate client first');

        if (!$carrier_id)
            throw new \Exception('ShopifyAPI: Bad carrier ID');

        try {

            $this->makeApiCall('/admin/carrier_services/'.$carrier_id.'.json', 'DELETE');


        } catch (\yii\base\UserException $e){

            // rethrow exception
            throw new \yii\base\UserException($e->getMessage());

        } catch (\Exception $e){

            \Yii::error($e->getMessage(), 'ShopifyAPI:deleteCarrierService');
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }

        return $this;

    }


    /**
     * method get script tags list
     * @param array $params
     * @return mixed
     * @throws \yii\base\UserException
     * @throws \Exception
     */
    public function getScriptTagsList($params = array()){

        if (!$this->_lastClient)
            throw new \Exception('ShopifyAPI: You need to activate client first');

        try {

            $result = $this->makeApiCall('/admin/script_tags.json', 'GET', $params ? $params : array());

            if (!$result || empty($result)){

                // loggin bad themes list
                \Yii::error('Bad script tags list', 'ShopifyAPI:getScriptTagsList');

                throw new \yii\base\UserException('Bad script tags list');

            }

        } catch (\yii\base\UserException $e){

            // rethrow exception
            throw new \yii\base\UserException($e->getMessage());

        } catch (\Exception $e){

            \Yii::error($e->getMessage(), 'ShopifyAPI:getScriptTagsList');
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }

        return $result;

    }


    /**
     * get theme's asset
     * @param $script_id
     * @param array $params
     * @return mixed
     * @throws \yii\base\UserException
     * @throws \Exception
     */
    public function getScriptTagElement($script_id, $params = array()){

        if (!$this->_lastClient)
            throw new \Exception('ShopifyAPI: You need to activate client first');

        if (!$script_id)
            throw new \Exception('ShopifyAPI: Bad script ID');

        try {

            $result = $this->makeApiCall('/admin/script_tags/' . $script_id . '.json', 'GET', $params ? $params : array());

            if (!$result || empty($result)){

                throw new \yii\base\UserException('Cannot find script tag '.$script_id);

            }

        } catch (\yii\base\UserException $e){

            // rethrow exception
            throw new \yii\base\UserException($e->getMessage());

        } catch (\Exception $e){

            \Yii::error($e->getMessage(), 'ShopifyAPI:getScriptTagElement');
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }

        return $result;

    }


    /**
     * method updates script tag element
     * @param string $src path to the script
     * @param string $event
     * @return $this
     * @throws \yii\base\UserException
     * @throws \Exception
     */
    public function installScriptTagElement($src, $event = 'onload'){

        if (!$this->_lastClient)
            throw new \Exception('ShopifyAPI: You need to activate client first');

        if (!$src)
            throw new \Exception('ShopifyAPI: Bad script source');

        try {

            $this->makeApiCall('/admin/script_tags.json', 'POST', array(), array(
                'script_tag' => array(
                    'event' => $event,
                    'src' => $src
                )
            ));


        } catch (\yii\base\UserException $e){

            // rethrow exception
            throw new \yii\base\UserException($e->getMessage());

        } catch (\Exception $e){

            \Yii::error($e->getMessage(), 'ShopifyAPI:updateScriptTagElement');
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }

        return $this;

    }


    /**
     * method deletes script tag element
     * @param string $script_id
     * @return $this
     * @throws \yii\base\UserException
     * @throws \Exception
     */
    public function deleteScriptTagElement($script_id){

        if (!$this->_lastClient)
            throw new \Exception('ShopifyAPI: You need to activate client first');

        if (!$script_id)
            throw new \Exception('ShopifyAPI: Bad script source');

        try {

            $this->makeApiCall('/admin/script_tags/'.$script_id.'.json', 'DELETE');


        } catch (\yii\base\UserException $e){

            // rethrow exception
            throw new \yii\base\UserException($e->getMessage());

        } catch (\Exception $e){

            \Yii::error($e->getMessage(), 'ShopifyAPI:deleteScriptTagElement');
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }

        return $this;

    }


    /**
     * method get themes list by parameters
     * @param array $params
     * @return mixed
     * @throws \yii\base\UserException
     * @throws \Exception
     */
    public function getThemesList($params = array()){

        if (!$this->_lastClient)
            throw new \Exception('ShopifyAPI: You need to activate client first');

        try {

            $result = $this->makeApiCall('/admin/themes.json', 'GET', $params ? $params : array());

            if (!$result || empty($result)){

                // loggin bad themes list
                \Yii::error('Bad themes list', 'ShopifyAPI:getThemesList');

                throw new \yii\base\UserException('Bad themes list');

            }

        } catch (\yii\base\UserException $e){

            // rethrow exception
            throw new \yii\base\UserException($e->getMessage());

        } catch (\Exception $e){

            \Yii::error($e->getMessage(), 'ShopifyAPI:getThemesList');
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }

        return $result;

    }

    /**
     * get theme's asset
     * @param $theme_id
     * @param array $params
     * @return mixed
     * @throws \yii\base\UserException
     * @throws \Exception
     */
    public function getThemeElement($theme_id, $params = array()){

        if (!$this->_lastClient)
            throw new \Exception('ShopifyAPI: You need to activate client first');

        if (!$theme_id)
            throw new \Exception('ShopifyAPI: Bad theme ID');

        try {

            $result = $this->makeApiCall('/admin/themes/' . $theme_id . '/assets.json', 'GET', $params ? $params : array());

            if (!$result || empty($result)){

                // loggin bad themes list
                \Yii::error('Cannot find element for the theme '.$theme_id, 'ShopifyAPI:getThemeElement');

                throw new \yii\base\UserException('Cannot find element for the theme '.$theme_id);

            }

        } catch (\yii\base\UserException $e){

            // rethrow exception
            throw new \yii\base\UserException($e->getMessage());

        } catch (\Exception $e){

            \Yii::error($e->getMessage(), 'ShopifyAPI:getThemeElement');
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }

        return $result;

    }

    /**
     * method updates theme element
     * @param $theme_id
     * @param array $params
     * @return $this
     * @throws \yii\base\UserException
     * @throws \Exception
     */
    public function updateThemeElement($theme_id, $params = array()){

        if (!$this->_lastClient)
            throw new \Exception('ShopifyAPI: You need to activate client first');

        if (!$theme_id)
            throw new \Exception('ShopifyAPI: Bad theme ID');

        try {

            $this->makeApiCall('/admin/themes/' . $theme_id . '/assets.json', 'PUT', array(), $params ? $params : array());


        } catch (\yii\base\UserException $e){

            // rethrow exception
            throw new \yii\base\UserException($e->getMessage());

        } catch (\Exception $e){

            \Yii::error($e->getMessage(), 'ShopifyAPI:updateThemeElement');
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }

        return $this;

    }


    /**
     * method delete theme element
     * @param $theme_id
     * @param array $params
     * @return $this
     * @throws \yii\base\UserException
     * @throws \Exception
     */
    public function deleteThemeElement($theme_id, $params = array()){

        if (!$this->_lastClient)
            throw new \Exception('ShopifyAPI: You need to activate client first');

        if (!$theme_id)
            throw new \Exception('ShopifyAPI: Bad theme ID');

        try {

            $this->makeApiCall('/admin/themes/' . $theme_id . '/assets.json', 'DELETE', array(), $params ? $params : array());


        } catch (\yii\base\UserException $e){

            // rethrow exception
            throw new \yii\base\UserException($e->getMessage());

        } catch (\Exception $e){

            \Yii::error($e->getMessage(), 'ShopifyAPI:deleteThemeElement');
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }

        return $this;

    }


    /**
     * install webooks
     * @param array $hooks
     * @return $this
     * @throws \Exception
     */
    public function installWebhooks($hooks = array()){

        if (!$this->_lastClient)
            throw new \Exception('ShopifyAPI: You need to activate client first');

        if ($hooks && is_array($hooks)){

            foreach($hooks as $hook => $address)
            {
                $arguments = array(
                    'webhook' => array(
                        'topic' => $hook,
                        'address' => $address,
                        'format' => "json"
                    )
                );
                try {
                    $this->makeApiCall('/admin/webhooks.json', 'POST', array(), $arguments);
                } catch (\Exception $e){
                    \Yii::error($e->getMessage(), 'ShopifyAPI:installWebhooks');
                }

            }

        }

        return $this;

    }

    /**
     * method uninstall webhooks
     * @param array $hooks
     * @return $this
     * @throws \yii\base\UserException
     * @throws \Exception
     */
    public function uninstallWebhooks($hooks = array()){

        if (!$this->_lastClient)
            throw new \Exception('ShopifyAPI: You need to activate client first');

        // try to get webhooks
        try{

            $all_hooks = $this->makeApiCall('/admin/webhooks.json', 'GET');

        } catch (\yii\base\UserException $e){

            // rethrow exception
            throw new \yii\base\UserException($e->getMessage());

        } catch (\Exception $e){

            \Yii::error($e->getMessage(), 'ShopifyAPI:installWebhooks');
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }


        if (isset($all_hooks) && $all_hooks){

            foreach($all_hooks as $hook)
            {

                if (in_array($hook['topic'], $hooks)){
                    try {

                        $this->makeApiCall('/admin/webhooks/' . $hook['id'] . '.json', 'DELETE');

                    } catch (\yii\base\UserException $e){

                        // rethrow exception
                        throw new \yii\base\UserException($e->getMessage());

                    } catch (\Exception $e){

                        \Yii::error($e->getMessage(), 'ShopifyAPI:uninstallWebhooks');
                    }
                }

            }

        }

        return $this;

    }


} 