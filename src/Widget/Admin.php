<?php

declare(strict_types=1);

namespace BC\Widget;

use BC\Core\Trait\LoggerTrait;
use BC\Core\Trait\PathsProviderTrait;
use BC\DTO\AppSettings\AppSettingsDTO;
use JsonException;
use RuntimeException;

class Admin extends AWidget {
    use PathsProviderTrait;
    use LoggerTrait;

    protected bool $isDevMode = false {
        get {
            return $this->isDevMode;
        }
    }

    protected string $webRoot = '' {
        get {
            return $this->webRoot;
        }
    }

    protected AppSettingsDTO $appSettings {
        get {
            return $this->appSettings;
        }
    }

    protected function applyContext(array $context): void {
        parent::applyContext($context);

        $this->isDevMode = (bool) ($context['devMode'] ?? false);
        $this->webRoot = (string) ($context['webRoot'] ?? '');

        if (isset($context['appSettings'])) {
            if ($context['appSettings'] instanceof AppSettingsDTO) {
                $this->appSettings = $context['appSettings'];
            } else {
                throw new RuntimeException(__METHOD__ . ': appSettings should be an instance of ' . AppSettingsDTO::class);
            }
        }
    }

    protected function getTemplatePath(): string {
        return 'admin.phtml';
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
        $appStaticPath = $pathsProvider->getStaticPath() . '/app';
        $manifestPath = "$appStaticPath/.vite/manifest.json";
        $appWebRoot = $pathsProvider->getStaticWebPath() . '/app';

        if (!file_exists($manifestPath)) {
            $this->getLogger()->warning("Vite manifest does not exist: $manifestPath");
        } elseif (!is_readable($manifestPath)) {
            $this->getLogger()->warning("Vite manifest is not readable: $manifestPath");
        } else {
            $manifest = @file_get_contents($manifestPath);

            if ($manifest) {
                try {
                    $data = json_decode($manifest, true, 512, JSON_THROW_ON_ERROR);
                    $entryKey = isset($data['index.html']) ? 'index.html' : 'src/main.tsx';

                    // Module federation replaces the entry with a bootstrap that
                    // initializes the host before importing the real entry chunk.
                    // It is not listed in the manifest, so probe the filesystem.
                    $mfBootstrap = 'mf-entry-bootstrap-0.js';

                    if (file_exists("$appStaticPath/$mfBootstrap")) {
                        $result['js'][] = "$appWebRoot/$mfBootstrap";
                    } elseif (isset($data[$entryKey]['file'])) {
                        $result['js'][] = "$appWebRoot/" . $data[$entryKey]['file'];
                    }

                    // CSS of statically imported chunks is only listed on those
                    // chunks, so walk the import graph. Imports go first so the
                    // entry's own CSS wins the cascade (same order Vite emits).
                    $visited = [];
                    $collectCss = function (string $key) use (&$collectCss, &$visited, &$result, $data, $appWebRoot): void {
                        if (isset($visited[$key]) || !isset($data[$key])) {
                            return;
                        }
                        $visited[$key] = true;

                        foreach ($data[$key]['imports'] ?? [] as $importKey) {
                            $collectCss($importKey);
                        }

                        foreach ($data[$key]['css'] ?? [] as $cssFile) {
                            $result['css'][] = "$appWebRoot/$cssFile";
                        }
                    };
                    $collectCss($entryKey);
                    $result['css'] = array_values(array_unique($result['css']));
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
