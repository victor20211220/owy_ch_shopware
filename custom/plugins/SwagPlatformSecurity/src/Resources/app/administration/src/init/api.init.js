import swagSecurityApiClient from '../service/swagSecurityApiClient';
import swagSecurityState from '../service/swagSecurityState';

const { Application } = Shopware;

Application.addServiceProvider('swagSecurityApi', (container) => {
    const initContainer = Application.getContainer('init');
    return new swagSecurityApiClient(initContainer.httpClient, container.loginService);
});

Application.addServiceProvider('swagSecurityState', () => {
    return new swagSecurityState();
});
