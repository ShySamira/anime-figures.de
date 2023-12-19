<?php

namespace App\Service;

use App\Model\Amount;
use App\Model\PaypalOrder;
use stdClass;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class PayPal
{
    private $session;
    private $parameters;
    private $serializer;
    private $client;
    private $router;

    public function __construct(SessionInterface $session, ParameterBagInterface $parameters, EncoderInterface $encoder, NormalizerInterface $normalizer, UrlGeneratorInterface $router)
    {
        $this->client = HttpClient::create();
        $this->session = $session;
        $this->parameters = $parameters;
        $this->serializer = new Serializer([new ObjectNormalizer()], [$encoder]);
        $this->router = $router;
    }

    public function getAccessToken() : string
    {
        if($this->session->has('paypal.acces.token') && 
            $this->session->has('paypal.acces.token.expires') &&
            $this->session->get('paypal.acces.token.expires') >= new \DateTime())
        {
            return $this->session->get('paypal.acces.token');
        }

        $authenticationResponse = $this->client->request('POST', $this->parameters->get('paypal.base.url') . '/v1/oauth2/token', [
            'auth_basic' => [$this->parameters->get('paypal.client.id'), $this->parameters->get('paypal.client.secret')],
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => [
                'grant_type' => 'client_credentials',
            ],
        ]);
        $authenticationResponse->getContent();

        $data = $this->serializer->decode($authenticationResponse->getContent(), 'json');

        $accessToken = $data['access_token'];
        $accessTokenExpires = new \DateTime();
        $accessTokenExpires->modify('+' . $data['expires_in'] . ' second');

        $this->session->set('paypal.acces.token', $accessToken);
        $this->session->set('paypal.acces.token.expires', $accessTokenExpires);

        return $accessToken;
    }

    public function createOrder(string $accessToken, string $invoiceId, string $orderamountObject)
    {
        $purchase_units = new stdClass();
        $purchase_units->amount = new stdClass();
        $purchase_units->amount->currency_code = 'EUR';
        $purchase_units->amount->value = 100;

        $paymentSource = new stdClass();
        $paymentSource->paypal = new stdClass();
        $paymentSource->paypal->experience_context = new stdClass();
        $paymentSource->paypal->experience_context->brand_name = 'Anime-Figures';
        $paymentSource->paypal->experience_context->user_action = 'PAY_NOW';
        $paymentSource->paypal->experience_context->return_url = 'https://127.0.0.1:8000' . $this->router->generate('orderProcess_complete');
        $paymentSource->paypal->experience_context->cancel_url = 'https://127.0.0.1:8000' . $this->router->generate('orderProcess_shipment');

        $data = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                $purchase_units
            ],
            "payment_source" => $paymentSource
        ];

        $dataString = $this->serializer->encode($data, 'json');

        $orderResponse = $this->client->request('POST', $this->parameters->get('paypal.base.url') . '/v2/checkout/orders', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ],
            'body' => $dataString            
        ]);

        $orderResponse = $this->serializer->decode($orderResponse->getContent(), 'json');

        $links = $orderResponse['links'];
        
        $checkoutLink = $links[1]['href'];

        return $checkoutLink;
    }

    public function capturePayment()
    {

    }
}

