export default class UrlHelper {
    static getUrlParam(parameter, defaultValue){
        let urlParameter = defaultValue;
        const urlVars = this._getUrlVars();

        if (parameter in urlVars) {
            urlParameter = urlVars[parameter];
        }

        return urlParameter;
    }

    static _getUrlVars() {
        const vars = {};

        window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, (m, key, value) => {
            vars[key] = decodeURIComponent(value);
        });

        return vars;
    }
}
