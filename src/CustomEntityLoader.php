<?php

declare(strict_types=1);

namespace Rush2929\CustomEntityLoader;

use pocketmine\event\EventPriority;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\cache\StaticPacketCache;
use pocketmine\network\mcpe\protocol\AvailableActorIdentifiersPacket;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use Webmozart\PathUtil\Path;

final class CustomEntityLoader extends PluginBase {

	private const ENTITIES_FILE = "entities.json";
	private const ENTITIES_FILE_EXAMPLE = "entities.example.json";

	private static ?EntityRegistry $registry = null;

	public static function getEntityRegistry() : EntityRegistry {
		return self::$registry ??= new EntityRegistry();
	}

	protected function onLoad() : void {
		$registry = self::getEntityRegistry();
		$this->saveResource(self::ENTITIES_FILE);
		$this->saveResource(self::ENTITIES_FILE_EXAMPLE);
		/** @var CompoundTag $root */
		$root = StaticPacketCache::getInstance()->getAvailableActorIdentifiers()->identifiers->getRoot();
		/** @var ListTag $idList */
		$idList = $root->getListTag(EntityRegistry::TAG_ID_LIST);
		/** @var CompoundTag $tag */
		foreach ($idList as $tag) {
			$registry->add(EntityRegistryEntry::fromTag($tag));
		}
		foreach ((new Config(Path::join($this->getDataFolder(), self::ENTITIES_FILE)))->getAll() as $entity) {
			$registry->add(EntityRegistryEntry::fromArray($entity));
		}
	}

	protected function onEnable() : void {
		$this->getServer()->getPluginManager()->registerEvent(DataPacketSendEvent::class, function(DataPacketSendEvent $ev) : void {
			foreach ($ev->getPackets() as $packet) {
				if ($packet instanceof AvailableActorIdentifiersPacket) {
					$packet->identifiers = self::getEntityRegistry()->getIdentifierTag();
					break;
				}
			}
		}, EventPriority::LOW, $this);
	}

	protected function onDisable() : void {
		self::$registry = null;
	}

}