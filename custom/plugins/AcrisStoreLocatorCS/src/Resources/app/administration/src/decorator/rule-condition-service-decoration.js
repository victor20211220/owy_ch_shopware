const { Application } = Shopware;
import '../core/component/location-rule';

Application.addServiceProviderDecorator('ruleConditionDataProviderService', (ruleConditionService) => {

    ruleConditionService.addCondition('location', {
        component: 'location-rule',
        label: 'acrisStoreLocator.condition.locationRule',
        scopes: ['global'],
        group: 'general',
    });

    return ruleConditionService;
});
