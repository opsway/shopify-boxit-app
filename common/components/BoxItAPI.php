<?php
/**
 * BoxIT API facade
 *
 * Date: 12.08.15
 * Time: 17:08
 * @version 1.0
 * @author goshi
 * @package web-T[]
 * 
 * Changelog:
 *	1.0	12.08.2015/goshi 
 */

namespace common\components;

use Yii;
use linslin\yii2\curl;

class BoxItAPI extends \yii\base\Component{

    /**
     * last API response
     * @var null
     */
    protected $_lastResponse = null;

    /**
     * return last response
     * @return null
     */
    public function getLastResponse(){

        return $this->_lastResponse;

    }

    /**
     * send new consumer delivery
     * @param array $data
     * @return bool
     */
    public function postConsumerDelivery($data = array()){

        // prepare request

        // create expected date
        $nextday = mktime(0, 0, 0, date('m'), date('d')+1, date('Y'));
        if (date('w',$nextday) == 0){
            $nextday += 24*60*60;
        } elseif (date('w',$nextday) == 6){
            $nextday += 2*24*60*60;
        }

        $body =
            '<?xml version="1.0"?>
            <ByBoxOrderRest xmlns="http://Flying-Cargo.com/WebServices/ByBox/V1">

        <OrderNum>'.$data['OrderNum'].'</OrderNum>
                <ExpectedDate>'.date('d/m/Y', $nextday).'</ExpectedDate>
                <LockerId>'.$data['LockerId'].'</LockerId>
                <CellSize>P</CellSize>
                <Pkgs>1</Pkgs>
                <CustId>'.$data['CustId'].'</CustId>
                <Name></Name>
                <Add1/>
                <Add2/>
                <City_Name/>
                <Zip/>
                <Cell_phone>'.$data['Cell_phone'].'</Cell_phone>
                <Phone2/>
                <Mail/>
                <Cust_Mail/>
                <Reference/>
                <Enginner_Code/>
                </ByBoxOrderRest>
        ';

        \Yii::error($body);

        $result = $this->makeApiCall('/ByBox/Service/OnRampByBoxService.svc/DoConsumerDeliveryRest', 'post', $body);

        if ($result && $result['response']){
            if (strpos($result['response'], '<Success>false</Success>') !== false){
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }

    }

    /**
     * method makes direct API calls
     * @param $endpoint
     * @param string $method
     * @param array $data
     * @return array|mixed
     * @throws \Exception
     */
    public function makeApiCall($endpoint, $method = 'get', $data = array()){

        if (!$method){
            $method = 'get';
        } else {
            $method = mb_strtolower($method);
        }

        //Init curl
        $curl = new curl\Curl();

        \Yii::error(\Yii::$app->params['boxit_api_url'].$endpoint);

        switch ($method){

            case 'get':
                $result = $curl->get(\Yii::$app->params['boxit_api_url'].$endpoint);
                break;

            case 'post':
                $curl->setOption(CURLOPT_HTTPHEADER,
                    array(
                        'Content-Type: text/xml; charset=utf-8',
                    ));
                $result = $curl->setOption(
                    CURLOPT_POSTFIELDS,
                    is_array($data) ? http_build_query($data) : $data
                )->post(\Yii::$app->params['boxit_api_url'].$endpoint);
                break;

            default:
                throw new \Exception('BoxIt: method not allowed');
                break;

        }

        $this->_lastResponse = $result = array(
            'code' => $curl->responseCode,
            'response' => $result
        );


        \Yii::error($this->_lastResponse);

        unset($curl);

        return $result;

    }

} 