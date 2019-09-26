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

namespace Benda95280\MyEntities;

use Benda95280\MyEntities\commands\PHCommand;
use Benda95280\MyEntities\entities\MyCustomEntity;
use CortexPE\Commando\PacketHooker;
use pocketmine\entity\Entity;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class MyEntities extends PluginBase implements Listener
{
    /** @var MyEntities */
    private static $instance;
    /** @var array $skinsList */
    public static $skinsList;
    /** @var array $miscList */
    public static $miscList;
    /** @var array $configData */
    public static $configData;
    /** @var String $pathSkins */
    public static $pathSkins;

    public const PREFIX = TextFormat::BLUE . 'MyEntities' . TextFormat::DARK_GRAY . '> ' . TextFormat::WHITE;

    /**
     * @throws \pocketmine\plugin\PluginException
     * @throws \CortexPE\Commando\exception\HookAlreadyRegistered
     */
    public function onEnable(): void
    {
        if (self::$instance === null) {
            self::$instance = $this;
        }
        if (!PacketHooker::isRegistered()) {
            PacketHooker::register($this);
        }

        self::$instance->saveDefaultConfig();
        self::loadConfig();

        self::logMessage("§aLoading ...", 1);

        //Set Folder Skins
        self::$pathSkins = $this->getDataFolder() . "skins";
        //Save default skins on first load
        if (!is_dir(self::$pathSkins)) {
            foreach ($this->getResources() as $resource) {
                $this->saveResource("skins" . DIRECTORY_SEPARATOR . $resource->getFilename());
            }
        }
        self::$pathSkins .= DIRECTORY_SEPARATOR;

        Entity::registerEntity(MyCustomEntity::class, true, ['MyEntities']);
        $this->getServer()->getCommandMap()->registerAll("MyEntities", [
            new PHCommand("myentities", "Give an entity or item to a player", ["mye"]),
        ]);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
        //Count skins available
        $countFileSkinsHeadSmall = 0;
        $countFileSkinsHeadNormal = 0;
        $countFileSkinsHeadBlock = 0;
        $countFileSkinsCustom = 0;

        try {
            CheckIn::check($countFileSkinsHeadSmall, $countFileSkinsHeadNormal, $countFileSkinsHeadBlock, $countFileSkinsCustom);
        } catch (\InvalidStateException $e) {
        }
    }

    public static function loadConfig()
    {
        //Load configuration file
        $data = self::getInstance()->getConfig()->getAll();
        //Define Public Var Data-Config File
        self::$configData = $data;
        self::$skinsList = $data["skins"];
        self::$miscList = $data["misc"];
    }

    public static function getInstance(): MyEntities
    {
        return self::$instance;
    }

    /**
     * @param string $name
     * @param string $nameFinal
     * @param array $param
     * @return Item
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public static function getPlayerHeadItem(string $name, string $nameFinal, array $param): Item
    {
        if (isset($param["usable"]["skinchange"]) && $param["usable"]["skinchange"] == 1)
            $item = (ItemFactory::get(Item::MOB_HEAD, 3))
                ->setCustomBlockData(new CompoundTag("", [
                    new CompoundTag('skin', [
                        new StringTag('Name', $name),
                        new ByteArrayTag('Data', MyEntities::createSkin($name)),
                    ]),
                    new ByteArrayTag('skin_empty', MyEntities::createSkin($name . "_empty")),
                    MyEntities::arrayToCompTag($param, "param")
                ]))
                ->setCustomName(TextFormat::colorize('&r' . $nameFinal, '&'));
        else if (isset($param["data"]))
            $item = (ItemFactory::get(Item::MOB_HEAD, 3))
                ->setCustomBlockData(new CompoundTag("", [
                    new CompoundTag('skin', [
                        new StringTag('Name', $name),
                        new ByteArrayTag('Data', $param["data"]),
                    ]),
                    MyEntities::arrayToCompTag($param, "param")
                ]))
                ->setCustomName(TextFormat::colorize(sprintf('&r&6%s\'s Head', $nameFinal), '&'));
        else
            $item = (ItemFactory::get(Item::MOB_HEAD, 3))
                ->setCustomBlockData(new CompoundTag("", [
                    new CompoundTag('skin', [
                        new StringTag('Name', $name),
                        new ByteArrayTag('Data', MyEntities::createSkin($name)),
                    ]),
                    MyEntities::arrayToCompTag($param, "param")
                ]))
                ->setCustomName(TextFormat::colorize('&r' . $nameFinal, '&'));
        return $item;
    }

    /**
     * @param string $name
     * @param string $nameFinal
     * @param array $param
     * @return Item
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public static function getPlayerCustomItem(string $name, string $nameFinal, array $param): Item
    {
        $pathSkinsHead = self::$instance->getDataFolder() . "skins" . DIRECTORY_SEPARATOR;
        if (isset($param["usable"]["skinchange"]) && $param["usable"]["skinchange"] == 1)
            $item = (ItemFactory::get(Item::END_PORTAL_FRAME))
                ->setCustomBlockData(new CompoundTag("", [
                    new CompoundTag('skin', [
                        new StringTag('Name', $name),
                        new ByteArrayTag('Data', MyEntities::createSkin($name)),
                    ]),
                    new ByteArrayTag('skin_empty', MyEntities::createSkin($name . "_empty")),
                    MyEntities::arrayToCompTag($param, "param"),
                    new StringTag('Geometry', file_get_contents($pathSkinsHead . $name . '.json'))
                ]))
                ->setCustomName(TextFormat::colorize('&r' . $nameFinal, '&'));
        else
            $item = (ItemFactory::get(Item::END_PORTAL_FRAME))
                ->setCustomBlockData(new CompoundTag("", [
                    new CompoundTag('skin', [
                        new StringTag('Name', $name),
                        new ByteArrayTag('Data', MyEntities::createSkin($name)),
                    ]),
                    MyEntities::arrayToCompTag($param, "param"),
                    new StringTag('Geometry', file_get_contents($pathSkinsHead . $name . '.json'))

                ]))
                ->setCustomName(TextFormat::colorize('&r' . $nameFinal, '&'));
        return $item;
    }

    public static function createSkin($skinName)
    {
        $path = MyEntities::getInstance()->getDataFolder() . "skins" . DIRECTORY_SEPARATOR . "{$skinName}.png";
        $img = @imagecreatefrompng($path);
        $bytes = '';
        $l = (int)@getimagesize($path)[1];
        for ($y = 0; $y < $l; $y++) {
            for ($x = 0; $x < 64; $x++) {
                $rgba = @imagecolorat($img, $x, $y);
                $a = ((~((int)($rgba >> 24))) << 1) & 0xff;
                $r = ($rgba >> 16) & 0xff;
                $g = ($rgba >> 8) & 0xff;
                $b = $rgba & 0xff;
                $bytes .= chr($r) . chr($g) . chr($b) . chr($a);
            }
        }
        @imagedestroy($img);
        return $bytes;
    }

    /**
     * @param $array
     * @param String $arrayname
     * @return CompoundTag
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public static function arrayToCompTag($array, String $arrayname)
    {
        $tag = new CompoundTag($arrayname, []);
        foreach ($array as $key => $value) {
            if (is_int($value)) $tag->setTag(new IntTag($key, $value));
            else if (is_string($value)) $tag->setTag(new StringTag($key, $value));
            else if (is_array($value)) $tag->setTag(MyEntities::arrayToCompTag($value, $key));
        }
        return $tag;
    }

    public static function logMessage(String $message, $level)
    {
        //Level 0 Only Error
        if (self::$miscList["log-level"] >= $level) self::$instance->getLogger()->info("§4" . $message);
        //Level 1 Minimal thing
        else if (self::$miscList["log-level"] >= $level) self::$instance->getLogger()->info($message);
        //Level 2 Usless Thing
        else if (self::$miscList["log-level"] >= $level) self::$instance->getLogger()->info($message);
    }

    public function checkAction($actions)
    {
        foreach ($actions as $actionName => $actionValue) {
            if (is_object($actionValue)) {
                foreach ($actionValue as $actionName1 => $actionValue1) {
                    //This actions is a set of actions
                    if (!self::checkActionDetail($actionName1, $actionValue1)) {
                        self::logMessage("Invalid action SET for '$actionName1' -> action-Usable-Param ! It has been removed from plugin.", 0);
                        return false;
                    }
                }
            } else {
                //No set of actions
                if (!self::checkActionDetail($actionName, $actionValue)) {
                    self::logMessage("Invalid action for '$actionName' -> action-Usable-Param ! It has been removed from plugin.", 0);
                    unset(self::$skinsList[$actionName]);
                    return false;
                }
            }

        }
        return true;
    }

    private function checkActionDetail($actionName, $actionValue)
    {
        //Check action
        switch ($actionName) {
            case "msg":
                if (is_string($actionValue)) return true;
                else return false;
                break;
            case "heal":
                if (is_integer($actionValue)) return true;
                else return false;
                break;
            case "teleport":
                $pos = explode(";", $actionValue);
                if (isset($pos[0]) AND isset($pos[1]) AND isset($pos[2]) AND !isset($pos[3])) return true;
                else return false;
                break;
            case "effect":
                //  EFFECT/Amplifier/Duration
                $effects = explode(";", $actionValue);
                foreach ($effects as $indvEffect) {
                    $effectsExp = explode("/", $indvEffect);
                    if (!isset($effectsExp[0]) OR !isset($effectsExp[1]) OR !isset($effectsExp[2]) OR isset($effectsExp[3])) return false;
                }
                return true;
                break;
            case "item":
                //  ID/meta/count
                $toGive = explode(";", $actionValue);
                foreach ($toGive as $indvtoGive) {
                    $toGiveExp = explode("/", $indvtoGive);
                    if (!isset($toGiveExp[0]) OR !isset($toGiveExp[1]) OR !isset($toGiveExp[2]) OR isset($toGiveExp[3])) return false;
                }
                return true;
                break;
            default:
                return false;
        }

    }

}