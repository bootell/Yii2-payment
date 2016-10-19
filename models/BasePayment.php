<?php

namespace bootell\payment\models;

use yii\base\Exception;

class BasePayment
{
    protected $config;
    protected $order;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function setOrder($order)
    {
        if (!array_key_exists('id', $order) || !array_key_exists('name', $order) || !array_key_exists('name', $order)) {
            throw new Exception('Wrong order params.');
        }
        return $this->order = $order;
    }

    public function signParams($params, $secret)
    {
        ksort($params);
        $sign = '';
        foreach ($params as $key => $value) {
            if (empty($value)) continue;
            $sign = $sign . $key . '=' . $value . '&';
        }
        $sign = substr($sign, 0, - 1);
        $sign .= $secret;

        if ($params['sign_type'] == 'RSA') {
            $priKey = openssl_get_privatekey(file_get_contents($this->config['private_key_path']));
            openssl_sign($sign, $result, $priKey);
            openssl_free_key($priKey);
            $result = base64_encode($result);
        } else {
            $result = md5($sign);
        }

        return $result;
    }

    public function postRequest($url, $params)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Payment Client 1.0');
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    public function getRequest($url, $params)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CAINFO, __DIR__ . '/cacert.pem');
        curl_setopt($curl, CURLOPT_USERAGENT, 'Payment Client 1.0');
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        $url = $url . '?' . http_build_query($params);
        curl_setopt($curl, CURLOPT_URL, $url);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
}