const f="modulepreload",h=function(e){return"/themes/contrib/dvb/dist/"+e},c={},i=function(a,l,u){if(!l||l.length===0)return a();const _=document.getElementsByTagName("link");return Promise.all(l.map(t=>{if(t=h(t),t in c)return;c[t]=!0;const n=t.endsWith(".css"),d=n?'[rel="stylesheet"]':"";if(!!u)for(let o=_.length-1;o>=0;o--){const s=_[o];if(s.href===t&&(!n||s.rel==="stylesheet"))return}else if(document.querySelector(`link[href="${t}"]${d}`))return;const r=document.createElement("link");if(r.rel=n?"stylesheet":f,n||(r.as="script",r.crossOrigin=""),r.href=t,document.head.appendChild(r),n)return new Promise((o,s)=>{r.addEventListener("load",o),r.addEventListener("error",()=>s(new Error(`Unable to preload CSS for ${t}`)))})})).then(()=>a()).catch(t=>{const n=new Event("vite:preloadError",{cancelable:!0});if(n.payload=t,window.dispatchEvent(n),!n.defaultPrevented)throw t})};i(()=>import("./fontawesome-d10823f9.js"),[]).then(({default:e})=>{new e});i(()=>import("./main-menu-7ad47cd9.js"),[]).then(({default:e})=>{new e});i(()=>import("./bootstrap-f8bb8920.js"),[]).then(({default:e})=>{new e});i(()=>import("./mobile-menu-34927e43.js"),["assets/mobile-menu-34927e43.js","assets/mobile-menu.38855dee.css"]).then(({default:e})=>{new e});i(()=>import("./back-top-c7144ae7.js"),["assets/back-top-c7144ae7.js","assets/back-top.36cd8800.css"]).then(({default:e})=>{new e});i(()=>import("./jquery-ui-26181ce4.js"),[]).then(({default:e})=>{new e});