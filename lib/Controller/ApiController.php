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
	 * GET request proxy to do web requests from the frontend by calling this route
	 * with a provided url parameter
	 *
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/proxy')]
	public function proxy() {
        $url = $this->request->getParam('url');
        try {
            $xmlContent = $this->wfsService->getRequestProxy($url);
            if ($xmlContent !== null) {
                $response = new DataResponse($xmlContent);
                $response->cacheFor(0);
                return $response;
            }
            return new JSONResponse(['error' => 'Failed to download content from URL'], 404);
        } catch (\Exception $e) {
            return new JSONResponse([
                'error' => 'Exception occurred while downloading content',
                'message' => $e->getMessage(),
            ], 500);
        }
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
        $postData = array(
            'wfs_url' => $url,
            'location' => $dir,
            'layers' => $layers,
        );

        try {
            $body = $this->wfsService->postRequestProxy('/wfs-download/', $postData);
            if ($body !== null) {
                return new JSONResponse($body);
            }
            return new JSONResponse(['error' => 'Failed to post data to backend'], 500);
        } catch (\Exception $e) {
            return new JSONResponse([
                'error' => 'Exception occurred while posting data to backend',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

}
