class r{constructor(){this.footer=document.querySelector(".site-footer"),this.footer&&(this.inject(),this.toggle(),window.addEventListener("scroll",this.toggle,{passive:!0}))}inject(){this.footer.insertAdjacentHTML("beforebegin",`
    <div class="back-to-top">
      <a href="#top" class="btn btn-primary rounded-circle" title="Top of page">
        <i class="fas fa-chevron-up" aria-hidden="true"></i>
      </a>
    </div>
    `),Drupal.behaviors.fa&&Drupal.behaviors.fa.attach(this.footer.parentNode)}toggle(){const t=window.scrollY,o=document.querySelector(".back-to-top"),e=document.querySelector(".site-footer").offsetTop-window.innerHeight+32;o.classList.toggle("show",t>=200||t>=e&&e>0)}}export{r as default};
