import './component';
import './config';


Shopware.Service('cmsService').registerCmsElement({
    name: 'owy-category-tree-teaser',
    label: 'Sub category teaser',
    component: 'sw-cms-el-owy-category-tree-teaser',
    configComponent: 'sw-cms-el-config-owy-category-tree-teaser',

    defaultConfig: {
        subCatHeading: {
            source: 'static',
            value: "Sub Category Widget"
        }
    }
});
