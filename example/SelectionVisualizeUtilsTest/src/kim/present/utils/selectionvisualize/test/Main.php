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
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;

final class Main extends PluginBase{
    use SingletonTrait;

    private BlockPreview $blockPreview;

    protected function onLoad() : void{
        self::setInstance($this);
    }

    protected function onEnable() : void{
        BuildSessionManager::init($this);
        $this->blockPreview = new BlockPreview($this);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    }

    protected function onDisable() : void{
        BuildSessionManager::close();
    }

    public function getBlockPreview() : BlockPreview{
        return $this->blockPreview;
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if(!$sender instanceof Player){
            $sender->sendMessage("This command can only be used in-game.");
            return true;
        }

        $session = BuildSessionManager::getSession($sender);
        if($session === null){
            BuildSessionManager::createSession($sender, $this->getBlockPreview());
            $sender->sendMessage("Starts the selection-visualize-utils test.");
            $sender->sendMessage(" - Left-click on the first point.");
            $sender->sendMessage(" - Right-click on the first point.");
        }else{
            BuildSessionManager::removeSession($sender);
            $sender->sendMessage("Ends the selection-visualize-utils test.");
        }

        return true;
    }

}

