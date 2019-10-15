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
use Benda95280\MyEntities\entities\entity\CustomEntity;
use Benda95280\MyEntities\entities\entity\CustomEntityProperties;
use Benda95280\MyEntities\entities\head\HeadEntity;
use Benda95280\MyEntities\entities\head\HeadProperties;
use Benda95280\MyEntities\entities\vehicle\CustomVehicle;
use Benda95280\MyEntities\entities\vehicle\VehicleProperties;
use CortexPE\Commando\PacketHooker;
use pocketmine\entity\Entity;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\lang\BaseLang;
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
    /** @var array */
    public static $skinsList;
    /** @var array */
    public static $miscList;
    /** @var string */
    public static $pathSkins;
    /** @var BaseLang */
    public static $language;

    public const PREFIX = TextFormat::BLUE . 'MyEntities' . TextFormat::DARK_GRAY . '> ' . TextFormat::WHITE;

    /**
     * @throws \pocketmine\plugin\PluginException
     * @throws \CortexPE\Commando\exception\HookAlreadyRegistered
     */
    public function onEnable(): void
    {
        self::$instance = $this;
        if (!PacketHooker::isRegistered()) {
            PacketHooker::register($this);
        }

        self::$instance->saveDefaultConfig();
        self::loadConfig();
        $lang = $this->getConfig()->get("misc.language", "en");
        self::$language = new BaseLang((string)$lang, $this->getFile() . "resources/lang/", "en");

        self::logMessage("§a" . self::$language->translateString('init_loading') . " ...", 1);

        //Set Folder Skins
        self::$pathSkins = $this->getDataFolder() . "skins";
        //Save default skins on first load
        if (!is_dir(self::$pathSkins)) {
            foreach ($this->getResources() as $resource) {
                $this->saveResource("skins" . DIRECTORY_SEPARATOR . $resource->getFilename());
            }
        }
        self::$pathSkins .= DIRECTORY_SEPARATOR;

        Entity::registerEntity(HeadEntity::class, true, ['mye_head']);
        Entity::registerEntity(CustomEntity::class, true, ['mye_entity']);
        Entity::registerEntity(CustomVehicle::class, true, ['mye_vehicle']);

        $this->getServer()->getCommandMap()->registerAll("MyEntities", [
            new PHCommand("myentities", self::$language->translateString('cmd_myentities'), ["mye"]),
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

    public static function loadConfig(): void
    {
        //Load configuration file
        //TODO note: this should not be done due to double memory allocation (double ram use) - get data directly from config
        $data = self::getInstance()->getConfig()->getAll();
        self::$skinsList = $data["skins"];
        self::$miscList = $data["misc"];
    }

    public static function getInstance(): MyEntities
    {
        return self::$instance;
    }

    /**
     * @param HeadProperties $properties
     * @return Item
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public static function getPlayerHeadItem(HeadProperties $properties): Item
    {
        $item = (ItemFactory::get(Item::MOB_HEAD, 3))
            ->setCustomBlockData(new CompoundTag("", [
                new CompoundTag("Skin", [
                    new StringTag("Name", $properties->skin->getSkinId()),
                    new ByteArrayTag("Data", $properties->skin->getSkinData()),
                    new ByteArrayTag("CapeData", ""),
                    new StringTag("GeometryName", "geometry.MyEntities_head"),
                    new ByteArrayTag("GeometryData", $properties::GEOMETRY)
                ]),
                self::arrayToCompTag((array)$properties, "MyEntities")
            ]))
            ->setCustomName(TextFormat::colorize('&r' . $properties->name, '&'));
        return $item;
    }

    /**
     * @param CustomEntityProperties $properties
     * @return Item
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public static function getPlayerCustomItem(CustomEntityProperties $properties): Item
    {
        $item = (ItemFactory::get(Item::MOB_HEAD));
        $compoundTag = new CompoundTag("", [
            new CompoundTag("Skin", [
                new StringTag("Name", $properties->skin->getSkinId()),
                new ByteArrayTag("Data", $properties->skin->getSkinData()),
                new ByteArrayTag("CapeData", ""),
                new StringTag("GeometryName", $properties->skin->getGeometryName()),
                new ByteArrayTag("GeometryData", $properties->skin->getGeometryData())
            ]),
            self::arrayToCompTag((array)$properties, "MyEntities"),
        ]);
        if ($properties->usable && $properties->usable["skinchange"] == 1) {
            $compoundTag->setTag(new ByteArrayTag('skin_empty', MyEntities::createSkin($properties->skin->getSkinId() . "_empty")));
        }
        return $item->setCustomBlockData($compoundTag)
            ->setCustomName(TextFormat::colorize('&r' . $properties->name, '&'));
    }

    /**
     * @param VehicleProperties $properties
     * @return Item
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public static function getPlayerCustomItemVehicle(VehicleProperties $properties): Item
    {
        $item = (ItemFactory::get(Item::BOOKSHELF));//TODO different item (minecart)
        $compoundTag = new CompoundTag("", [
            new CompoundTag("Skin", [
                new StringTag("Name", $properties->skin->getSkinId()),
                new ByteArrayTag("Data", $properties->skin->getSkinData()),
                new ByteArrayTag("CapeData", ""),
                new StringTag("GeometryName", $properties->skin->getGeometryName()),
                new ByteArrayTag("GeometryData", $properties->skin->getGeometryData())
            ]),
            self::arrayToCompTag((array)$properties, "MyEntities"),
        ]);
        if ($properties->usable && $properties->usable["skinchange"] === 1) {
            $compoundTag->setTag(new ByteArrayTag('skin_empty', MyEntities::createSkin($properties->skin->getSkinId() . "_empty")));
        }
        return $item->setCustomBlockData($compoundTag)
            ->setCustomName(TextFormat::colorize('&r' . $properties->name, '&'));
    }

    public static function createSkin($skinName): string
    {
        $path = MyEntities::getInstance()->getDataFolder() . "skins" . DIRECTORY_SEPARATOR . "{$skinName}.png";
        $img = @imagecreatefrompng($path);
        $bytes = '';
        for ($y = 0; $y < imagesy($img); $y++) {
            for ($x = 0; $x < imagesx($img); $x++) {
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
     * @param array $array
     * @param string $arrayname
     * @return CompoundTag
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public static function arrayToCompTag(array $array, string $arrayname): CompoundTag
    {
        $tag = new CompoundTag($arrayname, []);
        foreach ($array as $key => $value) {
            if (is_int($value)) $tag->setTag(new IntTag($key, $value));
            else if (is_string($value)) $tag->setTag(new StringTag($key, $value));
            else if (is_array($value)) $tag->setTag(MyEntities::arrayToCompTag($value, $key));
        }
        return $tag;
        //This sadly does not work correctly (because of "no dynamic fields") #blamepmmp - see NoDynamicFieldsTrait
        //$tag = NBTStream::fromArray($array);
        //$tag->setName($arrayname);
        //return $tag;
    }

    /**
     * @param string $message
     * @param int $level
     */
    public static function logMessage(string $message, int $level = 0)
    {
        //Level 0 Only Error
        if (self::$miscList["log-level"] >= $level) self::$instance->getLogger()->info("§4" . $message);
        //Level 1 Minimal thing
        else if (self::$miscList["log-level"] >= $level) self::$instance->getLogger()->info($message);
        //Level 2 Useless Thing
        else if (self::$miscList["log-level"] >= $level) self::$instance->getLogger()->info($message);
    }

}