<?php

declare(strict_types=1);

namespace Benda95280\MyEntities\entities\vehicle;

use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;

class HotairBalloonVehicle extends CustomVehicle
{
	protected $gravity = 0.0008;

	protected $baseOffset = 1.62;

	public function __construct(Level $level, CompoundTag $nbt)
	{
		parent::__construct($level, $nbt);
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
}
