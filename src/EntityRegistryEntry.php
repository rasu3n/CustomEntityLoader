<?php

declare(strict_types=1);

namespace Rush2929\CustomEntityLoader;

use pocketmine\nbt\tag\CompoundTag;

final class EntityRegistryEntry {

	public const TAG_BEHAVIOR_ID = "bid"; //TAG_String TODO: naming
	public const TAG_HAS_SPAWN_EGG = "hasspawnegg"; //TAG_Byte
	public const TAG_IDENTIFIER = "id"; //TAG_String
	public const TAG_RUNTIME_ID = "rid"; //TAG_Int
	public const TAG_SUMMONABLE = "summonable"; //TAG_Byte //TODO: Find out what this is for

	/**
	 * @phpstan-param mixed[] $array
	 */
	public static function fromArray(array $array) : self {
		return new self(
			$array[ConfigKeys::ENTITY_IDENTIFIER],
			$array[ConfigKeys::ENTITY_BEHAVIOR_ID] ?? "",
			$array[ConfigKeys::ENTITY_RUNTIME_ID] ?? null,
			$array[ConfigKeys::ENTITY_HAS_SPAWNEGG] ?? false,
			$array[ConfigKeys::ENTITY_IS_SUMMONABLE] ?? false
		);
	}

	public static function read(CompoundTag $entry) : self {
		return new self(
			$entry->getString(self::TAG_IDENTIFIER),
			$entry->getString(self::TAG_BEHAVIOR_ID, ""),
			$entry->getTag(self::TAG_RUNTIME_ID)?->getValue(),
			$entry->getByte(self::TAG_HAS_SPAWN_EGG, 0) !== 0,
			$entry->getByte(self::TAG_SUMMONABLE, 0) !== 0
		);
	}

	public function __construct(
		private string $identifier,
		private string $behaviorId = "", // name
		private ?int $runtimeId = null,
		private bool $hasSpawnEgg = false,
		private bool $isSummonable = false
	) {
		if ($this->runtimeId !== null) {
			EntityRegistry::validateRuntimeId($this->runtimeId);
		}
		EntityRegistry::validateIdentifier($this->identifier);
	}

	public function getIdentifier() : string { return $this->identifier; }

	public function getBehaviorId() : string { return $this->behaviorId; }

	public function getRuntimeId() : ?int { return $this->runtimeId; }

	public function hasSpawnEgg() : bool { return $this->hasSpawnEgg; }

	public function isSummonable() : bool { return $this->isSummonable; }

	public function write(CompoundTag $entry) : void {
		$entry->setString(self::TAG_BEHAVIOR_ID, $this->behaviorId);
		$entry->setByte(self::TAG_HAS_SPAWN_EGG, $this->hasSpawnEgg ? 1 : 0);
		$entry->setString(self::TAG_IDENTIFIER, $this->identifier);
		if ($this->runtimeId !== null) {
			$entry->setInt(self::TAG_RUNTIME_ID, $this->runtimeId);
		}
		$entry->setByte(self::TAG_SUMMONABLE, $this->isSummonable ? 1 : 0);
	}

}