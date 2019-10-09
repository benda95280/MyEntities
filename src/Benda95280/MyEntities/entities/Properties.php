<?php

declare(strict_types=1);

namespace Benda95280\MyEntities\entities;

use Benda95280\MyEntities\entities\block\BlockProperties;
use Benda95280\MyEntities\entities\entity\CustomEntityProperties;
use Benda95280\MyEntities\entities\head\HeadProperties;
use Benda95280\MyEntities\entities\vehicle\VehicleProperties;
use Benda95280\MyEntities\MyEntities;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;
use pocketmine\entity\Skin;
use pocketmine\nbt\NBTStream;
use pocketmine\nbt\tag\CompoundTag;

abstract class Properties
{
    const PROPERTY_TAG = "MyEntities";
    const PROPERTY_TEXTURE = "texture";
    const PROPERTY_TYPE = "type";
    const PROPERTY_SIZE = "size";
    const PROPERTY_SIZE_NORMAL = "normal";
    const PROPERTY_SIZE_SMALL = "small";
    const PROPERTY_SIZE_BLOCK = "block";
    const SIZE_NORMAL = 0.5;
    const SIZE_SMALL = 0.25;
    const SIZE_BLOCK = 1;
    const TYPES = [
        HeadProperties::TYPE,
        CustomEntityProperties::TYPE,
        VehicleProperties::TYPE,
    ];
    public $width;
    public $height;
    /** @var Skin */
    public $skin;
    public $skinEmpty;
    protected static $type;
    public $rotationPerTick = 0;
    public $size = self::SIZE_BLOCK;
    public $name = "";
    public $health = 1;
    public $unbreakable = false;
    public $usable = [
        "time" => 0,
        "skinchange" => 0,
        "reload" => 0,
        "destruction" => 0,
        "use_msg" => 1,
        "destruction_msg" => "",
        "action" => "{}",
        "action_random" => 0,
    ];
    #public $usable = false;
    public $lastUsed = -1;

    public function __construct(?CompoundTag $nbt = null)
    {
        if ($nbt) {
            foreach (NBTStream::toArray($nbt) as $property => $value) {
                if ($property === "size") {
                    switch ($value) {
                        case self::PROPERTY_SIZE_BLOCK:
                            {
                                $this->size = self::SIZE_BLOCK;
                                break;
                            }
                        case self::PROPERTY_SIZE_SMALL:
                            {
                                $this->size = self::SIZE_SMALL;
                                break;
                            }
                        case self::PROPERTY_SIZE_NORMAL:
                        default:
                            break;
                    }
                    continue;
                }
                $this->$property = $value;
            }
        }
    }

    public function toTag(): CompoundTag
    {
        return MyEntities::arrayToCompTag((array)$this, self::PROPERTY_TAG);
    }

    /**
     * @return int
     * @throws \InvalidArgumentException
     */
    public static function getType(): int
    {
        $search = array_search(self::$type, self::TYPES);
        if (!is_int($search)) throw new \InvalidArgumentException("Incorrect type given");
        return $search;
    }

    /**
     * @return Block
     * @throws \InvalidArgumentException
     */
    public static function getDestroyParticlesBlock(): Block
    {
        return BlockFactory::get(BlockIds::AIR);
    }
}