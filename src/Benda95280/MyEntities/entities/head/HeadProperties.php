<?php

declare(strict_types=1);

namespace Benda95280\MyEntities\entities\head;

use Benda95280\MyEntities\entities\Properties;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\nbt\tag\CompoundTag;

class HeadProperties extends Properties
{
    const TYPE = "head";
    public $userName;
    public $width = self::SIZE_NORMAL;
    public $height = self::SIZE_NORMAL;
    protected static $type = self::TYPE;

    public function __construct(?CompoundTag $nbt = null)
    {
        $this->size = self::SIZE_NORMAL;
        parent::__construct($nbt);
    }

    public const GEOMETRY = '{
	"geometry.MyEntities_head": {
		"texturewidth": 64,
		"textureheight": 64,
		"bones": [
			{
				"name": "head",
				"pivot": [0, 0, 0],
				"cubes": [
					{"origin": [-4, 0.5, -4], "size": [8, 8, 8], "uv": [0, 0]},
					{"origin": [-4, 0.5, -4], "size": [8, 8, 8], "uv": [32, 0], "inflate": 0.5}
				]
			}
		]
	}
}';
    public const GEOMETRY_NAME = "geometry.MyEntities_head";

    public static function getDestroyParticlesBlock(): Block
    {
        return BlockFactory::get(Block::MOB_HEAD_BLOCK);
    }
}