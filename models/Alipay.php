<?php

namespace bootell\payment\models;

use yii\base\Exception;

class Alipay extends BasePayment implements PaymentInterface
{
    use AlipayAuth;

    protected $directPayUrl = 'https://mapi.alipay.com/gateway.do';

    /**
     * 获取创建即时到账地址
     *
     * @param array $order
     * @return string
     * @throws Exception
     */
    public function createPayment($order)
    {
        $this->setOrder($order);

        $params = $this->setDirectPayParams();
        $str = '';
        foreach ($params as $key => $value) {
            $str .= $key . '=' . $value . '&';
        }
        $str = substr($str, 0, - 1);
        $url = $this->directPayUrl . '?' . $str;

        return $url;
    }

    /**
     * 查询交易订单状态
     *
     * @param string $order_id
     */
    public function checkPayment($order_id)
    {
        // TODO: Implement checkPayment() method.
    }

    /**
     * 关闭即时到账
     *
     * @param string $order_id
     * @return boolean
     */
    public function closePayment($order_id)
    {
        $this->order['id'] = $order_id;
        $params = $this->setCloseDirectPayParams();
        $response = $this->postRequest($this->directPayUrl, $params);
        $result = json_decode($response, true);
        return $result['trade_status'] == 'TRADE_CLOSED';
    }

    /**
     * 支付宝通知检测
     *
     * @param string $notify_id
     * @return boolean
     */
    public function notifyVerify($notify_id)
    {
        $params = [
            'service' => 'notify_verify',
            'partner' => $this->config['partner'],
            'notify_id' => $notify_id,
        ];
        $response = $this->getRequest($this->directPayUrl, $params);

        if ($response == 'true') {
            return true;
        }
        return false;
    }

    protected function setDirectPayParams()
    {
        $params = [
            'service' => 'create_direct_pay_by_user',
            'partner' => $this->config['partner'],
            '_input_charset' => 'UTF-8',
            'notify_url' => $this->config['notify_url'],
            'return_url' => $this->config['return_url'],
            'out_trade_no' => $this->order['id'],
            'subject' => $this->order['name'],
            'payment_type' => 1,
            'total_fee' => $this->order['money'] / 100.0,
            'seller_email' => $this->config['seller_email'],
        ];
        $params['sign'] = $this->signParams($params, $this->config['key']);
        $params['sign_type'] = 'MD5';

        return $params;
    }

    protected function setCloseDirectPayParams()
    {
        $params = [
            'service' => 'close_direct_pay_by_user',
            'partner' => $this->config['partner'],
            '_input_charset' => 'UTF-8',
            'out_trade_no' => $this->order['id'],
        ];
        $params['sign'] = $this->signParams($params, $this->config['key']);
        $params['sign_type'] = 'MD5';

        return $params;
    }

    public function checkSign($params, $sign)
    {
        ksort($params);
        $str = '';
        foreach ($params as $key => $param) {
            if ($key == 'sign' || $key == 'sign_type') continue;
            $str .= $key . '=' . $param . '&';
        }
        $str = substr($str, 0, - 1);
        $str .= $this->config['key'];

        switch ($params['sign_type']) {
            case 'MD5':
                return md5($str) == $sign;
            case 'RSA':
                $pubKey = openssl_pkey_get_public(file_get_contents($this->config['alipay_pubic_key']));
                return openssl_verify($str, $sign, $pubKey, 'sha1WithRSAEncryption');
            default:
                return false;
        }
    }
}