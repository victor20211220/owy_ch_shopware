!function(e){var t={};function n(o){if(t[o])return t[o].exports;var i=t[o]={i:o,l:!1,exports:{}};return e[o].call(i.exports,i,i.exports,n),i.l=!0,i.exports}n.m=e,n.c=t,n.d=function(e,t,o){n.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:o})},n.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.t=function(e,t){if(1&t&&(e=n(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var o=Object.create(null);if(n.r(o),Object.defineProperty(o,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var i in e)n.d(o,i,function(t){return e[t]}.bind(null,i));return o},n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,"a",t),t},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.p=(window.__sw__.assetPath + '/bundles/aggrotexteditormediamanager/'),n(n.s="n4OU")}({n4OU:function(e,t,n){"use strict";n.r(t);Shopware.Component.override("sw-text-editor",{template:'{% block sw_text_editor_box %}\n    {% parent %}\n    <sw-media-modal-v2\n        v-if="mediaModalIsOpen"\n        :allow-multi-select="false"\n        @modal-close="mediaModalIsOpen = false"\n        @media-modal-selection-change="onModalClosed"\n    />\n{% endblock %}\n',data:function(){return{mediaModalIsOpen:!1,lastSelectionRange:null}},created:function(){var e=this;this.buttonConfig.push({title:"Media",icon:"regular-image",position:"left",handler:function(){e.mediaModalIsOpen=!0}})},mounted:function(){document.addEventListener("selectionchange",this.onDocumentSelectionChange)},unmouted:function(){document.removeEventListener("selectionchange",this.onDocumentSelectionChange)},methods:{onModalClosed:function(e){if(e.length){this.restoreSelectionRange();var t=e[0];document.execCommand("insertHTML",!1,'<img src="'+t.url+'" class="img-fluid cms-image" alt="'+t.alt+'" title="'+t.title+'" />')}},onDocumentSelectionChange:function(){this.saveSelectionRange()},getContentValue:function(){return this.$refs.textEditor&&this.$refs.textEditor.innerHTML&&(this.$refs.textEditor.getElementsByTagName("img")||this.$refs.textEditor.textContent&&this.$refs.textEditor.textContent.length&&!(this.$refs.textEditor.textContent.length<=0))?this.$refs.textEditor.innerHTML:null},saveSelectionRange:function(){if(this.isActive){var e=document.getSelection();console.log("saveSelectionRange",this,e),e.rangeCount>0?this.lastSelectionRange=e.getRangeAt(0).cloneRange():this.lastSelectionRange=null}},restoreSelectionRange:function(){if(this.lastSelectionRange){this.$refs.textEditor.focus();var e=document.getSelection();e.removeAllRanges(),e.addRange(this.lastSelectionRange)}}}})}});