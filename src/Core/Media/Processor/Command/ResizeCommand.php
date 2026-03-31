<?php

namespace BC\Core\Media\Processor\Command;

use BC\Core\Exception\ImageException;
use BC\Core\Trait\LoggerTrait;

class ResizeCommand extends ACommand
{
    use LoggerTrait;

    public function __construct(
        private string $path,
        private string $output,
        private int $width,
        private int $height,
        private string $saveParameters = ''
    ) {
    }

    /**
     * @throws ImageException
     */
    public function execute(): void
    {
        $command = $this->getCommand();

        /** @var string[] $output */
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

    private function getCommand(): string {
        $size = ($this->width ?: '') . 'x' . ($this->height ?: '');
        $saveParams = $this->saveParameters
            ? "[$this->saveParameters]"
            : "";

        return $this->getVipsThumbnailPath() . " $this->path --size $size -o {$this->output}$saveParams";
    }
}
