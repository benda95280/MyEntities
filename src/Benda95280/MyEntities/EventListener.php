<?php

declare(strict_types=1);

namespace Benda95280\MyEntities;

use Benda95280\MyEntities\entities\MyCustomEntity;
use pocketmine\entity\Entity;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\math\Vector3;

class EventListener implements Listener
{

    /**
     * @param BlockPlaceEvent $event
     * @throws \BadMethodCallException
     * @throws \InvalidStateException
     * @throws \RuntimeException
     */
    public function onPlace(BlockPlaceEvent $event): void
    {
        $player = $event->getPlayer();
        if ($player->hasPermission('MyEntities.spawn') and (($item = $event->getItem())->getId() === Item::MOB_HEAD || $item->getId() === Item::END_PORTAL_FRAME) and ($blockData = $item->getCustomBlockData()) !== null) {
            $nbt = Entity::createBaseNBT($event->getBlock()->add(0.5, 0, 0.5), null, self::getYaw($event->getBlock()->add(0.5, 0, 0.5), $player)); // Add 0.5 because block center is at half coordinate
            if ($blockData->hasTag("skin_empty"))
                $nbt->setByteArray("Skin_empty", $blockData->getByteArray("skin_empty"));
            if ($blockData->hasTag("Geometry")) {
                $nbt->setString("Geometry", $blockData->getString("Geometry"));
            }
            $blockDataSkin = $blockData->getCompoundTag("skin");
            $blockDataParam = $blockData->getCompoundTag("param");
            $blockDataSkin->setName('Skin');
            $blockDataParam->setName('Param');
            $nbt->setTag($blockDataSkin);
            $nbt->setTag($blockDataParam);
            (new MyCustomEntity($player->level, $nbt))->spawnToAll();
            if (!$player->isCreative()) {
                $player->getInventory()->setItemInHand($item->setCount($item->getCount() - 1));
            }
            $event->setCancelled();
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