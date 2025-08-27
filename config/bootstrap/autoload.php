<?php

use App\Container\Container;
use App\Container\ServiceProvider;
spl_autoload_register(function ($class) {
    $base_dir = dirname(dirname(__DIR__)) . '/app/';
    
    $prefix = 'App\\';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

use App\Services\DatabaseService;
use App\Services\EmailService;
use App\Services\MailerService;
use App\Services\ConfigurationService;
use App\Services\AdminService;
use App\Services\AuthService;
use App\Services\CartService;
use App\Services\ProductService;
use App\Services\OrderService;
use App\Services\UserService;
use App\Services\VariantService;
use App\Services\NewsletterService;
use App\Services\AnalyticsService;
use App\Services\ValidationService;
use App\Services\EmailTemplateService;
use App\Utils\MessageHelper;
use App\Utils\BreadcrumbHelper;

function getDbService(): DatabaseService {
    return DatabaseService::getInstance();
}

function getConfigService(): ConfigurationService {
    return ConfigurationService::getInstance();
}

function getMailerService(): MailerService {
    return MailerService::getInstance();
}


function getDb(): PDO {
    return DatabaseService::getInstance()->getConnection();
}

function getPdo(): PDO {
    return getDb();
}

function getDatabaseConnection(): PDO {
    return getDb();
}

function config(string $key, mixed $default = null): mixed {
    return ConfigurationService::getInstance()->get($key, $default);
}


function validateEmail(string $email, PDO $pdo, ?string $oldEmail = null): array {
    return ValidationService::validateEmail($email, $pdo, $oldEmail);
}

function validatePassword(string $password, ?string $password_confirm = null): array {
    return ValidationService::validatePassword($password, $password_confirm);
}

function validateInput(string $input): string {
    return ValidationService::validateInput($input);
}




function is_locked_out(PDO $pdo, string $email): array {
    $authService = new AuthService();
    return $authService->isLockedOut($email);
}

function log_attempt(PDO $pdo, string $email, bool $success): void {
    $authService = new AuthService();
    $authService->logAttempt($email, $success);
}

function cleanup_expired_attempts(PDO $pdo): void {
    $authService = new AuthService();
    $authService->cleanupExpiredAttempts();
}

function authenticateUser(PDO $pdo, string $email, string $password): ?App\Models\User {
    $authService = new AuthService();
    return $authService->authenticateUser($email, $password);
}

function redirectBasedOnRole(int $role): void {
    $authService = new AuthService();
    $authService->redirectBasedOnRole($role);
}

function checkAdminRole(): void {
    $adminService = new AdminService();
    $adminService->checkAdminRole();
}

function checkUserAuthentication(): ?App\Models\User {
    $userService = new UserService();
    return $userService->checkUserAuthentication();
}

function getUserData(int $userId): ?array {
    $userService = new UserService();
    return $userService->getUserData($userId);
}

function renderAccountNavigation(): string {
    $userService = new UserService();
    return $userService->renderAccountNavigation();
}



function handleFileUpload(string $fileKey, string $uploadDir = 'uploads/'): string|false {
    $adminService = new AdminService();
    return $adminService->handleFileUpload($fileKey, $uploadDir);
}

function processProductUpdate($pdo, array $postData, string $currentImage = '', ?array $imageFile = null): bool {
    $adminService = new AdminService();
    return $adminService->processProductUpdate($postData, $currentImage, $imageFile);
}

function processProductInsert($pdo, array $postData, ?array $imageFile = null): int|false {
    $adminService = new AdminService();
    return $adminService->processProductInsert($postData, $imageFile);
}

function renderFilterForm(array $columns, array $getParams = []): string {
    $adminService = new AdminService();
    return $adminService->renderFilterForm($columns, $getParams);
}

function getCancellationEmailTemplate(string $order_number, string $total_amount = "0"): string {
    $adminService = new AdminService();
    return $adminService->getCancellationEmailTemplate($order_number, $total_amount);
}

function sendSimpleEmail(string $to, string $subject, string $html_content): bool {
    $adminService = new AdminService();
    return $adminService->sendSimpleEmail($to, $subject, $html_content);
}

function logMessage(string $message): void {
    $adminService = new AdminService();
    $adminService->logMessage($message);
}

function getNewsletterTemplate(): array {
    $newsletterService = new NewsletterService();
    return $newsletterService->getNewsletterTemplate();
}

function saveNewsletter(string $title, string $html_content, string $status, ?string $scheduled_at = null, ?int $id = null): int|false {
    $newsletterService = new NewsletterService();
    return $newsletterService->saveNewsletter($title, $html_content, $status, $scheduled_at, $id);
}

function sendNewsletterToSubscribers(int $newsletter_id, string $title, string $template_with_placeholders): int|false {
    $newsletterService = new NewsletterService();
    return $newsletterService->sendNewsletterToSubscribers($newsletter_id, $title, $template_with_placeholders);
}

function checkAndSendScheduledNewsletters(): int|false {
    $newsletterService = new NewsletterService();
    return $newsletterService->checkAndSendScheduledNewsletters();
}

function getAllNewsletters(): array {
    $newsletterService = new NewsletterService();
    return $newsletterService->getAllNewsletters();
}

function processNewsletterTemplate(array $templateData, array $postData): string {
    $newsletterService = new NewsletterService();
    return $newsletterService->processNewsletterTemplate($templateData, $postData);
}

function generatePreview(array $templateData, array $postData = []): string {
    $newsletterService = new NewsletterService();
    return $newsletterService->generatePreview($templateData, $postData);
}

function generateNewsletterPreview(array $templateData, array $postData = []): string {
    $newsletterService = new NewsletterService();
    return $newsletterService->generateNewsletterPreview($templateData, $postData);
}

function createFakturoidInvoice(array $orderData): array {
    $fakturoidService = new FakturoidService();
    return $fakturoidService->createInvoiceFromOrder($orderData);
}

function sendEmail(string $recipient, string $subject, string $body, ?string $attachmentPath = null): array {
    $emailService = new EmailService();
    return $emailService->sendEmail($recipient, $subject, $body, $attachmentPath);
}

function getMailer(): PHPMailer\PHPMailer\PHPMailer {
    return MailerService::getInstance()->getMailer();
}

function generateNextOrderNumber(): int {
    $orderService = new OrderService();
    return $orderService->generateNextOrderNumber();
}

function processCartToOrder(array $aggregated_cart, int $orderNumber): bool {
    $orderService = new OrderService();
    return $orderService->processCartToOrder($aggregated_cart, $orderNumber);
}

function getOrderItemsWithVariants(int $orderNumber): array {
    $orderService = new OrderService();
    return $orderService->getOrderItemsWithVariants($orderNumber);
}

function validateOrderNumber(int $orderNumber): bool {
    $orderService = new OrderService();
    return $orderService->validateOrderNumber($orderNumber);
}

function getOrderPaymentStatus(int $orderNumber): ?string {
    $orderService = new OrderService();
    return $orderService->getOrderPaymentStatus($orderNumber);
}

function updateInventoryAfterPaidOrder(int $orderNumber): bool {
    $productService = new ProductService();
    return $productService->updateInventoryAfterPaidOrder($orderNumber);
}

function generateUnsubscribeToken(string $email): string {
    $newsletterService = new NewsletterService();
    return $newsletterService->generateUnsubscribeToken($email);
}

function getUnsubscribeLink(string $email, string $base_url = 'https://touchthemagic.com'): string {
    $newsletterService = new NewsletterService();
    return $newsletterService->getUnsubscribeLink($email, $base_url);
}

function renderCookieBanner(): string {
    $templateService = new App\Services\TemplateService();
    return $templateService->renderCookieBanner();
}

function get_filter_conditions(): array {
    $templateService = new App\Services\TemplateService();
    return $templateService->getFilterConditions();
}

function get_sort_order(): string {
    $templateService = new App\Services\TemplateService();
    return $templateService->getSortOrder();
}

function render_product_filters(string $location, array $current_filters = []): string {
    $templateService = new App\Services\TemplateService();
    return $templateService->renderProductFilters($location, $current_filters);
}

function build_filter_url(string $base_location, array $new_params = []): string {
    $templateService = new App\Services\TemplateService();
    return $templateService->buildFilterUrl($base_location, $new_params);
}

if (!function_exists('header_html')) {
    require_once dirname(dirname(__DIR__)) . '/app/Views/template.php';
}

function displayErrorMessage(string $message): void {
    echo "<p style='color: red;'>" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</p>";
}

$GLOBALS['db'] = getPdo();
$GLOBALS['pdo'] = getPdo();

$container = Container::getInstance();
ServiceProvider::register($container);

function container(): Container {
    return Container::getInstance();
}

function resolve(string $abstract) {
    return container()->resolve($abstract);
}