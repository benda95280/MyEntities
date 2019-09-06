<?php

/*	
 *  Original Source: https://github.com/Enes5519/PlayerHead 
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

namespace Benda95280\PlayerHeadObj\entities;

use Benda95280\PlayerHeadObj\PlayerHeadObj;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\entity\Human;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\Player;

class HeadEntityObj extends Human{
    public const HEAD_GEOMETRY_NORMAL = '{
	"geometry.player_headObj_NORMAL": {
		"texturewidth": 64,
		"textureheight": 64,
		"bones": [
			{
				"name": "head",
				"pivot": [0, 0, 0],
				"cubes": [
					{"origin": [-4, 0.5, -4], "size": [8, 8, 8], "uv": [0, 0], "mirror": true},
					{"origin": [-4, 0.5, -4], "size": [8, 8, 8], "uv": [32, 0], "inflate": 0.5, "mirror": true}
				]
			}
		]
	}
}';
    public const HEAD_GEOMETRY_SMALL = '{
	"geometry.player_headObj_SMALL": {
		"texturewidth": 64,
		"textureheight": 64,
		"bones": [
			{
				"name": "head",
				"pivot": [0, 0, 0],
				"cubes": [
					{"origin": [-4, -1.5, -4], "size": [8, 8, 8], "uv": [0, 0], "inflate": -2, "mirror": true},
					{"origin": [-4, -1.5, -4], "size": [8, 8, 8], "uv": [32, 0], "inflate": -1.5, "mirror": true}
				]
			}
		]
	}
}';
    public const HEAD_GEOMETRY_BLOCK1 = '{
	"geometry.player_headObj_BLOCK1": {
		"texturewidth": 64,
		"textureheight": 64,
		"bones": [
			{
				"name": "head",
				"pivot": [0, 0, 0],
				"cubes": [
					{"origin": [-4, 4, -4], "size": [8, 8, 8], "uv": [0, 0], "inflate": 3.5, "mirror": true},
					{"origin": [-4, 4, -4], "size": [8, 8, 8], "uv": [32, 0], "inflate": 4, "mirror": true}
				]
			}
		]
	}
}';

    public $width = 0.5, $height = 0.6;

    protected function initEntity() : void{
		$nbt = $this->namedtag;
		// var_dump($nbt->getCompoundTag("Param"));		
	    $this->setMaxHealth($nbt->getCompoundTag("Param")->getInt("health"));
        $this->setSkin($this->getSkin());
	    parent::initEntity();
    }

    public function hasMovementUpdate() : bool{
        return false;
    }

    public function attack(EntityDamageEvent $source) : void{
        /** @var Player $player */ // #blameJetbrains
		$nbt = $this->namedtag;
		$attack = ($source instanceof EntityDamageByEntityEvent and ($player = $source->getDamager()) instanceof Player) ? $player->hasPermission('PlayerHeadObj.attack') : true;
        if($attack) {
			$player = $source->getDamager();
			$entity = $source->getEntity();
			$item = $player->getInventory()->getItemInHand();
			if ($item->getID() == 280 && $item->getCustomName() == "§6**Obj Rotation**") {
				if ($nbt->getCompoundTag("Param")->getString("size") == "block") {
					//Block must rotate 90°
					$newYaw = ($entity->getYaw() + 90) % 360;
					$entity->setRotation($newYaw, 0);
					$entity->respawnToAll();
				}
				else{
					$newYaw = ($entity->getYaw() + 45) % 360;
					$entity->setRotation($newYaw, 0);
					$entity->respawnToAll();
				}
			}
			elseif ($nbt->getCompoundTag("Param")->getInt("unbreakable") == 1 && $item->getID() != 280 && $item->getCustomName() != "§6**Obj Remover**") {
				//Nothing
			}
			else if ($item->getID() == 280 && $item->getCustomName() == "§6**Obj Remover**") {
				$entity->kill();
			}
			else parent::attack($source);
		}
    }

	public function setSkin(Skin $skin) : void{
		$nbt = $this->namedtag;
		if ($nbt->getCompoundTag("Param")->getString("size") == "small") {
			parent::setSkin(new Skin($skin->getSkinId(), $skin->getSkinData(), '', 'geometry.player_headObj_SMALL', self::HEAD_GEOMETRY_SMALL));
		}
		else if ($nbt->getCompoundTag("Param")->getString("size") == "block") {
			parent::setSkin(new Skin($skin->getSkinId(), $skin->getSkinData(), '', 'geometry.player_headObj_BLOCK1', self::HEAD_GEOMETRY_BLOCK1));
		}
		else {
			parent::setSkin(new Skin($skin->getSkinId(), $skin->getSkinData(), '', 'geometry.player_headObj_NORMAL', self::HEAD_GEOMETRY_NORMAL));
		}

	}

	protected function startDeathAnimation(): void {
    	// Replace death animation with particles
		$this->level->addParticle(new DestroyBlockParticle($this, BlockFactory::get(Block::SOUL_SAND)));
		$this->despawnFromAll();
	}

	protected function endDeathAnimation(): void {
		// We don't need to do this anymore
	}

	public function getDrops() : array{
		//TODO: What's happen if no more exist in config ?
		$nameFinal = ucfirst(PlayerHeadObj::$skinsList[$this->skin->getSkinId()]['name']);
		$param = PlayerHeadObj::$skinsList[$this->skin->getSkinId()]['param'];
        return [PlayerHeadObj::getPlayerHeadItem($this->skin->getSkinId(),$nameFinal,$param)];
    }
}