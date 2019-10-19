<?php

declare(strict_types=1);

namespace Benda95280\MyEntities\entities\entity;

use Benda95280\MyEntities\entities\Properties;
use pocketmine\nbt\tag\CompoundTag;
use xenialdan\customui\elements\Dropdown;
use xenialdan\customui\elements\Toggle;
use xenialdan\customui\windows\CustomForm;

class CustomEntityProperties extends Properties
{
    const TYPE = "entity";
    protected static $type = self::TYPE;
    public $geometryName;
    public $width = self::SIZE_BLOCK;
    public $height = self::SIZE_BLOCK;
    /** @var bool If the player can collide with the entity (entity acts like solid / boat) */
    public $solid = false;

    public function __construct(?CompoundTag $nbt = null)
    {
        $this->size = self::SIZE_BLOCK;
        parent::__construct($nbt);
    }

    public function getForm(): CustomForm
    {
        $form = parent::getForm();
        $form->addElement(new Dropdown("Size", ["Normal", "Block", "Small", "Custom"]));
        $form->addElement(new Toggle("Solid", $this->solid));
        return $form;
    }
}