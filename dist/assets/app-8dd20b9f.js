const f="modulepreload",h=function(e){return"/themes/contrib/dvb/dist/"+e},u={},o=function(a,l,_){if(!l||l.length===0)return a();const c=document.getElementsByTagName("link");return Promise.all(l.map(t=>{if(t=h(t),t in u)return;u[t]=!0;const n=t.endsWith(".css"),d=n?'[rel="stylesheet"]':"";if(!!_)for(let i=c.length-1;i>=0;i--){const s=c[i];if(s.href===t&&(!n||s.rel==="stylesheet"))return}else if(document.querySelector(`link[href="${t}"]${d}`))return;const r=document.createElement("link");if(r.rel=n?"stylesheet":f,n||(r.as="script",r.crossOrigin=""),r.href=t,document.head.appendChild(r),n)return new Promise((i,s)=>{r.addEventListener("load",i),r.addEventListener("error",()=>s(new Error(`Unable to preload CSS for ${t}`)))})})).then(()=>a()).catch(t=>{const n=new Event("vite:preloadError",{cancelable:!0});if(n.payload=t,window.dispatchEvent(n),!n.defaultPrevented)throw t})};o(()=>import("./fontawesome-cf27161e.js"),[]).then(({default:e})=>{new e});o(()=>import("./main-menu-7ad47cd9.js"),[]).then(({default:e})=>{new e});o(()=>import("./bootstrap-7689dcd7.js"),[]).then(({default:e})=>{new e});o(()=>import("./mobile-menu-34927e43.js"),["assets/mobile-menu-34927e43.js","assets/mobile-menu.38855dee.css"]).then(({default:e})=>{new e});o(()=>import("./back-top-3c91bb73.js"),["assets/back-top-3c91bb73.js","assets/back-top.36cd8800.css"]).then(({default:e})=>{new e});
