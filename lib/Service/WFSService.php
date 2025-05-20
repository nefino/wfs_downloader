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
use OCP\Http\Client\IClientService;


class WFSService {

    private IClientService $clientService;

    private string $backendUrl;


	/**
	 * @var IRootFolder
	 */
	private $root;
	/**
	 * @var LoggerInterface
	 */
	private $logger;

	public function __construct (IRootFolder $root,
                                IClientService $clientService,
								LoggerInterface $logger) {
		$this->root = $root;
        $this->clientService = $clientService;

		$this->logger = $logger;
        $this->backendUrl = $_GET['DJANGO_URL'] ?? 'https://echo.free.beeceptor.com';
	}


    /**
     * Downloads an XML file from the given URL and returns its content as a string.
     *
     * @param string $url
     * @return string|null
     */
    public function getRequestProxy(string $url): ?string {
        $client = $this->clientService->newClient();

        try {
            $response = $client->get($url, [
                'timeout' => 10,
                'verify' => true,
            ]);

            return $response->getBody();
        } catch (\Exception $e) {
            $this->logger->error("Failed to download content from URL: $url", [
                'app' => 'your_app_id',
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function forwardLayerDownload(array $data) {
        $client = $this->clientService->newClient();

        $response = $client->post($this->backendUrl, [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($data),
        ]);

        // Decode the response body
        $responseData = json_decode($response->getBody(), true);
        return $responseData;
    }

}
