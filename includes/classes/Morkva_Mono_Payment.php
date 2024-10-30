<?php
# Include namespaces
namespace MorkvaMonoGateway;

/**
 * Class Morkva_Mono_Payment file
 * */
class Morkva_Mono_Payment 
{
    /**
     * @var string Monopay token
     * */
    private $mrkv_mono_token;

    /**
     * @param object Order object
     * */
    protected $mrkv_mono_order;

    /**
     * @param string Order refund
     * */
    protected $mrkv_mono_refund_order = null;

    /**
     * @var string Api url
     * */
    const MRKV_MONO_API_URL = "https://api.monobank.ua/api/merchant";

    /**
     * Constructor for the gateway
     * */
    public function __construct($token) 
    {
        # Set data
        $this->mrkv_mono_token = $token;
    }

    /**
     * Send request to mono
     * @var string Endpoint url
     * @param array All request data
     * @var integer Invoice id
     * 
     * @return array Request response
     * */
    protected function mrkv_mono_apiRequest($endpoint, $post_fields, $invoice_id = null) 
    {
        # Create request url 
        $mrkv_mono_url = self::MRKV_MONO_API_URL . $endpoint;

        # Check endpoint
        if ($endpoint == "/invoice/status" && $invoice_id) 
        {
            # Change url
            $mrkv_mono_url .= "/$invoice_id";
        }

        # Create request header
        $mrkv_mono_headers = array(
            'Content-type'  => 'application/json',
            'X-Token' => $this->mrkv_mono_token,
            'X-Cms' => 'morkva'
        );

        # Create request body
        $mrkv_mono_body = apply_filters('convertkit-call-args', $post_fields);

        # Create request args
        $mrkv_mono_args = array(
            'method'      => ($endpoint == "/invoice/status") ? 'GET' : 'POST',
            'body'        => json_encode($mrkv_mono_body),
            'headers'     => $mrkv_mono_headers,
            'user-agent'  => 'WooCommerce/' . WC()->version,
        );

        # Send request
        $mrkv_mono_request = wp_safe_remote_post($mrkv_mono_url, $mrkv_mono_args);

        # Check request status
        if ($mrkv_mono_request === false) 
        {
            # Show error
            throw new \Exception("Connection error");
        }

        # Return answer
        return json_decode($mrkv_mono_request['body']);
    }

    /**
     * Set order data
     * @param object Order data
     * */
    public function mrkv_mono_setOrder($order) 
    {
        # Set data
        $this->mrkv_mono_order = $order;
    }

    /**
     * Create request
     * @return array Request response
     * */
    public function mrkv_mono_create() 
    {   
        # Create request body
        $mrkv_mono_body = array(
            'amount' => $this->mrkv_mono_order->mrkv_mono_getAmount(),
            'ccy' => $this->mrkv_mono_order->mrkv_mono_getCurrency(),
            'merchantPaymInfo' => array(
                'reference' => $this->mrkv_mono_order->mrkv_mono_getReference(),
                'destination' => $this->mrkv_mono_order->mrkv_mono_getDestination(),
                'basketOrder' => $this->mrkv_mono_order->mrkv_mono_getBasketOrder(),
            ),
            'redirectUrl' => $this->mrkv_mono_order->mrkv_mono_getRedirectUrl(),
            'webHookUrl' => $this->mrkv_mono_order->mrkv_mono_getWebHookUrl()
        );

        # Send data to request
        $mrkv_mono_response = $this->mrkv_mono_apiRequest("/invoice/create", $mrkv_mono_body);

        # Return response
        return $mrkv_mono_response;
    }

    /**
     * Get status
     * */
    public function mrkv_mono_getStatus() {}

    /**
     * Set Refund order
     * @var string Refund order
     * */
    public function mrkv_mono_setRefundOrder($refund_order) 
    {
        # Set data
        $this->mrkv_mono_refund_order = $refund_order;
    }

    /**
     * Send cancel request
     * @return array Request response
     * */
    public function mrkv_mono_cancel() 
    {   
        # Send request
        $mrkv_mono_response = $this->mrkv_mono_apiRequest("/cancel", $this->mrkv_mono_refund_order);

        # Return response
        return $mrkv_mono_response;
    }

}