<?php

class GeneralSetting extends DataObject {
	public $__table = 'aspen_lida_general_settings';
	public $id;
	public $name;
	public $autoRotateCard;
	public $enableSelfRegistration;
	public $showMoreInfoBtn;

	private $_libraries;

	static function getObjectStructure($context = ''): array {
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'The name for these settings',
				'maxLength' => 50,
				'required' => true,
			],
			'autoRotateCard' => [
				'property' => 'autoRotateCard',
				'type' => 'checkbox',
				'label' => 'Automatically rotate the library card screen to landscape',
				'description' => 'Whether or not the library card screen automatically rotates to landscape mode when navigated to.',
				'hideInLists' => true,
			],
			'enableSelfRegistration' => [
				'property' => 'enableSelfRegistration',
				'type' => 'checkbox',
				'label' => 'Enable self-registration',
				'description' => 'Whether or not users can self register for a new account in LiDA.',
				'hideInLists' => true,
			],
			'showMoreInfoBtn' => [
				'property' => 'showMoreInfoBtn',
				'type' => 'checkbox',
				'label' => 'Show More Info button on Grouped Work Screen',
				'note' => 'This button opens up an in-app Aspen Discovery session to see additional record information.',
				'description' => 'Whether or not to display a More Info button in Aspen LiDA on the Grouped Work screen.',
				'hideInLists' => true,
			],
			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use these settings',
				'values' => $libraryList,
				'hideInLists' => true,
			],
		];

		if (!UserAccount::userHasPermission('Administer Aspen LiDA Settings')) {
			unset($structure['libraries']);
		}

		return $structure;
	}

	public function saveLibraries() {
		if (isset ($this->_libraries) && is_array($this->_libraries)) {
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
			foreach ($libraryList as $libraryId => $displayName) {
				$library = new Library();
				$library->libraryId = $libraryId;
				$library->find(true);
				if (in_array($libraryId, $this->_libraries)) {
					//We want to apply the scope to this library
					if ($library->lidaGeneralSettingId != $this->id) {
						$library->lidaGeneralSettingId = $this->id;
						$library->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->lidaGeneralSettingId == $this->id) {
						$library->lidaGeneralSettingId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}

	public function __get($name) {
		if ($name == 'libraries') {
			if (!isset($this->_libraries) && $this->id) {
				$this->_libraries = [];
				$obj = new Library();
				$obj->lidaGeneralSettingId = $this->id;
				$obj->find();
				while ($obj->fetch()) {
					$this->_libraries[$obj->libraryId] = $obj->libraryId;
				}
			}
			return $this->_libraries;
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == 'libraries') {
			$this->_libraries = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	public function update($context = '') {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return true;
	}

	public function insert($context = '') {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return $ret;
	}

	function getEditLink($context): string {
		return '/AspenLiDA/GeneralSettings?objectAction=edit&id=' . $this->id;
	}
}