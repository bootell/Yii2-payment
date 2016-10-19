<?php
namespace bootell\payment\models;

use Yii;
use yii\base\Exception;

class Wxpay extends BasePayment implements PaymentInterface
{
    protected $unifiedOrderUrl = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
    protected $orderQueryUrl = 'https://api.mch.weixin.qq.com/pay/orderquery';
    protected $closeOrderUrl = 'https://api.mch.weixin.qq.com/pay/closeorder';

    /**
     * 统一下单
     * https://pay.weixin.qq.com/wiki/doc/api/app/app.php?chapter=9_1
     *
     * @param array $order
     * @return array
     */
    public function createPayment($order)
    {
        $this->setOrder($order);

        $params = $this->setUnifiedOrderParams();
        $xml = $this->toXml($params);
        $responseXml = $this->postRequest($this->unifiedOrderUrl, $xml);
        $response = $this->toArray($responseXml);
        return $response;
    }

    /**
     * 查询订单
     * https://pay.weixin.qq.com/wiki/doc/api/app/app.php?chapter=9_2&index=4
     *
     * @param string $order_id
     * @return array
     */
    public function checkPayment($order_id)
    {
        $params = $this->setOrderQueryParams();
        $xml = $this->toXml($params);
        $responseXml = $this->postRequest($this->orderQueryUrl, $xml);
        $response = $this->toArray($responseXml);
        return $response;
    }

    /**
     * 关闭订单
     * https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_3
     *
     * @param array $order_id
     * @return boolean
     */
    public function closePayment($order_id)
    {
        $params = $this->setOrderQueryParams();
        $xml = $this->toXml($params);
        $responseXml = $this->postRequest($this->closeOrderUrl, $xml);
        $response = $this->toArray($responseXml);
        if (!$this->checkSign($response)) return false;
        return $response['result_code'] == 'SUCCESS';
    }

    /**
     * 支付结果通知
     * https://pay.weixin.qq.com/wiki/doc/api/app/app.php?chapter=9_7&index=3
     *
     * @param $code
     * @param $msg
     * @return \SimpleXMLElement
     */
    public function orderResponse($code, $msg = 'OK')
    {
        if ($code) {
            $params = [
                'return_code' => 'SUCCESS',
                'return_msg' => $msg,
            ];
        } else {
            $params = [
                'return_code' => 'FAIL',
                'return_msg' => $msg,
            ];
        }
        $xml = $this->toXml($params);
        return $xml;
    }


    protected function setUnifiedOrderParams()
    {
        $params = [
            'appid' => $this->config['appid'],
            'mch_id' => $this->config['mch_id'],
            'device_info' => 'WEB',
            'nonce_str' => Yii::$app->getSecurity()->generateRandomString(32),
            'body' => $this->order['name'],
            'out_trade_no' => $this->order['id'],
            'total_fee' => $this->order['money'],
            'spbill_create_ip' => Yii::$app->request->getUserIP(),
            'notify_url' => $this->config['notify_url'],
            'trade_type' => 'NATIVE',
        ];
        $params['sign'] = strtoupper($this->signParams($params, '&key=' . $this->config['key']));

        return $params;
    }

    protected function setOrderQueryParams()
    {
        $params = [
            'appid' => $this->config['appid'],
            'mch_id' => $this->config['mch_id'],
            'out_trade_no' => $this->order['id'],
            'nonce_str' => Yii::$app->getSecurity()->generateRandomString(32),
        ];
        $params['sign'] = strtoupper($this->signParams($params, '&key=' . $this->config['key']));

        return $params;
    }

    protected function setCloseOrderParams()
    {
        $params = [
            'appid' => $this->config['appid'],
            'mch_id' => $this->config['mch_id'],
            'out_trade_no' => $this->order['id'],
            'nonce_str' => Yii::$app->getSecurity()->generateRandomString(32),
        ];
        $params['sign'] = strtoupper($this->signParams($params, '&key=' . $this->config['key']));
    }

    protected static function toXml($array)
    {
        $xml = new \simpleXMLElement('<xml></xml>');
        foreach ($array as $key => $value) {
            $xml->addChild($key, $value);
        }
        return $xml->saveXML();
    }

    public static function toArray($xml) {
        $simple_xml = new \SimpleXMLElement($xml, LIBXML_NOCDATA);
        return (array)$simple_xml;
    }

    protected function checkSign($params)
    {
        $signPars = '';
        ksort($params);
        foreach ($params as $key => $value) {
            if ($key != 'sign' && $value != '') {
                $signPars .= $key . "=" . $value . "&";
            }
        }
        $signPars .= "key=" . $this->config['secret'];

        $sign = strtoupper(md5($signPars));

        return $sign == $params['sign'];
    }
}