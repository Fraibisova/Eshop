<?php

namespace App\Container;

use App\Interfaces\UserServiceInterface;
use App\Interfaces\AuthServiceInterface;
use App\Interfaces\EmailServiceInterface;
use App\Interfaces\CartServiceInterface;
use App\Interfaces\ProductServiceInterface;
use App\Interfaces\PaymentGatewayInterface;
use App\Interfaces\SessionServiceInterface;

use App\Services\UserService;
use App\Services\AuthService;
use App\Services\EmailService;
use App\Services\CartService;
use App\Services\ProductService;
use App\Services\DatabaseService;
use App\Services\ConfigurationService;
use App\Services\MailerService;
use App\Services\AuthorizationService;
use App\Services\PaginationService;
use App\Services\QueryBuilderService;
use App\Services\AdminUIService;
use App\Services\ValidationService;
use App\Services\PaymentService;
use App\Services\SessionService;
use App\PaymentGateways\GopayGateway;

class ServiceProvider
{
    public static function register(Container $container): void
    {
        $container->singleton(DatabaseService::class);
        $container->singleton(ConfigurationService::class);
        $container->singleton(MailerService::class);

        $container->bind(UserServiceInterface::class, UserService::class);
        $container->bind(AuthServiceInterface::class, AuthService::class);
        $container->bind(EmailServiceInterface::class, EmailService::class);
        $container->bind(CartServiceInterface::class, CartService::class);
        $container->bind(ProductServiceInterface::class, ProductService::class);
        $container->bind(SessionServiceInterface::class, SessionService::class);
        
        $container->bind(PaymentGatewayInterface::class, GopayGateway::class);

        $container->bind(UserService::class);
        $container->bind(AuthService::class);
        $container->bind(EmailService::class);
        $container->bind(CartService::class);
        $container->bind(ProductService::class);
        $container->bind(PaymentService::class);
        $container->bind(ValidationService::class);
        $container->bind(SessionService::class);

        $container->bind(AuthorizationService::class);
        $container->bind(PaginationService::class);
        $container->bind(QueryBuilderService::class);
        $container->bind(AdminUIService::class);

        $container->bind(GopayGateway::class);
    }
}