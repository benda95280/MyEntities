<?php

declare(strict_types=1);

namespace Benda95280\MyEntities;

use Benda95280\MyEntities\entities\entity\CustomEntity;
use Benda95280\MyEntities\entities\head\HeadEntity;
use Benda95280\MyEntities\entities\Properties;
use Benda95280\MyEntities\entities\vehicle\CustomVehicle;
use pocketmine\entity\Entity;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\PlayerInputPacket;

class EventListener implements Listener
{

    /**
     * @param BlockPlaceEvent $event
     * @throws \BadMethodCallException
     * @throws \InvalidStateException
     */
    public function onPlace(BlockPlaceEvent $event): void
    {
        $player = $event->getPlayer();
        $item = $event->getItem();
        if ($player->hasPermission('MyEntities.spawn') and ($blockData = $item->getCustomBlockData()) !== null and $blockData->hasTag(Properties::PROPERTY_TAG, CompoundTag::class)) {
            $nbt = Entity::createBaseNBT($event->getBlockReplaced()->add(0.5, 0, 0.5), null, self::getYaw($event->getBlockReplaced()->add(0.5, 0, 0.5), $player));
            $nbt = $nbt->merge($blockData);
            if ($item->getId() === ItemIds::MOB_HEAD && $item->getDamage() === 3) {
                (new HeadEntity($player->level, $nbt))->spawnToAll();
            } else if ($item->getId() === ItemIds::MOB_HEAD && $item->getDamage() === 0) {
                (new CustomEntity($player->level, $nbt))->spawnToAll();
            } else if ($item->getId() === ItemIds::BOOKSHELF) {
                (new CustomVehicle($player->level, $nbt))->spawnToAll();
            } else return;//Not a MyEntities entity
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

    public function onDataPacketReceive(DataPacketReceiveEvent $event)
    {
        $packet = $event->getPacket();
        switch ($packet->pid()) {
            case InteractPacket::NETWORK_ID:
                /** @var InteractPacket $packet */
                if ($packet->action === InteractPacket::ACTION_LEAVE_VEHICLE) {
                    $target = $event->getPlayer()->getLevel()->getEntity($packet->target);
                    if ($target instanceof CustomVehicle and $target->getRider() === $event->getPlayer()) {
                        $target->dismount();
                        $event->setCancelled();
                    }
                }
                break;
            case InventoryTransactionPacket::NETWORK_ID:
                /** @var InventoryTransactionPacket $packet */
                if ($packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) {
                    $target = $event->getPlayer()->getLevel()->getEntity($packet->trData->entityRuntimeId);
                    if ($target instanceof CustomVehicle and $packet->trData->actionType === InventoryTransactionPacket::USE_ITEM_ON_ENTITY_ACTION_INTERACT) {
                        $target->ride($event->getPlayer());
                        $event->setCancelled();
                    }
                }
                break;
            case PlayerInputPacket::NETWORK_ID:
                /** @var PlayerInputPacket $packet */
                if ($packet->motionX === 0 and $packet->motionY === 0) return; // ignore non-input
                if (isset(CustomVehicle::$ridingEntities[$event->getPlayer()->getName()])) {
                    $riding = CustomVehicle::$ridingEntities[$event->getPlayer()->getName()];
                    $riding->input($packet->motionX, $packet->motionY);
                    $event->setCancelled();
                }
                break;
        }
    }
}