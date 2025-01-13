<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class LinkedAccounts extends MyAccount {
	function launch() : void {
		global $interface;
		$user = UserAccount::getLoggedInUser();

		if ($user) {
			if ($user->source == 'admin' || $user->source == 'development') {
				$interface->assign('invalidSource', true);
			}else{
				// Determine which user we are showing/updating settings for
				$linkedUsers = $user->getLinkedUsers();
				$patronId = isset($_REQUEST['patronId']) ? $_REQUEST['patronId'] : $user->id;
				/** @var User $patron */
				$patron = $user->getUserReferredTo($patronId);

				// Linked Accounts Selection Form set-up
				if (count($linkedUsers) > 0) {
					array_unshift($linkedUsers, $user); // Adds primary account to list for display in account selector
					$interface->assign('linkedUsers', $linkedUsers);
					$interface->assign('selectedUser', $patronId);
				}

				$userPType = $user->getPType();
				require_once ROOT_DIR . '/sys/Account/PType.php';
				$accountLinkingSetting = PType::getAccountLinkingSetting($userPType);
				$interface->assign('linkSetting', $accountLinkingSetting);
				$accountLinkRemoveSetting = PType::getAccountLinkRemoveSetting($userPType);
				$interface->assign('linkRemoveSetting', $accountLinkRemoveSetting);
				$interface->assign('profile', $patron);
				$interface->assign('barcodePin', $patron->getAccountProfile()->loginConfiguration == 'barcode_pin');
				// Switch for displaying the barcode in the account profile
			}
		}

		$this->display('linkedAccounts.tpl', 'Linked Accounts');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'Linked Accounts');
		return $breadcrumbs;
	}
}