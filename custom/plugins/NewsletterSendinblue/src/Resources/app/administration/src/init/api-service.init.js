import ConnectionService
    from '../../src/core/service/api/connection.service';

const { Application } = Shopware;

Application.addServiceProvider('ConnectionService', (container) => {
    const initContainer = Application.getContainer('init');

    return new ConnectionService(initContainer.httpClient, container.loginService);
});
