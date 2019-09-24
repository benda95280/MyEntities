<?php

declare(strict_types=1);

namespace Benda95280\MyEntities\commands;

use Benda95280\MyEntities\MyEntities;
use CortexPE\Commando\args\RawStringArgument;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;

class PlayerNameTargetArgument extends RawStringArgument
{

    public function getNetworkType(): int
    {
        return AvailableCommandsPacket::ARG_TYPE_TARGET;
    }

    public function parse(string $argument, CommandSender $sender)
    {
        return MyEntities::getInstance()->getServer()->getPlayer($argument);
    }

    public function canParse(string $testString, CommandSender $sender): bool
    {
        return !is_null(MyEntities::getInstance()->getServer()->getPlayer($testString));
    }
}