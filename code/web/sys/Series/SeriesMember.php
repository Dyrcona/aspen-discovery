<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class SeriesMember extends DataObject {
	public $__table = 'series_member';
	public $id;
	public $seriesId;
	public $groupedWorkPermanentId;
	public $isPlaceholder;
	public $displayName;
	public $author;
	public $description;
	public $volume;
	public $pubDate;
	public $weight;

	public static function getObjectStructure($context = ''): array {

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'displayName' => [
				'property' => 'displayName',
				'type' => 'text',
				'label' => 'Title',
			],
			'author' => [
				'property' => 'author',
				'type' => 'text',
				'label' => 'Author',
			],
			'description' => [
				'property' => 'description',
				'type' => 'text',
				'label' => 'Description',
				'description' => 'Series description',
				'note' => 'Title description',
			],
			'volume' => [
				'property' => 'volume',
				'type' => 'text',
				'label' => 'Volume',
				'description' => 'Modify to correct sorting by volume',
			],
			'pubDate' => [
				'property' => 'pubDate',
				'type' => 'integer',
				'label' => 'Earliest Publication Date',
				'description' => 'Modify to correct sorting by date',
				'default' => (int) Date("Y"),
				'min' => 0,
				'max' => (int) Date("Y") + 1, // No books from too far in the future
			],
			'groupedWorkPermanentId' => [
				'property' => 'groupedWorkPermanentId',
				'type' => 'text',
				'label' => 'Permanent Id',
				'readOnly' => true,
			],
			'isPlaceholder' => [
				'property' => 'isPlaceholder',
				'type' => 'checkbox',
				'label' => 'Library does not own',
				'readOnly' => true,
			],
			'weight' => [
				'property' => 'weight',
				'type' => 'numeric',
				'label' => 'Weight',
				'weight' => 'Defines how items are sorted.  Lower weights are displayed higher.',
			],
		];
		return $structure;
	}

	public function update($context = '') {
		if ($this->groupedWorkPermanentId) {
			$this->isPlaceholder = false;
		} else {
			$this->isPlaceholder = true;
		}
		return parent::update();
	}

	public function insert($context = '') {
		if ($this->groupedWorkPermanentId) {
			$this->isPlaceholder = false;
		} else {
			$this->isPlaceholder = true;
		}
		return parent::insert();
	}

	public function getNumericColumnNames(): array {
		return [
			'pubDate',
		];
	}

	public function getRecordDriver() {
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$recordDriver = new GroupedWorkDriver($this->groupedWorkPermanentId);
		if (!$recordDriver->isValid()) {
			return null;
		}
		return $recordDriver;
	}
}