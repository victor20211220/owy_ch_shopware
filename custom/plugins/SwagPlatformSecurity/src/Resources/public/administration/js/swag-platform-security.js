(()=>{var E=Object.create;var _=Object.defineProperty;var F=Object.getOwnPropertyDescriptor;var R=Object.getOwnPropertyNames;var V=Object.getPrototypeOf,U=Object.prototype.hasOwnProperty;var u=(i,e)=>()=>(e||i((e={exports:{}}).exports,e),e.exports);var D=(i,e,t,s)=>{if(e&&typeof e=="object"||typeof e=="function")for(let r of R(e))!U.call(i,r)&&r!==t&&_(i,r,{get:()=>e[r],enumerable:!(s=F(e,r))||s.enumerable});return i};var H=(i,e,t)=>(t=i!=null?E(V(i)):{},D(e||!i||!i.__esModule?_(t,"default",{value:i,enumerable:!0}):t,i));var v=u(h=>{"use strict";Object.defineProperty(h,"__esModule",{value:!0});var q=function(e){var t=e.sameSite;return typeof t>"u"?null:["lax","strict"].indexOf(t.toLowerCase())>=0?t:null},K=function(e){var t=e.path,s=e.domain,r=e.expires,o=e.secure,n=q(e);return[typeof t>"u"||t===null?"":";path="+t,typeof s>"u"||s===null?"":";domain="+s,typeof r>"u"||r===null?"":";expires="+r.toUTCString(),typeof o>"u"||o===!1?"":";secure",n===null?"":";SameSite="+n].join("")},N=function(e,t,s){return[encodeURIComponent(e),"=",encodeURIComponent(t),K(s)].join("")};h.formatCookie=N});var S=u(y=>{"use strict";function G(i,e){return X(i)||W(i,e)||z()}function z(){throw new TypeError("Invalid attempt to destructure non-iterable instance")}function W(i,e){if(Symbol.iterator in Object(i)||Object.prototype.toString.call(i)==="[object Arguments]"){var t=[],s=!0,r=!1,o=void 0;try{for(var n=i[Symbol.iterator](),l;!(s=(l=n.next()).done)&&(t.push(l.value),!(e&&t.length===e));s=!0);}catch(g){r=!0,o=g}finally{try{!s&&n.return!=null&&n.return()}finally{if(r)throw o}}return t}}function X(i){if(Array.isArray(i))return i}Object.defineProperty(y,"__esModule",{value:!0});var J=function(e){if(e.length===0)return{};var t={},s=new RegExp("\\s*;\\s*");return e.split(s).forEach(function(r){var o=r.split("="),n=G(o,2),l=n[0],g=n[1],T=decodeURIComponent(l),B=decodeURIComponent(g);t[T]=B}),t};y.parseCookies=J});var A=u(b=>{"use strict";function Q(i,e){if(!(i instanceof e))throw new TypeError("Cannot call a class as a function")}function x(i,e){for(var t=0;t<e.length;t++){var s=e[t];s.enumerable=s.enumerable||!1,s.configurable=!0,"value"in s&&(s.writable=!0),Object.defineProperty(i,s.key,s)}}function Y(i,e,t){return e&&x(i.prototype,e),t&&x(i,t),i}Object.defineProperty(b,"__esModule",{value:!0});var P=v(),f=S(),Z=function(){function i(e){if(Q(this,i),this._defaultOptions=Object.assign({domain:null,expires:null,path:null,secure:!1},e),typeof Proxy<"u")return new Proxy(this,ee)}return Y(i,[{key:"clear",value:function(){var t=this,s=f.parseCookies(this._getCookie()),r=Object.keys(s);r.forEach(function(o){return t.removeItem(o)})}},{key:"getItem",value:function(t){var s=f.parseCookies(this._getCookie());return Object.prototype.hasOwnProperty.call(s,t)?s[t]:null}},{key:"key",value:function(t){var s=f.parseCookies(this._getCookie()),r=Object.keys(s).sort();return t<r.length?r[t]:null}},{key:"removeItem",value:function(t,s){var r="",o=Object.assign(Object.assign(Object.assign({},this._defaultOptions),s),{expires:new Date(0)}),n=P.formatCookie(t,r,o);this._setCookie(n)}},{key:"setItem",value:function(t,s,r){var o=Object.assign(Object.assign({},this._defaultOptions),r),n=P.formatCookie(t,s,o);this._setCookie(n)}},{key:"_getCookie",value:function(){return typeof document>"u"||typeof document.cookie>"u"?"":document.cookie}},{key:"_setCookie",value:function(t){document.cookie=t}},{key:"length",get:function(){var t=f.parseCookies(this._getCookie()),s=Object.keys(t);return s.length}}]),i}();b.CookieStorage=Z;var ee={defineProperty:function(e,t,s){return e.setItem(t.toString(),String(s.value)),!0},deleteProperty:function(e,t){return e.removeItem(t.toString()),!0},get:function(e,t,s){if(typeof t=="string"&&t in e)return e[t];var r=e.getItem(t.toString());return r!==null?r:void 0},getOwnPropertyDescriptor:function(e,t){if(!(t in e))return{configurable:!0,enumerable:!0,value:e.getItem(t.toString()),writable:!0}},has:function(e,t){return typeof t=="string"&&t in e?!0:e.getItem(t.toString())!==null},ownKeys:function(e){for(var t=[],s=0;s<e.length;s++){var r=e.key(s);r!==null&&t.push(r)}return t},preventExtensions:function(e){throw new TypeError("can't prevent extensions on this proxy object")},set:function(e,t,s,r){return e.setItem(t.toString(),String(s)),!0}}});var I=u(c=>{"use strict";Object.defineProperty(c,"__esModule",{value:!0});var te=A();c.CookieStorage=te.CookieStorage;var ie=v();c.formatCookie=ie.formatCookie;var se=S();c.parseCookies=se.parseCookies});var d=Shopware.Classes.ApiService,m=class extends d{constructor(e,t,s="swag-security"){super(e,t,s)}getFixes(){let e=this.getBasicHeaders({});return this.httpClient.get(`_action/${this.getApiBasePath()}/available-fixes`,{headers:e}).then(t=>d.handleResponse(t))}getUpdate(){let e=this.getBasicHeaders({});return this.httpClient.get(`_action/${this.getApiBasePath()}/update-available`,{headers:e}).then(t=>d.handleResponse(t))}saveValues(e,t){let s=this.getBasicHeaders({});return this.httpClient.post(`_action/${this.getApiBasePath()}/save-config`,{config:e,currentPassword:t},{headers:s}).then(r=>d.handleResponse(r))}cacheClear(){let e=this.getBasicHeaders({});return this.httpClient.delete(`_action/${this.getApiBasePath()}/clear-container-cache`,{headers:e})}},k=m;var w=class{isActive(e){return Shopware.State.get("context").app.config.swagSecurity.includes(e)}},C=w;var O=H(I()),{Application:p}=Shopware;p.addServiceProvider("swagSecurityApi",i=>{let e=p.getContainer("init");return new k(e.httpClient,i.loginService)});p.addServiceProvider("swagSecurityState",()=>new C);p.addServiceProvider("swagSecurityCookieStorage",()=>{let i=Shopware.Context.api.host,e=Shopware.Context.api.basePath+Shopware.Context.api.pathInfo;return new O.CookieStorage({path:e,domain:i,secure:location.protocol==="https:",sameSite:"Strict"})});var $="swagSecurityDidSeen",re=async()=>{let i=Shopware.Service("swagSecurityCookieStorage");if(i.getItem($)!==null)return;let e=await Shopware.Service("swagSecurityApi").getUpdate(),t=new Date;if(t.setDate(t.getDate()+1),i.setItem($,"1",{expires:t}),!e.updateAvailable)return;let s=Shopware.Application.getApplicationRoot(),r=s.$tc("global.default.cancel"),o=s.$tc("global.notification-center.shopware-updates-listener.updateNow"),n={title:s.$t("sw-settings-security.notification.title",e),message:s.$t("sw-settings-security.notification.message",e),variant:"info",growl:!0,system:!0,actions:[{label:o,route:{name:"sw.plugin.index.updates"}},{label:r}],autoClose:!1};s.$store.dispatch("notification/createNotification",n)};setTimeout(re,1e3);var a=Shopware.Service("swagSecurityCookieStorage"),oe=Shopware.Service("swagSecurityState");oe.isActive("NEXT-9241")&&(Shopware.Service("loginService").addOnLoginListener(()=>{a.getItem("bearerAuth")&&a.setItem("bearerAuth",a.getItem("bearerAuth"))}),Shopware.Service("loginService").addOnTokenChangedListener(()=>{a.getItem("bearerAuth")&&a.setItem("bearerAuth",a.getItem("bearerAuth"))}));var j=`{% block sw_settings_security_index %}
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
`;var{Component:ae,Mixin:ce}=Shopware;ae.register("sw-settings-security-view",{template:j,inject:["swagSecurityApi","systemConfigApiService"],data(){return{isLoading:!0,isSaveSuccessful:!1,confirmPasswordModal:!1,confirmPassword:"",config:{},fixes:[]}},mixins:[ce.getByName("notification")],methods:{onCloseConfirmPasswordModal(){this.confirmPasswordModal=!1,this.isLoading=!1,this.confirmPassword=""},onSave(){this.confirmPasswordModal=!0},onVerifiedSave(){this.isLoading=!0,this.swagSecurityApi.saveValues(this.config,this.confirmPassword).then(()=>{this.isLoading=!0,this.confirmPasswordModal=!1,this.confirmPassword="",this.swagSecurityApi.cacheClear().then(()=>{this.isLoading=!1,this.isSaveSuccessful=!0,window.location.reload()})}).catch(()=>{this.createNotificationError({title:this.$tc("sw-profile.index.notificationPasswordErrorTitle"),message:this.$tc("sw-profile.index.notificationOldPasswordErrorMessage")})})},saveFinish(){this.isSaveSuccessful=!1}},async mounted(){this.fixes=await this.swagSecurityApi.getFixes();for(let i of this.fixes.availableFixes)this.config[i]=this.fixes.activeFixes.includes(i);this.isLoading=!1}});var L=`{% block sw_settings_content_card_slot_plugins %}
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
`;Shopware.Component.override("sw-settings-index",{template:L,computed:{canViewSecuritySettings(){let i=Shopware.Service("acl");return i?i.can("admin"):!0}}});var{Module:ue}=Shopware,M={type:"plugin",name:"settings-security",title:"sw-settings-security.general.mainMenuItemGeneral",description:"sw-settings-security.general.description",version:"1.0.0",targetVersion:"1.0.0",color:"#9AA8B5",icon:"regular-cog",favicon:"icon-module-settings.png",routes:{index:{component:"sw-settings-security-view",path:"index",meta:{parentPath:"sw.settings.index",privilege:"admin"}}},settingsItem:[{group:"plugins",to:"sw.settings.security.index",icon:"regular-shield",name:"sw-settings-security.general.mainMenuItemGeneral"}]};Shopware.Component.getComponentRegistry().has("sw-extension-config")||delete M.settingsItem;ue.register("sw-settings-security",M);})();
