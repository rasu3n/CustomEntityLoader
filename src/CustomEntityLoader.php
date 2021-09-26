<?php

declare(strict_types=1);

namespace Rush2929\CustomEntityLoader;

use muqsit\simplepackethandler\SimplePacketHandler;
use pocketmine\event\EventPriority;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\cache\StaticPacketCache;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\AvailableActorIdentifiersPacket;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use Webmozart\PathUtil\Path;
use function assert;

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
		$idList = $root->getListTag(EntityRegistry::TAG_ID_LIST);
		assert($idList !== null);
		/** @var CompoundTag $tag */
		foreach ($idList as $tag) {
			$registry->add(EntityRegistryEntry::fromTag($tag));
		}
		foreach ((new Config(Path::join($this->getDataFolder(), self::ENTITIES_FILE)))->getAll() as $entity) {
			$registry->add(EntityRegistryEntry::fromArray($entity));
		}
	}

	protected function onEnable() : void {
		SimplePacketHandler::createInterceptor($this, EventPriority::LOW)
			->interceptOutgoing(function(AvailableActorIdentifiersPacket $packet, NetworkSession $session) : bool {
				$packet->identifiers = self::getEntityRegistry()->getIdentifierTag();
				return true;
			});
	}

	protected function onDisable() : void {
		self::$registry = null;
	}

}