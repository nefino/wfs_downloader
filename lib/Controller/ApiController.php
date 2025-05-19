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
	 * API endpoint to fetch wfs capabilities (e.g. layers) because 
	 * frontend requests are not allowed in nextcloud
	 *
	 * @return DataResponse
	 */
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

	/**
	 * API endpoint to download selected layers.
	 * Post data is just forwarded to Django which handles downloading.
	 *
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'POST', url: '/download')]
	public function download(string $url, string $dir, array $layers) {

        // // Retrieve JSON data from the request
        // $data = $this->request->getParams();

		$postData = array(
			"url" => $url,
            "dir" => $dir,
            "layers" => $layers,
        );

        try {
			$msg = $this->wfsService->forwardLayerDownload($postData);
            // Decode the response body
            return new JSONResponse($msg);
        } catch (\Exception $e) {
            // Handle exceptions and return error response
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }

	}

}
