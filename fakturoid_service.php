<?php
if (!defined('APP_ACCESS')) {
    http_response_code(403);
    header('location: ../template/not-found-page.php');
}
session_start();

class FakturoidService {
    private $client;
    
    public function __construct(FakturoidClient $client) {
        $this->client = $client;
    }
    
    public function createInvoiceFromOrder($order) {
        $subject = $this->createOrFindSubject($order);
        
        $timezone = new DateTimeZone('Europe/Prague');
        $currentDate = new DateTime('now', $timezone);

        // Urči splatnost podle způsobu platby
        $paymentMethod = $order['payment_method'] ?? 'bank_transfer';

        switch ($paymentMethod) {
            case 'card':
            case 'online_card':
                $dueDays = 0; // Splatnost ihned
                $paymentMethodValue = 'card';
                $showBankDetails = false;
                break;

            case 'Dobírka':
            case 'cash_on_delivery':
                $dueDays = 0; // Dobírka se platí ihned při převzetí
                $paymentMethodValue = 'cod';
                $showBankDetails = false;
                break;

            case 'bank_transfer':
            default:
                $dueDays = 14; // Splatnost 14 dní
                $paymentMethodValue = 'bank';
                $showBankDetails = true;
                break;
        }

        $invoiceData = [
            'subject_id' => $subject['id'],
            'lines' => $this->prepareInvoiceLines($order),
            'due' => $dueDays, // Splatnost v dnech
            'issued_on' => $currentDate->format('Y-m-d'),
            'payment_method' => $paymentMethodValue, // Způsob platby
            'note' => $order['note'] ?? null
        ];

        $result = $this->client->createInvoice($invoiceData);
        
        if ($result['success']) {
            $invoice = $result['data'];
            
            // Stáhni a ulož PDF faktury
            $pdfSaved = $this->saveInvoicePDF($invoice);
            
            // Uložit číslo faktury do objednávky
            $this->updateOrderWithInvoice($order['order_number'], $invoice, $pdfSaved);
            
            return [
                'success' => true,
                'invoice' => $invoice,
                'pdf_saved' => $pdfSaved
            ];
        }
        
        throw new Exception('Failed to create invoice: ' . json_encode($result['data']));
    }
    
    /**
     * Vytvoří nebo najde kontakt v Fakturoid
     */
    private function createOrFindSubject($order) {
        $subjectData = $this->prepareSubjectData($order);

        // Zkus najít existujícího zákazníka
        $existingSubject = $this->findSubjectByEmail($order['customer_email']);

        if ($existingSubject) {
            // Aktualizuj existujícího zákazníka s aktuálními údaji z objednávky
            $result = $this->client->createOrUpdateSubject(
                array_merge(['id' => $existingSubject['id']], $subjectData)
            );

            if ($result['success']) {
                return $result['data'];
            }

            throw new Exception('Failed to update subject: ' . json_encode($result['data']));
        }

        // Pokud neexistuje, vytvoř nový
        $result = $this->client->createOrUpdateSubject($subjectData);

        if ($result['success']) {
            return $result['data'];
        }

        throw new Exception('Failed to create subject: ' . json_encode($result['data']));
    }

    
    /**
     * Najde kontakt podle emailu
     */
    private function findSubjectByEmail($email) {
        $result = $this->client->getSubjects(['email' => $email]);
        
        if ($result['success'] && !empty($result['data'])) {
            return $result['data'][0];
        }
        
        return null;
    }
    
    /**
     * Aktualizuje objednávku s informacemi o faktuře
     */
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
    
    /**
     * Připraví data zákazníka
     */
    private function prepareSubjectData($order) {
        $subject = [
            'name' => $order['customer_name'],
            'street' => $order['billing_address'],
            'city' => $order['billing_city'],
            'zip' => $order['billing_zip'],
            'country' => $order['billing_country'] ?? 'CZ',
            'email' => $order['customer_email']
        ];
        
        // Pokud je to firma, přidej IČO/DIČ
        if (!empty($order['company_name'])) {
            $subject['name'] = $order['company_name'];
            $subject['registration_no'] = $order['company_ico'] ?? null;
            $subject['vat_no'] = $order['company_dic'] ?? null;
        }
        
        return $subject;
    }
    
    /**
     * Připraví položky faktury
     */
    private function prepareInvoiceLines($order) {
        $lines = [];
        $groupedItems = [];
                
        // Seskup stejné položky podle názvu a ceny
        foreach ($order['items'] as $item) {
            $key = $item['name'] . '_' . $item['price']; // Klíč pro seskupení
            
            if (isset($groupedItems[$key])) {
                // Přičti množství k existující položce
                $groupedItems[$key]['quantity'] += isset($item['quantity']) ? $item['quantity'] : 1;
            } else {
                // Vytvoř novou položku
                $groupedItems[$key] = [
                    'name' => $item['name'],
                    'quantity' => isset($item['quantity']) ? $item['quantity'] : 1,
                    'price' => $item['price'],
                    'vat_rate' => $item['vat_rate'] ?? 0,
                    'unit' => $item['unit'] ?? 'ks'
                ];
            }
        }
        
        // Převeď seskupené položky na formát pro fakturu
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
    
    /**
     * Stáhne a uloží PDF faktury do složky invoices
     */
    private function saveInvoicePDF($invoice) {
    try {
        // Získej absolutní cestu k aktuálnímu adresáři
        $currentDir = getcwd();
        $invoiceDir = $currentDir . DIRECTORY_SEPARATOR . 'invoices';
        
        // Zkontroluj a vytvoř složku invoices
        if (!is_dir($invoiceDir)) {
            
            if (!mkdir($invoiceDir, 0755, true)) {
                $error = "Failed to create directory: " . $invoiceDir;
                return ['success' => false, 'error' => $error];
            }
            
        }
        
        // Zkontroluj oprávnění pro zápis
        if (!is_writable($invoiceDir)) {
            $error = "Directory is not writable: " . $invoiceDir;
            return ['success' => false, 'error' => $error];
        }
        
        // Vygeneruj název souboru
        $filename = $this->generateInvoiceFilename($invoice);
        $filepath = $invoiceDir . DIRECTORY_SEPARATOR . $filename;
        
        // Implementuj retry mechanismus - PDF může potřebovat čas na generování
        $maxRetries = 5;
        $retryDelay = 2; // sekundy
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {            
            // Stáhni PDF z Fakturoid
            $result = $this->client->downloadInvoicePDF($invoice['id']);
            
            // Pokud je HTTP 204, PDF ještě není připravené
            if ($result['status'] === 204) {
                
                if ($attempt < $maxRetries) {
                    sleep($retryDelay);
                    $retryDelay *= 2; // Exponential backoff
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
            
            // Pokud je jiná chyba než 204, ukončí retry
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
            
            // Pokud je odpověď úspěšná, zpracuj PDF
            $pdfContent = $result['data'];
            
            // Zkontroluj, jestli je odpověď prázdná
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
            
            // Zkontroluj content-type
            $contentType = $result['content_type'] ?? '';
            if (strpos($contentType, 'application/pdf') === false && 
                strpos($contentType, 'application/octet-stream') === false) {
                
                // Pokud je to JSON s chybou
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
            
            // Pokud je to string a vypadá jako base64, dekóduj
            if (is_string($pdfContent) && base64_encode(base64_decode($pdfContent, true)) === $pdfContent) {
                $pdfContent = base64_decode($pdfContent);
            }
            
            // Validuj, že je to skutečně PDF
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

            
            // Ulož soubor
            $saved = file_put_contents($filepath, $pdfContent);
            
            if ($saved !== false && $saved > 0) {
                // Ověř, že se soubor skutečně uložil
                if (file_exists($filepath)) {
                    $actualSize = filesize($filepath);
                    
                    // Zkontroluj oprávnění souboru
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
    
    /**
     * Vygeneruje název souboru pro fakturu
     */
    private function generateInvoiceFilename($invoice) {
        $invoiceNumber = $invoice['number'] ?? $invoice['id'];
        $date = date('Y-m-d');
        
        // Pokusíme se získat jméno zákazníka z různých možných míst
        $customerName = 'Unknown';
        if (isset($invoice['subject']['name'])) {
            $customerName = $invoice['subject']['name'];
        } elseif (isset($invoice['subject_name'])) {
            $customerName = $invoice['subject_name'];
        } elseif (isset($invoice['client_name'])) {
            $customerName = $invoice['client_name'];
        }
        
        
        $customerName = $this->sanitizeFilename($customerName);
        
        // Formát: faktura_2024001_2024-01-15_Jan-Novak.pdf
        return "faktura_{$invoiceNumber}_{$date}_{$customerName}.pdf";
    }
    
    /**
     * Vyčistí název souboru od nepovolených znaků
     */
    private function sanitizeFilename($filename) {
        // Odstraň diakritiku
        $filename = iconv('UTF-8', 'ASCII//TRANSLIT', $filename);
        
        // Nahraď nepovolené znaky
        $filename = preg_replace('/[^a-zA-Z0-9\-_]/', '-', $filename);
        
        // Odstraň vícenásobné pomlčky
        $filename = preg_replace('/-+/', '-', $filename);
        
        // Ořízni pomlčky ze začátku a konce
        $filename = trim($filename, '-');
        
        // Omez délku
        return substr($filename, 0, 50);
    }
    
    /**
     * Označí fakturu jako zaplacenou
     */
    public function markInvoicePaid($invoiceId, $paymentAmount, $paymentDate = null) {
        $paymentData = [
            'paid_amount' => $paymentAmount,
            'paid_on' => $paymentDate ?? date('Y-m-d')
        ];
        
        return $this->client->markInvoicePaid($invoiceId, $paymentData);
    }
    
    /**
     * Získá PDF faktury jako base64
     */
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
    
    /**
     * Provede HTTP požadavek na Fakturoid API
     */
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
    
    /**
     * Vytvorí novou fakturu
     */
    public function createInvoice($invoiceData) {
        return $this->makeRequest('/invoices', 'POST', $invoiceData);
    }
    
    /**
     * Získá fakturu podle ID
     */
    public function getInvoice($invoiceId) {
        return $this->makeRequest("/invoices/{$invoiceId}");
    }
    
    /**
     * Získá seznam faktur
     */
    public function getInvoices($params = []) {
        $query = !empty($params) ? '?' . http_build_query($params) : '';
        return $this->makeRequest("/invoices{$query}");
    }
    
    /**
     * Stáhne PDF faktury
     */
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Zvýšený timeout pro PDF
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
    
    /**
     * Oznací fakturu jako zaplacenou
     */
    public function markInvoicePaid($invoiceId, $paymentData) {
        return $this->makeRequest("/invoices/{$invoiceId}/payments", 'POST', $paymentData);
    }
    
    /**
     * Vytvorí nebo aktualizuje kontakt
     */
    public function createOrUpdateSubject($subjectData) {
        return $this->makeRequest('/subjects', 'POST', $subjectData);
    }
    
    /**
     * Získá seznam kontaktů
     */
    public function getSubjects($params = []) {
        $query = !empty($params) ? '?' . http_build_query($params) : '';
        return $this->makeRequest("/subjects{$query}");
    }
    
    /**
     * Získá informace o účtu
     */
    public function getAccount() {
        return $this->makeRequest('/account');
    }
}

class TokenManager {
    private $db;
    private $config;
    
    public function __construct($database, $config) {
        $this->db = $database;
        $this->config = $config;
    }
    
    /**
     * Získá platný access token (automaticky obnoví pokud vypršel)
     */
    public function getValidAccessToken() {
        $token = $this->getStoredToken();
        
        if (!$token) {
            throw new Exception('No token found. Please authorize first.');
        }
        
        // Zkontroluj zda token nevypršel (s rezervou 5 minut)
        if (time() >= ($token['expires_at'] - 300)) {
            return $this->refreshToken($token['refresh_token']);
        }
        
        return $token['access_token'];
    }
    
    /**
     * Získá uložený token z databáze
     */
    private function getStoredToken() {
        $stmt = $this->db->prepare("SELECT * FROM oauth_tokens WHERE service = 'fakturoid' ORDER BY updated_at DESC LIMIT 1");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obnoví access token pomocí refresh token
     */
    private function refreshToken($refreshToken) {
        $data = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken
        ];
        
        $auth = base64_encode($this->config['client_id'] . ':' . $this->config['client_secret']);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://app.fakturoid.cz/api/v3/oauth/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . $auth,
            'User-Agent: ' . $this->config['user_agent']
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
    
    /**
     * Uloží nové tokeny do databáze
     */
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

// Inicializace
include 'config.php';
$config = include 'fakturoid_config.php';

$tokenManager = new TokenManager($db, $config);
$fakturoidClient = new FakturoidClient($tokenManager, 'beatafraibisova');
$fakturoidService = new FakturoidService($fakturoidClient);

// Objednávky
$order = [
    'order_number' => $_SESSION['user_info']['order_number'] ?? '', // nebo $_SESSION['order_number']
    'note' => 'Děkujeme za vaši objednávku!',

    // Zákaznické údaje
    'customer_name' => $_SESSION['user_info']['name'] . ' ' . $_SESSION['user_info']['surname'],
    'customer_email' => $_SESSION['user_info']['email'],
    'billing_address' => $_SESSION['user_info']['street'] . ' ' . $_SESSION['user_info']['housenumber'],
    'billing_city' => $_SESSION['user_info']['city'],
    'billing_zip' => $_SESSION['user_info']['zipcode'],
    'billing_country' => $_SESSION['user_info']['country'] ?? 'Česká republika',

    // Položky objednávky
    'items' => $_SESSION['bought_items'] ?? [],

    // Doprava
    'shipping_method' => $_SESSION['methodData']['shipping']['name'] ?? '',
    'shipping_price' => floatval($_SESSION['methodData']['shipping']['price'] ?? 0),

    // Platba (volitelné)
    'payment_method' => $_SESSION['methodData']['payment']['name'] ?? '',
    'payment_price' => floatval($_SESSION['methodData']['payment']['price'] ?? 0),

    // Zásilkovna - pokud je zvolená
    'zasilkovna_branch_id' => $_SESSION['zasilkovna_branch'] ?? '',
    'zasilkovna_branch_name' => $_SESSION['zasilkovna_branch_name'] ?? ''
];
// Použití
try {
    $result = $fakturoidService->createInvoiceFromOrder($order);
    
    if ($result['success']) {
        $invoice = $result['invoice'];
        $pdfSaved = $result['pdf_saved'];
        
        
        if ($pdfSaved['success']) {
            if($order['payment_method'] == 'Dobírka'){
                header('location: action/thankyou.php');
                die();
            }else{
                header('location: action/checkout.php');
                die();
            }
        } else {
            error_log("Chyba při ukládání PDF: {$pdfSaved['error']}\n");

        }
    }
    
} catch (Exception $e) {
    error_log("Fakturoid error: " . $e->getMessage());
}
?>