<?php

declare(strict_types=1);

namespace Benda95280\MyEntities\entities\vehicle;

use Benda95280\MyEntities\entities\BaseEntity;
use Benda95280\MyEntities\entities\entity\CustomEntity;
use Benda95280\MyEntities\MyEntities;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;
use pocketmine\entity\Entity;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\SetActorLinkPacket;
use pocketmine\network\mcpe\protocol\types\EntityLink;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\utils\UUID;

class CustomVehicle extends CustomEntity
{
	/** @var VehicleProperties */
	public $properties;

	/** @var null|Player */
	private $driver = null;
	/** @var null|UUID */
	private $owner = null;
	/** @var Player[] */
	protected $passengers = [];

	public function __construct(Level $level, CompoundTag $nbt)
	{
		$properties = new VehicleProperties($nbt->getCompoundTag("MyEntities"));
		BaseEntity::__construct($properties, $level, $nbt);
		$properties->skin = $this->skin;
	}

	protected function initEntity(): void
	{
		parent::initEntity();
		$this->setGenericFlag(Entity::DATA_FLAG_AFFECTED_BY_GRAVITY, false);
	}

	/**
	 * @return Block
	 * @throws \InvalidArgumentException
	 */
	public static function getDestroyParticlesBlock(): Block
	{
		return BlockFactory::get(BlockIds::IRON_BLOCK);
	}

	public function getInteractString(): string
	{
		return "Ride";
	}

	public function getDriverSeatPosition(): ?Vector3
	{
		if ($this->properties->driverPosition === null) return new Vector3(0, $this->height, 0);
		else return $this->properties->driverPosition;
	}

	public function getPassengerSeatPosition(int $seatNumber): ?Vector3
	{
		if (isset($this->properties->passengerPositions[$seatNumber])) return $this->properties->passengerPositions[$seatNumber];
		return null;
	}

	public function getNextAvailableSeat(): ?int
	{
		$max = count($this->properties->passengerPositions);
		$current = count($this->passengers);
		if ($max === $current) return null;
		for ($i = 0; $i < $max; $i++) {
			if (!isset($this->passengers[$i])) return $i;
		}
		return null;
	}

	public function isEmpty(): bool
	{
		if (count($this->passengers) === 0 and $this->driver === null) return true;
		return false;
	}

	/**
	 * Remove the given player from the vehicle
	 * @param Player $player
	 * @return bool
	 */
	public function removePlayer(Player $player): bool
	{
		if ($this->driver !== null) {
			if ($this->driver->getUniqueId()->equals($player->getUniqueId())) return $this->removeDriver();
		}
		return $this->removePassengerByUUID($player->getUniqueId());
	}

	public function removePassengerByUUID(UUID $id): bool
	{
		foreach (array_keys($this->passengers) as $i) {
			if ($this->passengers[$i]->getUniqueId() === $id) {
				return $this->removePassenger($i);
			}
		}
		return false;
	}

	/**
	 * Remove passenger by seat number.
	 * @param int $seat
	 * @return bool
	 */
	public function removePassenger($seat): bool
	{
		if (isset($this->passengers[$seat])) {
			$player = $this->passengers[$seat];
			unset($this->passengers[$seat]);
			unset(MyEntities::$inVehicle[$player->getRawUniqueId()]);
			$player->setGenericFlag(Entity::DATA_FLAG_RIDING, false);
			$player->setGenericFlag(Entity::DATA_FLAG_SITTING, false);
			$this->broadcastLink($player, EntityLink::TYPE_REMOVE);
			$player->sendMessage(TextFormat::GREEN . "You are no longer in this vehicle.");
			return true;
		}
		return false;
	}

	/**
	 * Removes the driver if possible.
	 * @return bool
	 */
	public function removeDriver(): bool
	{
		if ($this->driver === null) return false;
		$this->driver->setGenericFlag(Entity::DATA_FLAG_RIDING, false);
		$this->driver->setGenericFlag(Entity::DATA_FLAG_SITTING, false);
		$this->driver->setGenericFlag(Entity::DATA_FLAG_WASD_CONTROLLED, false);
		$this->setGenericFlag(Entity::DATA_FLAG_SADDLED, false);
		$this->driver->sendMessage(TextFormat::GREEN . "You are no longer driving this vehicle.");
		$this->broadcastLink($this->driver, EntityLink::TYPE_REMOVE);
		unset(MyEntities::$inVehicle[$this->driver->getRawUniqueId()]);
		$this->driver = null;
		return true;
	}

	public function setPassenger(Player $player, ?int $seat = null): bool
	{
		if ($this->isLocked() && !$player->getUniqueId()->equals($this->getOwner())) {
			$player->sendMessage(TextFormat::RED . "This vehicle is locked.");
			return false;
		}
		if ($seat !== null) {
			if (isset($this->passengers[$seat])) return false;
		} else {
			$seat = $this->getNextAvailableSeat();
			if ($seat === null) return false;
		}
		$this->passengers[$seat] = $player;
		MyEntities::$inVehicle[$player->getRawUniqueId()] = $this;
		$player->setGenericFlag(Entity::DATA_FLAG_RIDING, true);
		$player->setGenericFlag(Entity::DATA_FLAG_SITTING, true);
		$player->getDataPropertyManager()->setVector3(Entity::DATA_RIDER_SEAT_POSITION, $this->getPassengerSeatPosition($seat));
		$this->broadcastLink($player, EntityLink::TYPE_PASSENGER);
		$player->sendMessage(TextFormat::GREEN . "You are now a passenger in this vehicle.");
		return true;
	}

	/**
	 * Sets the driver to the given player if possible.
	 * @param Player $player
	 * @return bool
	 */
	public function setDriver(Player $player): bool
	{
		if ($this->isLocked() && !$player->getUniqueId()->equals($this->getOwner())) {
			$player->sendMessage(TextFormat::RED . "This vehicle is locked, you must be the owner to enter.");
			return false;
		}
		if ($this->driver !== null) {
			if ($this->driver->getUniqueId()->equals($player->getUniqueId())) {
				$player->sendMessage(TextFormat::RED . "You are already driving this vehicle.");
				return false;
			}
			$player->sendMessage(TextFormat::RED . $this->driver->getName() . " is driving this vehicle.");
			return false;
		}
		$player->setGenericFlag(Entity::DATA_FLAG_RIDING, true);
		$player->setGenericFlag(Entity::DATA_FLAG_SITTING, true);
		$player->setGenericFlag(Entity::DATA_FLAG_WASD_CONTROLLED, true);
		$player->getDataPropertyManager()->setVector3(Entity::DATA_RIDER_SEAT_POSITION, $this->getDriverSeatPosition());
		$this->setGenericFlag(Entity::DATA_FLAG_SADDLED, true);
		$this->driver = $player;
		MyEntities::$inVehicle[$this->driver->getRawUniqueId()] = $this;
		$player->sendMessage(TextFormat::GREEN . "You are now driving this vehicle.");
		$this->broadcastLink($this->driver);
		$player->sendTip(TextFormat::GREEN . "Sneak/Jump to leave the vehicle.");
		if ($this->owner === null) {
			$this->setOwner($player);
			$player->sendMessage(TextFormat::GREEN . "You have claimed this vehicle, you are now its owner.");
		}
		return true;
	}

	/**
	 * Returns the driver if there is one.
	 * @return Player|null
	 */
	public function getDriver(): ?Player
	{
		return $this->driver;
	}

	/**
	 * Checks if the vehicle as a driver.
	 * @return bool
	 */
	public function hasDriver(): bool
	{
		return $this->driver !== null;
	}

	/**
	 * Check if vehicle is locked.
	 * @return bool
	 */
	public function isLocked(): bool
	{
		return $this->properties->carLocked;
	}

	/**
	 * Set vehicle locked.
	 * @param bool $locked
	 */
	public function setLocked(bool $locked = true): void
	{
		$this->properties->carLocked = $locked;
	}

	/**
	 * Get the vehicles owner.
	 * @return UUID|null
	 */
	public function getOwner(): ?UUID
	{
		return $this->owner;
	}

	public function setOwner(Player $player): void
	{
		$this->owner = $player->getUniqueId();
	}

	public function removeOwner(): void
	{
		$this->owner = null;
		$this->properties->carLocked = false;
	}

	public function isFireProof(): bool
	{
		return true;
	}

	protected function broadcastLink(Player $player, int $type = EntityLink::TYPE_RIDER): void
	{
		$pk = new SetActorLinkPacket();
		$pk->link = new EntityLink($this->getId(), $player->getId(), $type);
		MyEntities::getInstance()->getServer()->broadcastPacket($this->getViewers(), $pk);
	}

	/**
	 * This is controller axis input
	 * WASD and phone input is mapped to controller axis input
	 * @see https://docs.unity3d.com/560/Documentation/Manual/ConventionalGameInput.html
	 * @param float $xAxisInput LEFT = 1, RIGHT = -1 Yes, Minecraft got this one messed up -.- It is inverted
	 * @param float $yAxisInput UP/FORWARD = 1, DOWN/BACKWARDS = -1
	 */
	public function input(float $xAxisInput, float $yAxisInput): void
	{
	}

	/**
	 * Calculates the direction in degrees in which the controller stick was pushed
	 * Returns 0 degrees if the controller stick was not pushed
	 * @param float $xAxisInput
	 * @param float $yAxisInput
	 * @return float
	 */
	public function calculateInputDeg(float $xAxisInput, float $yAxisInput): float
	{
		//motionY = cos(deg2rad(α))
		//motionX = sin(deg2rad(α))
		//hypot = hypot(x,y) //should be 1 when not using controller inputs ("snapping" controls)
		//hypot can be zero if x and y are 0, which can lead to division by 0 errors
		//Find angle: sin(α) = opposite/hypot
		//            sin(α) = x/hypot(x,y)
		//Find angle that has sin(α)
		//            α = asin(α)
		//            α = asin(x/hypot(x,y))
		if (($hypot = $this->calculateInputFactor($xAxisInput, $yAxisInput)) === 0.0) return 0.0;
		return rad2deg(asin($xAxisInput / $hypot));
	}

	/**
	 * This calculates the hypotenuse of the inputs, which is how far the controller stick was pushed
	 * @param float $xAxisInput
	 * @param float $yAxisInput
	 * @return float between 0.0 and 1.0
	 */
	public function calculateInputFactor(float $xAxisInput, float $yAxisInput): float
	{
		return hypot($xAxisInput, $yAxisInput);
	}

	public function entityBaseTick(int $tickDiff = 1): bool
	{
		$hasUpdate = parent::entityBaseTick($tickDiff);

		if ($this->isAlive() and $this->hasDriver()) {
			foreach ($this->passengers as $passenger) {
				$passenger->resetFallDistance();
			}
			$this->getDriver()->resetFallDistance();
		}
		return $hasUpdate;
	}
}
