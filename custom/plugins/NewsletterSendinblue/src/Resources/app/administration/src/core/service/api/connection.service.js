const ApiService = Shopware.Classes.ApiService;

class ConnectionService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'sendinblue') {
        super(httpClient, loginService, apiEndpoint);
    }

    testConnection() {
        // const apiRoute = `${this.getApiBasePath()}/connection`;
        // return this.httpClient.get(
        //     apiRoute,
        //     {
        //         headers: this.getBasicHeaders()
        //     }
        // ).then((response) => {
        //     return ApiService.handleResponse(response);
        // });
    }

    getConnectionSettingsLink(salesChannelId) {
        let apiRoute = `${this.getApiBasePath()}/settings`;
        if (salesChannelId !== null) {
            apiRoute += '?sid=' + salesChannelId
        }
        return this.httpClient.get(
            apiRoute,
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }
}

export default ConnectionService;
