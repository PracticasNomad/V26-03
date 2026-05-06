<?php
// Archivo: includes/PaylandsClient.php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Dotenv\Dotenv;

class PaylandsClient
{
    private $baseUrlPayment;
    private $baseUrlWallet;
    private $username;
    private $password;
    private $clientId;     // <--- NUEVO
    private $clientSecret; // <--- NUEVO
    private $token;

    public function __construct()
    {
        $dotenv = Dotenv::createImmutable(dirname(__DIR__));
        $dotenv->safeLoad();

        if (empty($_ENV['PAYLANDS_USER']) || empty($_ENV['PAYLANDS_PASS'])) {
            throw new Exception("ERROR CRÍTICO: Credenciales no encontradas en el archivo .env");
        }

        $this->baseUrlPayment = $_ENV['PAYLANDS_BASE_URL'] ?? 'https://api.paylands.com/v1/sandbox';
        $this->baseUrlWallet  = $_ENV['PAYNOPAIN_WALLET_URL'] ?? 'https://preproduccion.paynopain.com:3443/changeit-wallet-api-payment-entity';
        $this->username       = $_ENV['PAYLANDS_USER'];
        $this->password       = $_ENV['PAYLANDS_PASS'];
        $this->clientId       = $_ENV['PAYLANDS_CLIENT_ID'] ?? '';     // <--- NUEVO
        $this->clientSecret   = $_ENV['PAYLANDS_CLIENT_SECRET'] ?? ''; // <--- NUEVO
    }

    /**
     * Obtiene el token usando la API de Wallet (ChangeIt)[cite: 4]
     */
    private function getAccessToken()
    {
        if ($this->token) return $this->token;

        $url = $this->baseUrlWallet . '/oauth/v2/token';

        // ¡Aquí es donde le mandamos lo que nos estaba pidiendo!
        $payload = [
            "grant_type"    => "https://changeit.paynopain.com/server/password",
            "username"      => $this->username,
            "password"      => $this->password,
            "client_id"     => $this->clientId,     // <--- AÑADIDO
            "client_secret" => $this->clientSecret  // <--- AÑADIDO
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json']
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);
        if ($httpCode >= 200 && $httpCode < 300 && isset($data['access_token'])) {
            $this->token = $data['access_token'];
            return $this->token;
        } else {
            throw new Exception("Error Autenticación Paylands: " . $response);
        }
    }

    /**
     * Hace la petición a la API. Permite elegir si usamos la URL de Payments o la de Wallet
     */
    public function request($endpoint, $method = 'GET', $payload = null, $useWalletApi = false)
    {
        $token = $this->getAccessToken();

        // Seleccionamos la URL correcta según la operación
        $baseUrl = $useWalletApi ? $this->baseUrlWallet : $this->baseUrlPayment;
        $url = $baseUrl . $endpoint;

        $ch = curl_init($url);
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ];

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers
        ];

        if ($payload !== null) {
            $options[CURLOPT_POSTFIELDS] = json_encode($payload);
        }

        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['status' => $httpCode, 'data' => json_decode($response, true)];
    }

    /**
     * Registra un anfitrión como Autónomo en Paylands usando la API Wallet[cite: 4]
     */
    public function registrarAutonomo($datos)
    {
        $payload = [
            "business_name" => $datos['nombre_completo'], //[cite: 4]
            "trading_name" => $datos['nombre_comercial'], //[cite: 4]
            "corporate_type" => "FREELANCE", //[cite: 4]
            "send_for_revision" => true, //[cite: 4]
            "address" => [
                "country" => "ES",
                "province" => $datos['provincia'], //[cite: 4]
                "city" => $datos['localidad'], //[cite: 4]
                "street_name" => $datos['direccion'], //[cite: 4]
                "zip_code" => $datos['codigo_postal'] //[cite: 4]
            ],
            "email" => $datos['email'], //[cite: 4]
            "id_num" => null, //[cite: 4]
            "contact" => [
                "name" => $datos['nombre_completo'], //[cite: 4]
                "phone" => $datos['telefono'], //[cite: 4]
                "email" => $datos['email'], //[cite: 4]
                "is_other_person_authority" => true, //[cite: 4]
                "document_id" => "4789" // MCC para tu sector[cite: 4]
            ],
            "kyb" => [
                "registration_number" => $datos['dni'], //[cite: 4]
                "vat_number" => null,
                "industry" => null,
                "registration_document" => null,
                "member_identification" => null,
                "bank_statement" => null,
                "terminals_number" => 1,
                "terminal_delivery_address" => [
                    "country" => "ES",
                    "province" => $datos['provincia'],
                    "city" => $datos['localidad'],
                    "street_name" => $datos['direccion'],
                    "zip_code" => $datos['codigo_postal']
                ],
                "documents" => [] // Opcional por ahora[cite: 4]
            ],
            "bank" => [
                "currency" => "EUR",
                "iban" => $datos['iban'], //[cite: 4]
                "name" => $datos['banco_nombre'] //[cite: 4]
            ],
            "merchant_data" => [
                "role" => "role_merchant", //[cite: 4]
                "status" => "ACTIVE", //[cite: 4]
                "password_auto_generation" => true, //[cite: 4]
                "profile" => [
                    "name" => $datos['nombre_pila'], //[cite: 4]
                    "surname" => $datos['apellidos'], //[cite: 4]
                    "email" => $datos['email'], //[cite: 4]
                    "phone" => $datos['telefono'] //[cite: 4]
                ]
            ],
            "location_data" => [
                "name" => $datos['nombre_completo'], //[cite: 4]
                "currency" => "EUR",
                "import_marketplace_data" => true //[cite: 4]
            ],
            "directors" => [
                [
                    "name" => $datos['nombre_pila'], //[cite: 4]
                    "surname" => $datos['apellidos'], //[cite: 4]
                    "document_id" => $datos['dni'], //[cite: 4]
                    "birth_date" => $datos['fecha_nacimiento'], //[cite: 4]
                    "address" => [
                        "street_name" => $datos['direccion'],
                        "zip_code" => $datos['codigo_postal'],
                        "province" => $datos['provincia'],
                        "city" => $datos['localidad'],
                        "country" => "ES"
                    ],
                    "document_type" => "IDENTITY", //[cite: 4]
                    "nationality_country" => "ES",
                    "country_issuing_document" => "ES",
                    "is_public_responsibility" => false,
                    "is_owner" => true,
                    "document_expiry_date" => $datos['caducidad_dni'], //[cite: 4]
                    "is_signator" => true
                ]
            ]
        ];

        // NOTA: Pasamos "true" al final para usar la API de Wallet (ChangeIt)[cite: 4]
        return $this->request('/business', 'POST', $payload, true);
    }


    /**
     * Registra una Gestora como Sociedad Mercantil en Paylands[cite: 5]
     */
    public function registrarEmpresa($datos)
    {
        $payload = [
            "business_name" => $datos['empresa'], //[cite: 5]
            "trading_name" => $datos['empresa'], //[cite: 5]
            "validation_required" => true, //[cite: 5]
            "corporate_type" => "BUSINESS", // ¡Clave! Esto le dice a Paylands que es una SL[cite: 5]
            "profile" => [
                "name" => $datos['empresa'], //[cite: 5]
                "language" => "es",
                "address" => [
                    "country" => "ES",
                    "province" => $datos['provincia'], //[cite: 5]
                    "city" => $datos['localidad'], //[cite: 5]
                    "street_name" => $datos['direccion'], //[cite: 5]
                    "zip_code" => $datos['codigo_postal'] //[cite: 5]
                ],
                "email" => $datos['email'], //[cite: 5]
                "id_num" => null //[cite: 5]
            ],
            "contact" => [
                "name" => $datos['representante_nombre'], //[cite: 5]
                "phone" => $datos['telefono'], //[cite: 5]
                "email" => $datos['email'], //[cite: 5]
                "is_other_person_authority" => true,
                "document_id" => "4789" // MCC[cite: 5]
            ],
            "kyb" => [
                "registration_number" => $datos['cif'], //[cite: 5]
                "vat_number" => null,
                "industry" => null,
                "registration_document" => null,
                "member_identification" => null,
                "bank_statement" => null,
                "terminals_number" => 1,
                "terminal_delivery_address" => [
                    "country" => "ES",
                    "province" => $datos['provincia'],
                    "city" => $datos['localidad'],
                    "street_name" => $datos['direccion'],
                    "zip_code" => $datos['codigo_postal']
                ]
            ],
            "bank" => [
                "currency" => "EUR",
                "iban" => $datos['iban'], //[cite: 5]
                "name" => $datos['empresa'] //[cite: 5]
            ],
            "merchant_data" => [
                "role" => "role_merchant", //[cite: 5]
                "status" => "ACTIVE", //[cite: 5]
                "password_auto_generation" => true, //[cite: 5]
                "profile" => [
                    "name" => $datos['representante_nombre'], //[cite: 5]
                    "surname" => $datos['representante_apellidos'], //[cite: 5]
                    "email" => $datos['email'], //[cite: 5]
                    "phone" => $datos['telefono'] //[cite: 5]
                ]
            ],
            "directors" => [
                [
                    "name" => $datos['representante_nombre'], //[cite: 5]
                    "surname" => $datos['representante_apellidos'], //[cite: 5]
                    "document_id" => $datos['representante_dni'], //[cite: 5]
                    "birth_date" => $datos['representante_nacimiento'], //[cite: 5]
                    "address" => [
                        "street_name" => $datos['direccion'],
                        "zip_code" => $datos['codigo_postal'],
                        "province" => $datos['provincia'],
                        "city" => $datos['localidad'],
                        "country" => "ES"
                    ],
                    "document_type" => "IDENTITY", //[cite: 5]
                    "nationality_country" => "ES",
                    "country_issuing_document" => "ES",
                    "is_public_responsibility" => false,
                    "is_owner" => true,
                    "document_expiry_date" => $datos['representante_caducidad_dni'], //[cite: 5]
                    "is_signator" => true,
                    "work_position" => "ADMINISTRATOR" //[cite: 5]
                ]
            ]
        ];

        return $this->request('/business', 'POST', $payload, true);
    }
}
