<?php

namespace BC\Widget;

use BC\Core\Trait\LoggerTrait;
use BC\Core\Trait\PathsProviderTrait;
use BC\DTO\AppSettings\AppSettingsDTO;
use JsonException;
use RuntimeException;

class Admin extends AWidget
{
    use PathsProviderTrait;
    use LoggerTrait;

    protected bool $isDevMode = false {
        get {
            return $this->isDevMode;
        }
    }

    protected string $webRoot = "" {
        get {
            return $this->webRoot;
        }
    }

    protected AppSettingsDTO $appSettings {
        get {
            return $this->appSettings;
        }
    }

    protected function applyContext(array $context): void
    {
        parent::applyContext($context);

        $this->isDevMode = (bool)($context['devMode'] ?? false);
        $this->webRoot = (string)($context['webRoot'] ?? "");

        if (isset($context['appSettings'])) {
            if ($context['appSettings'] instanceof AppSettingsDTO) {
                $this->appSettings = $context['appSettings'];
            } else {
                throw new RuntimeException(__METHOD__ . ": appSettings should be an instance of " . AppSettingsDTO::class);
            }
        }
    }

    protected function getTemplatePath(): string
    {
        return "admin.phtml";
    }

    /**
     * @return array{js: string[], css: string[]}
     */
    protected function getManifestImports(): array {
        $result = [
            'js'  => [],
            'css' => []
        ];
        $pathsProvider = $this->getPathsProvider();
        $manifestPath = $pathsProvider->getStaticPath() . '/app/.vite/manifest.json';
        $appWebRoot = $pathsProvider->getStaticWebPath() . "/app";

        if (!file_exists($manifestPath)) {
            $this->getLogger()->warning("Vite manifest does not exist: $manifestPath");
        } elseif (!is_readable($manifestPath)) {
            $this->getLogger()->warning("Vite manifest is not readable: $manifestPath");
        } else {
            $manifest = @file_get_contents(
                $this->getPathsProvider()->getStaticPath() . '/app/.vite/manifest.json'
            );

            if ($manifest) {
                try {
                    $data = json_decode($manifest, true, 512, JSON_THROW_ON_ERROR);
                    $main = $data['index.html'] ?? $data['src/main.tsx'] ?? [];
                    $result['js'][] = "$appWebRoot/" . ($main['file'] ?? '');
                    foreach ($main['css'] ?? [] as $cssFile) {
                        $result['css'][] = "$appWebRoot/$cssFile";
                    }
                } catch (JsonException $e) {
                    $this->getLogger()->warning(
                        "Vite manifest has incorrect JSON: $manifestPath.",
                        ['error' => $e->getMessage()]
                    );
                }
            }
        }

        return $result;
    }
}
