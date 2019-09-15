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
 
		//CHECK IF WE HAVE NEW SKIN not yet added
		
		$files = glob($pathSkinsHead.'*.{png}', GLOB_BRACE);
		foreach($files as $file) {
		  $filename = pathinfo($file)['filename'];
		  $filename_explode = explode("_", $filename);
		  $filename_exp_end = end($filename_explode);
		  if (strtolower($filename_exp_end) != "empty") {
			  if(!isset(self::$skinsList[$filename])) {
				  self::$skinsList[$filename]["type"] = "head";
				  self::$skinsList[$filename]["name"] = $filename;
				  self::$skinsList[$filename]["param"]["size"] = "normal";
				  self::$skinsList[$filename]["param"]["health"] = 1;
				  self::$skinsList[$filename]["param"]["unbreakable"] = 0;
				  self::logMessage("'".$filename."' Has been added to your config file as 'Head' type",1);
			  }
		  }
		}
		self::$configData["skins"] = self::$skinsList;
		self::$instance->getConfig()->setAll(self::$configData);
		self::$instance->getConfig()->save();

		
		
		
		//CHECK SKIN CONFIGURATION

		foreach(self::$skinsList as $skinName => $skinValue) {
			
			// ** BASIC CHECK ** //
			
			//Entity must have a skin file
			if (!file_exists($pathSkinsHead.$skinName.'.png')) {
				self::logMessage("'".$skinName."' Do not have any skin (png) file ! It has been removed from plugin.",0);
				unset(self::$skinsList[$skinName]);
				continue;
			}
			//Entity declaration cannot have white space and correct lenght
			if (preg_match('/\s/',$skinName) || strlen($skinName) <= 4 || strlen($skinName) >= 22) {
				self::logMessage("'".$skinName."' Entity declaration cannot contain space and have 4-22 Char ! It has been removed from plugin.",0);
				unset(self::$skinsList[$skinName]);
				continue;
			}
			//Entity must have a correct lenght	name
			if (!isset($skinValue["name"]) || strlen($skinValue["name"]) <= 4 || strlen($skinValue["name"]) >= 22) {
				self::logMessage("'".$skinName."' Name must have have 4-22 Char ! It has been removed from plugin.",0);
				unset(self::$skinsList[$skinName]);
				continue;
			}
			
			// ** BASIC PARAM CHECK ** //
			
			//Entity must have Param child
			if (!isset($skinValue["param"])) {
				self::logMessage("'".$skinName."' must have a param child ! It has been removed from plugin.",0);
				unset(self::$skinsList[$skinName]);
				continue;
			}
			//Entity must have a parameter Health in Param
			if (!isset($skinValue["param"]["health"]) || !is_int($skinValue["param"]["health"]) || $skinValue["param"]["health"] < 1 || $skinValue["param"]["health"] > 75) {
				self::logMessage("'".$skinName."' must have  1-75 (Int) Health-Param ! It has been removed from plugin.",0);			
				unset(self::$skinsList[$skinName]);
				continue;
			}
			//Entity must have a parameter Unbreakable in Param
			if (!isset($skinValue["param"]["unbreakable"]) || !is_int($skinValue["param"]["unbreakable"]) || !($skinValue["param"]["unbreakable"] == 1 || $skinValue["param"]["unbreakable"] == 0)) {
				self::logMessage("'".$skinName."' must have 0 or 1 int Unbreakable-Param ! It has been removed from plugin.",0);								
				unset(self::$skinsList[$skinName]);
				continue;
			}
			
			// ** USABLE PARAM CHECK ** //
			
			if (isset($skinValue["param"]["usable"]) ) {
				//These variable must be set and correct
				
				
				//Must be unbreakable to be usable !
				if ($skinValue["param"]["unbreakable"] == 0) {
					self::logMessage("'".$skinName."' must be unbreakable, because you set is usable ! It has been removed from plugin.",0);
					unset(self::$skinsList[$skinName]);
					continue;
				}					
				//Check usable time (1-20)
				if (!isset($skinValue["param"]["usable"]["time"]) || !is_int($skinValue["param"]["usable"]["time"]) || $skinValue["param"]["usable"]["time"] < 1 || $skinValue["param"]["usable"]["time"] > 20) {
					self::logMessage("'".$skinName."' must have correct value for Time-Usable-Param (1-20) ! It has been removed from plugin.",0);
					unset(self::$skinsList[$skinName]);
					continue;
				}
				//Check Reload Time (0-20)
				if (!isset($skinValue["param"]["usable"]["reload"]) || !is_int($skinValue["param"]["usable"]["reload"]) || $skinValue["param"]["usable"]["reload"] < 0 || $skinValue["param"]["usable"]["reload"] > 300) {
					self::logMessage("'".$skinName."' must have correct value for Reload-Usable-Param (0-300) ! It has been removed from plugin.",0);
					unset(self::$skinsList[$skinName]);
					continue;
				}
				//Check Skin change 
				if (!isset($skinValue["param"]["usable"]["skinchange"]) || !is_int($skinValue["param"]["usable"]["skinchange"]) || !($skinValue["param"]["usable"]["skinchange"] == 1 || $skinValue["param"]["usable"]["skinchange"] == 0)) {
					self::logMessage("'".$skinName."' must have 0 or 1 int for SkinChange-Usable-Param ! It has been removed from plugin.",0);
					unset(self::$skinsList[$skinName]);
					continue;
				}
				//Check Skin change, the skin has to exist
				if (isset($skinValue["param"]["usable"]["skinchange"]) && $skinValue["param"]["usable"]["skinchange"] == 1 && !file_exists($pathSkinsHead.$skinName.'_empty.png') ) {
					self::logMessage("'".$skinName."' have skinChange Set, but no skin available '".$skinName."_empty.png'! It has been removed from plugin.",0);
					unset(self::$skinsList[$skinName]);
					continue;
				}
				//Check Descruction 
				if (!isset($skinValue["param"]["usable"]["destruction"]) || !is_int($skinValue["param"]["usable"]["destruction"]) || !($skinValue["param"]["usable"]["destruction"] == 1 || $skinValue["param"]["usable"]["destruction"] == 0)) {
					self::logMessage("'".$skinName."' must have 0 or 1 int for Destruction-Usable-Param ! It has been removed from plugin.",0);
					unset(self::$skinsList[$skinName]);
					continue;
				}
				//Check Destruction_MSG
				if (!isset($skinValue["param"]["usable"]["destruction_msg"]) || !is_string($skinValue["param"]["usable"]["destruction_msg"])) {
					self::logMessage("'".$skinName."' must have correct value for Destruction_msg-Usable-Param (String or empty) ! It has been removed from plugin.",0);
					unset(self::$skinsList[$skinName]);
					continue;
				}
				//Check if message show up when used
				if (!isset($skinValue["param"]["usable"]["use_msg"]) || !is_int($skinValue["param"]["usable"]["use_msg"]) || !($skinValue["param"]["usable"]["use_msg"] == 1 || $skinValue["param"]["usable"]["use_msg"] == 0)) {
					self::logMessage("'".$skinName."' must have 0 or 1 int for use_msg-Usable-Param ! It has been removed from plugin.",0);
					unset(self::$skinsList[$skinName]);
					continue;
				}
				//Check Action is set
				if (!isset($skinValue["param"]["usable"]["action"]) || !is_string($skinValue["param"]["usable"]["action"])) {
					self::logMessage("'".$skinName."' must be set for action_random-Usable-Param ! It has been removed from plugin.",0);
					unset(self::$skinsList[$skinName]);
					continue;
				}
				//Check Action validity
				if (!json_decode($skinValue["param"]["usable"]["action"])) {
					self::logMessage("'".$skinName."' invalid JSON for action-Usable-Param ! It has been removed from plugin.",0);
					unset(self::$skinsList[$skinName]);
					continue;					
				}
				//Check Action validity in details
				if (!self::checkAction(json_decode($skinValue["param"]["usable"]["action"]))) {
					self::logMessage("'".$skinName."' invalid ACTIONS in action-Usable-Param ! It has been removed from plugin.",0);
					unset(self::$skinsList[$skinName]);
					continue;										
				}
				
				//Check RandomAction change when empty
				if (!isset($skinValue["param"]["usable"]["action_random"]) || !is_int($skinValue["param"]["usable"]["action_random"]) || !($skinValue["param"]["usable"]["action_random"] == 1 || $skinValue["param"]["usable"]["action_random"] == 0)) {
					self::logMessage("'".$skinName."' must have 0 or 1 int for action_random-Usable-Param ! It has been removed from plugin.",0);
					unset(self::$skinsList[$skinName]);
					continue;
				}
			}			
			
			
			//** Entity verification **//
			
			// HEAD ENTITY //
			//Type of entity is a must to have
			
			//TODO: Create missing parameter and save it
			//TODO: Log error if unknown parameter
			
			if (isset($skinValue["type"]) && $skinValue["type"] == "head") {
				//Head must have a size
				if (isset($skinValue["param"]["size"]) && $skinValue["param"]["size"] === "small") $countFileSkinsHeadSmall++;
				else if (isset($skinValue["param"]["size"]) && $skinValue["param"]["size"] === "normal") $countFileSkinsHeadNormal++;
				else if (isset($skinValue["param"]["size"]) && $skinValue["param"]["size"] === "block") $countFileSkinsHeadBlock++;
				else {
					self::logMessage("'".$skinName."' Size error ! It has been removed from plugin.",0);
					unset(self::$skinsList[$skinName]);
					continue;
				}
				self::logMessage("§b§lLoaded: §r§6Head Skin§r§f $skinName / Size: ".$skinValue["param"]["size"]." / name: '".$skinValue["name"]."'",2);
			}
			else if (isset($skinValue["type"]) && $skinValue["type"] == "custom") {
				//CustomSkin must have json geometry file
				if (file_exists($pathSkinsHead.$skinName.'.json')) {
					$decodedGeometry = json_decode(file_get_contents($pathSkinsHead.$skinName.'.json'));
					//Test json Validity
					if (!is_null($decodedGeometry)) {
						$countFileSkinsCustom++;
					}
					else {
						self::logMessage("'".$skinName."' Geometry, JSON is incorrect ! It has been removed from plugin.",0);
						unset(self::$skinsList[$skinName]);
						continue;
					}
					if (!isset($skinValue["param"]["geometryName"])){
						self::logMessage("'".$skinName."' Geometry Name of JSON is missing ! It has been removed from plugin.",0);
						unset(self::$skinsList[$skinName]);
						continue;
					}
					if (isset($skinValue["param"]["size"])) {
						self::logMessage("'".$skinName."' Custom entity cannot have a size ! It has been removed from plugin.",0);
						unset(self::$skinsList[$skinName]);
						continue;						
					}
				}
				else {
					self::logMessage("'".$skinName."' Geometry JSON Missing ! It has been removed from plugin.",0);
					unset(self::$skinsList[$skinName]);
					continue;
				}
				self::logMessage("§b§lLoaded: §r§6Custom Skin§r§f $skinName / name: '".$skinValue["name"]."'",2);
			}
			else {
				self::logMessage($skinName." Type do not exist ! It has been removed from plugin.",0);
				unset(self::$skinsList[$skinName]);
				continue;
			}
		}
		self::logMessage("§b§l$countFileSkinsHeadSmall §r§bHead skin small§r§f found",1);
		self::logMessage("§b§l$countFileSkinsHeadNormal §r§bHead skin normal§r§f found",1);
		self::logMessage("§b§l$countFileSkinsHeadBlock §r§bHead skin block§r§f found",1);
		self::logMessage("§b§l$countFileSkinsCustom §r§bCustom skin§r§f found",1);
		self::logMessage("§aActivated",1);

?>