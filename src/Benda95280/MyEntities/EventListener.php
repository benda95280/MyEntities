<?php

declare(strict_types=1);

namespace Benda95280\MyEntities;

use Benda95280\MyEntities\entities\entity\CloneEntity;
use Benda95280\MyEntities\entities\entity\CustomEntity;
use Benda95280\MyEntities\entities\head\HeadEntity;
use Benda95280\MyEntities\entities\Properties;
use InvalidStateException;
use pocketmine\entity\Entity;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\tile\Skull;

class EventListener implements Listener
{

    /**
     * @param BlockPlaceEvent $event
     * @throws InvalidStateException
     */
    public function onPlace(BlockPlaceEvent $event): void
    {
        $player = $event->getPlayer();
        $item = $event->getItem();
        if ($player->hasPermission('MyEntities.spawn') and ($blockData = $item->getCustomBlockData()) !== null and $blockData->hasTag(Properties::PROPERTY_TAG, CompoundTag::class)) {
            $nbt = Entity::createBaseNBT($event->getBlockReplaced()->add(0.5, 0, 0.5), null, self::getYaw($event->getBlockReplaced()->add(0.5, 0, 0.5), $player));
            $nbt = $nbt->merge($blockData);
            if ($item->getId() === ItemIds::MOB_HEAD && $item->getDamage() === Skull::TYPE_HUMAN) {
                (new HeadEntity($player->level, $nbt))->spawnToAll();
            } else if ($item->getId() === ItemIds::MOB_HEAD && $item->getDamage() === Skull::TYPE_SKELETON) {
                (new CustomEntity($player->level, $nbt))->spawnToAll();
            } else if ($item->getId() === ItemIds::MOB_HEAD && $item->getDamage() === Skull::TYPE_ZOMBIE) {
                (new CloneEntity($player->level, $nbt))->spawnToAll();
            } else return;//Not a MyEntities entity
            $event->setCancelled();
            if (!$player->isCreative()) {
                $player->getInventory()->setItemInHand($item->setCount($item->getCount() - 1));
            }
        }
    }

    private static function getYaw(Vector3 $pos, Vector3 $target): float
    {
        //Entity must rotate 90° cause of block
        //TODO: Handle 45° for other block
        $yaw = atan2($target->z - $pos->z, $target->x - $pos->x) / M_PI * 180 - 90;
        if ($yaw < 0) {
            $yaw += 360.0;
        }
        // Round to nearest multiple of 45
        return round($yaw / 90) * 90;
    }
}