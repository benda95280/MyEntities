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

namespace Benda95280\MyEntities\commands;

use Benda95280\MyEntities\MyEntities;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\plugin\PluginBase;
use pocketmine\item\ItemFactory;



class PHCommand extends Command{
	/** @var array */
	private $messages;

	public function __construct(array $messages){
		$this->messages = $messages;
		parent::__construct('MyEntities', 'Give an entity to a player', '/MyEntities [entity/item] [name] <playerName:string>', ['mye']);
		$this->setPermission('MyEntities.give');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$this->testPermission($sender)){
			return true;
		}

		if(empty($args)){
			throw new InvalidCommandSyntaxException();
		}
		//Did we have any arguments ?
		if (isset($args[0])) {
			//Console need player specified
			if (!($sender instanceof Player) && !isset($args[2])) {
				MyEntities::logMessage("Sorry, from console, need a player to give it ...",0);
				return true;
			}
			//Give it to who ?
			if (isset ($args[2])) {
				$player = $sender->getServer()->getPlayer($args[2]);
				if($player instanceof Player){		
					$giver = $player;
				}
				else {
					$sender->sendMessage(MyEntities::PREFIX . TextFormat::colorize("&4Error: Player not online"));
					return true;					
				}						
			}
			else $giver = $sender;
			
			// Give item
			if (strtolower($args[0]) == "item"){
				unset ($args[0]);
				$itemName = $args[1];
				
				if (!empty($itemName)) {
					if ($itemName == "rotator") {
						$item = ItemFactory::get(280 /* ID */, 0 /* Item Damage/meta */, 1 /*Count*/);
						$item->setCustomName("§6**Obj Rotation**");
						$giver->getInventory()->addItem($item);
						$giver->sendMessage(TextFormat::colorize(sprintf($this->messages['message-head-added'], "Obj_Rotation")));

					}
					else if ($itemName == "remover") {
						$item = ItemFactory::get(280 /* ID */, 0 /* Item Damage/meta */, 1 /*Count*/);
						$item->setCustomName("§6**Obj Remover**");
						$giver->getInventory()->addItem($item);
						$giver->sendMessage(TextFormat::colorize(sprintf($this->messages['message-head-added'], "Obj_Remover")));
					}
					else $sender->sendMessage(MyEntities::PREFIX ."Error: Item do not exist !");
				}
				else $sender->sendMessage(MyEntities::PREFIX ."Error: Item error !");
			}
			
			//Else, is it a skin ?
			elseif (strtolower($args[0]) == "entity") {
				unset ($args[0]);
				$skinName = $args[1];
				
				if (isset(MyEntities::$skinsList[$skinName])) {
					$nameFinal = ucfirst(MyEntities::$skinsList[$skinName]['name']);
					$param = MyEntities::$skinsList[$skinName]['param'];
					//Checker si l'entity est custom ...  
					if (MyEntities::$skinsList[$skinName]['type'] = "custom") {
						$giver->getInventory()->addItem(MyEntities::getPlayerCustomItem($skinName,$nameFinal,$param));
					}
					else {
						$giver->getInventory()->addItem(MyEntities::getPlayerHeadItem($skinName,$nameFinal,$param));
					}
					$giver->sendMessage(TextFormat::colorize(sprintf($this->messages['message-head-added'], $nameFinal)));

				}
				else {
					$sender->sendMessage(MyEntities::PREFIX ."Error: Entity do not exist !");
				}			

			}
			else $sender->sendMessage(MyEntities::PREFIX ."Error: What do you say ? How may  help you ?");
			
		}
		else {
			$sender->sendMessage(MyEntities::PREFIX ."Error: What do you say ? How may  help you ?");
		}
		return true;
	}

}

