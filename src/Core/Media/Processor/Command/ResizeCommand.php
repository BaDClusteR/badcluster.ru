<?php

declare(strict_types=1);

namespace BC\Core\Media\Processor\Command;

use BC\Core\Exception\ImageException;
use BC\Core\Trait\LoggerTrait;

class ResizeCommand extends ACommand {
    use LoggerTrait;

    public function __construct(
        private readonly string $path,
        private readonly string $output,
        private readonly int $width,
        private readonly int $height,
        private readonly string $saveParameters = ''
    ) {
    }

    /**
     * @throws ImageException
     */
    public function execute(): void {
        $command = $this->getCommand();

        $output = [];
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->getLogger()->warning(
                "vipsthumbnail returned code $returnCode.",
                [
                    'command'    => $command,
                    'output'     => implode("\n", $output),
                    'returnCode' => $returnCode,
                    'path'       => $this->path,
                    'out'        => $this->output,
                    'width'      => $this->width,
                    'height'     => $this->height,
                    'saveParams' => $this->saveParameters
                ]
            );

            throw new ImageException("vipsthumbnail returned code $returnCode.");
        }
    }

    /**
     * Все аргументы экранируются: пути могут содержать пробелы и символы шелла.
     * Параметры сохранения vips пишет в квадратных скобках прямо после имени файла
     */
    protected function getCommand(): string {
        $size = ($this->width ?: '') . 'x' . ($this->height ?: '');
        $saveParams = $this->saveParameters
            ? "[$this->saveParameters]"
            : '';

        return escapeshellarg($this->getVipsThumbnailPath())
               . ' ' . escapeshellarg($this->path)
               . ' --size ' . escapeshellarg($size)
               . ' -o ' . escapeshellarg($this->output . $saveParams);
    }
}
