import HttpClient from 'src/service/http-client.service';

export default class HttpGateway {
    constructor() {
        this.client = new HttpClient();
    }

    async get(url) {
        return new Promise((resolve, reject) => {
            const request = this.client.get(url, response => {
                if (200 === request.status) {
                    resolve(JSON.parse(response));
                } else {
                    reject({ request, response });
                }
            });
        });
    }

    async post(url, data) {
        return new Promise((resolve, reject) => {
            const request = this.client.post(
                url,
                data,
                response => {
                    if (200 === request.status) {
                        resolve(JSON.parse(response));
                    } else {
                        reject({ request, response });
                    }
                },
            );
        });
    }
}
