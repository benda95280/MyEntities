<?php

declare(strict_types=1);

namespace Benda95280\MyEntities\entities\entity;

use Benda95280\MyEntities\entities\Properties;
use pocketmine\nbt\tag\CompoundTag;

class CustomEntityProperties extends Properties
{
    const TYPE = "entity";
    public static $sizeDefault = [self::SIZE_BLOCK, self::SIZE_BLOCK];
    protected static $type = self::TYPE;
    public $geometryName;
    public $width = self::SIZE_BLOCK;
    public $height = self::SIZE_BLOCK;

    public function __construct(?CompoundTag $nbt = null)
    {
        $this->size = self::SIZE_BLOCK;
        parent::__construct($nbt);
    }
}