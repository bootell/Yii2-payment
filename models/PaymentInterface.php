<?php

namespace bootell\payment\models;

interface PaymentInterface
{
    /**
     * 创建订单
     *
     * @param array $order
     */
    public function createPayment($order);

    /**
     * 查询订单
     *
     * @param string $order_id
     */
    public function checkPayment($order_id);

    /**
     * 关闭订单
     *
     * @param string $order_id
     */
    public function closePayment($order_id);
}