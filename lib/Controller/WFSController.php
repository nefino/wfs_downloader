<?php

declare(strict_types=1);

namespace OCA\WFSDownloader\Controller;

use OC\User\NoUserException;
use OCA\WFSDownloader\Service\WFSService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Services\IInitialState;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Lock\LockedException;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

class WFSController extends OCSController  {
    
	/**
	 * @var string|null
	 */
	private $userId;
	/**
	 * @var WFSService
	 */
	private $wfsService;

	public function __construct(string        $appName,
								IRequest      $request,
								IInitialState $initialStateService,
								WFSService    $wfsService,
								?string       $userId)
	{
		parent::__construct($appName, $request);
		$this->initialStateService = $initialStateService;
		$this->userId = $userId;
		$this->wfsService = $wfsService;
	}

	/**
	 * @return DataDownloadResponse|DataResponse
	 * @throws InvalidPathException
	 * @throws NoUserException
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 * @throws LockedException
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/capabilities')]
	public function getCapabilities() {
        $url = $this->request->getParam('url');

		$xmlContent = $this->wfsService->getCapabilitiesXML($this->userId, $url);
		if ($xmlContent !== null) {
			$response = new DataResponse($xmlContent);
			// $response->cacheFor(60 * 60);
			$response->cacheFor(0);
			return $response;
		}

		return new DataResponse('', Http::STATUS_NOT_FOUND);
	}
}
