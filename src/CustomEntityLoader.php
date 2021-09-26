<?php

declare(strict_types=1);

namespace Rush2929\CustomEntityLoader;

use muqsit\simplepackethandler\SimplePacketHandler;
use pocketmine\event\EventPriority;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\cache\StaticPacketCache;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\AvailableActorIdentifiersPacket;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use WeakMap;
use Webmozart\PathUtil\Path;
use function array_flip;
use function array_keys;
use function assert;

final class CustomEntityLoader extends PluginBase {

	private const ENTITIES_FILE = "entities.json";
	private const ENTITIES_FILE_EXAMPLE = "entities.example.json";

	private static ?EntityRegistry $registry = null;
	private static ?string $fallbackEntity = null;

	public static function getEntityRegistry() : EntityRegistry {
		return self::$registry ??= new EntityRegistry();
	}

	public static function getFallbackEntity() : ?string { return self::$fallbackEntity; }

	/** @var WeakMap<NetworkSession, array<string, int>>|null */
	private ?WeakMap $sentIdentifiers = null;

	protected function onLoad() : void {
		if (($fallbackEntity = $this->getConfig()->get(ConfigKeys::FALLBACK_ENTITY)) !== false) {
			self::$fallbackEntity = $fallbackEntity;
		}
		/** @phpstan-var WeakMap<NetworkSession, array<string, int>> $weakMap */
		$weakMap = new WeakMap();
		$this->sentIdentifiers = $weakMap;
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
				$registry = self::getEntityRegistry();
				$packet->identifiers = $registry->getIdentifierTag();
				$this->sentIdentifiers[$session] = array_flip(array_keys($registry->getAll()));
				return true;
			})
			->interceptOutgoing(function(AddActorPacket $packet, NetworkSession $session) : bool {
				$FALLBACK_ENTITY = self::getFallbackEntity();
				if (($sentIdentifiers = $this->sentIdentifiers[$session] ?? null) !== null && $FALLBACK_ENTITY !== null && !isset($sentIdentifiers[$packet->type])) {
					$this->getLogger()->warning($session->getDisplayName() . " can't use \"$packet->type\". Instead, it sends \"$FALLBACK_ENTITY\".");
					$packet->type = $FALLBACK_ENTITY;
				}
				return true;
			});
	}

	protected function onDisable() : void {
		self::$registry = null;
	}

}