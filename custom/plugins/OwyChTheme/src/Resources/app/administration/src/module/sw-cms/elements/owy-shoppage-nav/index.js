import './component';
import './config';


const Criteria = Shopware.Data.Criteria;
const criteria = new Criteria();

Shopware.Service('cmsService').registerCmsElement({
    name: 'owy-shoppage-nav',
    label: 'Shop Pages Navigation',
    component: 'sw-cms-el-owy-shoppage-nav',
    configComponent: 'sw-cms-el-config-owy-shoppage-nav',

    defaultConfig: {
        categories: {
            source: 'static',
            value: [],
            required: true,
            entity: {
                name: 'category',
                criteria: criteria
            }
        }

    }
});
