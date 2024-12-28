<?php

require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';

abstract class BaseEContentDriver extends MarcRecordDriver {
	/**
	 * Constructor.  We build the object using all the data retrieved
	 * from the (Solr) index.  Since we have to
	 * make a search call to find out which record driver to construct,
	 * we will already have this data available, so we might as well
	 * just pass it into the constructor.
	 *
	 * @param array|File_MARC_Record||string   $recordData     Data to construct the driver from
	 * @param GroupedWork $groupedWork ;
	 * @access  public
	 */
	public function __construct($recordData, $groupedWork = null) {
		parent::__construct($recordData, $groupedWork);
	}


	abstract function isEContentHoldable($locationCode, $eContentFieldData);

	abstract function isLocalItem($locationCode, $eContentFieldData);

	abstract function isLibraryItem($locationCode, $eContentFieldData);

	abstract function isItemAvailable($itemId, $totalCopies);

	abstract function isValidForUser($locationCode, $eContentFieldData);

	abstract function getSharing($locationCode, $eContentFieldData);

	abstract function getEContentFormat($fileOrUrl, $iType);

	protected function isHoldable() {
		return false;
	}

	public function getItemActions($itemInfo) {
		if ($itemInfo instanceof Grouping_Item) {
			return $this->createActionsFromUrls($itemInfo->getRelatedUrls(), $itemInfo);
		} else {
			return $this->createActionsFromUrls($itemInfo['relatedUrls'], $itemInfo);
		}
	}

	public function getRecordActions($relatedRecord, $variationId, $isAvailable, $isHoldable, $volumeData = null) : array {
		return [];
	}

	function createActionsFromUrls($relatedUrls, $itemInfo = null, $variationId = 'any') {
		global $configArray;
		$actions = [];
		$i = 0;
		if (count($relatedUrls) > 1) {
			//We will show a popup to let people choose the URL they want
			$title = translate([
				'text' => 'Access Online',
				'isPublicFacing' => true,
			]);
			$actions[] = [
				'title' => $title,
				'url' => '',
				'onclick' => "return AspenDiscovery.EContent.selectItemLink('{$this->getId()}');",
				'requireLogin' => false,
				'type' => 'access_online',
				'id' => "accessOnline_{$this->getId()}",
				'target' => '_blank',
			];
		} elseif (count($relatedUrls)  == 1) {
			$urlInfo = reset($relatedUrls);

			//Revert to access online per Karen at CCU.  If people want to switch it back, we can add a per library switch
			$title = translate([
				'text' => 'Access Online',
				'isPublicFacing' => true,
			]);
			$alt = 'Available online from ' . $urlInfo['source'];
			$action = $configArray['Site']['url'] . '/' . $this->getModule() . '/' . $this->id . "/AccessOnline?index=$i";
			$fileOrUrl = isset($urlInfo['url']) ? $urlInfo['url'] : $urlInfo['file'];
			if (strlen($fileOrUrl) > 0) {
				if (strlen($fileOrUrl) >= 3) {
					$extension = strtolower(substr($fileOrUrl, strlen($fileOrUrl), 3));
					if ($extension == 'pdf') {
						$title = translate([
							'text' => 'Access PDF',
							'isPublicFacing' => true,
						]);
					}
				}
				$actions[] = [
					'url' => $action,
					'redirectUrl' => $fileOrUrl,
					'title' => $title,
					'requireLogin' => false,
					'alt' => $alt,
					'target' => '_blank',
				];
			}
		} else {
			foreach ($relatedUrls as $urlInfo) {
				$title = translate([
					'text' => 'Access Online',
					'isPublicFacing' => true,
				]);
				$alt = 'Available online from ' . $urlInfo['source'];
				$action = $configArray['Site']['url'] . '/' . $this->getModule() . '/' . $this->id . "/AccessOnline?index=$i";
				$fileOrUrl = isset($urlInfo['url']) ? $urlInfo['url'] : $urlInfo['file'];
				if (strlen($fileOrUrl) > 0) {
					if (strlen($fileOrUrl) >= 3) {
						$extension = strtolower(substr($fileOrUrl, strlen($fileOrUrl), 3));
						if ($extension == 'pdf') {
							$title = translate([
								'text' => 'Access PDF',
								'isPublicFacing' => true,
							]);
						}
					}
					$actions[] = [
						'url' => $action,
						'redirectUrl' => $fileOrUrl,
						'title' => $title,
						'requireLogin' => false,
						'alt' => $alt,
						'target' => '_blank',
					];
				}
			}
		}

		return $actions;
	}

	function getRelatedRecord() {
		return $this->getGroupedWorkDriver()->getRelatedRecord($this->getIdWithSource());
	}

	public function getRecordType() {
		return $this->profileType;
	}
}
