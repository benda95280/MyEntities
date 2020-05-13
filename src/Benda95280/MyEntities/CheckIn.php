<?php

declare(strict_types=1);

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

namespace Benda95280\MyEntities;

use InvalidStateException;

class CheckIn
{

    /**
     * @param $countFileSkinsHeadSmall
     * @param $countFileSkinsHeadNormal
     * @param $countFileSkinsHeadBlock
     * @param $countFileSkinsCustom
     * @throws InvalidStateException
     */
    public static function check($countFileSkinsHeadSmall, $countFileSkinsHeadNormal, $countFileSkinsHeadBlock, $countFileSkinsCustom): void
    {
//CHECK IF WE HAVE NEW SKIN not yet added

        $files = glob(MyEntities::$pathSkins . '*.{png}', GLOB_BRACE);
        foreach ($files as $file) {
            $filename = pathinfo($file)['filename'];
            $filename_explode = explode("_", $filename);
            $filename_exp_end = end($filename_explode);
            if (strtolower($filename_exp_end) != "empty") {
                if (!isset(MyEntities::$skinsList[$filename])) {
					//Entity declaration cannot have white space and correct length
					if (preg_match('/\s/', $filename) || strlen($filename) < 4 || strlen($filename) > 22) {
						MyEntities::logMessage(MyEntities::$language->translateString('checkin_entspaceorchar', [$filename]), 0);
					}
					else {
						MyEntities::$skinsList[$filename]["type"] = "head";
						MyEntities::$skinsList[$filename]["name"] = $filename;
						MyEntities::$skinsList[$filename]["param"]["size"] = "normal";
						MyEntities::$skinsList[$filename]["param"]["health"] = 1;
						MyEntities::$skinsList[$filename]["param"]["unbreakable"] = 0;
						MyEntities::logMessage(MyEntities::$language->translateString('checkin_newadded', [$filename]), 1);
					}
                }
            }
        }
        MyEntities::getInstance()->getConfig()->set('skins', MyEntities::$skinsList);
        MyEntities::getInstance()->getConfig()->save();

//CHECK SKIN CONFIGURATION

        foreach (MyEntities::$skinsList as $skinName => $skinValue) {

            // ** BASIC CHECK ** //

            //Entity must have a skin file
            if (!file_exists(MyEntities::$pathSkins . $skinName . '.png')) {
                MyEntities::logMessage(MyEntities::$language->translateString('checkin_noskinpng', [$skinName]), 0);
                unset(MyEntities::$skinsList[$skinName]);
                continue;
            }

            //Entity skin cannot exeed 256x256px
            if (getImageSize(MyEntities::$pathSkins . $skinName . '.png')[0] > 256  || getImageSize(MyEntities::$pathSkins . $skinName . '.png')[1] > 256) {
                MyEntities::logMessage(MyEntities::$language->translateString('checkin_skinsizeover256', [$skinName]), 0);
                unset(MyEntities::$skinsList[$skinName]);
                continue;
            }
            //Entity skin is big ...prevent ...
            elseif (getImageSize(MyEntities::$pathSkins . $skinName . '.png')[0] >= 128  || getImageSize(MyEntities::$pathSkins . $skinName . '.png')[1] >= 128) {
                MyEntities::logMessage(MyEntities::$language->translateString('checkin_skinsizeover128', [$skinName]), 0);
            }
            //Entity declaration cannot have white space and correct length
			if (preg_match('/\s/', $skinName) || strlen($skinName) < 4 || strlen($skinName) > 22) {
                MyEntities::logMessage(MyEntities::$language->translateString('checkin_entspaceorchar', [$skinName]), 0);
                unset(MyEntities::$skinsList[$skinName]);
                continue;
            }
			//Entity must have a correct length	name
			//TODO allow longer and shorter names. This restriction is stupid
			if (!isset($skinValue["name"]) || strlen($skinValue["name"]) < 4 || strlen($skinValue["name"]) > 22) {
                MyEntities::logMessage(MyEntities::$language->translateString('checkin_namespaceorchar', [$skinName]), 0);
                unset(MyEntities::$skinsList[$skinName]);
                continue;
            }

            // ** BASIC PARAM CHECK ** //

            //Entity must have Param child
            if (!isset($skinValue["param"])) {
                MyEntities::logMessage(MyEntities::$language->translateString('checkin_paramchildmissing', [$skinName]), 0);
                unset(MyEntities::$skinsList[$skinName]);
                continue;
            }
            //Entity must have a parameter Health in Param
            if (!isset($skinValue["param"]["health"]) || !is_int($skinValue["param"]["health"]) || $skinValue["param"]["health"] < 1 || $skinValue["param"]["health"] > 75) {
                MyEntities::logMessage(MyEntities::$language->translateString('checkin_health', [$skinName]), 0);
                unset(MyEntities::$skinsList[$skinName]);
                continue;
            }
            //Entity must have a parameter Unbreakable in Param
            if (!isset($skinValue["param"]["unbreakable"]) || !is_int($skinValue["param"]["unbreakable"]) || !($skinValue["param"]["unbreakable"] == 1 || $skinValue["param"]["unbreakable"] == 0)) {
                MyEntities::logMessage(MyEntities::$language->translateString('checkin_unbreakable', [$skinName]), 0);
                unset(MyEntities::$skinsList[$skinName]);
                continue;
            }

            // ** USABLE PARAM CHECK ** //

            if (isset($skinValue["param"]["usable"])) {
                //These variable must be set and correct

                //Must be unbreakable to be usable !
                if ($skinValue["param"]["unbreakable"] == 0) {
                    MyEntities::logMessage(MyEntities::$language->translateString('checkin_usable_unbreakablenotset', [$skinName]), 0);
                    unset(MyEntities::$skinsList[$skinName]);
                    continue;
                }
                //Check usable time (1-20)
                if (!isset($skinValue["param"]["usable"]["time"]) || !is_int($skinValue["param"]["usable"]["time"]) || $skinValue["param"]["usable"]["time"] < 1 || $skinValue["param"]["usable"]["time"] > 20) {
                    MyEntities::logMessage(MyEntities::$language->translateString('checkin_usable_timeusable', [$skinName]), 0);
                    unset(MyEntities::$skinsList[$skinName]);
                    continue;
                }
                //Check Reload Time (0-20)
                if (!isset($skinValue["param"]["usable"]["reload"]) || !is_int($skinValue["param"]["usable"]["reload"]) || $skinValue["param"]["usable"]["reload"] < 0 || $skinValue["param"]["usable"]["reload"] > 300) {
                    MyEntities::logMessage(MyEntities::$language->translateString('checkin_usable_reloadtime', [$skinName]), 0);
                    unset(MyEntities::$skinsList[$skinName]);
                    continue;
                }
                //Check Skin change
                if (!isset($skinValue["param"]["usable"]["skinchange"]) || !is_int($skinValue["param"]["usable"]["skinchange"]) || !($skinValue["param"]["usable"]["skinchange"] == 1 || $skinValue["param"]["usable"]["skinchange"] == 0)) {
                    MyEntities::logMessage(MyEntities::$language->translateString('checkin_usable_skinchange', [$skinName]), 0);
                    unset(MyEntities::$skinsList[$skinName]);
                    continue;
                }
                //Check Skin change, the skin has to exist
                if (isset($skinValue["param"]["usable"]["skinchange"]) && $skinValue["param"]["usable"]["skinchange"] == 1 && !file_exists(MyEntities::$pathSkins . $skinName . '_empty.png')) {
                    MyEntities::logMessage(MyEntities::$language->translateString('checkin_usable_missingskinempty', [$skinName, $skinName]), 0);
                    unset(MyEntities::$skinsList[$skinName]);
                    continue;
                }
                //Check Descruction
                if (!isset($skinValue["param"]["usable"]["destruction"]) || !is_int($skinValue["param"]["usable"]["destruction"]) || !($skinValue["param"]["usable"]["destruction"] == 1 || $skinValue["param"]["usable"]["destruction"] == 0)) {
                    MyEntities::logMessage(MyEntities::$language->translateString('checkin_usable_destruction', [$skinName]), 0);
                    unset(MyEntities::$skinsList[$skinName]);
                    continue;
                }
                //Check Destruction_MSG
                if (!isset($skinValue["param"]["usable"]["destruction_msg"]) || !is_string($skinValue["param"]["usable"]["destruction_msg"])) {
                    MyEntities::logMessage(MyEntities::$language->translateString('checkin_usable_destructionmsg', [$skinName]), 0);
                    unset(MyEntities::$skinsList[$skinName]);
                    continue;
                }
                //Check if message show up when used
                if (!isset($skinValue["param"]["usable"]["use_msg"]) || !is_int($skinValue["param"]["usable"]["use_msg"]) || !($skinValue["param"]["usable"]["use_msg"] == 1 || $skinValue["param"]["usable"]["use_msg"] == 0)) {
                    MyEntities::logMessage(MyEntities::$language->translateString('checkin_usable_messagewhenused', [$skinName]), 0);
                    unset(MyEntities::$skinsList[$skinName]);
                    continue;
                }
                //Check Action is set
                if (!isset($skinValue["param"]["usable"]["action"]) || !is_string($skinValue["param"]["usable"]["action"])) {
                    MyEntities::logMessage(MyEntities::$language->translateString('checkin_usable_missingaction', [$skinName]), 0);
                    unset(MyEntities::$skinsList[$skinName]);
                    continue;
                }
                //Check Action validity JSON
                if (!json_decode($skinValue["param"]["usable"]["action"])) {
                    MyEntities::logMessage(MyEntities::$language->translateString('checkin_usable_actionJSON', [$skinName]), 0);
                    unset(MyEntities::$skinsList[$skinName]);
                    continue;
                }
                //Check Action validity in details
                if (!self::checkAction(json_decode($skinValue["param"]["usable"]["action"]))) {
                    MyEntities::logMessage(MyEntities::$language->translateString('checkin_usable_actioninvalid', [$skinName]), 0);
                    unset(MyEntities::$skinsList[$skinName]);
                    continue;
                }

                //Check RandomAction change when empty
                if (!isset($skinValue["param"]["usable"]["action_random"]) || !is_int($skinValue["param"]["usable"]["action_random"]) || !($skinValue["param"]["usable"]["action_random"] == 1 || $skinValue["param"]["usable"]["action_random"] == 0)) {
                    MyEntities::logMessage(MyEntities::$language->translateString('checkin_usable_actionrandom', [$skinName]), 0);
                    unset(MyEntities::$skinsList[$skinName]);
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
                    MyEntities::logMessage(MyEntities::$language->translateString('checkin_entity_head_size', [$skinName]), 0);
                    unset(MyEntities::$skinsList[$skinName]);
                    continue;
                }
                MyEntities::logMessage(MyEntities::$language->translateString('checkin_entity_head_loaded', [$skinName, $skinValue["param"]["size"], $skinValue["name"]]), 2);

            } else if (isset($skinValue["type"]) && ($skinValue["type"] == "custom")) {
                //CustomSkin must have json geometry file
                if (file_exists(MyEntities::$pathSkins . $skinName . '.json')) {
                    $decodedGeometry = json_decode(file_get_contents(MyEntities::$pathSkins . $skinName . '.json'), true);
					
                    if (!isset($skinValue["param"]["geometryName"])) {
                        MyEntities::logMessage(MyEntities::$language->translateString('checkin_entity_custom_geometryname', [$skinName]), 0);
                        unset(MyEntities::$skinsList[$skinName]);
                        continue;
                    }
                    //Test json Validity
                    if (!is_null($decodedGeometry)) {
						if(isset($decodedGeometry["format_version"]) && isset($decodedGeometry[$skinValue["param"]["geometryName"]])) {
							$countFileSkinsCustom++;
						}
						else {
							MyEntities::logMessage(MyEntities::$language->translateString('checkin_entity_custom_geometryjsonwronginside', [$skinName, $skinValue["param"]["geometryName"]]), 0);
							unset(MyEntities::$skinsList[$skinName]);
							continue;							
						}
                    } else {
                        MyEntities::logMessage(MyEntities::$language->translateString('checkin_entity_custom_geometryjson', [$skinName]), 0);
                        unset(MyEntities::$skinsList[$skinName]);
                        continue;
                    }
					
                    if (isset($skinValue["param"]["size"])) {
                        MyEntities::logMessage(MyEntities::$language->translateString('checkin_entity_custom_size', [$skinName]), 0);
                        unset(MyEntities::$skinsList[$skinName]);
                        continue;
                    }
                } else {
                    MyEntities::logMessage(MyEntities::$language->translateString('checkin_entity_custom_missingjsonfile', [$skinName]), 0);
                    unset(MyEntities::$skinsList[$skinName]);
                    continue;
                }
                MyEntities::logMessage(MyEntities::$language->translateString('checkin_entity_custom_loaded', [$skinName, $skinValue["name"]]), 2);
            } else {
                MyEntities::logMessage(MyEntities::$language->translateString('checkin_entity_error_typenotexist', [$skinName]), 0);
                unset(MyEntities::$skinsList[$skinName]);
                continue;
            }
        }
        MyEntities::logMessage(MyEntities::$language->translateString('checkin_entity_loaded_headsmall', [$countFileSkinsHeadSmall]), 1);
        MyEntities::logMessage(MyEntities::$language->translateString('checkin_entity_loaded_headnormal', [$countFileSkinsHeadNormal]), 1);
        MyEntities::logMessage(MyEntities::$language->translateString('checkin_entity_loaded_headblock', [$countFileSkinsHeadBlock]), 1);
        MyEntities::logMessage(MyEntities::$language->translateString('checkin_entity_loaded_custom', [$countFileSkinsCustom]), 1);
        MyEntities::logMessage(MyEntities::$language->translateString('checkin_activated'), 1);
    }

    private static function checkAction($actions)
    {
        foreach ($actions as $actionName => $actionValue) {
            if (is_object($actionValue)) {
                foreach ($actionValue as $actionName1 => $actionValue1) {
                    //This actions is a set of actions
                    if (!self::checkActionDetail($actionName1, $actionValue1)) {
                        MyEntities::logMessage(MyEntities::$language->translateString('checkin_entity_checkaction_actionset', [$actionName1]), 1);
                        return false;
                    }
                }
            } else {
                //No set of actions
                if (!self::checkActionDetail($actionName, $actionValue)) {
                    MyEntities::logMessage(MyEntities::$language->translateString('checkin_entity_checkaction_action', [$actionName]), 1);
                    unset(MyEntities::$skinsList[$actionName]);
                    return false;
                }
            }

        }
        return true;
    }

    private static function checkActionDetail($actionName, $actionValue)
    {
        //Check action
        switch ($actionName) {
            case "msg":
                if (is_string($actionValue)) return true;
                else return false;
                break;
            case "repair":
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
            case "cmd":
                $toExecute = explode(";", $actionValue);
                $whoExecute = $toExecute[0];
                if (!isset($toExecute[0]) OR ($whoExecute != "console" AND $whoExecute != "player") OR !$toExecute[1] OR $toExecute[1] == "")
                    return false;
                else
                    return true;
            default:
                return false;
        }

    }
}
