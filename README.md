Yii2 Payment
===

Yii2 alipay & wxpay

支付宝即时到账，微信扫码支付在Yii2下的封装

Configuration
---

``` php
return [
    'components' => [
        'payment' => [
            'class' => 'bootell\Payment',
            'alipay' => [
                // 支付宝认证
                'app_id' => '',
                'auth_redirect' => '',
            
                // 支付宝支付
                'partner' => '',
                'seller_id' => '',
                'seller_email' => '',
                'key' => '',
                'notify_url' => '',
                'return_url' => '',
                'private_key_path' => '',
                'alipay_pubic_key' => '',
            ],
            'wxpay' => [
                'appid' => '',
                'mch_id' => '',
                'key' => '',
                'secret' => '',
            ]
        ],
    ],
];
```

Usage
---

``` php
$alipay = Yii::$app->payment->alipay()->createPayment($order);
$wxpay = Yii::$app->payment->wxpay()->createPayment($order);
```