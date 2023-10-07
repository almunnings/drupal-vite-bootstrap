const t=window.jQuery;class u{constructor(){const i=t;i.widget("ui.dialog",i.ui.dialog,{open:function(){const s=`
        <span class="ui-button-icon ui-icon ui-icon-closethick"></span>
        <span class="ui-button-icon-space"></span>
        Close
        `,n=["ui-button","ui-corner-all","ui-widget","ui-button-icon-only","ui-dialog-titlebar-close"];return[...this.uiDialogTitlebarClose].forEach(o=>{o.innerHTML=s,o.classList.add(...n)}),this._super()}})}}export{u as default};
