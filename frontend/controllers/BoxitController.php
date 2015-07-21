<?php
namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use sandeepshetty\shopify_api;
use common\models\Usersettings;
use common\models\Appsettings;

/**
 * Site controller
 */
class BoxitController extends Controller
{

	public $enableCsrfValidation = false;
	
    public function behaviors()
    {
        return [
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
			'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }
	
	public function actionIndex()
	{
		$rates = array( 'rates' => 
			array(
				'service_name'		=>	'Boxit',
				'service_code'		=>	'BXT',
				'total_price'		=>	'10',
				'currency'			=>	'NIS',
				'min_delivery_date'	=>	'2013-04-12 14:48:45 -0400',
				'max_delivery_date'	=>	'2013-04-12 14:48:45 -0400'
			),
			array(
				'service_name'		=>	'Foxit',
				'service_code'		=>	'BXT',
				'total_price'		=>	'15',
				'currency'			=>	'NIS',
				'min_delivery_date'	=>	'2013-04-12 14:48:45 -0400',
				'max_delivery_date'	=>	'2013-04-12 14:48:45 -0400'
			)
		);
		
		echo json_encode($rates);
		die;
		$_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN'] = 'stder.myshopify.com';
		$appSettings = Yii::$app->db->createCommand('SELECT * FROM app_settings')->queryOne();
		$userSettings = Usersettings::getByParams(['store_name' => $_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN']]);
		
		var_dump($userSettings['access_token']);die;
		$shopify = shopify_api\client(
				$_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN'], $userSettings['access_token'], $appSettings['api_key'], $appSettings['shared_secret']
		);		
		
		$result = $shopify('GET','/admin/themes.json',['role' => 'main']);
	}
	
	public function actionCallback()
	{
		Yii::info('asd', 'info');
	}
	
	public function actionCarrier()
	{
		/*$rates = array();
		$rates[] = array( 'rates' => 
			array(
				'service_name'		=>	'Boxit',
				'service_code'		=>	'BXT',
				'total_price'		=>	'10',
				'currency'			=>	'NIS',
				'min_delivery_date'	=>	'2013-04-12 14:48:45 -0400',
				'max_delivery_date'	=>	'2013-04-12 14:48:45 -0400'
			),
			array(
				'service_name'		=>	'Foxit',
				'service_code'		=>	'BXT',
				'total_price'		=>	'15',
				'currency'			=>	'NIS',
				'min_delivery_date'	=>	'2013-04-12 14:48:45 -0400',
				'max_delivery_date'	=>	'2013-04-12 14:48:45 -0400'
			)
		);
		
		echo json_encode($rates);*/
		/*echo '{
           "rates": [
               {
                   "service_name": "canadapost-overnight",
                   "service_code": "ON",
                   "total_price": "1295",
                   "currency": "CAD",
                   "min_delivery_date": "2013-04-12 14:48:45 -0400",
                   "max_delivery_date": "2013-04-12 14:48:45 -0400"
               },
               {
                   "service_name": "fedex-2dayground",
                   "service_code": "1D",
                   "total_price": "2934",
                   "currency": "USD",
                   "min_delivery_date": "2013-04-12 14:48:45 -0400",
                   "max_delivery_date": "2013-04-12 14:48:45 -0400"
               },
               {
                   "service_name": "fedex-2dayground",
                   "service_code": "1D",
                   "total_price": "2934",
                   "currency": "USD",
                   "min_delivery_date": "2013-04-12 14:48:45 -0400",
                   "max_delivery_date": "2013-04-12 14:48:45 -0400"
               }
           ]
        }';*/
		Yii::info('carrierAPI', 'info');
	}

}
?>