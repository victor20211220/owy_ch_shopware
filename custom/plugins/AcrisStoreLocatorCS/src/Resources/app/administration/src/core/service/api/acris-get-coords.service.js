const ApiService = Shopware.Classes.ApiService;

class AcrisGetCoordsApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'acris-get-coords') {
        super(httpClient, loginService, apiEndpoint);
    }

    getCoords(street, zipcode, city, country) {
        const headers = this.getBasicHeaders();
        const apiRoute = `/_action/${this.getApiBasePath()}`;

        return this.httpClient
            .get(apiRoute, {
                params: { street, zipcode, city, country },
                headers
            })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

}
export default AcrisGetCoordsApiService;
