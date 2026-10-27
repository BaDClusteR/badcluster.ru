<?php

declare(strict_types=1);

namespace BC\Tests\Integration;

use BC\Controller\Controller404;
use BC\Controller\System\ApiController;
use BC\Controller\System\ExceptionController;
use BC\Controller\System\MethodNotAllowedController;
use BC\Logger\AlertingLogger;
use BC\Logger\MaskingLogger;
use BC\Tests\Support\SqliteSchema;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Runway\Controller\IController404;
use Runway\Controller\IExceptionController;
use Runway\Controller\IMethodNotAllowedController;
use Runway\Logger\ILogger;
use Runway\Logger\Logger;
use Runway\Service\DTO\ServiceDTO;
use Runway\Service\Provider\ConfigProvider;
use Runway\Singleton\Container;

/**
 * Every service of the configs of the site (config/, modules/<Module>/config/) can be created by the real container:
 * the constructor parameters, the arguments, the parameters and the env variables of the configs fit the classes.
 */
#[CoversNothing]
final class ContainerServicesTest extends TestCase {
    protected function setUp(): void {
        // Some services read the database in their constructors.
        SqliteSchema::createSiteTables();
    }

    /**
     * @return array<string, ServiceDTO>
     */
    private static function getSiteServices(): array {
        $services = [];
        $roots = [
            str_replace('\\', '/', PROJECT_ROOT . '/config/'),
            str_replace('\\', '/', PROJECT_ROOT . '/modules/'),
        ];

        foreach (new ConfigProvider()->getConfig()->getServiceConfig() as $service) {
            $file = str_replace('\\', '/', $service->getFilePath());

            if (array_any($roots, static fn(string $root): bool => str_starts_with($file, $root))) {
                $services[$service->getName()] = $service;
            }
        }

        return $services;
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideServices(): iterable {
        foreach (self::getSiteServices() as $name => $service) {
            // A decorator cannot be requested by its name: it's checked by its decorated service.
            if ($service->getDecoratee() === '') {
                yield $name => [$name];
            }
        }
    }

    #[DataProvider('provideServices')]
    public function testServiceIsCreated(string $name): void {
        $service = Container::getInstance()->getService($name);
        $class = self::getSiteServices()[$name]->getClass();

        self::assertIsObject($service);

        $decorators = self::getAllServices()[$name]->getDecorators();
        self::assertInstanceOf($decorators !== [] ? end($decorators) : $class, $service);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideDecorators(): iterable {
        foreach (self::getSiteServices() as $name => $service) {
            if ($service->getDecoratee() !== '') {
                yield $name => [$service->getDecoratee(), $service->getClass()];
            }
        }
    }

    #[DataProvider('provideDecorators')]
    public function testDecoratorIsInTheChain(string $decoratee, string $decorator): void {
        $service = Container::getInstance()->getService($decoratee);
        self::assertIsObject($service);

        $chain = self::getChain($service);

        self::assertContains($decorator, $chain, "$decorator does not decorate $decoratee: " . implode(' -> ', $chain));
    }

    public function testListenersOfTheEventsExist(): void {
        $checked = 0;

        foreach (self::getSiteServices() as $service) {
            foreach ($service->getEvents() as $event) {
                self::assertTrue(
                    method_exists($service->getClass(), $event->getMethod()),
                    "{$service->getClass()}::{$event->getMethod()}() of the event {$event->getName()} does not exist."
                );
                $checked++;
            }
        }

        self::assertGreaterThanOrEqual(3, $checked);
    }

    /**
     * The order matters: AlertingLogger inside MaskingLogger records the masked context (the passwords never reach the
     * journal and Slack).
     */
    public function testLoggerChain(): void {
        $logger = Container::getInstance()->getService(ILogger::class);

        self::assertSame([MaskingLogger::class, AlertingLogger::class, Logger::class], self::getChain($logger));
    }

    public function testControllersOfTheSiteWrapTheOnesOfApiPlatform(): void {
        $container = Container::getInstance();

        self::assertInstanceOf(Controller404::class, $container->getService(IController404::class));
        self::assertInstanceOf(ExceptionController::class, $container->getService(IExceptionController::class));
        self::assertInstanceOf(MethodNotAllowedController::class, $container->getService(IMethodNotAllowedController::class));
        self::assertInstanceOf(ApiController::class, $container->getService(\ApiPlatform\Controller\IApiController::class));
    }

    public function testApiPrefixIsTheAdminOne(): void {
        self::assertSame('/admin/api', new ConfigProvider()->getConfig()->getParameter('api_path_prefix'));
    }

    /**
     * @return array<string, ServiceDTO>
     */
    private static function getAllServices(): array {
        $services = [];

        foreach (new ConfigProvider()->getConfig()->getServiceConfig() as $service) {
            $services[$service->getName()] = $service;
        }

        return $services;
    }

    /**
     * @return list<string> The classes of the decorator chain, from the outer one: the "inner" property of each one.
     */
    private static function getChain(object $service): array {
        $chain = [];

        for ($current = $service; is_object($current) && count($chain) < 20;) {
            $chain[] = $current::class;
            $next = null;

            foreach (['inner', 'apiExceptionController'] as $property) {
                if (property_exists($current, $property)) {
                    $next = new ReflectionProperty($current, $property)->getValue($current);
                    break;
                }
            }

            $current = $next;
        }

        return $chain;
    }
}
