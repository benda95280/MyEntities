<?php

declare(strict_types=1);

namespace Benda95280\MyEntities\entities\vehicle;

use Benda95280\MyEntities\entities\entity\CustomEntity;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityIds;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\SetActorLinkPacket;
use pocketmine\network\mcpe\protocol\types\EntityLink;
use pocketmine\Player;
use pocketmine\utils\UUID;

class CustomVehicle extends CustomEntity
{

	public const NETWORK_ID = EntityIds::PLAYER;

	public static $ridingEntities = [];

	protected $uuid;
	protected $rider = null;
	protected $riderOffset = -8;

	protected $gravity = 0.0008;

	protected $baseOffset = 1.62;

	public function __construct(Level $level, CompoundTag $nbt)
	{
		parent::__construct($level, $nbt);
	}

	protected function initEntity(): void
	{
		parent::initEntity();

		$this->uuid = UUID::fromRandom();

		// Not working since Minecraft hardcoded this
		$this->propertyManager->setString(Entity::DATA_INTERACTIVE_TAG, "Ride");

		$this->setGenericFlag(Entity::DATA_FLAG_AFFECTED_BY_GRAVITY, false);
	}

	/**
	 * @return Block
	 * @throws \InvalidArgumentException
	 */
	public static function getDestroyParticlesBlock(): Block
	{
		return BlockFactory::get(BlockIds::AIR);
	}

	public function getName(): string
	{
		return $this->getNameTag();
	}

	public function getUniqueId(): ?UUID
	{
		return $this->uuid;
	}

	public function getRider(): ?Player
	{
		return $this->rider;
	}

	public function isRiding()
	{
		return $this->rider instanceof Player;
	}

	public function ride(Player $player): void
	{
		if (isset(self::$ridingEntities[$player->getName()])) {
			$player->sendPopup("§cYou are now riding");
			return;
		}
		if ($this->rider instanceof Player) {
			$player->sendPopup("§cSomeone is already riding");
			return;
		}
		$this->rider = $player;
		self::$ridingEntities[$player->getName()] = $this;

		$player->setGenericFlag(Entity::DATA_FLAG_WASD_CONTROLLED, true);
		$player->setGenericFlag(Entity::DATA_FLAG_RIDING, true);
		$this->setGenericFlag(Entity::DATA_FLAG_SADDLED, true);

		// not working AFAIK
		$this->propertyManager->setVector3(Entity::DATA_RIDER_SEAT_POSITION, $this->getRiderSeatPosition());
		$this->propertyManager->setByte(Entity::DATA_CONTROLLING_RIDER_SEAT_NUMBER, 0);

		foreach ($this->getViewers() as $viewer) {
			$this->sendLink($viewer);
		}
		$this->rider->sendPopup("§bPress jump or sneak to throw off");
	}

	/**
	 * This is controller axis input
	 * WASD and phone input is mapped to controller axis input
	 * @see https://docs.unity3d.com/560/Documentation/Manual/ConventionalGameInput.html
	 * @param float $xAxisInput
	 * @param float $yAxisInput
	 */
	public function input(float $xAxisInput, float $yAxisInput)
	{
		// motionX LEFT = 1, RIGHT = -1
		// motionY UP = 1, DOWN = -1
		//
		// * NOTE
		// when player press a couple of KEY at the same time,
		// motionX and motionY will have a slightly lower value,
		// ~ 0.707 (or cos(45 deg))
		// This is due to controller input mapping

		// you can implement player input override this method
		if ($xAxisInput > 0) $this->yaw -= 5;
		else if ($xAxisInput < 0) $this->yaw += 5;

		if ($yAxisInput > 0) {
			$this->motion = $this->getDirectionVector()->multiply(0.2);
			$this->motion->y = 0.1;
		} else if ($yAxisInput < 0) {
			$this->motion = $this->getDirectionVector()->multiply(0.2);
			$this->motion->y = -0.1;
		}
	}

	/**
	 * Calculates the direction in degrees in which the controller stick was pushed
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
		if (($hypot = $this->calculateInputFactor($xAxisInput, $yAxisInput)) === 0.0) return rad2deg(asin($hypot));
		return rad2deg(asin($xAxisInput / $hypot));
	}

	/**
	 * This calculates the hypotenuse of the inputs, which is how far the controller stick was pushed
	 * @param float $xAxisInput
	 * @param float $yAxisInput
	 * @return float
	 */
	public function calculateInputFactor(float $xAxisInput, float $yAxisInput): float
	{
		return hypot($xAxisInput, $yAxisInput);
	}

	public function entityBaseTick(int $tickDiff = 1): bool
	{
		$hasUpdate = parent::entityBaseTick($tickDiff);

		if ($this->isAlive() and $this->isRiding()) {
			$this->getRider()->resetFallDistance();
		}
		return $hasUpdate;
	}

	public function dismount(bool $immediate = false)
	{
		if (!$this->rider instanceof Player) return;

		$this->rider->setGenericFlag(Entity::DATA_FLAG_WASD_CONTROLLED, false);
		$this->rider->setGenericFlag(Entity::DATA_FLAG_RIDING, false);
		$this->setGenericFlag(Entity::DATA_FLAG_SADDLED, false);

		foreach ($this->getViewers() as $viewer) {
			$this->sendLink($viewer, EntityLink::TYPE_REMOVE, $immediate);
		}

		unset(self::$ridingEntities[$this->rider->getName()]);
		$this->rider = null;
	}

	public function kill(): void
	{
		$this->dismount(true);
		parent::kill();
	}

	//TODO
	public function getRiderSeatPosition(int $seatNumber = 0)
	{
		return new Vector3(0, $this->height * 0.75 + $this->riderOffset, 0);
	}

	public function sendLink(Player $player, int $type = EntityLink::TYPE_RIDER, bool $immediate = false): void
	{
		if (!$this->rider instanceof Player) return;

		if (!isset($player->getViewers()[$this->rider->getLoaderId()])) {
			// force spawn for link
			$this->rider->spawnTo($player);
		}

		$from = $this->getId();
		$to = $this->rider->getId();

		$pk = new SetActorLinkPacket();
		$pk->link = new EntityLink($from, $to, $type, $immediate);
		$player->sendDataPacket($pk);
	}
}
