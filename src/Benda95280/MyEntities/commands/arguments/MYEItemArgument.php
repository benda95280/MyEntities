<?php

declare(strict_types=1);

namespace Benda95280\MyEntities\commands\arguments;

use CortexPE\Commando\args\StringEnumArgument;
use pocketmine\command\CommandSender;

class MYEItemArgument extends StringEnumArgument
{
    protected const VALUES = [
        "remover" => "remover",
        "rotator" => "rotator",
    ];

    public function getTypeName(): string
    {
        return "string";
    }

    public function parse(string $argument, CommandSender $sender)
    {
        return $this->getValue($argument);
    }
}