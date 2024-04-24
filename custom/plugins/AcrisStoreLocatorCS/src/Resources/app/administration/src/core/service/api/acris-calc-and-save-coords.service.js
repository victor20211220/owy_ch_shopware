const ApiService = Shopware.Classes.ApiService;

class AcrisCalcAndSaveCoordsApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'acris-calc-and-save-coords') {
        super(httpClient, loginService, apiEndpoint);
    }

    async calcAndSaveCoords(callback, offset, total, limit = 25, errors = '') {
        const headers = this.getBasicHeaders();
        const apiRoute = `/_action/${this.getApiBasePath()}`;
        return this.httpClient
            .get(apiRoute, {
                params: {offset, limit, total, errors},
                headers
            })
            .then(response => {
                    if (response.data.offset <= response.data.total && response.data.offset !== 'reached') {
                        callback.call(response.data.offset, response.data, false);
                        this.calcAndSaveCoords(callback, response.data.offset, response.data.total, response.data.limit, response.data.errors);
                    } else if (response.data.offset === 'reached') {
                        callback.call(response.data.offset, response.data, true);
                    } else if (!response.data.success) {
                        callback.call(response.data.offset, response.data, true);
                    }
                    // return ApiService.handleResponse(response);
                }
            );

    }
}

export default AcrisCalcAndSaveCoordsApiService;
