<?php

namespace BC\Core\Media\Processor;

use BC\Core\DTO\ImageDTO;
use BC\Core\Exception\ImageException;
use BC\Core\Media\Processor\Command\ResizeCommand;
use Runway\Env\Provider\IEnvVariablesProvider;
use Runway\Singleton\Container;

abstract readonly class AImageProcessor implements IImageProcessor
{
    abstract protected function getResultImageExtension(): string;
    abstract protected function getSaveParameters(): string;

    /**
     * @throws ImageException
     */
    public function getThumbnail(string $path, int $width, int $sourceWidth): ImageDTO
    {
        if (!file_exists($path)) {
            throw new ImageException("File $path does not exist");
        }

        if (!is_readable($path)) {
            throw new ImageException("File $path is not readable");
        }

        $pathInfo = pathinfo($path);
        $output = ($pathInfo['dirname'] ?? '') . '/' . ($pathInfo['filename'] ?? '');
        if ($width && $width !== $sourceWidth) {
            $output .= "-w$width";
        }
//        if ($width && !$height) {
//            $output .= "-w$width";
//        } elseif (!$width && $height) {
//            $output .= "-h$height";
//        } else {
//            $output .= "-s{$width}x$height";
//        }
        $output .= '.' . $this->getResultImageExtension();

        new ResizeCommand($path, $output, $width, 0, $this->getSaveParameters())->execute();

        if (!file_exists($output)) {
            throw new ImageException("File $output does not exist");
        }

        if (!is_readable($output)) {
            throw new ImageException("File $output is not readable");
        }

        $sizes = getimagesize($output);

        if ($sizes === false) {
            throw new ImageException("Cannot get image meta info for $output. Is it an image?");
        }

        return new ImageDTO(
            $output,
            (int)($sizes[0] ?? 0),
            (int)($sizes[1] ?? 0),
            (string)($sizes['mime'] ?? ''),
            filesize($output),
            md5_file($output)
        );
    }

    public function isApplicable(string $path): bool
    {
        return $this->isVipsThumbnailEnabled();
    }

    private function isVipsThumbnailEnabled(): bool {
        return !empty($this->getVipsThumbnailPath());
    }

    private function getVipsThumbnailPath(): string {
        return (string)$this->getEnvVariablesProvider()->getEnvVariable('VIPSTHUMBNAIL_PATH');
    }

    private function getEnvVariablesProvider(): IEnvVariablesProvider {
        return Container::getInstance()->getService(IEnvVariablesProvider::class);
    }
}
