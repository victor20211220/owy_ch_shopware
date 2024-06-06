(()=>{var a=Shopware.Classes.ApiService,o=class extends a{constructor(e,s,i="swag-security"){super(e,s,i)}getFixes(){let e=this.getBasicHeaders({});return this.httpClient.get(`_action/${this.getApiBasePath()}/available-fixes`,{headers:e}).then(s=>a.handleResponse(s))}getUpdate(){let e=this.getBasicHeaders({});return this.httpClient.get(`_action/${this.getApiBasePath()}/update-available`,{headers:e}).then(s=>a.handleResponse(s))}saveValues(e,s){let i=this.getBasicHeaders({});return this.httpClient.post(`_action/${this.getApiBasePath()}/save-config`,{config:e,currentPassword:s},{headers:i}).then(n=>a.handleResponse(n))}cacheClear(){let e=this.getBasicHeaders({});return this.httpClient.delete(`_action/${this.getApiBasePath()}/clear-container-cache`,{headers:e})}},l=o;var r=class{isActive(e){return Shopware.State.get("context").app.config.swagSecurity.includes(e)}},d=r;var{Application:c}=Shopware;c.addServiceProvider("swagSecurityApi",t=>{let e=c.getContainer("init");return new l(e.httpClient,t.loginService)});c.addServiceProvider("swagSecurityState",()=>new d);var g="swagSecurityDidSeen",h=async()=>{let t=Shopware.Service("swagSecurityCookieStorage");if(t.getItem(g)!==null)return;let e=await Shopware.Service("swagSecurityApi").getUpdate(),s=new Date;if(s.setDate(s.getDate()+1),t.setItem(g,"1",{expires:s}),!e.updateAvailable)return;let i=Shopware.Application.getApplicationRoot(),n=i.$tc("global.default.cancel"),m=i.$tc("global.notification-center.shopware-updates-listener.updateNow"),f={title:i.$t("sw-settings-security.notification.title",e),message:i.$t("sw-settings-security.notification.message",e),variant:"info",growl:!0,system:!0,actions:[{label:m,route:{name:"sw.plugin.index.updates"}},{label:n}],autoClose:!1};i.$store.dispatch("notification/createNotification",f)};setTimeout(h,1e3);var w=`{% block sw_settings_security_index %}
    <sw-page class="sw-settings-security">

        {% block sw_settings_security_search_bar %}
            <template slot="search-bar">
                <sw-search-bar>
                </sw-search-bar>
            </template>
        {% endblock %}

        {% block sw_settings_security_smart_bar_header %}
            <template slot="smart-bar-header">
                {% block sw_settings_security_smart_bar_header_title %}
                    <h2>
                        {% block sw_settings_security_smart_bar_header_title_text %}
                            {{ $tc('sw-settings.index.title') }}
                            <sw-icon name="regular-chevron-right-xs" small>
                            </sw-icon>
                            {{ $tc('sw-settings-security.general.textHeadline') }}
                        {% endblock %}
                    </h2>
                {% endblock %}
            </template>
        {% endblock %}

        {% block sw_settings_security_smart_bar_actions %}
            <template slot="smart-bar-actions">
                {% block sw_settings_security_actions_save %}
                    <sw-button-process
                        v-if="fixes.availableFixes && fixes.availableFixes.length"
                        class="sw-settings-security__save-action"
                        :isLoading="isLoading"
                        :processSuccess="isSaveSuccessful"
                        :disabled="isLoading"
                        variant="primary"
                        @process-finish="saveFinish"
                        @click="onSave">
                        {{ $tc('sw-settings-security.general.buttonSave') }}
                    </sw-button-process>
                {% endblock %}
            </template>
        {% endblock %}

        {% block sw_settings_security_content %}
            <template slot="content">
                <sw-card-view>
                    <sw-card :title="$tc('sw-settings-security.general.cardTitle')" :isLoading="isLoading" v-if="isLoading || fixes.availableFixes.length">
                        <sw-alert variant="warning">{{ $tc('sw-settings-security.general.alert') }}</sw-alert>

                        <div v-for="fix in fixes.availableFixes" v-if="fixes">
                            <sw-field
                                type="checkbox"
                                :name="fix"
                                :label="$tc('sw-settings-security.fixes.' + fix + '.label')"
                                v-model="config[fix]"
                                :helpText="$tc('sw-settings-security.fixes.' + fix + '.tooltip')"
                            />
                        </div>
                    </sw-card>

                    <sw-empty-state v-else
                                    :title="$tc('sw-settings-security.general.noFixes')">
                    </sw-empty-state>
                </sw-card-view>

                <sw-modal v-if="confirmPasswordModal"
                          @modal-close="onCloseConfirmPasswordModal"
                          :title="$tc('sw-profile.index.titleConfirmPasswordModal')"
                          variant="small">
                    <sw-password-field
                        class="sw-settings-user-detail__confirm-password"
                        v-model="confirmPassword"
                        required
                        name="sw-field--confirm-password"
                        :passwordToggleAble="true"
                        :copyAble="false"
                        :label="$tc('sw-profile.index.labelConfirmPassword')"
                        :placeholder="$tc('sw-profile.index.placeholderConfirmPassword')">
                    </sw-password-field>

                    <template #modal-footer>
                        <sw-button @click="onCloseConfirmPasswordModal"
                                   size="small">
                            {{ $tc('sw-profile.index.labelButtonCancel') }}
                        </sw-button>
                        <sw-button @click="onVerifiedSave"
                                   variant="primary"
                                   :disabled="!confirmPassword"
                                   size="small">
                            {{ $tc('sw-profile.index.labelButtonConfirm') }}
                        </sw-button>
                    </template>
                </sw-modal>
            </template>
        {% endblock %}
    </sw-page>
{% endblock %}
`;var{Component:v,Mixin:y}=Shopware;v.register("sw-settings-security-view",{template:w,inject:["swagSecurityApi","systemConfigApiService"],data(){return{isLoading:!0,isSaveSuccessful:!1,confirmPasswordModal:!1,confirmPassword:"",config:{},fixes:[]}},mixins:[y.getByName("notification")],methods:{onCloseConfirmPasswordModal(){this.confirmPasswordModal=!1,this.isLoading=!1,this.confirmPassword=""},onSave(){this.confirmPasswordModal=!0},onVerifiedSave(){this.isLoading=!0,this.swagSecurityApi.saveValues(this.config,this.confirmPassword).then(()=>{this.isLoading=!0,this.confirmPasswordModal=!1,this.confirmPassword="",this.swagSecurityApi.cacheClear().then(()=>{this.isLoading=!1,this.isSaveSuccessful=!0,window.location.reload()})}).catch(()=>{this.createNotificationError({title:this.$tc("sw-profile.index.notificationPasswordErrorTitle"),message:this.$tc("sw-profile.index.notificationOldPasswordErrorMessage")})})},saveFinish(){this.isSaveSuccessful=!1}},async mounted(){this.fixes=await this.swagSecurityApi.getFixes();for(let t of this.fixes.availableFixes)this.config[t]=this.fixes.activeFixes.includes(t);this.isLoading=!1}});var p=`{% block sw_settings_content_card_slot_plugins %}
    {% parent %}

    {% block sw_settings_swag_security %}
        <sw-settings-item
            v-if="canViewSecuritySettings"
            :label="$tc('sw-settings-security.general.mainMenuItemGeneral')"
            :to="{ name: 'sw.settings.security.index' }">
            <template slot="icon">
                <sw-icon name="regular-shield"></sw-icon>
            </template>
        </sw-settings-item>
    {% endblock %}
{% endblock %}
`;Shopware.Component.override("sw-settings-index",{template:p,computed:{canViewSecuritySettings(){let t=Shopware.Service("acl");return t?t.can("admin"):!0}}});var{Module:S}=Shopware,u={type:"plugin",name:"settings-security",title:"sw-settings-security.general.mainMenuItemGeneral",description:"sw-settings-security.general.description",version:"1.0.0",targetVersion:"1.0.0",color:"#9AA8B5",icon:"regular-cog",favicon:"icon-module-settings.png",routes:{index:{component:"sw-settings-security-view",path:"index",meta:{parentPath:"sw.settings.index",privilege:"admin"}}},settingsItem:[{group:"plugins",to:"sw.settings.security.index",icon:"regular-shield",name:"sw-settings-security.general.mainMenuItemGeneral"}]};Shopware.Component.getComponentRegistry().has("sw-extension-config")||delete u.settingsItem;S.register("sw-settings-security",u);})();
