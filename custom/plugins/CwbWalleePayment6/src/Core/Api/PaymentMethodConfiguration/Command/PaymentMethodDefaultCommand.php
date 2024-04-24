<?php declare(strict_types=1);


namespace CwbWalleePayment6\Core\Api\PaymentMethodConfiguration\Command;

use Shopware\Core\Framework\Context;
use Symfony\Component\{
	Console\Command\Command,
	Console\Input\InputInterface,
	Console\Output\OutputInterface};
use CwbWalleePayment6\Core\Util\PaymentMethodUtil;

/**
 * Class PaymentMethodDefaultCommand
 *
 * @package CwbWalleePayment6\Core\Api\PaymentMethodConfiguration\Command
 */
class PaymentMethodDefaultCommand extends Command {

	/**
	 * @var string
	 */
	protected static $defaultName = 'wallee:payment-method:default';

	/**
	 * @var \CwbWalleePayment6\Core\Util\PaymentMethodUtil
	 */
	protected $paymentMethodUtil;

	/**
	 * PaymentMethodDefaultCommand constructor.
	 *
	 * @param \CwbWalleePayment6\Core\Util\PaymentMethodUtil $paymentMethodUtil
	 */
	public function __construct(PaymentMethodUtil $paymentMethodUtil)
	{
		parent::__construct(self::$defaultName);
		$this->paymentMethodUtil = $paymentMethodUtil;
	}

	/**
	 * @param \Symfony\Component\Console\Input\InputInterface   $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$output->writeln('Set CwbWalleePayment6 as default payment method...');
		$context = Context::createDefaultContext();
		$this->paymentMethodUtil->setWalleeAsDefaultPaymentMethod($context);
		$this->paymentMethodUtil->disableSystemPaymentMethods($context);
		return 0;
	}

	/**
	 * Configures the current command.
	 */
	protected function configure()
	{
		$this->setDescription('Sets CwbWalleePayment6 as default payment method.')
			 ->setHelp('This command updates CwbWalleePayment6 as default payment method for all SalesChannels.');
	}

}