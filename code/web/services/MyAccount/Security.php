<?php

require_once ROOT_DIR . '/CatalogConnection.php';
require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

class Security extends MyAccount {
	function launch() : void {
		global $interface;

		$twoFactor = UserAccount::has2FAEnabledForPType();
		$interface->assign('twoFactorEnabled', $twoFactor);
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getActiveUserObj();

			$twoFactorAuthSetting = $user->getTwoFactorAuthenticationSetting();
			if ($twoFactorAuthSetting != null) {
				$isEnabled = $twoFactorAuthSetting->isEnabled;
				if ($isEnabled != 'notAvailable') {
					$interface->assign('twoFactorStatus', $user->twoFactorStatus);
					$interface->assign('showBackupCodes', false);
					$interface->assign('enableDeactivation', true);
					if ($user->twoFactorStatus == '1') {
						$interface->assign('showBackupCodes', true);
						require_once ROOT_DIR . '/sys/TwoFactorAuthCode.php';
						$backupCode = new TwoFactorAuthCode();
						$backupCodes = $backupCode->getBackups();
						$numBackupCodes = count($backupCodes);
						$interface->assign('backupCodes', $backupCodes);
						$interface->assign('numBackupCodes', $numBackupCodes);
						if ($isEnabled == 'mandatory') {
							$interface->assign('enableDeactivation', false);
						}
					}

				}
			}
		}

		$this->display('securityPage.tpl', 'Security');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('', 'Security');
		return $breadcrumbs;
	}
}