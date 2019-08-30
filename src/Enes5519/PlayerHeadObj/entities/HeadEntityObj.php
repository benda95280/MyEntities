<?php

/*
 *  PlayerHeadObj - a Altay and PocketMine-MP plugin to add player head on server
 *  Copyright (C) 2018 Enes Yıldırım
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace Enes5519\PlayerHeadObj\entities;

use Enes5519\PlayerHeadObj\PlayerHeadObj;
use pocketmine\entity\Human;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;

class HeadEntityObj extends Human{
    public const HEAD_GEOMETRY = '{
	"geometry.player_headObj2": {
		"texturewidth": 64,
		"textureheight": 64,
		"bones": [
			{
				"name": "head",
				"pivot": [0, 0, 0],
				"cubes": [
					{"origin": [-4, 0, -4], "size": [8, 8, 8], "uv": [0, 0], "mirror": true},
					{"origin": [-4, 0, -4], "size": [8, 8, 8], "uv": [32, 0], "inflate": 0.5, "mirror": true}
				]
			}
		]
	}
}';

    public $width = 0.5, $height = 0.6;

    protected function initEntity() : void{
	    $this->setMaxHealth(1);
        $this->setSkin($this->getSkin());
	    parent::initEntity();
    }

    public function hasMovementUpdate() : bool{
        return false;
    }

    public function attack(EntityDamageEvent $source) : void{
        /** @var Player $player */ // #blameJetbrains
		$attack = ($source instanceof EntityDamageByEntityEvent and ($player = $source->getDamager()) instanceof Player) ? $player->hasPermission('PlayerHeadObj.attack') : true;
        if($attack) parent::attack($source);
    }

	public function setSkin(Skin $skin) : void{
		parent::setSkin(new Skin($skin->getSkinId(), $skin->getSkinData(), '', 'geometry.player_headObj', self::HEAD_GEOMETRY));
		echo"ok";
	}

	public function getDrops() : array{
        return [PlayerHeadObj::getPlayerHeadItem($this->skin, $this->skin->getSkinId())];
    }
}