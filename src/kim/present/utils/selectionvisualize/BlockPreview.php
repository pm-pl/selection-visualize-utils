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

namespace kim\present\utils\selectionvisualize;

use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;

/**
 * Renders a temporary block preview to a player by:
 * - faking the install blocks on the normal layer
 * - overlaying a tinted glass on the liquid layer
 * - delaying packets by one tick to avoid being overwritten by server sync
 *
 * Typical usage:
 *
 * $preview = new BlockPreview($plugin);
 * $preview->show($player,
 *     new PreviewEntry($pos1, $block),
 *     new PreviewEntry($pos2, $block),
 * );
 *
 * // later
 * $preview->clear($player);
 */
final class BlockPreview{
    /**
     * @var array<string, BlockPosition[]>
     */
    private array $lastPreview = [];

    /**
     * @var array<string, PreviewEntry[]>
     */
    private array $pendingEntries = [];

    /**
     * @var array<string, bool>
     */
    private array $pendingTasks = [];

    /**
     * @param PluginBase $plugin Owner plugin used to schedule delayed preview tasks.
     */
    public function __construct(
        private readonly PluginBase $plugin,
    ){}

    /**
     * Returns the last previewed block positions for the given player.
     * This can be used for custom ray-casting (e.g. air-click targeting).
     *
     * @param Player $player Target player.
     *
     * @return BlockPosition[] Positions currently used to show the preview to this player.
     */
    public function getPreviewPositions(Player $player) : array{
        return $this->lastPreview[$player->getName()] ?? [];
    }

    /**
     * Clears any active preview for the given player.
     *
     * Behaviour:
     * - Restores the NORMAL data layer to the real world block at each previewed position.
     * - Clears the LIQUID data layer overlay (tinted glass or custom liquidBlock).
     * - Forgets the stored preview positions for this player.
     *
     * @param Player $player Target player to clear preview for.
     */
    public function clear(Player $player) : void{
        $playerName = $player->getName();
        if(!isset($this->lastPreview[$playerName]) || $this->lastPreview[$playerName] === []){
            return;
        }

        $airRuntimeId = $this->getAirRuntimeId();
        $packets = [];
        $blocks = [];
        foreach($this->lastPreview[$playerName] as $blockPosition){
            $packets[] = UpdateBlockPacket::create(
                $blockPosition,
                $airRuntimeId,
                UpdateBlockPacket::FLAG_NETWORK,
                UpdateBlockPacket::DATA_LAYER_LIQUID
            );

            $blocks[] = new Vector3($blockPosition->getX(), $blockPosition->getY(), $blockPosition->getZ());
        }
        NetworkBroadcastUtils::broadcastPackets(
            [$player],
            [...$packets, ...$player->getWorld()->createBlockUpdatePackets($blocks)]
        );

        unset($this->lastPreview[$player->getName()]);
    }

    /**
     * Shows a block preview to the given player.
     *
     * Behaviour:
     * - Immediately clears any existing preview for the player.
     * - Stores the provided PreviewEntry list as a pending preview.
     * - Schedules a 1-tick delayed task to actually send UpdateBlockPacket(s), to avoid
     *   being overwritten by PocketMine-MP's own resync packets on the same tick.
     *
     * Each PreviewEntry is rendered as:
     * - NORMAL layer: entry->block (fake install block)
     * - LIQUID layer: entry->liquidBlock if provided, otherwise tinted glass.
     *
     * @param Player       $player     Target player.
     * @param PreviewEntry ...$entries One or more preview entries to render.
     */
    public function show(Player $player, PreviewEntry ...$entries) : void{
        $this->clear($player);

        if($entries === []){
            return;
        }

        $name = $player->getName();
        $this->pendingEntries[$name] = $entries;

        if(isset($this->pendingTasks[$name])){
            return;
        }

        $this->pendingTasks[$name] = true;

        // NOTE:
        // We delay preview packets by 1 tick instead of sending them immediately.
        // PocketMine-MP, on receiving a block interaction from the client, often sends its own
        // UpdateBlockPacket(s) around the clicked position to resync the client with the server state.
        // If we sent preview packets in the same tick, those automatic sync packets could overwrite
        // our fake NORMAL-layer blocks, causing missing or flickering preview (especially on corners).
        // Sending preview one tick later guarantees that the server has finished its resync and
        // our preview safely "wins" as the last writer for the client view.
        $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player, $name) : void{
            unset($this->pendingTasks[$name]);

            $entries = $this->pendingEntries[$name] ?? null;
            if($entries === null){
                return;
            }
            unset($this->pendingEntries[$name]);

            if(!$player->isOnline()){
                return;
            }

            $this->flush($player, $entries);
        }), 1);
    }

    /**
     * Sends the collected preview packets for the given player.
     *
     * @param Player         $player  Target player.
     * @param PreviewEntry[] $entries Entries to render for this flush.
     */
    private function flush(Player $player, array $entries) : void{
        $packets = [];
        $positions = [];
        foreach($entries as $entry){
            $pos = $entry->position;
            if($pos->getWorld() !== $player->getWorld()){
                continue;
            }

            $blockPosition = BlockPosition::fromVector3($pos);
            $positions[] = $blockPosition;

            $installRuntimeId = $this->getRuntimeIdFromBlock($entry->block);
            $packets[] = UpdateBlockPacket::create(
                $blockPosition,
                $installRuntimeId,
                UpdateBlockPacket::FLAG_NETWORK,
                UpdateBlockPacket::DATA_LAYER_NORMAL
            );

            if($entry->liquidBlock !== null){
                $packets[] = UpdateBlockPacket::create(
                    $blockPosition,
                    $this->getRuntimeIdFromBlock($entry->liquidBlock),
                    UpdateBlockPacket::FLAG_NETWORK,
                    UpdateBlockPacket::DATA_LAYER_LIQUID
                );
            }
        }

        if($positions !== []){
            $this->lastPreview[$player->getName()] = $positions;
            NetworkBroadcastUtils::broadcastPackets([$player], $packets);
        }
    }

    private function getAirRuntimeId() : int{
        /** @var int|null $runtimeId */
        static $runtimeId = null;
        return $runtimeId ??= $this->getRuntimeIdFromBlock(VanillaBlocks::AIR());
    }

    private function getRuntimeIdFromBlock(Block $block) : int{
        return TypeConverter::getInstance()
                            ->getBlockTranslator()
                            ->internalIdToNetworkId($block->getStateId());
    }
}

