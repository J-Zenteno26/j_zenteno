/**
 * ZentDigital · UX/UI system 2026
 * Motion is shared across breakpoints; mobile adapts layout, not logic.
 */
(function () {
  "use strict";

  const body = document.body;
  const header = document.querySelector("#header");
  const mobileNavToggleBtn = document.querySelector(".mobile-nav-toggle");
  const scrollTop = document.querySelector(".scroll-top");
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
  const mobile = window.matchMedia("(max-width: 767px)");

  function toggleScrolled() {
    if (!header) return;
    if (
      !header.classList.contains("scroll-up-sticky") &&
      !header.classList.contains("sticky-top") &&
      !header.classList.contains("fixed-top")
    ) return;
    body.classList.toggle("scrolled", window.scrollY > 100);
  }

  document.addEventListener("scroll", toggleScrolled, { passive: true });
  window.addEventListener("load", toggleScrolled);

  function mobileNavToggle(forceClose = false) {
    if (!mobileNavToggleBtn) return;
    const open = forceClose ? false : !body.classList.contains("mobile-nav-active");
    body.classList.toggle("mobile-nav-active", open);
    mobileNavToggleBtn.classList.toggle("bi-list", !open);
    mobileNavToggleBtn.classList.toggle("bi-x", open);
    mobileNavToggleBtn.setAttribute("aria-expanded", open ? "true" : "false");
    mobileNavToggleBtn.setAttribute("aria-label", open ? "Cerrar navegación" : "Abrir navegación");
  }

  if (mobileNavToggleBtn) {
    mobileNavToggleBtn.setAttribute("aria-expanded", "false");
    mobileNavToggleBtn.addEventListener("click", () => mobileNavToggle());
  }

  document.querySelectorAll("#navmenu a").forEach((link) => {
    link.addEventListener("click", () => {
      if (body.classList.contains("mobile-nav-active")) mobileNavToggle(true);
    });
  });

  function toggleScrollTop() {
    if (!scrollTop) return;
    scrollTop.classList.toggle("active", window.scrollY > 100);
  }

  if (scrollTop) {
    scrollTop.addEventListener("click", (event) => {
      event.preventDefault();
      window.scrollTo({ top: 0, behavior: reducedMotion.matches ? "auto" : "smooth" });
    });
  }
  window.addEventListener("load", toggleScrollTop);
  document.addEventListener("scroll", toggleScrollTop, { passive: true });

  function aosInit() {
    if (typeof AOS === "undefined") return;
    AOS.init({
      duration: 620,
      easing: "ease-out-cubic",
      once: true,
      mirror: false,
      offset: 34,
      disable: reducedMotion.matches
    });
  }
  window.addEventListener("load", aosInit);

  /** FAQ */
  document.querySelectorAll(".faq-item h3, .faq-item .faq-toggle").forEach((trigger) => {
    trigger.addEventListener("click", () => {
      trigger.closest(".faq-item")?.classList.toggle("faq-active");
    });
  });

  /** Spotlight shared by interactive surfaces */
  document.querySelectorAll(".zd-interactive").forEach((surface) => {
    surface.addEventListener("pointermove", (event) => {
      const rect = surface.getBoundingClientRect();
      surface.style.setProperty("--spot-x", `${event.clientX - rect.left}px`);
      surface.style.setProperty("--spot-y", `${event.clientY - rect.top}px`);
    });

    const press = () => surface.classList.add("is-pressed");
    const release = () => surface.classList.remove("is-pressed");

    surface.addEventListener("pointerdown", press);
    surface.addEventListener("pointerup", release);
    surface.addEventListener("pointercancel", release);
    surface.addEventListener("pointerleave", release);
  });

  /** Position an actual moving node over another real node. */
  const movingNodeLayouts = new Set();

  function placeMovingNode(container, target, traveler) {
    if (!container || !target || !traveler) return;
    const containerRect = container.getBoundingClientRect();
    const targetRect = target.getBoundingClientRect();
    traveler.style.left = `${targetRect.left - containerRect.left + targetRect.width / 2}px`;
    traveler.style.top = `${targetRect.top - containerRect.top + targetRect.height / 2}px`;
  }

  function registerMovingNodeLayout(callback) {
    movingNodeLayouts.add(callback);
  }

  window.addEventListener("resize", () => {
    window.clearTimeout(window._zdNodeResizeTimer);
    window._zdNodeResizeTimer = window.setTimeout(() => {
      movingNodeLayouts.forEach((callback) => callback());
    }, 80);
  }, { passive: true });

 
  /**
   * Adaptive carousels
   * Desktop remains grid. Mobile becomes native scroll-snap using same cards.
   */
  document.querySelectorAll("[data-zd-carousel]").forEach((carousel) => {
    const cards = Array.from(carousel.children);
    if (cards.length < 2) return;

    let timer = null;
    let inView = false;
    let pausedUntil = 0;

    function nearestIndex() {
      const center = carousel.scrollLeft + carousel.clientWidth / 2;
      let best = 0;
      let dist = Infinity;
      cards.forEach((card, index) => {
        const c = card.offsetLeft + card.offsetWidth / 2;
        const d = Math.abs(c - center);
        if (d < dist) { dist = d; best = index; }
      });
      return best;
    }

    function setActive(index) {
      cards.forEach((card, i) => card.classList.toggle("is-carousel-active", i === index));
    }

    function scrollToIndex(index) {
      const card = cards[index];
      if (!card) return;
      card.scrollIntoView({
        behavior: reducedMotion.matches ? "auto" : "smooth",
        block: "nearest",
        inline: "center"
      });
      setActive(index);
    }

    function start() {
      window.clearInterval(timer);
      if (reducedMotion.matches) return;
      timer = window.setInterval(() => {
        if (!mobile.matches || !inView || document.hidden || Date.now() < pausedUntil) return;
        const next = (nearestIndex() + 1) % cards.length;
        scrollToIndex(next);
      }, 5200);
    }

    carousel.addEventListener("pointerdown", () => { pausedUntil = Date.now() + 6000; });
    carousel.addEventListener("touchstart", () => { pausedUntil = Date.now() + 6000; }, { passive: true });
    carousel.addEventListener("scroll", () => {
      if (!mobile.matches) return;
      window.clearTimeout(carousel._zdScrollTimer);
      carousel._zdScrollTimer = window.setTimeout(() => setActive(nearestIndex()), 90);
    }, { passive: true });

    if ("IntersectionObserver" in window) {
      const observer = new IntersectionObserver(([entry]) => {
        inView = entry.isIntersecting;
      }, { threshold: 0.28 });
      observer.observe(carousel);
    } else {
      inView = true;
    }

    mobile.addEventListener("change", () => {
      setActive(0);
      if (mobile.matches) carousel.scrollTo({ left: 0, behavior: "auto" });
      start();
    });

    setActive(0);
    start();
  });
/** Enfoque mobile: acordeón vertical */
document.querySelectorAll(".values-zd").forEach((section)=>{
  const mq=window.matchMedia("(max-width: 767px)");
  const items=Array.from(section.querySelectorAll(".values-list li"));
  if(!items.length) return;
  function closeAll(){
    items.forEach((item)=>{
      item.classList.remove("is-accordion-open");
      item.setAttribute("aria-expanded","false");
    });
  }
  function openItem(item){
    closeAll();
    item.classList.add("is-accordion-open");
    item.setAttribute("aria-expanded","true");
  }
  function setup(){
    items.forEach((item,index)=>{
      item.setAttribute("role","button");
      item.setAttribute("aria-expanded","false");
      if(!item.hasAttribute("tabindex")) item.tabIndex=0;
      if(mq.matches&&index===0) openItem(item);
      if(!mq.matches) item.classList.remove("is-accordion-open");
    });
  }
  items.forEach((item)=>{
    item.addEventListener("click",()=>{
      if(!mq.matches) return;
      if(item.classList.contains("is-accordion-open")){
        item.classList.remove("is-accordion-open");
        item.setAttribute("aria-expanded","false");
        return;
      }
      openItem(item);
    });
    item.addEventListener("keydown",(event)=>{
      if(!mq.matches) return;
      if(event.key!=="Enter"&&event.key!==" ") return;
      event.preventDefault();
      item.click();
    });
  });
  mq.addEventListener("change",setup);
  setup();
});
/**
 * Enfoque / pilares: revelado acumulativo.
 * Cada pilar entra -> palabra se escribe -> descripción aparece -> queda visible.
 */
document.querySelectorAll(".values-zd").forEach((section) => {
  const items = Array.from(section.querySelectorAll(".values-list li"));
  if (!items.length) return;

  const titles = items.map((item) =>
    item.querySelector("h3")?.textContent.trim() || ""
  );

  let hasPlayed = false;
  let sequenceId = 0;

  items.forEach((item) => {
    if (!item.hasAttribute("tabindex")) {
      item.tabIndex = 0;
    }

    item.classList.add("is-pending");
  });

  function wait(ms) {
    return new Promise((resolve) => {
      window.setTimeout(resolve, ms);
    });
  }

  async function typeTitle(item, title, id) {
    const heading = item.querySelector("h3");
    if (!heading) return;

    heading.textContent = "";
    item.classList.add("is-typing");

    for (let i = 1; i <= title.length; i += 1) {
      if (id !== sequenceId) return;

      heading.textContent = title.slice(0, i);
      await wait(68);
    }

    item.classList.remove("is-typing");
  }

  async function revealSequence() {
    if (hasPlayed) return;

    hasPlayed = true;
    sequenceId += 1;

    const id = sequenceId;

    if (reducedMotion.matches) {
      items.forEach((item, index) => {
        item.classList.remove("is-pending");
        item.classList.add("is-revealed");

        const heading = item.querySelector("h3");
        if (heading) {
          heading.textContent = titles[index];
        }
      });

      return;
    }

    for (let index = 0; index < items.length; index += 1) {
      if (id !== sequenceId) return;

      const item = items[index];

      item.classList.remove("is-pending");
      item.classList.add("is-entering");

      await wait(170);

      item.classList.add("is-revealed");

      await typeTitle(
        item,
        titles[index],
        id
      );

      if (id !== sequenceId) return;

      item.classList.add("is-description-visible");

      await wait(720);

      item.classList.remove("is-entering");
    }
  }

  items.forEach((item) => {
    item.addEventListener("pointerenter", () => {
      if (!item.classList.contains("is-revealed")) return;
      item.classList.add("is-hovered");
    });

    item.addEventListener("pointerleave", () => {
      item.classList.remove("is-hovered");
    });

    item.addEventListener("focusin", () => {
      if (!item.classList.contains("is-revealed")) return;
      item.classList.add("is-hovered");
    });

    item.addEventListener("focusout", () => {
      item.classList.remove("is-hovered");
    });
  });

  if ("IntersectionObserver" in window) {
    const observer = new IntersectionObserver(
      ([entry]) => {
        if (!entry.isIntersecting || hasPlayed) return;

        revealSequence();
        observer.disconnect();
      },
      {
        threshold: 0.28
      }
    );

    observer.observe(section);
  } else {
    revealSequence();
  }
});

  /** Capabilities: visible network nodes + synchronized list. */
  document.querySelectorAll(".features-zd").forEach((section) => {
    const items = Array.from(section.querySelectorAll(".capability-list article"));
    const network = section.querySelector("[data-capability-network] .capability-network");
    const nodes = Array.from(section.querySelectorAll("[data-capability-target]"));
    const traveler = network?.querySelector(".capability-traveler");
    if (!items.length) return;

    let current = 0;
    let inView = false;
    let pausedUntil = 0;
    let arrivalStartTimer = null;
    let arrivalEndTimer = null;

    function syncTraveler() {
      if (network && nodes[current] && traveler) {
        placeMovingNode(network, nodes[current], traveler);
      }
    }

  function activate(index) {
    current = (index + items.length) % items.length;

    items.forEach((item, i) => {
      item.classList.toggle("is-capability-active", i === current);
    });

    nodes.forEach((node, i) => {
      node.classList.toggle("is-active", i === current);
      node.classList.remove("is-arriving");
    });

    syncTraveler();

    if (!reducedMotion.matches && nodes[current]) {
      window.clearTimeout(arrivalStartTimer);
      window.clearTimeout(arrivalEndTimer);

      arrivalStartTimer = window.setTimeout(() => {
        nodes[current].classList.add("is-arriving");

        arrivalEndTimer = window.setTimeout(() => {
          nodes[current]?.classList.remove("is-arriving");
        }, 900);
      }, 760);
    }
  }

    items.forEach((item, index) => {
      item.addEventListener("pointerdown", () => {
        pausedUntil = Date.now() + 5200;
        activate(index);
      });
      item.addEventListener("mouseenter", () => {
        if (!mobile.matches) activate(index);
      });
      item.addEventListener("focusin", () => activate(index));
    });

    nodes.forEach((node, index) => {
      node.addEventListener("click", () => {
        pausedUntil = Date.now() + 6000;
        activate(index);
        if (mobile.matches) {
          items[index].scrollIntoView({
            behavior: reducedMotion.matches ? "auto" : "smooth",
            block: "center"
          });
        }
      });
    });

    if ("IntersectionObserver" in window) {
      const observer = new IntersectionObserver(([entry]) => {
        inView = entry.isIntersecting;
        if (inView) syncTraveler();
      }, { threshold: .30 });
      observer.observe(section);
    } else inView = true;

    registerMovingNodeLayout(syncTraveler);
    activate(0);

    if (!reducedMotion.matches) {
      window.setInterval(() => {
        if (inView && !document.hidden && Date.now() >= pausedUntil) activate(current + 1);
      }, 5200);
    }
  });
  document.querySelectorAll(".features-statement").forEach((statement)=>{
  const reset=()=>statement.style.setProperty("--statement-x","50%");
  statement.addEventListener("pointermove",(event)=>{
    const rect=statement.getBoundingClientRect();
    const x=Math.max(0,Math.min(100,((event.clientX-rect.left)/rect.width)*100));
    statement.style.setProperty("--statement-x",`${x}%`);
  });
  statement.addEventListener("pointerleave",reset);
  statement.addEventListener("pointerdown",(event)=>{
    if(event.pointerType==="mouse") return;
    const rect=statement.getBoundingClientRect();
    const x=Math.max(0,Math.min(100,((event.clientX-rect.left)/rect.width)*100));
    statement.style.setProperty("--statement-x",`${x}%`);
    statement.classList.add("is-statement-active");
    window.clearTimeout(statement._zdStatementTimer);
    statement._zdStatementTimer=window.setTimeout(()=>statement.classList.remove("is-statement-active"),650);
  });
});
  /** Focus: real navigation nodes + moving traveler. */
  document.querySelectorAll("[data-focus-map]").forEach((map) => {
    const cards = Array.from(map.querySelectorAll(".focus-node"));
    const route = map.querySelector(".focus-route");
    const routeNodes = Array.from(map.querySelectorAll("[data-focus-target]"));
    const traveler = map.querySelector(".focus-route-traveler");
    if (!cards.length) return;

    let current = 0;
    let inView = false;
    let pausedUntil = 0;

    function syncTraveler() {
      if (route && routeNodes[current] && traveler) {
        placeMovingNode(route, routeNodes[current], traveler);
      }
    }

    function activate(index) {
      current = (index + cards.length) % cards.length;
      cards.forEach((card, i) => card.classList.toggle("is-active", i === current));
      routeNodes.forEach((node, i) => node.classList.toggle("is-active", i === current));

      const progress = cards.length <= 1 ? 100 : (current / (cards.length - 1)) * 100;
      map.style.setProperty("--focus-progress", `${progress}%`);
      syncTraveler();
    }

    routeNodes.forEach((node, index) => {
      node.addEventListener("click", () => {
        pausedUntil = Date.now() + 5200;
        activate(index);
      });
    });

    cards.forEach((card, index) => {
      card.addEventListener("pointerdown", () => {
        pausedUntil = Date.now() + 4500;
        activate(index);
      });
      card.addEventListener("mouseenter", () => {
        if (!mobile.matches) activate(index);
      });
      card.addEventListener("focusin", () => activate(index));
    });

    if ("IntersectionObserver" in window) {
      const observer = new IntersectionObserver(([entry]) => {
        inView = entry.isIntersecting;
        if (inView) syncTraveler();
      }, { threshold: .30 });
      observer.observe(map);
    } else inView = true;

    registerMovingNodeLayout(syncTraveler);
    activate(0);

    if (!reducedMotion.matches) {
      window.setInterval(() => {
        if (inView && !document.hidden && Date.now() >= pausedUntil) activate(current + 1);
      }, 2900);
    }
  });

  /** Process: progressive stages. */
document.querySelectorAll("[data-process-flow]").forEach((flow)=>{
  const steps=Array.from(flow.querySelectorAll(".process-step"));
  const nodes=Array.from(flow.querySelectorAll("[data-process-target]"));
  if(!steps.length) return;
  let current=0;
  let inView=false;
  let pausedUntil=0;
  function activate(index){
    current=(index+steps.length)%steps.length;
    steps.forEach((step,i)=>{
      step.classList.toggle("is-process-active",i===current);
      step.classList.toggle("is-process-complete",i<current);
    });
    nodes.forEach((node,i)=>node.classList.toggle("is-active",i===current));
  }
  nodes.forEach((node,index)=>{
    node.addEventListener("click",()=>{
      pausedUntil=Date.now()+6000;
      activate(index);
      if(mobile.matches){
        steps[index].scrollIntoView({behavior:reducedMotion.matches?"auto":"smooth",block:"center"});
      }
    });
  });
  steps.forEach((step,index)=>{
    step.addEventListener("pointerdown",()=>{
      pausedUntil=Date.now()+5000;
      activate(index);
    });
    step.addEventListener("mouseenter",()=>{
      if(!mobile.matches) activate(index);
    });
    step.addEventListener("focusin",()=>activate(index));
  });
  if("IntersectionObserver" in window){
    const observer=new IntersectionObserver(([entry])=>{inView=entry.isIntersecting;},{threshold:.25});
    observer.observe(flow);
  }else inView=true;
  activate(0);
  if(!reducedMotion.matches){
    window.setInterval(()=>{
      if(!inView||document.hidden||Date.now()<pausedUntil) return;
      activate(current===steps.length-1?0:current+1);
    },3400);
  }
});

  /** Project deck: one medal at a time on every viewport */
  document.querySelectorAll("[data-project-deck]").forEach((deck) => {
    const tabs = Array.from(deck.querySelectorAll("[data-project-target]"));
    const panels = Array.from(deck.querySelectorAll("[data-project-panel]"));
    if (!tabs.length || !panels.length) return;

    let active = 0;
    let inView = false;
    let pausedUntil = 0;
    let timer;

    function select(index, focus = false) {
      active = (index + tabs.length) % tabs.length;
      tabs.forEach((tab, i) => {
        const on = i === active;
        tab.classList.toggle("is-active", on);
        tab.setAttribute("aria-selected", on ? "true" : "false");
      });
      panels.forEach((panel, i) => {
        const on = i === active;
        panel.classList.toggle("is-active", on);
        panel.setAttribute("aria-hidden", on ? "false" : "true");
      });
      if (mobile.matches) {
        tabs[active].scrollIntoView({ behavior: reducedMotion.matches ? "auto" : "smooth", block: "nearest", inline: "center" });
      }
      if (focus) tabs[active].focus({ preventScroll: true });
    }

    tabs.forEach((tab, index) => {
      tab.addEventListener("click", () => {
        pausedUntil = Date.now() + 8000;
        select(index);
      });
      tab.addEventListener("keydown", (event) => {
        if (event.key !== "ArrowRight" && event.key !== "ArrowLeft") return;
        event.preventDefault();
        pausedUntil = Date.now() + 8000;
        select(active + (event.key === "ArrowRight" ? 1 : -1), true);
      });
    });

    if ("IntersectionObserver" in window) {
      const observer = new IntersectionObserver(([entry]) => { inView = entry.isIntersecting; }, { threshold: .3 });
      observer.observe(deck);
    } else inView = true;

    select(0);
    if (!reducedMotion.matches) {
      timer = window.setInterval(() => {
        if (inView && !document.hidden && Date.now() >= pausedUntil) select(active + 1);
      }, 5600);
    }
  });

  /** Initial hash correction */
  window.addEventListener("load", () => {
    if (!window.location.hash) return;
    const section = document.querySelector(window.location.hash);
    if (!section) return;
    window.setTimeout(() => {
      const margin = parseInt(getComputedStyle(section).scrollMarginTop) || 0;
      window.scrollTo({ top: section.offsetTop - margin, behavior: reducedMotion.matches ? "auto" : "smooth" });
    }, 100);
  });

  /** Scrollspy */
  const navLinks = document.querySelectorAll(".navmenu a");
  function navmenuScrollspy() {
    const position = window.scrollY + 200;
    navLinks.forEach((link) => {
      if (!link.hash) return;
      const section = document.querySelector(link.hash);
      if (!section) return;
      const on = position >= section.offsetTop && position <= section.offsetTop + section.offsetHeight;
      link.classList.toggle("active", on);
    });
  }
  window.addEventListener("load", navmenuScrollspy);
  document.addEventListener("scroll", navmenuScrollspy, { passive: true });
})();
