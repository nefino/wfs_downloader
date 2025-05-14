<?php

declare(strict_types=1);

namespace OCA\WFSDownloader\Service;

use OC\Files\Node\File;
use OC\Files\Node\Node;
use OC\User\NoUserException;
use OCA\WFSDownloader\AppInfo\Application;
use OCP\Dashboard\Model\WidgetItem;
use OCP\Files\Folder;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use Psr\Log\LoggerInterface;


class WFSService {

	public const GIF_DIR_NAME = 'gifs';

	/**
	 * @var IRootFolder
	 */
	private $root;
	/**
	 * @var LoggerInterface
	 */
	private $logger;

	public function __construct (IRootFolder $root,
								LoggerInterface $logger) {
		$this->root = $root;
		$this->logger = $logger;
	}


    /**
     * Downloads an XML file from the given URL and returns its content as a string.
     *
     * @param string $url
     * @return string|null
     */
    public function getCapabilitiesXML(string $url): ?string {
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $content = @file_get_contents($url, false, $context);

        if ($content === false) {
            $error = error_get_last();
            $this->logger->error("Failed to download XML from URL: $url", [
                'app' => Application::APP_ID,
                'error' => $error['message'] ?? 'unknown error'
            ]);
            return null;
        }

        $this->logger->info("Successfully downloaded capabilities XML from $url", ['app' => Application::APP_ID]);

        return $content;
    }

}
