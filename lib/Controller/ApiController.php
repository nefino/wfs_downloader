<?php

declare(strict_types=1);

namespace OCA\WFSDownloader\Controller;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCA\WFSDownloader\Service\WFSService;

use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Http\Client\IClientService;


/**
 * @psalm-suppress UnusedClass
 */
class ApiController extends OCSController {

	/**
	 * @var WFSService
	 */
	private WFSService $wfsService;


	public function __construct(string        $appName,
								IRequest      $request,
								WFSService    $wfsService)
	{ 
		parent::__construct($appName, $request);
		$this->wfsService = $wfsService;
	}



	/**
	 * An example API endpoint
	 *
	 * @return DataResponse<Http::STATUS_OK, array{message: string}, array{}>
	 *
	 * 200: Data returned
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api')]
	public function index(): DataResponse {
		return new DataResponse(
			['message' => 'Hello world!']
		);
	}


	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/capabilities')]
	public function getCapabilities() {
        $url = $this->request->getParam('url');

		$xmlContent = $this->wfsService->getCapabilitiesXML($url);
		if ($xmlContent !== null) {
			$response = new DataResponse($xmlContent);
			// $response->cacheFor(60 * 60);
			$response->cacheFor(0);
			return $response;
		}

		return new DataResponse('', Http::STATUS_NOT_FOUND);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'POST', url: '/download')]
	public function download(string $url, array  $layers) {

        // Retrieve JSON data from the request
        $data = $this->request->getParams();

        try {
			$msg = $this->wfsService->forwardLayerDownload($data);
            // Decode the response body
            return new DataResponse($msg);
        } catch (\Exception $e) {
            // Handle exceptions and return error response
            return new DataResponse(['error' => $e->getMessage()], 500);
        }

	}

}
