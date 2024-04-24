import AcrisGetCoordsApiService
    from '../core/service/api/acris-get-coords.service';

const { Application } = Shopware;

Application.addServiceProvider('AcrisGetCoordsApiService', (container) => {
    const initContainer = Application.getContainer('init');

    return new AcrisGetCoordsApiService(initContainer.httpClient, container.loginService);
});

