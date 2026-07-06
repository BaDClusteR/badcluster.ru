<?php

namespace BC\Event;

use ApiPlatform\Core\Trait\RequestTrait;
use BC\Core\Trait\Controller404Trait;
use BC\Core\Trait\LoggerTrait;
use BC\Core\Trait\WebsiteSettingsTrait;
use Runway\Exception\Exception;
use Runway\Request\Response;
use Runway\Singleton\Container;
use Runway\Singleton\IKernel;

class Redirect {
    use RequestTrait;
    use LoggerTrait;
    use WebsiteSettingsTrait;
    use Controller404Trait;

    public function redirectIfNeeded(): void {
        $path = strtolower($this->getRequest()->getPath());
        try {
            $redirect = \BC\Model\Redirect::findOne(['path' => $path]);
            if ($redirect) {
                $code = $redirect->getCode();

                if ($code === 301 && ($dest = $redirect->getDestination())) {
                    if (str_starts_with($dest, '/')) {
                        $dest = $this->getWebsiteSettings()->getWebRoot() . $dest;
                    }

                    Container::getInstance()->getService(IKernel::class)->processResponse(
                        new Response(
                            301,
                            '',
                            ['Location' => $dest]
                        )
                    );

                    exit(0);
                }

                if ($code === 410) {
                    Container::getInstance()->getService(IKernel::class)->processResponse(
                        $this->get404Controller()->run()->setCode(410)
                    );

                    exit(0);
                }
            }
        } catch (Exception $e) {
            $this->getLogger()->error(
                'Cannot look up redirect: ' . $e->getMessage(),
                [
                    'path'       => $path,
                    'errCode'    => $e->getCode(),
                    'errMessage' => $e->getMessage(),
                    'errTrace'   => $e->getTraceAsString(),
                ]
            );
        }
    }
}
