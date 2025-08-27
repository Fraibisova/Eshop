<?php

namespace App\Services;

use PDO;
use Exception;
use DateTime;
use DateTimeZone;

class FakturoidService {
    private FakturoidClient $client;
    private ConfigurationService $config;
    
    public function __construct() {
        $this->config = ConfigurationService::getInstance();
        $db = DatabaseService::getInstance()->getConnection();
        $tokenManager = new TokenManager($db, $this->config);
        $fakturoidConfig = $this->config->getFakturoidConfig();
        $this->client = new FakturoidClient($tokenManager, $fakturoidConfig['account']);
    }
    
    public function createInvoiceFromOrder($order) {
        $subject = $this->createOrFindSubject($order);
        
        $timezone = new DateTimeZone('Europe/Prague');
        $currentDate = new DateTime('now', $timezone);

        $paymentMethod = $order['payment_method'] ?? 'bank_transfer';

        switch ($paymentMethod) {
            case 'card':
            case 'online_card':
                $dueDays = 0; 
                $paymentMethodValue = 'card';
                $showBankDetails = false;
                break;

            case 'Dobírka':
            case 'cash_on_delivery':
                $dueDays = 0;
                $paymentMethodValue = 'cod';
                $showBankDetails = false;
                break;

            case 'bank_transfer':
            default:
                $dueDays = 14;
                $paymentMethodValue = 'bank';
                $showBankDetails = true;
                break;
        }

        $invoiceData = [
            'subject_id' => $subject['id'],
            'lines' => $this->prepareInvoiceLines($order),
            'due' => $dueDays,
            'issued_on' => $currentDate->format('Y-m-d'),
            'payment_method' => $paymentMethodValue,
            'note' => $order['note'] ?? null
        ];

        $result = $this->client->createInvoice($invoiceData);
        
        if ($result['success']) {
            $invoice = $result['data'];
            
            $pdfSaved = $this->saveInvoicePDF($invoice);
            
            $this->updateOrderWithInvoice($order['order_number'], $invoice, $pdfSaved);
            
            return [
                'success' => true,
                'invoice' => $invoice,
                'pdf_saved' => $pdfSaved
            ];
        }
        
        throw new Exception('Failed to create invoice: ' . json_encode($result['data']));
    }
    
    private function createOrFindSubject($order) {
        $subjectData = $this->prepareSubjectData($order);

        $existingSubject = $this->findSubjectByEmail($order['customer_email']);

        if ($existingSubject) {
            $result = $this->client->createOrUpdateSubject(
                array_merge(['id' => $existingSubject['id']], $subjectData)
            );

            if ($result['success']) {
                return $result['data'];
            }

            throw new Exception('Failed to update subject: ' . json_encode($result['data']));
        }

        $result = $this->client->createOrUpdateSubject($subjectData);

        if ($result['success']) {
            return $result['data'];
        }

        throw new Exception('Failed to create subject: ' . json_encode($result['data']));
    }

    
    private function findSubjectByEmail($email) {
        $result = $this->client->getSubjects(['email' => $email]);
        
        if ($result['success'] && !empty($result['data'])) {
            return $result['data'][0];
        }
        
        return null;
    }
    
    private function updateOrderWithInvoice($order_number, $invoice, $pdfSaved) {
        global $db;

        $stmt = $db->prepare("
            UPDATE orders_user 
            SET 
                fakturoid_invoice_id = ?, 
                fakturoid_invoice_url = ?, 
                invoice_pdf_path = ?, 
                invoice_created_at = NOW()
            WHERE id = ?
        ");

        $success = $stmt->execute([
            $invoice['id'],
            $invoice['number'],
            $pdfSaved['success'] ? $pdfSaved['filepath'] : null,
            $order_number
        ]); 

    }
    
    private function prepareSubjectData($order) {
        $subject = [
            'name' => $order['customer_name'],
            'street' => $order['billing_address'],
            'city' => $order['billing_city'],
            'zip' => $order['billing_zip'],
            'country' => $order['billing_country'] ?? 'CZ',
            'email' => $order['customer_email']
        ];
        
        if (!empty($order['company_name'])) {
            $subject['name'] = $order['company_name'];
            $subject['registration_no'] = $order['company_ico'] ?? null;
            $subject['vat_no'] = $order['company_dic'] ?? null;
        }
        
        return $subject;
    }
    
    private function prepareInvoiceLines($order) {
        $lines = [];
        $groupedItems = [];
                
        foreach ($order['items'] as $item) {
            $key = $item['name'] . '_' . $item['price']; 
            
            if (isset($groupedItems[$key])) {
                $groupedItems[$key]['quantity'] += isset($item['quantity']) ? $item['quantity'] : 1;
            } else {
                $groupedItems[$key] = [
                    'name' => $item['name'],
                    'quantity' => isset($item['quantity']) ? $item['quantity'] : 1,
                    'price' => $item['price'],
                    'vat_rate' => $item['vat_rate'] ?? 0,
                    'unit' => $item['unit'] ?? 'ks'
                ];
            }
        }
        
        foreach ($groupedItems as $item) {
            $lines[] = [
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
                'vat_rate' => $item['vat_rate'],
                'unit_name' => $item['unit']
            ];
        }
        
        if (!empty($order['shipping_price']) && $order['shipping_price'] > 0) {
            $lines[] = [
                'name' => $order['shipping_method'] ?? 'Doprava',
                'quantity' => 1,
                'unit_price' => $order['shipping_price'],
                'vat_rate' => 0,
                'unit_name' => 'služba'
            ];
        }
        if (!empty($order['payment_price']) && $order['payment_price'] > 0) {
            $lines[] = [
                'name' => $order['payment_method'] ?? 'Dobírka',
                'quantity' => 1,
                'unit_price' => $order['payment_price'],
                'vat_rate' => 0,
                'unit_name' => 'poplatek'
            ];
        }
        return $lines;
    }
    
    private function saveInvoicePDF($invoice) {
    try {
        $currentDir = getcwd();
        $invoiceDir = $currentDir . DIRECTORY_SEPARATOR . 'invoices';
        
        if (!is_dir($invoiceDir)) {
            
            if (!mkdir($invoiceDir, 0755, true)) {
                $error = "Failed to create directory: " . $invoiceDir;
                return ['success' => false, 'error' => $error];
            }
            
        }
        
        if (!is_writable($invoiceDir)) {
            $error = "Directory is not writable: " . $invoiceDir;
            return ['success' => false, 'error' => $error];
        }
        
        $filename = $this->generateInvoiceFilename($invoice);
        $filepath = $invoiceDir . DIRECTORY_SEPARATOR . $filename;
        
        $maxRetries = 5;
        $retryDelay = 2; // sekundy
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {            
            $result = $this->client->downloadInvoicePDF($invoice['id']);
            
            if ($result['status'] === 204) {
                
                if ($attempt < $maxRetries) {
                    sleep($retryDelay);
                    $retryDelay *= 2; 
                    continue;
                } else {
                    return [
                        'success' => false, 
                        'error' => 'PDF generation timeout - tried ' . $maxRetries . ' times',
                        'debug_info' => [
                            'current_dir' => $currentDir,
                            'invoice_dir' => $invoiceDir,
                            'filepath' => $filepath
                        ]
                    ];
                }
            }
            
            if (!$result['success']) {
                return [
                    'success' => false, 
                    'error' => 'Failed to download PDF: HTTP ' . $result['status'],
                    'debug_info' => [
                        'current_dir' => $currentDir,
                        'invoice_dir' => $invoiceDir,
                        'filepath' => $filepath
                    ]
                ];
            }
            
            $pdfContent = $result['data'];
            
            if (empty($pdfContent)) {
                
                if ($attempt < $maxRetries) {
                    sleep($retryDelay);
                    $retryDelay *= 2;
                    continue;
                } else {
                    return [
                        'success' => false, 
                        'error' => 'PDF content is empty after all retries',
                        'debug_info' => [
                            'current_dir' => $currentDir,
                            'invoice_dir' => $invoiceDir,
                            'filepath' => $filepath
                        ]
                    ];
                }
            }
            
            $contentType = $result['content_type'] ?? '';
            if (strpos($contentType, 'application/pdf') === false && 
                strpos($contentType, 'application/octet-stream') === false) {
                
                if (strpos($contentType, 'application/json') !== false || 
                    (is_string($pdfContent) && substr($pdfContent, 0, 1) === '{')) {
                    
                    $jsonError = json_decode($pdfContent, true);
                    return [
                        'success' => false, 
                        'error' => 'PDF download returned error: ' . ($jsonError['message'] ?? 'Unknown error'),
                        'debug_info' => [
                            'current_dir' => $currentDir,
                            'invoice_dir' => $invoiceDir,
                            'filepath' => $filepath
                        ]
                    ];
                }
            }
            
            if (is_string($pdfContent) && base64_encode(base64_decode($pdfContent, true)) === $pdfContent) {
                $pdfContent = base64_decode($pdfContent);
            }
            
            if (is_string($pdfContent) && substr($pdfContent, 0, 4) !== '%PDF') {
                
                if ($attempt < $maxRetries) {
                    sleep($retryDelay);
                    $retryDelay *= 2;
                    continue;
                } else {
                    return [
                        'success' => false, 
                        'error' => 'Downloaded content is not a valid PDF',
                        'debug_info' => [
                            'current_dir' => $currentDir,
                            'invoice_dir' => $invoiceDir,
                            'filepath' => $filepath,
                            'content_preview' => substr($pdfContent, 0, 200)
                        ]
                    ];
                }
            }

            
            $saved = file_put_contents($filepath, $pdfContent);
            
            if ($saved !== false && $saved > 0) {
                if (file_exists($filepath)) {
                    $actualSize = filesize($filepath);
                    
                    $perms = substr(sprintf('%o', fileperms($filepath)), -4);
                    
                    return [
                        'success' => true,
                        'filepath' => $filepath,
                        'filename' => $filename,
                        'size' => $saved,
                        'actual_size' => $actualSize,
                        'attempts' => $attempt,
                        'permissions' => $perms,
                        'debug_info' => [
                            'current_dir' => $currentDir,
                            'invoice_dir' => $invoiceDir,
                            'file_exists' => true
                        ]
                    ];
                } else {
                    return [
                        'success' => false, 
                        'error' => 'file_put_contents succeeded but file not found on disk',
                        'debug_info' => [
                            'current_dir' => $currentDir,
                            'invoice_dir' => $invoiceDir,
                            'filepath' => $filepath,
                            'bytes_written' => $saved
                        ]
                    ];
                }
            } else {
                $lastError = error_get_last();
                
                if ($attempt < $maxRetries) {
                    sleep($retryDelay);
                    $retryDelay *= 2;
                    continue;
                } else {
                    return [
                        'success' => false, 
                        'error' => 'Failed to write PDF file after all retries. Last error: ' . ($lastError['message'] ?? 'Unknown'),
                        'debug_info' => [
                            'current_dir' => $currentDir,
                            'invoice_dir' => $invoiceDir,
                            'filepath' => $filepath,
                            'last_error' => $lastError
                        ]
                    ];
                }
            }
        }
        
        return [
            'success' => false, 
            'error' => 'Maximum retry attempts reached',
            'debug_info' => [
                'current_dir' => $currentDir,
                'invoice_dir' => $invoiceDir,
                'filepath' => $filepath
            ]
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false, 
            'error' => $e->getMessage(),
            'debug_info' => [
                'current_dir' => getcwd(),
                'invoice_dir' => $invoiceDir ?? 'not set',
                'filepath' => $filepath ?? 'not set'
            ]
        ];
    }
}
    
    private function generateInvoiceFilename($invoice) {
        $invoiceNumber = $invoice['number'] ?? $invoice['id'];
        $date = date('Y-m-d');
        
        $customerName = 'Unknown';
        if (isset($invoice['subject']['name'])) {
            $customerName = $invoice['subject']['name'];
        } elseif (isset($invoice['subject_name'])) {
            $customerName = $invoice['subject_name'];
        } elseif (isset($invoice['client_name'])) {
            $customerName = $invoice['client_name'];
        }
        
        
        $customerName = $this->sanitizeFilename($customerName);
        
        return "faktura_{$invoiceNumber}_{$date}_{$customerName}.pdf";
    }
    
    private function sanitizeFilename($filename) {
        $filename = iconv('UTF-8', 'ASCII//TRANSLIT', $filename);
        
        $filename = preg_replace('/[^a-zA-Z0-9\-_]/', '-', $filename);
        
        $filename = preg_replace('/-+/', '-', $filename);
        
        $filename = trim($filename, '-');
        
        return substr($filename, 0, 50);
    }
    
    public function markInvoicePaid($invoiceId, $paymentAmount, $paymentDate = null) {
        $paymentData = [
            'paid_amount' => $paymentAmount,
            'paid_on' => $paymentDate ?? date('Y-m-d')
        ];
        
        return $this->client->markInvoicePaid($invoiceId, $paymentData);
    }
    
    public function getInvoicePDF($invoiceId) {
        $result = $this->client->downloadInvoicePDF($invoiceId);
        
        if ($result['success']) {
            return $result['data'];
        }
        
        throw new Exception('Failed to download invoice PDF');
    }
}

class FakturoidClient {
    private $tokenManager;
    private $subdomain;
    private $baseUrl;
    
    public function __construct(TokenManager $tokenManager, $subdomain) {
        $this->tokenManager = $tokenManager;
        $this->subdomain = $subdomain;
        $this->baseUrl = "https://app.fakturoid.cz/api/v3/accounts/{$subdomain}";
    }
    
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        $accessToken = $this->tokenManager->getValidAccessToken();
        
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
            'User-Agent: YourApp (info@touchthemagic.com)'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('cURL error: ' . $error);
        }
        
        $decodedResponse = json_decode($response, true);
        
        return [
            'status' => $httpCode,
            'data' => $decodedResponse,
            'success' => $httpCode >= 200 && $httpCode < 300
        ];
    }
    
    public function createInvoice($invoiceData) {
        return $this->makeRequest('/invoices', 'POST', $invoiceData);
    }
    
    public function getInvoice($invoiceId) {
        return $this->makeRequest("/invoices/{$invoiceId}");
    }
    
    public function getInvoices($params = []) {
        $query = !empty($params) ? '?' . http_build_query($params) : '';
        return $this->makeRequest("/invoices{$query}");
    }
    
    public function downloadInvoicePDF($invoiceId) {
        $url = $this->baseUrl . "/invoices/{$invoiceId}/download.pdf";
        $accessToken = $this->tokenManager->getValidAccessToken();
        
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'User-Agent: YourApp (info@touchthemagic.com)'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('cURL error: ' . $error);
        }
        
        return [
            'status' => $httpCode,
            'data' => $response,
            'success' => $httpCode >= 200 && $httpCode < 300 && !empty($response),
            'content_type' => $contentType
        ];
    }
    
    public function markInvoicePaid($invoiceId, $paymentData) {
        return $this->makeRequest("/invoices/{$invoiceId}/payments", 'POST', $paymentData);
    }
    
    public function createOrUpdateSubject($subjectData) {
        return $this->makeRequest('/subjects', 'POST', $subjectData);
    }
    
    public function getSubjects($params = []) {
        $query = !empty($params) ? '?' . http_build_query($params) : '';
        return $this->makeRequest("/subjects{$query}");
    }
    
    public function getAccount() {
        return $this->makeRequest('/account');
    }
}

class TokenManager {
    private PDO $db;
    private ConfigurationService $config;
    
    public function __construct(PDO $database, ConfigurationService $config) {
        $this->db = $database;
        $this->config = $config;
    }
    
    public function getValidAccessToken() {
        $token = $this->getStoredToken();
        
        if (!$token) {
            throw new Exception('No token found. Please authorize first.');
        }
        
        if (time() >= ($token['expires_at'] - 300)) {
            return $this->refreshToken($token['refresh_token']);
        }
        
        return $token['access_token'];
    }

    private function getStoredToken() {
        $stmt = $this->db->prepare("SELECT * FROM oauth_tokens WHERE service = 'fakturoid' ORDER BY updated_at DESC LIMIT 1");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function refreshToken($refreshToken) {
        $data = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken
        ];
        
        $fakturoidConfig = $this->config->getFakturoidConfig();
        $auth = base64_encode($fakturoidConfig['client_id'] . ':' . $fakturoidConfig['client_secret']);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://app.fakturoid.cz/api/v3/oauth/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . $auth,
            'User-Agent: TouchTheMagic (info@touchthemagic.com)'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('cURL error during token refresh: ' . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception('Failed to refresh token. HTTP Code: ' . $httpCode . ', Response: ' . $response);
        }
        
        $tokens = json_decode($response, true);
        
        if (!$tokens || !isset($tokens['access_token'])) {
            throw new Exception('Invalid token response: ' . $response);
        }
        
        $this->saveTokens($tokens);
        
        return $tokens['access_token'];
    }
    
    public function saveTokens($tokens) {
        $expiresAt = time() + $tokens['expires_in'];
        
        $stmt = $this->db->prepare("
            INSERT INTO oauth_tokens (service, access_token, refresh_token, expires_at, created_at, updated_at) 
            VALUES ('fakturoid', :access_token, :refresh_token, :expires_at, NOW(), NOW())
            ON DUPLICATE KEY UPDATE 
            access_token = :access_token, 
            refresh_token = COALESCE(:refresh_token, refresh_token), 
            expires_at = :expires_at,
            updated_at = NOW()
        ");
        
        $stmt->execute([
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'] ?? null,
            'expires_at' => $expiresAt
        ]);
    }
}
