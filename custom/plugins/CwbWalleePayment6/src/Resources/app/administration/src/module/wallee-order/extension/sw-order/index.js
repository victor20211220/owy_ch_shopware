/* global Shopware */

import template from './sw-order.html.twig';
import './sw-order.scss';

const {Component, Context} = Shopware;
const Criteria = Shopware.Data.Criteria;

const walleeFormattedHandlerIdentifier = 'handler_cwbwalleepayment6_cwbwalleepayment6handler';

Component.override('sw-order-detail', {
	template,

	data() {
		return {
			isCwbWalleePayment6: false
		};
	},

	computed: {
		isEditable() {
			return !this.isCwbWalleePayment6 || this.$route.name !== 'wallee.order.detail';
		},
		showTabs() {
			return true;
		}
	},

	watch: {
		orderId: {
			deep: true,
			handler() {
				if (!this.orderId) {
					this.setIsCwbWalleePayment6(null);
					return;
				}

				const orderRepository = this.repositoryFactory.create('order');
				const orderCriteria = new Criteria(1, 1);
				orderCriteria.addAssociation('transactions');

				orderRepository.get(this.orderId, Context.api, orderCriteria).then((order) => {
					if (
						(order.amountTotal <= 0) ||
						(order.transactions.length <= 0) ||
						!order.transactions[0].paymentMethodId
					) {
						this.setIsCwbWalleePayment6(null);
						return;
					}

					const paymentMethodId = order.transactions[0].paymentMethodId;
					if (paymentMethodId !== undefined && paymentMethodId !== null) {
						this.setIsCwbWalleePayment6(paymentMethodId);
					}
				});
			},
			immediate: true
		}
	},

	methods: {
		setIsCwbWalleePayment6(paymentMethodId) {
			if (!paymentMethodId) {
				return;
			}
			const paymentMethodRepository = this.repositoryFactory.create('payment_method');
			paymentMethodRepository.get(paymentMethodId, Context.api).then(
				(paymentMethod) => {
					this.isCwbWalleePayment6 = (paymentMethod.formattedHandlerIdentifier === walleeFormattedHandlerIdentifier);
				}
			);
		}
	}
});
