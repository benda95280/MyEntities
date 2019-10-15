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
use pocketmine\command\ConsoleCommandSender;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\level\Level;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\level\particle\HeartParticle;
use pocketmine\level\Position;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

abstract class BaseEntity extends Human
{
    /** @var Properties */
    public $properties;

    public function __construct(Properties $properties, Level $level, CompoundTag $nbt)
    {
        $this->properties = $properties;
        $this->width = $this->properties->width;
        $this->height = $this->properties->height;
        $this->maxDeadTicks = 0;
        parent::__construct($level, $nbt);
    }

    protected function initEntity(): void
    {
        $this->setMaxHealth($this->properties->health);
        parent::initEntity();
        $this->setNameTagVisible(false);
        $this->setNameTagAlwaysVisible(false);
        $this->setGenericFlag(self::DATA_FLAG_AFFECTED_BY_GRAVITY, false);
        $this->setImmobile(true);
        $this->setGenericFlag(self::DATA_FLAG_SILENT, true);
        $this->setGenericFlag(self::DATA_FLAG_STACKABLE, true);
        $this->setGenericFlag(self::DATA_FLAG_HAS_COLLISION, true);
        $this->getDataPropertyManager()->setFloat(self::DATA_SCALE, $this->properties->size / Properties::SIZE_NORMAL);
    }

    protected function applyGravity(): void
    {
    }

    protected function doFoodTick(int $tickDiff = 1): void
    {
    }

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        if ($this->properties->rotationPerTick !== 0) {
            $newYaw = ($this->getYaw() + $this->properties->rotationPerTick) % 360;
            $this->setRotation($newYaw, 0);
        }
        return parent::entityBaseTick($tickDiff);
    }

    public function onCollideWithPlayer(Player $player): void
    {
        $player->setGenericFlag(self::DATA_FLAG_STACKABLE, true);
        /*if ($player->isCollidedHorizontally)*/
        $player->updateFallState(0, true);
    }

    protected function recalculateBoundingBox(): void
    {
        parent::recalculateBoundingBox();
        if (isset($this->propertyManager)) {
            $this->getDataPropertyManager()->setFloat(self::DATA_BOUNDING_BOX_HEIGHT, $this->width);
            $this->getDataPropertyManager()->setFloat(self::DATA_BOUNDING_BOX_WIDTH, $this->height);
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    protected function startDeathAnimation(): void
    {
        $this->level->addParticle(new DestroyBlockParticle($this, $this->properties::getDestroyParticlesBlock()));
    }

    public function getDrops(): array
    {
        return [];
    }

    public function attack(EntityDamageEvent $source): void
    {
        /** @var Player $player */
        if ($source instanceof EntityDamageByEntityEvent AND ($player = $source->getDamager()) instanceof Player) {
            $item = $player->getInventory()->getItemInHand();
            if ($player->hasPermission('MyEntities.attack')) {
                //handle admin items
                if ($item->getID() === ItemIds::STICK && $item->getCustomName() === "§6**Obj Remover**") {
                    $this->kill();
                    $source->setCancelled();
                    return;
                }
                if ($item->getID() === ItemIds::STICK && $item->getCustomName() === "§6**Obj Rotation**") {
                    $newYaw = ($this->getYaw() + 45) % 360;
                    $this->setRotation($newYaw, 0);
                    #$this->respawnToAll();//Without it rotates smoothly
                    $source->setCancelled();
                    return;
                }
                //Handle use
                if ($this->properties->usable) {
                    $usable_time = $this->properties->usable["time"];
                    $empty_remove = $this->properties->usable["destruction"];
                    $showMsg = $this->properties->usable["use_msg"];
                    $msgDestruction = $this->properties->usable["destruction_msg"];
                    $skinChange = $this->properties->usable["skinchange"];

                    if ($usable_time >= 1) {
                        //I'm usable item, so use it !

                        //Create var to prevent spamming entity
                        //Prevent Spamming entity
                        if ($this->properties->lastUsed === -1 || (MyEntities::getInstance()->getServer()->getTick() - $this->properties->lastUsed) > 20) {
                            $this->properties->lastUsed = MyEntities::getInstance()->getServer()->getTick();

                            $actions = json_decode($this->properties->usable["action"], true);
                            $randAction = $this->properties->usable["action_random"];
                            if ($randAction == 1) {
                                $randIndex = array_rand($actions);
                                if (is_array($actions[$randIndex])) {
                                    foreach ($actions[$randIndex] as $actionName => $actionValue) {
                                        $this->doAction($actionName, $actionValue, $player);
                                    }
                                } else $this->doAction($randIndex, $actions[$randIndex], $player);
                            } else {
                                foreach ($actions as $actionName => $actionValue) {
                                    $this->doAction($actionName, $actionValue, $player);
                                }
                            }
                            //After used, change value
                            $usable_time--;
                            if ($showMsg == 1 && $usable_time != 0)
                                $player->sendMessage(TextFormat::colorize(MyEntities::$language->translateString('ent_remaining') . ": " . $usable_time));
                            else if ($showMsg == 1 && $usable_time == 0) {
                                $player->sendMessage(TextFormat::colorize(MyEntities::$language->translateString('ent_empty')));
                                //Need new skin ?
                                if ($skinChange == 1) {
                                    $this->properties->usable["time"] = 0;
                                    $this->setSkin($this->getSkin());
                                    $this->respawnToAll();
                                }
                            }
                        } else $player->sendMessage(TextFormat::colorize(MyEntities::$language->translateString('ent_donotspam')));

                    } else $player->sendMessage(TextFormat::colorize(MyEntities::$language->translateString('ent_notusable')));
                    //Show message that is not usable for now ... (Or forever)

                    //Do i need to be removed ?
                    if ($usable_time < 1 && $empty_remove == 1) {
                        $this->kill();
                        if ($showMsg == 1)
                            $player->sendMessage(TextFormat::colorize($msgDestruction));
                    } else $this->properties->usable["time"] = $usable_time;

                    $source->setCancelled();
                    return;
                }
                Entity::attack($source);
            }
        }
    }

    public function getSkin(): Skin
    {
        if ($this->properties->usable && $this->properties->usable["time"] === 0) {
            return new Skin(
                parent::getSkin()->getSkinId(),
                $this->namedtag->getByteArray("skin_empty", parent::getSkin()->getSkinData(), true),
                parent::getSkin()->getCapeData(),
                parent::getSkin()->getGeometryName(),
                parent::getSkin()->getGeometryData()
            );
        }
        return parent::getSkin();
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
            case "repair":
                //Repair item in hand
                $index = $player->getInventory()->getHeldItemIndex();
                $item = $player->getInventory()->getItem($index);
                if ($item instanceof Durable) {
                    if ($item->getDamage() > 0) {
                        $player->getInventory()->setItem($index, $item->setDamage(0));
                        $player->sendMessage(TextFormat::GREEN . " " . MyEntities::$language->translateString('action_repair_success'));
                    } else {
                        $player->sendMessage(TextFormat::RED . "[Error]" . TextFormat::DARK_RED . " " . MyEntities::$language->translateString('action_repair_nodmg'));
                    }
                } else {
                    $player->sendMessage(TextFormat::RED . "[Error]" . TextFormat::DARK_RED . " " . MyEntities::$language->translateString('action_repair_cannot'));
                }
                break;
            case "cmd":
                //Execute command / actionValue = {console/player};command 1;command 2 ...
                //TODO: How to Handle Error
                $toExecute = explode(";", $actionValue);
                $whoExecute = $toExecute[0];
                unset($toExecute[0]);

                foreach ($toExecute as $indvtoExecute) {
                    if ($whoExecute === "console")
                        $this->getPlugin()->getServer()->dispatchCommand(new ConsoleCommandSender(), $indvtoExecute);
                    else if ($whoExecute === "player")
                        $this->getPlugin()->getServer()->dispatchCommand($player, $indvtoExecute);
                }
                break;

            //TODO: DEFAULT ? ERROR ?
        }

    }

    public function saveNBT(): void
    {
        parent::saveNBT();
        $this->namedtag->setTag($this->properties->toTag());
    }

}