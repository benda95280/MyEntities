<?php

declare(strict_types=1);

namespace Benda95280\MyEntities\commands;

use Benda95280\MyEntities\MyEntities;
use CortexPE\Commando\args\StringEnumArgument;
use pocketmine\command\CommandSender;

class SkinsEnumArgument extends StringEnumArgument
{
    public function getTypeName(): string
    {
        return "string";
    }

    public function parse(string $argument, CommandSender $sender)
    {
        return $argument;
    }

    public function getEnumValues(): array
    {
        return array_keys(MyEntities::$skinsList);
    }

    public function getEnumName(): string
    {
        return "skin";
    }
}