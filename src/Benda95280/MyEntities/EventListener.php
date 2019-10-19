<?php

declare(strict_types=1);

namespace Benda95280\MyEntities;

use Benda95280\MyEntities\entities\entity\CustomEntity;
use Benda95280\MyEntities\entities\head\HeadEntity;
use Benda95280\MyEntities\entities\Properties;
use Benda95280\MyEntities\entities\vehicle\CustomVehicle;
use pocketmine\entity\Entity;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\PlayerInputPacket;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

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

	public function onPlayerLeaveEvent(PlayerQuitEvent $event)
	{
		$player = $event->getPlayer();
		if (isset(MyEntities::$inVehicle[$player->getRawUniqueId()])) {
			MyEntities::$inVehicle[$player->getRawUniqueId()]->removePlayer($player);
			MyEntities::getInstance()->getLogger()->debug($player->getName() . " Has left the server while in a vehicle, they have been kicked from the vehicle.");
		}
	}

	public function onPlayerChangeLevelEvent(EntityLevelChangeEvent $event)
	{
		if ($event->getEntity() instanceof Player) {
			/** @var Player $player */
			$player = $event->getEntity();
			if (isset(MyEntities::$inVehicle[$player->getRawUniqueId()])) {
				MyEntities::$inVehicle[$player->getRawUniqueId()]->removePlayer($player);
				$player->sendMessage(TextFormat::RED . "You cannot change level with a vehicle, you have been kicked from your vehicle.");
				MyEntities::getInstance()->getLogger()->debug($player->getName() . " Has changed level while in a vehicle, they have been kicked from the vehicle.");
			}
		}
	}

	public function onPlayerDeathEvent(PlayerDeathEvent $event)
	{
		$player = $event->getPlayer();
		if (isset(MyEntities::$inVehicle[$player->getRawUniqueId()])) {
			MyEntities::$inVehicle[$player->getRawUniqueId()]->removePlayer($player);
			$player->sendMessage(TextFormat::RED . "You were killed so you have been kicked from your vehicle.");
			MyEntities::getInstance()->getLogger()->debug($player->getName() . " Has died while in a vehicle, they have been kicked from the vehicle.");
		}
	}

	public function onPlayerTeleportEvent(EntityTeleportEvent $event)
	{
		if ($event->getEntity() instanceof Player) {
			/** @var Player $player */
			$player = $event->getEntity();
			if (isset(MyEntities::$inVehicle[$player->getRawUniqueId()])) {
				MyEntities::$inVehicle[$player->getRawUniqueId()]->removePlayer($player);
				$player->sendMessage(TextFormat::RED . "You cannot teleport with a vehicle, you have been kicked from your vehicle.");
				MyEntities::getInstance()->getLogger()->debug($player->getName() . " Has teleported while in a vehicle, they have been kicked from their vehicle.");
			}
		}
	}

	public function onDataPacketReceive(DataPacketReceiveEvent $event)
	{
		$packet = $event->getPacket();
		switch ($packet->pid()) {
			case InteractPacket::NETWORK_ID:
				/** @var InteractPacket $packet */
				if ($packet->action === InteractPacket::ACTION_LEAVE_VEHICLE) {
					$player = $event->getPlayer();
					$vehicle = $player->getLevel()->getEntity($packet->target);
					if ($vehicle instanceof CustomVehicle) {
						$vehicle->removePlayer($event->getPlayer());
						$event->setCancelled();
					}
				}
				if ($packet->action === InteractPacket::ACTION_MOUSEOVER) {
					$player = $event->getPlayer();
					$vehicle = $player->getLevel()->getEntity($packet->target);
					if ($vehicle instanceof CustomVehicle) {
						$player->getDataPropertyManager()->setString(Entity::DATA_INTERACTIVE_TAG, $vehicle->getInteractString());
						$event->setCancelled();
					}
				}
				break;
			case InventoryTransactionPacket::NETWORK_ID:
				/** @var InventoryTransactionPacket $packet */
				if ($packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) {
					$player = $event->getPlayer();
					$vehicle = $event->getPlayer()->getLevel()->getEntity($packet->trData->entityRuntimeId);
					if ($vehicle instanceof CustomVehicle and $packet->trData->actionType === InventoryTransactionPacket::USE_ITEM_ON_ENTITY_ACTION_INTERACT) {
						if (($item = $player->getInventory()->getItemInHand())->getId() === ItemIds::TRIPWIRE_HOOK && $item->hasCustomName() && $item->getCustomName() === "Car Key") {
							//TODO key check (like is the key actually the owner's key)
							if ($vehicle->getOwner() === $player->getUniqueId()) {
								$vehicle->setLocked(!$vehicle->isLocked());
								if ($vehicle->isLocked()) $player->sendMessage(TextFormat::RED . "You locked the vehicle");
								else $player->sendMessage(TextFormat::GREEN . "You unlocked the vehicle");//TODO translation
							}
						} else {
							if ($vehicle->hasDriver()) $vehicle->setPassenger($player);
							else $vehicle->setDriver($player);
						}
						$event->setCancelled();
					}
				}
				break;
			case PlayerInputPacket::NETWORK_ID:
				/** @var PlayerInputPacket $packet */
				$packet = $event->getPacket();
				$player = $event->getPlayer();
				if (isset(MyEntities::$inVehicle[$player->getRawUniqueId()])) {
					$event->setCancelled();
					//Process packet anyways since we want to set car motion to 0
					/*if($packet->motionX === 0.0 and $packet->motionY === 0.0) {
						return;
					} *///MCPE Likes to send a lot of useless packets, this cuts down the ones we handle.
					/** @var CustomVehicle $vehicle */
					$vehicle = MyEntities::$inVehicle[$player->getRawUniqueId()];
					if ($vehicle->getDriver() === null) return;
					if ($vehicle->getDriver()->getUniqueId()->equals($player->getUniqueId())) $vehicle->input($packet->motionX, $packet->motionY);
				}
				break;
		}
	}
}