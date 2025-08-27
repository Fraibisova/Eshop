<?php

namespace App\Container;

use App\Controllers\BaseController;
use Exception;

class ControllerFactory
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function create(string $controllerClass): BaseController
    {
        try {
            if (!class_exists($controllerClass)) {
                throw new Exception("Controller class {$controllerClass} not found");
            }

            if (!is_subclass_of($controllerClass, BaseController::class)) {
                throw new Exception("Controller {$controllerClass} must extend BaseController");
            }

            return $this->container->resolve($controllerClass);
        } catch (Exception $e) {
            throw new Exception("Failed to create controller {$controllerClass}: " . $e->getMessage());
        }
    }

    public static function getInstance(): self
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new self(Container::getInstance());
        }
        return $instance;
    }

    public function createFromRoute(string $route): BaseController
    {
        $controllerMapping = [
            'admin' => \App\Controllers\AdminController::class,
            'auth' => \App\Controllers\Auth\AuthController::class,
            'profile' => \App\Controllers\Account\ProfileController::class,
            'product' => \App\Controllers\ProductController::class,
            'cart' => \App\Controllers\CartController::class,
            'payment' => \App\Controllers\PaymentController::class,
        ];

        $controllerClass = $controllerMapping[$route] ?? null;
        
        if (!$controllerClass) {
            throw new Exception("No controller found for route: {$route}");
        }

        return $this->create($controllerClass);
    }
}