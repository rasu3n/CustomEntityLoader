<?php

declare(strict_types=1);

namespace Rush2929\CustomEntityLoader;

use InvalidStateException;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\utils\Limits;

final class EntityRegistry {

	public const TAG_ID_LIST = "idlist";
	private const THE_IDENTIFIER_IS_INVALID = "The identifier is invalid.";

	public static function validateIdentifier(string $identifier) : void {
		//TODO: implements validation
	}

	public static function validateRuntimeId(int $runtimeId) : void {
		if ($runtimeId < Limits::INT16_MIN || $runtimeId > Limits::INT16_MAX) {
			throw new InvalidStateException("The runtime ID is invalid. The runtime ID must be a signed 16-bit integer value."); // for spawnEgg
		}
	}

	/** @var array<string, EntityRegistryEntry> */
	private array $entities = [];
	private bool $isDirty = false;
	/** @var CacheableNbt<CompoundTag>|null */
	private ?CacheableNbt $identifiers = null;

	public function add(EntityRegistryEntry $entry) : self {
		$identifier = $entry->getIdentifier();
		if (isset($this->entities[$identifier])) {
			throw new InvalidStateException(self::THE_IDENTIFIER_IS_INVALID . " The identifier is already in use.");
		}

		$this->entities[$identifier] = $entry;
		$this->isDirty = true;
		return $this;
	}

	public function get(string $identifier) : ?EntityRegistryEntry {
		return $this->entities[$identifier] ?? null;
	}

	/**
	 * @return array<string, EntityRegistryEntry>
	 */
	public function getAll() : array {
		return $this->entities;
	}

	public function remove(string $identifier) : self {
		unset($this->entities[$identifier]); //TODO: If the identifier is not actually registered, it throws an exception.
		$this->isDirty = true;
		return $this;
	}

	/**
	 * @return CacheableNbt<CompoundTag>
	 */
	public function getIdentifierTag() : CacheableNbt {
		if ($this->identifiers === null || $this->isDirty) {
			$listTag = new ListTag();
			foreach ($this->entities as $entry) {
				$entryTag = CompoundTag::create();
				$entry->write($entryTag);
				$listTag->push($entryTag);
			}
			$this->identifiers = new CacheableNbt(CompoundTag::create()->setTag(self::TAG_ID_LIST, $listTag));
			$this->isDirty = false;
		}

		return $this->identifiers;
	}

}