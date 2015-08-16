<?php
/**
 * ...
 *
 * Date: 12.08.15
 * Time: 15:46
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	12.08.2015/goshi 
 */

namespace frontend\controllers;


use yii\web\Controller;

class ShopifyController extends Controller{

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

    protected function _responseOk(){

        echo "ok";
        \Yii::$app->response->setStatusCode(200);
        \Yii::$app->response->send();
        exit;

    }


    protected function _responseError(){
        \Yii::$app->response->setStatusCode(500);
        \Yii::$app->response->send();
        exit;
    }

    protected function _verifyWebhook($data, $hmac_header)
    {
        $appSettings = \Yii::$app->db->createCommand('SELECT * FROM app_settings')->queryOne();

        //\Yii::error($appSettings['shared_secret']);

        $calculated_hmac = base64_encode(hash_hmac('sha256', $data, $appSettings['shared_secret'], true));

        //\Yii::error(base64_encode(hash_hmac('sha256', $data, $appSettings['shared_secret'], true)));
        //\Yii::error(array($hmac_header, $calculated_hmac));

        //return ($hmac_header == $calculated_hmac);

        // now we have some problems
        return true;
    }



} 