<?php

namespace BC\Model;

use Runway\DataStorage\Attribute as DS;
use Runway\Model\AEntity;

/**
 * @generated-model-helpers
 * @method int getId()
 * @method self setId(int $id)
 * @method string getTag()
 * @method self setTag(string $tag)
 * @method string getTitle()
 * @method self setTitle(string $title)
 * @method string getText()
 * @method self setText(string $text)
 * @method string getStatusTitle()
 * @method self setStatusTitle(string $statusTitle)
 * @method string getStatusText()
 * @method self setStatusText(string $statusText)
 * @method string getIcon()
 * @method self setIcon(string $icon)
 * @method Media|null getImage()
 * @method self setImage(Media|null $image)
 * @method int getPosition()
 * @method self setPosition(int $position)
 * @method string|null getUrl()
 * @method self setUrl(string|null $url)
 * @method bool getIsTall()
 * @method self setIsTall(bool $isTall)
 * @method bool getIsSurfaced()
 * @method self setIsSurfaced(bool $isSurfaced)
 */
#[DS\Table('pulse_items')]
class PulseItem extends AEntity {
    #[DS\Id]
    protected int $id;

    #[DS\Column]
    protected string $tag = '';

    #[DS\Column]
    protected string $title = '';

    #[DS\Column]
    protected string $text = '';

    #[DS\Column]
    protected string $statusTitle = '';

    #[DS\Column]
    protected string $statusText = '';

    #[DS\Column]
    protected string $icon = '';

    #[DS\Column]
    protected ?Media $image = null;

    #[DS\Column]
    protected int $position = 0;

    #[DS\Column]
    protected ?string $url = null;

    #[DS\Column]
    protected bool $isTall = false;

    #[DS\Column]
    protected bool $isSurfaced = false;
}
