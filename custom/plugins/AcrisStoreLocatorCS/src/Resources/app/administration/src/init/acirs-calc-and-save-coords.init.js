import AcrisCalcAndSaveCoordsApiService
    from '../core/service/api/acris-calc-and-save-coords.service';

const { Application } = Shopware;

Application.addServiceProvider('AcrisCalcAndSaveCoordsApiService', (container) => {
    const initContainer = Application.getContainer('init');

    return new AcrisCalcAndSaveCoordsApiService(initContainer.httpClient, container.loginService);
});
