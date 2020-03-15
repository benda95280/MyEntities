<?php

declare(strict_types=1);

namespace Benda95280\MyEntities\commands\arguments;

use CortexPE\Commando\args\StringEnumArgument;
use pocketmine\command\CommandSender;

class EntityTypeArgument extends StringEnumArgument
{
    const ENTITY = "entity";
    const HEAD = "head";
	const CLONE = "clone";

    protected const VALUES = [
        self::ENTITY => self::ENTITY,
        self::HEAD => self::HEAD,
		self::CLONE => self::CLONE,
    ];

    public function getTypeName(): string
    {
        return "type";
    }

    public function parse(string $argument, CommandSender $sender)
    {
        return $this->getValue($argument);
    }
}