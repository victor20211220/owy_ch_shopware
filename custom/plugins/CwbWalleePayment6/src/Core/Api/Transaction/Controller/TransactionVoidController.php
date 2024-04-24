<?php declare(strict_types=1);

namespace CwbWalleePayment6\Core\Api\Transaction\Controller;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\{
	HttpFoundation\JsonResponse,
	HttpFoundation\Request,
	HttpFoundation\Response,
	Routing\Annotation\Route};
use Wallee\Sdk\{
	Model\TransactionState};
use CwbWalleePayment6\Core\Settings\Service\SettingsService;

/**
 * Class TransactionVoidController
 *
 * @package CwbWalleePayment6\Core\Api\Transaction\Controller
 *
 * @Route(defaults={"_routeScope"={"api"}})
 */
class TransactionVoidController extends AbstractController {

	/**
	 * @var \CwbWalleePayment6\Core\Settings\Service\SettingsService
	 */
	protected $settingsService;

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $logger;

	/**
	 * TransactionVoidController constructor.
	 *
	 * @param \CwbWalleePayment6\Core\Settings\Service\SettingsService $settingsService
	 */
	public function __construct(SettingsService $settingsService)
	{
		$this->settingsService = $settingsService;
	}

	/**
	 * @param \Psr\Log\LoggerInterface $logger
	 * @internal
	 * @required
	 *
	 */
	public function setLogger(LoggerInterface $logger): void
	{
		$this->logger = $logger;
	}

	/**
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 * @throws \Wallee\Sdk\ApiException
	 * @throws \Wallee\Sdk\Http\ConnectionException
	 * @throws \Wallee\Sdk\VersioningException
	 *
	 * @Route(
	 *     "/api/_action/wallee/transaction-void/create-transaction-void/",
	 *     name="api.action.wallee.transaction-void.create-transaction-void",
	 *     methods={"POST"}
	 *     )
	 */
	public function createTransactionVoid(Request $request): JsonResponse
	{
		$salesChannelId = $request->request->get('salesChannelId');
		$transactionId  = $request->request->get('transactionId');

		$settings  = $this->settingsService->getSettings($salesChannelId);
		$apiClient = $settings->getApiClient();

		$transaction = $apiClient->getTransactionService()->read($settings->getSpaceId(), $transactionId);
		if ($transaction->getState() == TransactionState::AUTHORIZED) {
			$transactionVoid = $apiClient->getTransactionVoidService()->voidOnline($settings->getSpaceId(), $transaction->getId());
			return new JsonResponse(strval($transactionVoid), Response::HTTP_OK, [], true);
		}

		return new JsonResponse(
			[
				'message' => strtr('Transaction is in state {state}, it can not be completed at this time.', ['{state}' => $transaction->getState()]),
			],
			Response::HTTP_NOT_ACCEPTABLE
		);
	}
}