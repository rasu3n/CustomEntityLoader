<?php

declare(strict_types=1);

namespace Rush2929\CustomEntityLoader;

use InvalidStateException;
use pocketmine\event\EventPriority;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\protocol\AvailableActorIdentifiersPacket;
use pocketmine\plugin\PluginBase;

final class CustomEntityLoader extends PluginBase {

	private const TAG_ID_LIST = "idlist";

	private static ?EntityRegistry $registry = null;

	private static bool $isAlreadySentAvailableActorIdentifiers = false;

	public static function getCustomEntityRegistry() : EntityRegistry {
		return self::$registry ??= new EntityRegistry();
	}

	public static function checkIsAlreadySentAvailableActorIdentifiers() : void {
		if (self::$isAlreadySentAvailableActorIdentifiers) {
			throw new InvalidStateException("The AvailableActorIdentifiersPacket has already been sent.");
		}
	}

	protected function onEnable() : void {
		$registry = self::getCustomEntityRegistry();
		foreach ($this->getConfig()->get(ConfigKeys::ENTITIES, []) as $entity) {
			$registry->add(EntityRegistryEntry::fromArray($entity));
		}

		$this->getServer()->getPluginManager()->registerEvent(DataPacketSendEvent::class, function(DataPacketSendEvent $ev) : void {
			if (self::$isAlreadySentAvailableActorIdentifiers) {
				return;
			}

			foreach ($ev->getPackets() as $packet) {
				if ($packet instanceof AvailableActorIdentifiersPacket) {
					self::$isAlreadySentAvailableActorIdentifiers = true;
					/** @var CompoundTag $root */
					$root = $packet->identifiers->getRoot();
					/** @var ListTag $idList */
					$idList = $root->getListTag(self::TAG_ID_LIST);
					foreach (self::getCustomEntityRegistry()->getAll() as $customEntityEntry) {
						$entryTag = CompoundTag::create();
						$customEntityEntry->write($entryTag);
						$idList->push($entryTag);
					}
					break;
				}
			}
		}, EventPriority::LOW, $this);
	}

	protected function onDisable() : void {
		self::$registry = null;
	}

}