<?php

namespace BC\Widget;

use BC\Core\Provider\IPathsProvider;
use BC\Core\Scanner\IWidgetClassScanner;
use BC\Core\Trait\LoggerTrait;
use BC\Widget\Attribute\WidgetList;
use ReflectionClass;
use ReflectionException;
use Runway\Exception\RuntimeException;
use Runway\Singleton\Container;

abstract class AWidget
{
    use LoggerTrait;

    protected array $context = [];

    public function __construct(array $context = []) {
        $this->applyContext($context);
    }

    abstract protected function getTemplatePath(): string;

    protected function applyContext(array $context): void {
        $this->context = array_merge(
            $this->context,
            $context
        );
    }

    public function render(array $context = []): string {
        $oldContext = $this->context;
        $this->applyContext($context);

        $fullPath = $this->getFullTemplatePath();

        ob_start();
        include $fullPath;
        $result = ob_get_clean();

        if ($result === false) {
            throw new RuntimeException(
                sprintf("Error while rendering %s: output buffer is inactive", get_class($this))
            );
        }

        $this->applyContext($oldContext);

        return $result;
    }

    protected function getFullTemplatePath(): string {
        $relativePath = $this->getTemplatePath();

        foreach ($this->getPathsProvider()->getTemplatePaths() as $path) {
            $fullPath = "$path/$relativePath";
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }

        throw new RuntimeException("Cannot find template $relativePath");
    }

    protected function getPathsProvider(): IPathsProvider {
        return Container::getInstance()->getService(IPathsProvider::class);
    }

    public function renderWidgetList(string $list, array $context = []): string {
        /** @var array{0: integer, 1: AWidget} $entries */
        $entries = array_merge(
            $this->getWidgetListEntriesByAttribute($list),
            $this->getWidgetListEntriesByTag($list)
        );

        usort($entries, static fn(array $a, array $b) => $a[0] <=> $b[0]);

        $result = '';
        /** @var AWidget $widget */
        foreach ($entries as [, $widget]) {
            $result .= $widget->render($context);
        }

        return $result;
    }

    /**
     * @return AWidget[]
     */
    protected function getWidgetListEntriesByAttribute(string $list): array {
        $entries = [];
        $container = Container::getInstance();

        /** @var IWidgetClassScanner $scanner */
        $scanner = $container->getService(IWidgetClassScanner::class);

        foreach ($scanner->getWidgetClasses() as $className) {
            try {
                $reflection = new ReflectionClass($className);
            } catch (ReflectionException $e) {
                $this->getLogger()->warning(
                    sprintf('%s: Cannot get reflection of class %s: %s', __METHOD__, $className, $e->getMessage())
                );

                continue;
            }

            if (!$reflection->isInstantiable() || !$reflection->isSubclassOf(self::class)) {
                continue;
            }

            foreach ($reflection->getAttributes(WidgetList::class) as $attr) {
                $widgetListAttr = $attr->newInstance();

                if ($widgetListAttr->name === $list) {
                    $service = $container->hasService($className)
                        ? $container->getService($className)
                        : new $className();

                    $entries[] = [$widgetListAttr->priority, $service];
                }
            }
        }

        return $entries;
    }

    protected function getWidgetListEntriesByTag(string $list): array {
        $entries = [];
        $container = Container::getInstance();

        foreach ($container->getServiceTagsByName("widget-list.$list") as $tagInfo) {
            $widget = $container->getService($tagInfo['serviceName']);

            if ($widget instanceof self) {
                $priority = (int)($tagInfo['extra']['priority'] ?? 100);
                $entries[] = [$priority, $widget];
            }
        }

        return $entries;
    }
}
