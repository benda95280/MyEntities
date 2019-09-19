<?php

/*	
 *  Original Source: https://github.com/Enes5519/PlayerHead 
 *  MyEntities - a PocketMine-MP plugin to add player custom entities and support for custom Player Head on server
 *  Copyright (C) 2019 Benda95280
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

namespace Benda95280\MyEntities\entities;

use Benda95280\MyEntities\MyEntities;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Human;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\level\particle\HeartParticle;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class MyCustomEntity extends Human
{
    const SIZE_NORMAL = 1 / 16 * 8;
    const SIZE_SMALL = self::SIZE_NORMAL * 0.5;
    const SIZE_BLOCK = 1;

    public const HEAD_GEOMETRY_NORMAL = '{
	"geometry.MyEntities_head_NORMAL": {
		"texturewidth": 64,
		"textureheight": 64,
		"bones": [
			{
				"name": "head",
				"pivot": [0, 0, 0],
				"cubes": [
					{"origin": [-4, 0.5, -4], "size": [8, 8, 8], "uv": [0, 0]},
					{"origin": [-4, 0.5, -4], "size": [8, 8, 8], "uv": [32, 0], "inflate": 0.5}
				]
			}
		]
	}
}';
    public const HEAD_GEOMETRY_SMALL = '{
	"geometry.MyEntities_head_SMALL": {
		"texturewidth": 64,
		"textureheight": 64,
		"bones": [
			{
				"name": "head",
				"pivot": [0, 0, 0],
				"cubes": [
					{"origin": [-4, -1.5, -4], "size": [8, 8, 8], "uv": [0, 0], "inflate": -2},
					{"origin": [-4, -1.5, -4], "size": [8, 8, 8], "uv": [32, 0], "inflate": -1.5}
				]
			}
		]
	}
}';
    public const HEAD_GEOMETRY_BLOCK = '{
	"geometry.MyEntities_head_BLOCK": {
		"texturewidth": 64,
		"textureheight": 64,
		"bones": [
			{
				"name": "head",
				"pivot": [0, 0, 0],
				"cubes": [
					{"origin": [-4, 4, -4], "size": [8, 8, 8], "uv": [0, 0], "inflate": 3.5},
					{"origin": [-4, 4, -4], "size": [8, 8, 8], "uv": [32, 0], "inflate": 4}
				]
			}
		]
	}
}';
    public $width = self::SIZE_NORMAL;
    public $height = self::SIZE_NORMAL;

    protected function initEntity(): void
    {
        $nbt = $this->namedtag;
        $this->setMaxHealth($nbt->getCompoundTag("Param")->getInt("health"));
        parent::initEntity();
        #$this->setGenericFlag(self::DATA_FLAG_HAS_COLLISION, true);
        $this->setGenericFlag(self::DATA_FLAG_STACKABLE, true);
        $this->setSkin($this->getSkin());//see setSkil for custom geometry collision hack
        $this->refreshBoundingBoxProperties();
        $this->setNameTagVisible(false);
        $this->setNameTagAlwaysVisible(false);
        $this->setGenericFlag(self::DATA_FLAG_AFFECTED_BY_GRAVITY, false);
        $this->setImmobile(true);
        $this->setGenericFlag(self::DATA_FLAG_SILENT, true);//TODO custom sounds
        $this->setPositionAndRotation($this->asVector3(), $this->yaw, $this->pitch);
    }

    protected function applyGravity(): void
    {
    }

    public function onCollideWithPlayer(Player $player): void
    {
        $player->setGenericFlag(self::DATA_FLAG_STACKABLE, true);
    }

    /**
     * @param EntityDamageEvent $source
     * @throws \InvalidStateException
     * @throws \InvalidArgumentException
     */
    public function attack(EntityDamageEvent $source): void
    {
        if ($source instanceof EntityDamageByEntityEvent AND $source->getDamager() instanceof Player) {
            $player = $source->getDamager();
            if ($player instanceof Player && $player->hasPermission('MyEntities.attack')) {
                $nbt = $this->namedtag;
                $entity = $source->getEntity();
                if (!$entity instanceof MyCustomEntity) return;
                $item = $player->getInventory()->getItemInHand();
                if ($item->getID() === ItemIds::STICK && $item->getCustomName() === "§6**Obj Rotation**") {
                    if ($nbt->getCompoundTag("Param")->getString("size", "block") == "block") {
                        //Block must rotate 90°
                        $newYaw = ($entity->getYaw() + 90) % 360;
                        $entity->setRotation($newYaw, 0);
                        $entity->respawnToAll();
                    } else {
                        $newYaw = ($entity->getYaw() + 45) % 360;
                        $entity->setRotation($newYaw, 0);
                        $entity->respawnToAll();
                    }
                } else if ($nbt->getCompoundTag("Param")->getInt("unbreakable") == 1 && $item->getID() != ItemIds::STICK && $item->getCustomName() != "§6**Obj Remover**") {
                    //Nothing
                    $player->sendMessage(TextFormat::colorize("§4Unbreakable"));
                } else if ($item->getID() == ItemIds::STICK && $item->getCustomName() == "§6**Obj Remover**") {
                    $entity->kill();
                } else if ($nbt->getCompoundTag("Param")->hasTag("usable")) {
                    $usable_time = $nbt->getCompoundTag("Param")->getCompoundTag("usable")->getInt("time");
                    $empty_remove = $nbt->getCompoundTag("Param")->getCompoundTag("usable")->getInt("destruction");
                    $showMsg = $nbt->getCompoundTag("Param")->getCompoundTag("usable")->getInt("use_msg");
                    $msgDestruction = $nbt->getCompoundTag("Param")->getCompoundTag("usable")->getString("destruction_msg");
                    $skinChange = $nbt->getCompoundTag("Param")->getCompoundTag("usable")->getInt("skinchange");

                    if ($usable_time >= 1) {
                        //I'm usable item, so use it !
                        $actions = json_decode($nbt->getCompoundTag("Param")->getCompoundTag("usable")->getString("action"), true);
                        $randAction = $nbt->getCompoundTag("Param")->getCompoundTag("usable")->getInt("action_random");
                        if ($randAction == 1) {
                            $randIndex = array_rand($actions);
                            if (is_array($actions[$randIndex])) {
                                foreach ($actions[$randIndex] as $actionName => $actionValue) {
                                    self::doAction($actionName, $actionValue, $player);
                                }
                            } else self::doAction($randIndex, $actions[$randIndex], $player);
                        } else {
                            foreach ($actions as $actionName => $actionValue) {
                                self::doAction($actionName, $actionValue, $player);
                            }
                        }
                        //After used, change value
                        $usable_time--;
                        if ($showMsg == 1 && $usable_time != 0)
                            $player->sendMessage(TextFormat::colorize("Remaining: " . $usable_time));
                        else if ($showMsg == 1 && $usable_time == 0) {
                            $player->sendMessage(TextFormat::colorize("Nom it's empty ..."));
                            //Need new skin ?
                            if ($skinChange == 1) {
                                $nbt->getCompoundTag("Param")->getCompoundTag("usable")->setInt("time", 0);
                                $entity->setSkin($this->getSkin());
                                $entity->respawnToAll();
                            }
                        }
                    } else {
                        //Show message that is not usable for now ... (Or forever)
                        $player->sendMessage(TextFormat::colorize("Not usable ... Sorry"));
                    }

                    //Do i need to be removed ?
                    if ($usable_time < 1 && $empty_remove == 1) {
                        $entity->kill();
                        if ($showMsg == 1)
                            $player->sendMessage(TextFormat::colorize($msgDestruction));
                    } else $nbt->getCompoundTag("Param")->getCompoundTag("usable")->setInt("time", $usable_time);
                } else {
                    parent::attack($source);
                }
            }
        }
    }

    public function setSkin(Skin $skin): void
    {
        $nbt = $this->namedtag;
        //Which Skin i need to set ?
        if ($nbt->getCompoundTag("Param")->hasTag("usable") && $this->namedtag->getCompoundTag("Param")->getCompoundTag("usable")->getInt("skinchange") == 1 && $nbt->getCompoundTag("Param")->getCompoundTag("usable")->getInt("time") == 0) {
            $skinToSet = $this->namedtag->getByteArray("Skin_empty");
        } else {
            $skinToSet = $skin->getSkinData();
        }
        if ($nbt->hasTag("Geometry")) {
            parent::setSkin(new Skin($skin->getSkinId(), $skinToSet, '', $nbt->getCompoundTag("Param")->getString("geometryName"), $nbt->getString("Geometry")));
            $this->width = $this->height = self::SIZE_NORMAL;//TODO correct based on geometry?
            $this->refreshBoundingBoxProperties();
            //hack, we can not detect custom entity bounds yet, so disable colliding
            $this->setGenericFlag(self::DATA_FLAG_STACKABLE, false);
        } else if ($nbt->getCompoundTag("Param")->getString("size") == "small") {
            parent::setSkin(new Skin($skin->getSkinId(), $skinToSet, '', 'geometry.MyEntities_head_SMALL', self::HEAD_GEOMETRY_SMALL));
            $this->width = $this->height = self::SIZE_SMALL;
            $this->refreshBoundingBoxProperties();
        } else if ($nbt->getCompoundTag("Param")->getString("size") == "block") {
            parent::setSkin(new Skin($skin->getSkinId(), $skinToSet, '', 'geometry.MyEntities_head_BLOCK', self::HEAD_GEOMETRY_BLOCK));
            $this->width = $this->height = self::SIZE_BLOCK;
            $this->refreshBoundingBoxProperties();
        } else {
            parent::setSkin(new Skin($skin->getSkinId(), $skinToSet, '', 'geometry.MyEntities_head_NORMAL', self::HEAD_GEOMETRY_NORMAL));
            $this->width = $this->height = self::SIZE_NORMAL;
            $this->refreshBoundingBoxProperties();
        }

    }

    private function refreshBoundingBoxProperties(): void
    {
        $this->getDataPropertyManager()->setFloat(self::DATA_BOUNDING_BOX_HEIGHT, $this->width);
        $this->getDataPropertyManager()->setFloat(self::DATA_BOUNDING_BOX_WIDTH, $this->height);
    }

    /**
     * @throws \InvalidArgumentException
     */
    protected function startDeathAnimation(): void
    {
        // Replace death animation with particles
        $this->level->addParticle(new DestroyBlockParticle($this, BlockFactory::get(Block::MOB_HEAD_BLOCK)));
        $this->despawnFromAll();
    }

    protected function endDeathAnimation(): void
    {
        // We don't need to do this anymore
    }

    /**
     * @return array
     * @throws \RuntimeException
     */
    public function getDrops(): array
    {
        //TODO: What's happen if no more exist in config ?
        try {
            if (!$this->namedtag->getCompoundTag("Param")->hasTag("usable")) {
                $nameFinal = ucfirst(MyEntities::$skinsList[$this->skin->getSkinId()]['name']);
                $param = MyEntities::$skinsList[$this->skin->getSkinId()]['param'];
                return [MyEntities::getPlayerHeadItem($this->skin->getSkinId(), $nameFinal, $param)];
            } else return [];
        } catch (\InvalidArgumentException $exception) {
            return [];
        }
    }

    /**
     * @param $actionName
     * @param $actionValue
     * @param Player $player
     * @throws \InvalidArgumentException
     */
    private function doAction($actionName, $actionValue, Player $player): void
    {
        //Do action
        switch ($actionName) {
            case "msg":
                $msgs = explode(";", $actionValue);
                foreach ($msgs as $indvMsg) {
                    $player->sendMessage(TextFormat::colorize($indvMsg));
                }
                break;
            case "heal":
                $player->heal(new EntityRegainHealthEvent($player, $actionValue, EntityRegainHealthEvent::CAUSE_CUSTOM));
                $player->getLevel()->addParticle(new HeartParticle($player->add(0, 2), 4));
                break;
            case "teleport":
                $pos = explode(";", $actionValue);
                $player->teleport(new Position(intval($pos[0]), intval($pos[1]), intval($pos[2])));
                break;
            case "effect":
                //  EFFECT/Amplifier/Duration
                $effects = explode(";", $actionValue);
                foreach ($effects as $indvEffect) {
                    $effectsExp = explode("/", $indvEffect);
                    $player->addEffect((new EffectInstance(Effect::getEffect(intval($effectsExp[0]))))->setAmplifier(intval($effectsExp[1]))->setDuration(20 * intval($effectsExp[2]))->setVisible(false));
                }
                break;
            case "item":
                //  ID/meta/count
                $toGive = explode(";", $actionValue);
                foreach ($toGive as $indvtoGive) {
                    $toGiveExp = explode("/", $indvtoGive);
                    $player->getInventory()->addItem(Item::get(intval($toGiveExp[0]), intval($toGiveExp[1]), intval($toGiveExp[2])));
                }
                break;
        }

    }
}