const u=window.jQuery;class a{constructor(){var o;const i=u;(o=i.ui)!=null&&o.dialog&&i.widget("ui.dialog",i.ui.dialog,{open:function(){const n=`
        <span class="ui-button-icon ui-icon ui-icon-closethick"></span>
        <span class="ui-button-icon-space"></span>
        Close
        `,t=["ui-button","ui-corner-all","ui-widget","ui-button-icon-only","ui-dialog-titlebar-close"];return[...this.uiDialogTitlebarClose].forEach(s=>{s.innerHTML=n,s.classList.add(...t)}),this._super()}})}}export{a as default};
