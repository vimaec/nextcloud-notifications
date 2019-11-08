<?php
/**
 * @copyright Copyright (c) 2018 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Notifications\Workflow;

use DateTime;
use OC\Files\Node\File;
use OCP\EventDispatcher\Event;
use OCP\IL10N;
use OCP\WorkflowEngine\IRuleMatcher;
use OCP\WorkflowEngine\IOperation;
use Symfony\Component\EventDispatcher\GenericEvent;

class Operation implements IOperation {

	/**
	 * @var \OCP\Notification\IManager
	 */
	private $notificationManager;

	public function __construct(IL10N $l10n) {
		$this->l10n = $l10n;
	}

	/**
	 * returns a translated name to be presented in the web interface
	 *
	 * Example: "Automated tagging" (en), "Aŭtomata etikedado" (eo)
	 *
	 * @since 18.0.0
	 */
	public function getDisplayName(): string {
		return $this->l10n->t('Send a notification');
	}

	/**
	 * returns a translated, descriptive text to be presented in the web interface.
	 *
	 * It should be short and precise.
	 *
	 * Example: "Tag based automatic deletion of files after a given time." (en)
	 *
	 * @since 18.0.0
	 */
	public function getDescription(): string {
		return '';
	}

	/**
	 * returns the URL to the icon of the operator for display in the web interface.
	 *
	 * Usually, the implementation would utilize the `imagePath()` method of the
	 * `\OCP\IURLGenerator` instance and simply return its result.
	 *
	 * Example implementation: return $this->urlGenerator->imagePath('myApp', 'cat.svg');
	 *
	 * @since 18.0.0
	 */
	public function getIcon(): string {
		return \OC::$server->getURLGenerator()->imagePath('notifications', 'notifications.svg');
	}

	/**
	 * returns whether the operation can be used in the requested scope.
	 *
	 * Scope IDs are defined as constants in OCP\WorkflowEngine\IManager. At
	 * time of writing these are SCOPE_ADMIN and SCOPE_USER.
	 *
	 * For possibly unknown future scopes the recommended behaviour is: if
	 * user scope is permitted, the default behaviour should return `true`,
	 * otherwise `false`.
	 *
	 * @since 18.0.0
	 */
	public function isAvailableForScope(int $scope): bool {
		return true;
	}

	/**
	 * Validates whether a configured workflow rule is valid. If it is not,
	 * an `\UnexpectedValueException` is supposed to be thrown.
	 *
	 * @throws \UnexpectedValueException
	 * @since 9.1
	 */
	public function validateOperation(string $name, array $checks, string $operation): void {

	}

	/**
	 * Is being called by the workflow engine when an event was triggered that
	 * is configured for this operation. An evaluation whether the event
	 * qualifies for this operation to run has still to be done by the
	 * implementor by calling the RuleMatchers getMatchingOperations method
	 * and evaluating the results.
	 *
	 * If the implementor is an IComplexOperation, this method will not be
	 * called automatically. It can be used or left as no-op by the implementor.
	 *
	 * @since 18.0.0
	 */
	public function onEvent(string $eventName, Event $event, IRuleMatcher $ruleMatcher): void {

		$this->notificationManager = \OC::$server->getNotificationManager();
		/** @var File $file */
		$file = $event->getSubject();
		if (is_array($file)) {
			$file = $file[0];
		}
		$ruleMatcher->setFileInfo($file->getStorage(), $file->getFileInfo()->getPath());

		$rule = $ruleMatcher->getMatchingOperations(self::class, false);
		//if ($rule[0]['type'] === '1' && $ruleScopeUser = $rule[0]['value']) {
		if ($ruleScopeUser = 'admin') {
			if (get_class($file) === File::class && sizeof($rule) > 0) {

				$notification = $this->notificationManager->createNotification();
				$notification
					->setApp('notifications')
					->setUser($ruleScopeUser)
					->setDateTime(new DateTime())
					->setObject('file', (string) $file->getId())
					->setSubject($eventName, ['fileName' => $file->getName(), 'user' => \OC::$server->getUserSession()->getUser()->getUID()]);
				$this->notificationManager->notify($notification);
			}

		}


	}
}
