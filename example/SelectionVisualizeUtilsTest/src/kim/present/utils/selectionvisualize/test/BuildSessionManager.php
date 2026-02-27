<?php

/**
 *
 *  ____                           _   _  ___
 * |  _ \ _ __ ___  ___  ___ _ __ | |_| |/ (_)_ __ ___
 * | |_) | '__/ _ \/ __|/ _ \ '_ \| __| ' /| | '_ ` _ \
 * |  __/| | |  __/\__ \  __/ | | | |_| . \| | | | | | |
 * |_|   |_|  \___||___/\___|_| |_|\__|_|\_\_|_| |_| |_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the MIT License. see <https://opensource.org/licenses/MIT>.
 *
 * @author       PresentKim (debe3721@gmail.com)
 * @link         https://github.com/PresentKim
 * @license      https://opensource.org/licenses/MIT MIT License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 *
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace kim\present\utils\selectionvisualize\test;

use kim\present\utils\selectionvisualize\BlockPreview;
use pocketmine\player\Player;

final class BuildSessionManager{
    /** @var BuildSession[] */
    private static array $sessions = [];

    private static ?Main $plugin = null;

    public static function init(Main $plugin) : void{
        self::$plugin = $plugin;
    }

    public static function close() : void{
        foreach(self::$sessions as $session){
            $session->close();
        }

        self::$sessions = [];
        self::$plugin = null;
    }

    public static function getPlugin() : ?Main{
        return self::$plugin;
    }

    public static function getSession(Player $player) : ?BuildSession{
        return self::$sessions[$player->getName()] ?? null;
    }

    public static function createSession(Player $player, BlockPreview $blockPreview) : BuildSession{
        $session = new BuildSession($player, $blockPreview);
        self::$sessions[$player->getName()] = $session;
        return $session;
    }

    public static function removeSession(Player $player) : void{
        self::$sessions[$player->getName()]?->close();
        unset(self::$sessions[$player->getName()]);
    }
}

