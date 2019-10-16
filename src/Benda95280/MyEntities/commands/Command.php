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
use CortexPE\Commando\args\BaseArgument;
use CortexPE\Commando\BaseCommand;
use pocketmine\command\CommandSender;

class Command extends BaseCommand
{
    /**
     * This is where all the arguments, permissions, sub-commands, etc would be registered
     * @throws \CortexPE\Commando\exception\SubCommandCollision
     */
    protected function prepare(): void
    {
        /*
         * /mye entity [SkinName] {PlayerName} : Give player headObj
         * /mye item remover {PlayerName} : Give item Remover
         * /mye item rotator {PlayerName} : Give item Rotator
         * /mye head {PlayerName} : Give a player's head
         * /mye reload : Reload configuration
         */
        $this->setPermission("MyEntities");
        $this->registerSubCommand(new ItemCommand("item", MyEntities::$language->translateString('cmd_giveitem')));
        $this->registerSubCommand(new EntityCommand("entity", MyEntities::$language->translateString('cmd_giveentity')));
        $this->registerSubCommand(new HeadCommand("head", MyEntities::$language->translateString('cmd_givehead')));
        $this->registerSubCommand(new ReloadCommand("reload", MyEntities::$language->translateString('cmd_reload')));
    }

    /**
     * @param CommandSender $sender
     * @param string $aliasUsed
     * @param BaseArgument[] $args
     */
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $sender->sendMessage($this->getUsage());
    }
}

