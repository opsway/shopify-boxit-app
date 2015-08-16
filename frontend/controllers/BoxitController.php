<?php
namespace frontend\controllers;

use Yii;
use phpish\shopify;
use common\models\Usersettings;

/**
 * Boxit carriers controller
 */
class BoxitController extends ShopifyController
{

    /**
     * action boxit carrier info
     */
    public function actionCarrier()
	{

        $rates = array('rates' => array());

        /**
         * @var $ShopifyAPI \common\components\ShopifyAPI
         */
        $ShopifyAPI = \Yii::$app->get('ShopifyAPI');
        $ShopifyAPI->handleRequest();

        // check request from shopify server
        $data = file_get_contents('php://input');
        $verified = $this->_verifyWebhook($data, $ShopifyAPI->getRequestVerifyCode());

        if ($verified){

            $userSettings = Usersettings::getByParams(['store_name' => $ShopifyAPI->getRequestShop()]);

            if ($userSettings){

                /**
                 * @var $ShopifyModule \common\components\ShopifyApp
                 */
                $ShopifyModule = \Yii::$app->get('ShopifyApp');

                $settings = $ShopifyModule->getAppSettings();

                // get shop currency
                $shop_data = $ShopifyAPI->activateClient($ShopifyAPI->getRequestShop(), $settings['api_key'], $userSettings['access_token'])->getShop();

                // detect delivery date
                $nextday = mktime(0, 0, 0, date('m'), date('d')+1, date('Y'));
                if (date('w',$nextday) == 0){
                    $nextday += 24*60*60;
                } elseif (date('w',$nextday) == 6){
                    $nextday += 2*24*60*60;
                }

                foreach (\Yii::$app->params['shopify_app']['carrier_services'] as $app_service_name => $app_service_action ){

                    $rates['rates'][] = array(
                        'service_name'		=>	$app_service_name,
                        'service_code'		=>	$app_service_action,
                        'total_price'		=>	$userSettings[$app_service_action.'_carrier_cost']*100,
                        'currency'			=>	$shop_data['currency'],
                        'min_delivery_date'	=>	date('Y-m-d H:i:s O', $nextday),
                        'max_delivery_date'	=>	date('Y-m-d H:i:s O', $nextday)
                    );

                }

            }
        }
		
		echo json_encode($rates);
		exit;

	}


}
