<?php
/**
 * ...
 *
 * Date: 12.08.15
 * Time: 15:21
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	12.08.2015/goshi 
 */

namespace frontend\controllers;

use common\models\Usercart;
use common\models\Usersettings;
use Yii;

class OrdersController extends ShopifyController{

    /**
     * possible shipping names
     * @var array
     */
    protected $_shippings_map = array(
        'boxit' => 'boxit',
        'shopcollect' => 'shop_collect',
        'shopandcollect' => 'shop_collect',
    );

    public function actionUpdated(){

        $this->actionCreate();

    }

    public function actionCreate(){

        $request = Yii::$app->request;

        // check request from shopify server
        $data = file_get_contents('php://input');
        //Yii::error($data);
        $verified = $this->_verifyWebhook($data, $request->getHeaders()->get('x-shopify-hmac-sha256')/*$_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256']*/);

        if ($verified){

            // decode json data and extract phone numbers
            $data = (array)json_decode($data, true);
            $phone = null;

            Yii::error($data);

            // cleanup phone
            $phones = array();

            if (isset($data['billing_address']['phone'])){

                $phones[] = preg_replace('#[^0-9]#', '', $data['billing_address']['phone']);
            }

            if (isset($data['shipping_address']['phone'])){

                $phones[] = preg_replace('#[^0-9]#', '', $data['shipping_address']['phone']);
            }

            if (isset($data['customer']['default_address']['phone'])){

                $phones[] = preg_replace('#[^0-9]#', '', $data['customer']['default_address']['phone']);
            }

            // add delivery type
            $shippings = array();
            if (!empty($data['shipping_lines'])){
                foreach ($data['shipping_lines'] as $shipping){
                    $shipping = preg_replace('#[^a-zA-Z]#', '', mb_strtolower($shipping['title']));
                    if ($shipping && isset($this->_shippings_map[$shipping])){
                        $shippings[] = $this->_shippings_map[$shipping];
                    }
                }
            }


            if (!empty($phones) && !empty($shippings)){

                $userSettings = Usersettings::getByParams(['store_name' => $request->getHeaders()->get('x-shopify-shop-domain')/*$_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN']*/]);

                if ($userSettings){

                    // try to find user with this phone in not complete user carts
                    $query = Usercart::find()
                        ->where(['store_name' => $request->getHeaders()->get('x-shopify-shop-domain'), 'is_complete' => 0]);

                    $_operand = ['or'];
                    foreach ($shippings as $shipping){
                        $_operand[] = ['type' => $shipping];
                    }
                    $query->andWhere($_operand);

                    //$query->andWhere(['like', 'phone', $phone]);
                    $_operand = ['or'];
                    foreach ($phones as $phone){
                        $_operand[] = ['like', 'phone', $phone];
                    }
                    $query->andWhere($_operand);

                    $query->orderBy(array('date_add' => SORT_DESC));
                    Yii::error($query->createCommand());
                    $result = $query->one();

                    Yii::error($result);

                    // Hooray - we have found
                    if ($result){

                        $result->date_order = date('Y-m-d H:i:s');
                        $result->order_id = $request->getHeaders()->get('x-shopify-order-id');
                        $result->is_complete = 1;
                        $result->save();

                        Yii::info(array_merge(array('New order was created'), $result->toArray()));

                    }
                }
            } else {
                Yii::error(array_merge(array('Ooops, no phone or right shipping detected'), $data));
            }
        } else {
            Yii::error('Webhook NOT verified. IP: '.$request->getUserIP());
        }

        $this->_responseOk();
    }

} 