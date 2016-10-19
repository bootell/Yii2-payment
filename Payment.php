<?php

namespace bootell\payment;

use yii\base\Component;
use bootell\payment\models\Alipay;
use bootell\payment\models\Wxpay;

class Payment extends Component
{
    public $alipay;
    public $wxpay;

    public function alipay()
    {
        return new Alipay($this->alipay, get_called_class());
    }

    public function wxpay()
    {
        return new Wxpay($this->alipay, get_called_class());
    }
}