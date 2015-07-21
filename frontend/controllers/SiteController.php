<?php
namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use sandeepshetty\shopify_api;
use common\models\Usersettings;

/**
 * Site controller
 */
class SiteController extends Controller
{
	
	public $enableCsrfValidation = false;
    /**
     * @inheritdoc
     */
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
	\Yii::$app->view->renderFile('@app/views/boxit/cart.php',['test' => 'test']);
    }
	
    public function actionCallback()
    {
        $get = Yii::$app->request->get();
        if(isset($get['code']))
        {
            $shop = $get['shop'];
            $command = Yii::$app->db->createCommand('SELECT * FROM app_settings');
            $settings = $command->queryOne();
            $access_token = shopify_api\oauth_access_token(
                            $shop, $settings['api_key'], $settings['shared_secret'], $get['code']
            );
            $shopify = shopify_api\client(
                            $shop, $access_token, $settings['api_key'], $settings['shared_secret']
            );

            /*$hooks = array(
                    'app/uninstalled'
            );

            foreach($hooks as $hook)
            {
                    $arguments = array(
                            'webhook' => array(
                                    'topic' => $hook,
                                    'address' => 'https://apps.opsway.com/shopify/boxit/frontend/web/index.php?r=' . $hook,
                                    'format' => "json"
                            )
                    );
                    $shopify('POST', '/admin/webhooks.json', $arguments);
            }
			*/
            $arguments = array(
                    'carrier_service'	=>	array(
                            'name' => 'Boxit',
                            'callback_url' => 'https:\/\/apps.opsway.com/shopify/boxit/frontend/web/index.php?r=boxit/carrier',
                            'format' => 'json',
                            'service_discovery' => true
                    )
            );

            $shopify('POST', '/admin/carrier_services.json', $arguments);

            /*$result = $shopify('GET','/admin/themes.json',['role' => 'main']);

            $asset = $shopify('GET', '/admin/themes/' . $result[0]['id'] . '/assets.json', ['asset[key]' => 'templates/cart.liquid','theme_id' => $result[0]['id']]);

            preg_match('|({%\s+if\s+additional_checkout_buttons\s+%})|sm',$asset['value'],$match);

            $content = preg_replace('|({%\s+if\s+additional_checkout_buttons\s+%})|sm',\Yii::$app->view->renderFile('@app/views/boxit/include.php') . "\\r\\t\\t" . '$1',$asset['value']);

            $content = str_replace('"','\'',$content);
            $content = str_replace("\n","\\n",$content);
            $content = str_replace("\t","\\t",$content);
            $content = str_replace("\r","\\r",$content);


            $shopify('PUT','/admin/themes/' . $result[0]['id'] . '/assets.json',[
                    'asset'	=>	[
                            'key'	=>	'templates/cart.liquid',
                            'value'	=>	$content
                    ]
            ]);

            $shopify('PUT','/admin/themes/' . $result[0]['id'] . '/assets.json',[
                    'asset'	=>	[
                            'key'	=>	'assets/jquery.js',
                            'attachment'	=>	base64_encode(\Yii::$app->view->renderFile('@app/views/boxit/jquery.js'))
                    ]
            ]);

            $shopify('PUT','/admin/themes/' . $result[0]['id'] . '/assets.json',[
                    'asset'	=>	[
                            'key'	=>	'assets/common.js',
                            'attachment'	=>	base64_encode(\Yii::$app->view->renderFile('@app/views/boxit/common.js'))
                    ]
            ]);

            //$assets = $shopify('GET','/admin/themes/' . $result[0]['id'] . '/assets.json');

            $content = \Yii::$app->view->renderFile('@app/views/boxit/cart.php',['test' => 'test']);
            $content = str_replace("\r","\\r",$content);
            $content = str_replace("\n","\\n",$content);
            $content = str_replace("\t","\\t",$content);
            $shopify('PUT','/admin/themes/' . $result[0]['id'] . '/assets.json',[
                    'asset'	=>	[
                            'key'	=>	'snippets/boxit.liquid',
                            'value'	=>	$content
                    ]
            ]);

            $userSettings = new Usersettings();
            $userSettings->access_token = $access_token;
            $userSettings->store_name = $shop;
            $userSettings->old_cart = $asset['value'];
            $userSettings->save();*/

            //Yii::$app->db->createCommand('INSERT INTO user_settings(`access_token`,`store_name`,`old_cart`) VALUES("' . $access_token . '", "' . $shop . '","' . \Yii::$app->db->quoteValue() . '")')->execute();

            $this->redirect('https://' . $shop . '/admin/apps',302);
        } else {
            echo \Yii::$app->view->renderFile('@app/views/boxit/settings.php',['test' => 'test']);
        }
    }
}
