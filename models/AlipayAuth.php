<?php

namespace bootell\payment\models;

trait AlipayAuth
{
    protected $authUrl = 'https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?app_id=%s&scope=%s&redirect_uri=%s';
    protected $alipayGateway = 'https://openapi.alipay.com/gateway.do';

    /**
     * 获取支付宝认证授权地址
     *
     * @param string $auth_redirect
     * @return string
     */
    public function getAuthUrl($auth_redirect = null)
    {
        $auth_redirect = $auth_redirect ?: $this->config['auth_redirect'];
        return sprintf($this->authUrl, $this->config['app_id'], 'auth_userinfo', urlencode($auth_redirect));
    }

    /**
     * 通过 auth_code 换取 access_token
     *
     * @param string $code
     * @return array
     */
    public function getAccessToken($code)
    {
        $params = $this->setAccessTokenParams($code);
        $response = $this->postRequest($this->alipayGateway, $params);
        $result = json_decode($response, true);

        return $result['alipay_system_oauth_token_response'];
    }

    /**
     * 获取支付宝用户信息
     *
     * @param string $token
     * @return array
     */
    public function getUserInfo($token)
    {
        $params = $this->setUserInfoParams($token);
        $response = $this->postRequest($this->alipayGateway, $params);
        $result = json_decode($response, true);

        // todo 返回结果验签
        // $this->checkSign($result['alipay_user_userinfo_share_response'], $result['sign']);

        return $result['alipay_user_userinfo_share_response'];
    }

    protected function setAccessTokenParams($code)
    {
        $params = [
            'app_id' => $this->config['app_id'],
            'method' => 'alipay.system.oauth.token',
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => 'RSA',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'grant_type' => 'authorization_code',
            'code' => $code,
        ];
        $params['sign'] = $this->signParams($params, '');

        return $params;
    }

    protected function setUserInfoParams($token)
    {
        $params = [
            'app_id' => $this->config['app_id'],
            'method' => 'alipay.user.userinfo.share',
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => 'RSA',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'grant_type' => 'authorization_code',
            'auth_token' => $token,
        ];
        $params['sign'] = $this->signParams($params, '');

        return $params;
    }
}