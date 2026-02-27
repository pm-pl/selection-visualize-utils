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

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\Position;

final readonly class EventListener implements Listener{

    private const MAX_DISTANCE = 20;

    public function __construct(
        private Main $plugin,
    ){}

    public function onPlayerInteract(PlayerInteractEvent $event) : void{
        $player = $event->getPlayer();

        $session = BuildSessionManager::getSession($player);
        if($session === null){
            return;
        }

        $event->cancel();
        $session->setPreviewBlock($event->getItem()->getBlock());

        $pos = $event->getBlock()->getPosition();
        if($event->getAction() === PlayerInteractEvent::LEFT_CLICK_BLOCK){
            $session->setPos1($pos);
            $player->sendPopup("selected the first point");
        }else{
            $session->setPos2($pos);
            $player->sendPopup("selected the second point");
        }
    }

    public function onPlayerItemUse(PlayerItemUseEvent $event) : void{
        $player = $event->getPlayer();
        $session = BuildSessionManager::getSession($player);
        if($session === null){
            return;
        }

        $event->cancel();
        $session->setPreviewBlock($event->getItem()->getBlock());

        $pos = $this->findPreviewTarget($player, $event->getDirectionVector());
        if($pos === null){
            $target = $player->getTargetBlock(self::MAX_DISTANCE);
            if($target === null){
                return;
            }
            $pos = $target->getPosition();
        }

        $session->setPos2($pos);
        $player->sendPopup("selected the second point");
    }

    private function findPreviewTarget(Player $player, Vector3 $direction) : ?Position{
        $positions = $this->plugin->getBlockPreview()->getPreviewPositions($player);
        if($positions === []){
            return null;
        }

        $dir = clone $direction;
        $len = $dir->length();
        if($len <= 0){
            return null;
        }
        $dir = $dir->divide($len);

        $eye = $player->getEyePos();
        $bestT = null;
        $bestPos = null;
        $radiusSq = 0.75 * 0.75;

        foreach($positions as $blockPosition){
            $center = new Vector3(
                $blockPosition->getX() + 0.5,
                $blockPosition->getY() + 0.5,
                $blockPosition->getZ() + 0.5
            );

            $v = $center->subtractVector($eye);
            $t = $v->x * $dir->x + $v->y * $dir->y + $v->z * $dir->z;
            if($t <= 0 || $t > self::MAX_DISTANCE){
                continue;
            }

            $closest = $eye->addVector($dir->multiply($t));
            $dx = $center->x - $closest->x;
            $dy = $center->y - $closest->y;
            $dz = $center->z - $closest->z;
            $distSq = $dx * $dx + $dy * $dy + $dz * $dz;
            if($distSq > $radiusSq){
                continue;
            }

            if($bestT === null || $t < $bestT){
                $bestT = $t;
                $bestPos = $blockPosition;
            }
        }

        if($bestPos === null){
            return null;
        }

        return new Position($bestPos->getX(), $bestPos->getY(), $bestPos->getZ(), $player->getWorld());
    }

    public function onItemHeld(PlayerItemHeldEvent $event) : void{
        $player = $event->getPlayer();
        $session = BuildSessionManager::getSession($player);
        $session?->setPreviewBlock($player->getInventory()->getItem($event->getSlot())->getBlock());
    }
}
