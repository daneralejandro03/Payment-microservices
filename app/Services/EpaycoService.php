<?php

namespace App\Services;

use App\Models\SessionToken;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use App\Models\CardToken;
use App\Services\CustomSession;

class EpaycoService
{
    protected $client;
    protected $publicKey;
    protected $privateKey;
    protected $apiUrl;
    protected $username;
    protected $password;
    protected $entityClientId;

    public function __construct()
    {
        $this->client = new Client();
        $this->publicKey = env('EPAYCO_PUBLIC_KEY');
        $this->privateKey = env('EPAYCO_PRIVATE_KEY');
        $this->apiUrl = env('EPAYCO_API_URL');
        $this->username = env('USER');
        $this->password = env('PASSWORD');
        $this->entityClientId = env('P_CUST_ID_CLIENTE');

        // Logging the values to ensure they are being read correctly
        Log::info('Public Key: ' . $this->publicKey);
        Log::info('Private Key: ' . $this->privateKey);
        Log::info('API URL: ' . $this->apiUrl);
        Log::info('Username: ' . $this->username);
        Log::info('Password: ' . $this->password);
    }

    //CREAR SESSIÓN PARA EL CLIENTE CUANDO INGRESA
    public function createPaymentSession($data)
    {
        try {
            $response = $this->client->request('POST', $this->apiUrl . '/payment/session/create', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->getBearerToken(), // Obtener el token de autenticación
                ],
                'json' => $data,
            ]);

            $statusCode = $response->getStatusCode();
            $responseData = json_decode($response->getBody()->getContents(), true);

            if ($statusCode === 200) {
                if (isset($responseData['data']['sessionId'])) {
                    return $responseData['data']['sessionId'];
                } else {
                    return [
                        'error' => true,
                        'message' => 'La respuesta no contiene un sessionId',
                    ];
                }
            } else {
                return [
                    'error' => true,
                    'message' => 'La solicitud no fue exitosa',
                ];
            }
        } catch (RequestException $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    //AUNTENTICA AL CLIENTE Y SE LOGGEA DIRECTAMENTE
    public function authenticateAndCreateSession($sessionData)
    {
        try {
            // Autenticación
            $bearerToken = $this->authenticateDirect();
            if (isset($bearerToken['error'])) {
                return $bearerToken;
            }

            // Crear sesión de pago
            $response = $this->client->request('POST', $this->apiUrl . '/payment/session/create', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $bearerToken,
                ],
                'json' => $sessionData,
            ]);

            $statusCode = $response->getStatusCode();
            $responseData = json_decode($response->getBody()->getContents(), true);

            Log::info('Response data from payment session creation: ', $responseData);

            if ($statusCode === 200 && isset($responseData['data']['sessionId'])) {
                // Guardar token y sessionId en la base de datos
                $this->storeBearerTokenSessionId($responseData['data']['sessionId'], $bearerToken);

                return $responseData['data']['sessionId'];
            } else {
                return [
                    'error' => true,
                    'message' => $responseData['message'] ?? 'Session creation failed',
                ];
            }
        } catch (RequestException $e) {
            Log::error('Error creating payment session: ' . $e->getMessage());
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }


    //AUTENTICACIÓN PARA OBTENER EL TOKEN POR MEDIO DE CREDENCIALES DE LA PLATAFORMA
    public function authenticate($username, $password)
    {
        try {
            $response = $this->client->request('POST', $this->apiUrl . '/login/mail', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Basic ' . base64_encode($username . ':' . $password),
                    'public_key' => $this->publicKey,
                ],
                'json' => [],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['token'])) {
                return $data['token'];
            } else {
                return [
                    'error' => true,
                    'message' => 'La respuesta no contiene un token',
                ];
            }
        } catch (RequestException $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    //SE OBTIENE EL TOKEN DIRECTAMENTE SIN NECECIDAD DE LOGGEARSE ES MUY UTIL
    public function authenticateDirect()
    {
        try {
            // Log the username and password for debugging
            Log::info('Attempting to authenticate with Username: ' . $this->username);
            Log::info('Password: ' . $this->password);

            $response = $this->client->request('POST', $this->apiUrl . '/login/mail', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Basic ' . base64_encode($this->username . ':' . $this->password),
                    'public_key' => $this->publicKey,
                ],
                'json' => [],
            ]);

            $responseData = $response->getBody()->getContents();
            Log::info('Response Data: ' . $responseData);

            $data = json_decode($responseData, true);

            if (isset($data['token'])) {
                return $data['token'];
            } else {
                return [
                    'error' => true,
                    'message' => 'La respuesta no contiene un token',
                ];
            }
        } catch (RequestException $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    // ES PARA OBTENER EL TOKEN DIRECTAMENTE PERO TIENE ERRORES ¡NO USAR! SOLO INFORMATIVA
    public function requestJwtLogin()
    {
        try {
            Log::info('Public Key: ' . $this->publicKey);
            Log::info('Private Key: ' . $this->privateKey);
            Log::info('API URL: ' . $this->apiUrl);
            Log::info('Entity Client ID: ' . $this->entityClientId);

            $response = $this->client->request('POST', $this->apiUrl . '/login', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Basic ' . base64_encode($this->publicKey . ':' . $this->privateKey),
                    'EntityClientId' => $this->entityClientId,
                ],
                'json' => [],
            ]);

            $statusCode = $response->getStatusCode();
            $data = json_decode($response->getBody()->getContents(), true);

            if ($statusCode === 200) {
                if (isset($data['token'])) {
                    return $data['token'];
                } else {
                    return [
                        'error' => true,
                        'message' => 'La respuesta no contiene un token',
                    ];
                }
            } else {
                return [
                    'error' => true,
                    'message' => 'La solicitud no fue exitosa',
                ];
            }
        } catch (RequestException $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    //OBTERNER TOKEN POR ID DE SESSION ASOCIADO AL CLIENTE
    public function storeBearerTokenSessionId($sessionId, $token)
    {
        $customSession = new CustomSession();
        $customSession->storeToken($sessionId, $token);
        Log::info('Bearer token stored in session for email ' . $sessionId . ': ' . $token);
    }

    public function getBearerTokenSessionId($sessionId)
    {
        $customSession = new CustomSession();
        $token = $customSession->getToken($sessionId);
        Log::info('Bearer token retrieved from session for email ' . $sessionId . ': ' . $token);
        return $token;
    }

    // OBTENER TOKEN ID AOSCIACIÓN AL CLIENTE EN EL MOMENTO
    public function storeBearerToken($token)
    {
        $customSession = new CustomSession();
        $customSession->storeToken('epayco_bearer_token', $token);
        Log::info('Bearer token stored in session: ' . $token);
    }

    public function getBearerToken()
    {
        $customSession = new CustomSession();
        $token = $customSession->getToken('epayco_bearer_token');
        Log::info('Bearer token retrieved from session: ' . $token);
        return $token;
    }

    // PROCESA EL PAGO POR TARJETA DE CREDITO DE 1 SOLO CLIENTE POR QUE NO HAY SESSIÓN
    public function processCreditCardTransaction($data)
    {
        try {
            $bearerToken = $this->getBearerToken();
            Log::info("Bearer token: " . $bearerToken);

            if (!$bearerToken) {
                throw new \Exception('Bearer token is not available. Please log in.');
            }

            Log::info('Bearer token received: ' . $bearerToken);

            $cardToken = CardToken::where('email', $data['email'])->first();
            Log::info('Card token found: ', ['cardToken' => $cardToken]);

            if ($cardToken) {
                $data['customerId'] = $cardToken->customerId;
                $data['cardTokenId'] = $cardToken->cardTokenId;
                unset($data['cardNumber']);
                unset($data['cardExpYear']);
                unset($data['cardExpMonth']);
                unset($data['cardCvc']);
            }

            $response = $this->client->request('POST', $this->apiUrl . '/payment/process', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $bearerToken,
                ],
                'json' => $data,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            Log::info('Response data: ', $responseData);

            if (!$cardToken && isset($responseData['data']['tokenCard'])) {
                $tokenCard = $responseData['data']['tokenCard'];
                CardToken::create([
                    'email' => $tokenCard['email'],
                    'customerId' => $tokenCard['customerId'] ?? 'N/A',
                    'cardTokenId' => $tokenCard['cardTokenId']
                ]);
            }

            return $responseData;
        } catch (RequestException $e) {
            Log::error('Error processing credit card transaction: ' . $e->getMessage());
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    // PROCESA EL PAGO DE MUCHOS CLIENTES POR QUE UN CLIENTE YA TIENE UNA SESIÓN DE IDENTIFICACIÓN UNICA
    public function processCreditCardTransactionSession($data, $sessionId)
    {
        try {
            $bearerToken = $this->getBearerTokenSessionId($sessionId);
            Log::info("Bearer token: " . $bearerToken);

            if (!$bearerToken) {
                throw new \Exception('Bearer token is not available. Please log in.');
            }

            Log::info('Bearer token received: ' . $bearerToken);

            $cardToken = CardToken::where('email', $data['email'])->first();
            Log::info('Card token found: ', ['cardToken' => $cardToken]);

            if ($cardToken) {
                $data['customerId'] = $cardToken->customerId;
                $data['cardTokenId'] = $cardToken->cardTokenId;
                unset($data['cardNumber']);
                unset($data['cardExpYear']);
                unset($data['cardExpMonth']);
                unset($data['cardCvc']);
            }

            $response = $this->client->request('POST', $this->apiUrl . '/payment/process', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $bearerToken,
                ],
                'json' => $data,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            Log::info('Response data: ', $responseData);

            if (!$cardToken && isset($responseData['data']['tokenCard'])) {
                $tokenCard = $responseData['data']['tokenCard'];
                CardToken::create([
                    'email' => $tokenCard['email'],
                    'customerId' => $tokenCard['customerId'] ?? 'N/A',
                    'cardTokenId' => $tokenCard['cardTokenId']
                ]);
            }

            return $responseData;
        } catch (RequestException $e) {
            Log::error('Error processing credit card transaction: ' . $e->getMessage());
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    //PAGO POR DAVIPLATA SESSION
    public function createDaviplataPaymentSession($data, $sessionId)
    {
        try {
            $response = $this->client->request('POST', $this->apiUrl . '/payment/process/daviplata', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->getBearerTokenSessionId($sessionId) // Obtener el token de autenticación
                ],
                'json' => $data,
            ]);

            $statusCode = $response->getStatusCode();
            $responseData = json_decode($response->getBody()->getContents(), true);

            if ($statusCode === 200) {
                if (isset($responseData)) {
                    return $responseData;
                } else {
                    return [
                        'error' => true,
                        'message' => 'La respuesta no contiene un sessionId',
                    ];
                }
            } else {
                return [
                    'error' => true,
                    'message' => 'La solicitud no fue exitosa',
                ];
            }
        } catch (RequestException $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    // SESIÓN DE PAGO POR PSE
    public function createPSEPaymentSession($data, $sessionId)
    {
        try {
            $response = $this->client->request('POST', $this->apiUrl . '/payment/process/pse', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->getBearerTokenSessionId($sessionId) // Obtener el token de autenticación
                ],
                'json' => $data,
            ]);

            $statusCode = $response->getStatusCode();
            $responseData = json_decode($response->getBody()->getContents(), true);

            if ($statusCode === 200) {
                if (isset($responseData)) {
                    return $responseData;
                } else {
                    return [
                        'error' => true,
                        'message' => 'La respuesta no contiene un sessionId',
                    ];
                }
            } else {
                return [
                    'error' => true,
                    'message' => 'La solicitud no fue exitosa',
                ];
            }
        } catch (RequestException $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

}
