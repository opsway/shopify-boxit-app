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

class FulfillmentsController extends ShopifyController
{


    public function actionUpdate(){

        $this->actionCreate();

    }

    public function actionCreate(){

        $request = Yii::$app->request;

        /**
         * @var $ShopifyAPI \common\components\ShopifyAPI
         */
        $ShopifyAPI = \Yii::$app->get('ShopifyAPI');
        $ShopifyAPI->handleRequest();


        // check request from shopify server
        $data = file_get_contents('php://input');
        $verified = $this->_verifyWebhook($data, $ShopifyAPI->getRequestVerifyCode());

        if ($verified){

            // decode json data and extract phone numbers
            $data = json_decode($data, true);
            \Yii::error($data);

            //if (isset($data['tracking_number']) && $data['tracking_number']){
            if (isset($data['order_id']) && $data['order_id']){

                $userSettings = Usersettings::getByParams(['store_name' => $ShopifyAPI->getRequestShop()]);

                if ($userSettings ){

                    // try to find user with this phone in not complete user carts
                    $result = Usercart::find()
                        ->where([
                            'store_name' => $ShopifyAPI->getRequestShop(),
                            'order_id' => $data['order_id'],
                            'is_fulfilled' => 0
                            ]
                        )->one();

                    // Hooray - we have found not fulfilled item
                    if ($result){

                        $result->date_fulfilled = date('Y-m-d H:i:s');
                        $result->is_fulfilled = 1;
                        $result->save();

                        $service = $result->type == 'boxit' ? 'BoxIt' : 'Shop&Collect';

                        // send API call to the BoxIt
                        $BoxItApi = \Yii::$app->get('BoxItAPI');
                        if ($BoxItApi->postConsumerDelivery(array(
                            'OrderNum' => $result->order_id,
                            'LockerId' => $result->locker_id,
                            'CustId' => $result->type == 'boxit' ? $userSettings['boxit_api_key'] : $userSettings['shopandcollect_api_key'],
                            'Cell_phone' => $result->phone
                        ))){
                            Yii::info('Sent to '.$service.' success. Order: '.$result->order_id);
                        } else {
                            Yii::error(array_merge(array('Sent to '.$service.' failed. Order: '.$result->order_id), $BoxItApi->getLastResponse()));
                        }

                    } else {
                        Yii::error('Fulfillment always sent. Order: '.$result->order_id);
                    }
                }
            }
        } else {
            Yii::error('Webhook NOT verified. IP: '.$request->getUserIP());
        }

        $this->_responseOk();

    }



} 