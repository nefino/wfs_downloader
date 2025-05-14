<?php

declare(strict_types=1);

namespace OCA\WFSDownloader\Listeners;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

class LoadAdditionalScriptsListener implements IEventListener {
	public function handle(Event $event): void {
		Util::addInitScript("wfs_downloader", "main");
	}
}