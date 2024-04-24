<?php declare(strict_types=1);

namespace CwbWalleePayment6\Core\Util\Traits;

use Doctrine\DBAL\Connection;
use Shopware\Core\{
	Framework\Context,
	Framework\DataAbstractionLayer\EntityRepositoryInterface,
	Framework\DataAbstractionLayer\Search\Criteria,
	Framework\DataAbstractionLayer\Search\Filter\ContainsFilter,
	Framework\DataAbstractionLayer\Search\Filter\EqualsFilter,
	Framework\Plugin\Context\UninstallContext};
use CwbWalleePayment6\Core\{
	Checkout\PaymentHandler\CwbWalleePayment6Handler,
	Settings\Service\SettingsService};

/**
 * Trait CwbWalleePayment6PluginTrait
 *
 * We use a trait keep the plugin class clean and free of auxiliary functions.
 *
 * @package CwbWalleePayment6\Core\Util\Traits
 */
trait CwbWalleePayment6PluginTrait {

	/**
	 * @param \Shopware\Core\Framework\Context $context
	 */
	protected function enablePaymentMethods(Context $context)
	{
		$paymentMethodIds = $this->getPaymentMethodIds($context);
		foreach ($paymentMethodIds as $paymentMethodId) {
			$this->setPaymentMethodIsActive($paymentMethodId, true, $context);
		}
	}

	/**
	 * @param \Shopware\Core\Framework\Context $context
	 * @return string[]
	 */
	protected function getPaymentMethodIds(Context $context): array
	{
		/** @var EntityRepositoryInterface $paymentRepository */
		$paymentRepository = $this->container->get('payment_method.repository');
		$criteria          = (new Criteria())
			->addFilter(new EqualsFilter('handlerIdentifier', CwbWalleePayment6Handler::class));

		return $paymentRepository->searchIds($criteria, $context)->getIds();
	}

	/**
	 * @param string                           $paymentMethodId
	 * @param bool                             $active
	 * @param \Shopware\Core\Framework\Context $context
	 * @return void
	 */
	protected function setPaymentMethodIsActive(string $paymentMethodId, bool $active, Context $context): void
	{
		$paymentMethod = [
			'id'     => $paymentMethodId,
			'active' => $active,
		];

		/** @var EntityRepositoryInterface $paymentRepository */
		$paymentRepository = $this->container->get('payment_method.repository');
		$paymentRepository->update([$paymentMethod], $context);
	}

	/**
	 * @param \Shopware\Core\Framework\Context $context
	 * @return void
	 */
	protected function disablePaymentMethods(Context $context): void
	{
		$paymentMethodIds = $this->getPaymentMethodIds($context);
		foreach ($paymentMethodIds as $paymentMethodId) {
			$this->setPaymentMethodIsActive($paymentMethodId, false, $context);
		}
	}

	/**
	 * @param \Shopware\Core\Framework\Context $context
	 * @return void
	 */
	private function removeConfiguration(Context $context): void
	{
		$criteria = (new Criteria())
			->addFilter(new ContainsFilter('configurationKey', SettingsService::SYSTEM_CONFIG_DOMAIN));

		$systemConfigRepository = $this->container->get('system_config.repository');
		$idSearchResult         = $systemConfigRepository->searchIds($criteria, $context);

		foreach ($idSearchResult->getIds() as $id) {
			$systemConfigRepository->delete([['id' => $id]], $context);
		}
	}

	/**
	 * Delete user data when plugin is uninstalled
	 *
	 * @internal
	 * @param \Shopware\Core\Framework\Plugin\Context\UninstallContext $uninstallContext
	 * @return void
	 */
	protected function deleteUserData(UninstallContext $uninstallContext): void
	{
		$connection = $this->container->get(Connection::class);
		$query = 'ALTER TABLE `order` DROP COLUMN `wallee_lock`;';
		$connection->executeStatement($query);
	}
}
