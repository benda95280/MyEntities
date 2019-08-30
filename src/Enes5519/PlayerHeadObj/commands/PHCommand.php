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

namespace Enes5519\PlayerHeadObj\commands;

use Enes5519\PlayerHeadObj\PlayerHeadObj;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\plugin\PluginBase;



class PHCommand extends Command{
	/** @var array */
	private $messages;

	public function __construct(array $messages){
		$this->messages = $messages;
		parent::__construct('PlayerHeadObj', 'Give a player head', '/PlayerHeadObj <playerName:string>', ['pho']);
		$this->setPermission('PlayerHeadObj.give');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$this->testPermission($sender) or !($sender instanceof Player)){
			return true;
		}

		if(empty($args)){
			throw new InvalidCommandSyntaxException();
		}

			
			$skinName = implode(' ', $args);
			echo PlayerHeadObj::getInstance()->getDataFolder() . "skins\\".$skinName . ".png";
			if(file_exists(PlayerHeadObj::getInstance()->getDataFolder() . "skins\\".$skinName . ".png")) {
			// new Skin("Standard_Custom", $this->createSkin($skin))
			    // $sender->getInventory()->addItem(PlayerHeadObj::getPlayerHeadItem($player->getSkin(), $player->getName()));
				$sender->getInventory()->addItem(PlayerHeadObj::getPlayerHeadItem($sender->getSkin(), $skinName));
				$sender->sendMessage(PlayerHeadObj::PREFIX . TextFormat::colorize(sprintf($this->messages['message-head-added'], $skinName)));

			}
			else {
                $sender->sendMessage("Error: Skin do not exist !");
            }			

		
		return true;
	}

}

