<?php
namespace App\Client;

use Exception;

class AmoCrmV4Client
{
    private string $subDomain;
    private string $clientId;
    private string $clientSecret;
    private string $code;
    private string $redirectUri;
    private string $accessToken;
    private string $tokenFile;

    public function __construct(array $config)
    {
        $this->subDomain = $config['sub_domain'];
        $this->clientId = $config['client_id'];
        $this->clientSecret = $config['client_secret'];
        $this->code = $config['code'];
        $this->redirectUri = $config['redirect_uri'];
        $this->tokenFile = $config['token_file'];

        if (file_exists($this->tokenFile)) {
            $tokenData = json_decode(file_get_contents($this->tokenFile), true);
            
            if ($tokenData['expires_in'] < time()) {
                // Токен истек - обновляем
                $this->refreshAccessToken($tokenData['refresh_token']);
            } else {
                // Токен валиден
                $this->accessToken = $tokenData['access_token'];
            }
        } else {
            // Токена нет - получаем новый
            $this->requestAccessToken();
        }
    }

    private function initializeToken(): void
    {
        if (file_exists($this->tokenFile)) {
            $tokenData = json_decode(file_get_contents($this->tokenFile), true);
            
            if ($tokenData['expires_in'] < time()) {
                $this->refreshAccessToken($tokenData['refresh_token']);
            } else {
                $this->accessToken = $tokenData['access_token'];
            }
        } else {
            $this->requestAccessToken();
        }
    }

    private function requestAccessToken(): void
    {
        $this->makeTokenRequest([
            'grant_type' => 'authorization_code',
            'code' => $this->code
        ]);
    }

    private function refreshAccessToken(string $refreshToken): void
    {
        $this->makeTokenRequest([
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken
        ]);
    }

    private function makeTokenRequest(array $data): void
    {
        $link = 'https://' . $this->subDomain . '.amocrm.ru/oauth2/access_token';
        
        $requestData = array_merge([
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri
        ], $data);

        $curl = curl_init();
        
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-oAuth-client/1.0');
        curl_setopt($curl, CURLOPT_URL, $link);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($requestData));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        
        $out = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        $code = (int)$code;
        $errors = [
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not found',
            500 => 'Internal server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable',
        ];

        try {
            if ($code < 200 || $code > 204) {
                throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
            }
        } catch (Exception $e) {
            echo "Response from API: " . $out . "\n";
            throw new Exception('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
        }

        $response = json_decode($out, true);
        
        if (!$response || !isset($response['access_token'])) {
            echo "Raw API Response: " . $out . "\n";
            throw new Exception('Неверный ответ от API при получении токена');
        }

        $this->accessToken = $response['access_token'];
        $this->saveToken($response);
    }

    private function saveToken(array $tokenData): void
    {
        $token = [
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'],
            'token_type' => $tokenData['token_type'],
            'expires_in' => time() + $tokenData['expires_in']
        ];

        file_put_contents($this->tokenFile, json_encode($token));
    }

    public function get(string $endpoint, array $params = []): array
    {
        $url = $this->buildUrl($endpoint, $params);
        return $this->executeApiRequest($url, 'GET');
    }

    public function post(string $endpoint, array $data = []): array
    {
        $url = $this->buildUrl($endpoint);
        return $this->executeApiRequest($url, 'POST', $data);
    }

    public function patch(string $endpoint, array $data = []): array
    {
        $url = $this->buildUrl($endpoint);
        return $this->executeApiRequest($url, 'PATCH', $data);
    }

    private function buildUrl(string $endpoint, array $params = []): string
    {
        $url = "https://{$this->subDomain}.amocrm.ru/api/v4/{$endpoint}";
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $url;
    }

    private function executeApiRequest(string $url, string $method, array $data = []): array
    {
        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ];

        return $this->executeCurlRequest($url, $method, $data, $headers);
    }

    private function executeCurlRequest(
        string $url, 
        string $method, 
        array $data = [], 
        array $headers = []
    ): array {
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => 'amoCRM-oAuth-client/1.0',
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        if (in_array($method, ['POST', 'PATCH', 'PUT']) && !empty($data)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        if (curl_errno($curl)) {
            throw new Exception('CURL Error: ' . curl_error($curl));
        }
        
        curl_close($curl);

        $this->handleHttpErrors($httpCode, $response);
        
        // Задержка для соблюдения лимитов API
        usleep(250000);

        return json_decode($response, true) ?? [];
    }

    private function handleHttpErrors(int $httpCode, string $response): void
    {
        if ($httpCode >= 200 && $httpCode <= 204) {
            return;
        }

        $errors = [
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not found',
            500 => 'Internal server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable',
        ];

        $errorMessage = $errors[$httpCode] ?? "Unknown error (HTTP {$httpCode})";
        throw new Exception("API Error: {$errorMessage}. Response: {$response}", $httpCode);
    }

    public function getAll(string $endpoint, array $params = []): array
    {
        $allItems = [];
        $page = 1;
        $limit = 250;

        do {
            $params['page'] = $page;
            $params['limit'] = $limit;
            
            $response = $this->get($endpoint, $params);
            
            // Для вложенных эндпоинтов типа leads/{id}/notes
            if (strpos($endpoint, '/') !== false) {
                // Разбиваем эндпоинт на части
                $parts = explode('/', $endpoint);
                $entity = end($parts); // 'notes'
                $items = $response['_embedded'][$entity] ?? $response ?? [];
            } else {
                // Для простых эндпоинтов типа 'leads', 'contacts'
                $items = $response['_embedded'][$endpoint] ?? [];
            }
            
            if (empty($items)) {
                break;
            }

            $allItems = array_merge($allItems, $items);
            $page++;
        } while (count($items) >= $limit);

        return $allItems;
    }
}