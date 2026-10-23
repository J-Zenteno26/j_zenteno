(function () {
  "use strict";

  const body = document.body;
  const header = document.querySelector("#header");
  const mobileNavToggleBtn = document.querySelector(".mobile-nav-toggle");
  const scrollTop = document.querySelector(".scroll-top");
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");

  function toggleScrolled() {
    if (!header) return;
    body.classList.toggle("scrolled", window.scrollY > 28);
    scrollTop?.classList.toggle("active", window.scrollY > 500);
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
  }

  mobileNavToggleBtn?.addEventListener("click", () => mobileNavToggle());
  document.querySelectorAll("#navmenu a").forEach((link) => link.addEventListener("click", () => mobileNavToggle(true)));

  scrollTop?.addEventListener("click", (event) => {
    event.preventDefault();
    window.scrollTo({ top: 0, behavior: reducedMotion.matches ? "auto" : "smooth" });
  });

  document.querySelectorAll(".faq-item h3, .faq-item .faq-toggle").forEach((trigger) => {
    trigger.addEventListener("click", () => {
      const item = trigger.closest(".faq-item");
      if (!item) return;
      item.classList.toggle("faq-active");
      const icon = item.querySelector(".faq-toggle");
      icon?.classList.toggle("bi-plus-lg", !item.classList.contains("faq-active"));
      icon?.classList.toggle("bi-dash-lg", item.classList.contains("faq-active"));
    });
  });

  /* =========================================================
     INVESTMENT / TIME
     Selector directo: sin scroll horizontal en móvil.
  ========================================================= */

  const investment = document.querySelector(".zd-investment");

  if (investment) {
    const investmentData = {
      presence: {
        price: "$450.000",
        time: "3–4",
        timeA11y: "3 a 4 semanas",
        weeks: 4,
        title: "Sitio corporativo + integración de pago",
        copy: "Una presencia digital implementada de forma responsive y preparada para recibir pagos. Si no necesitas pagos, una landing simple puede partir desde $280.000.",
        includes: ["Implementación responsive", "Webpay", "Formulario", "SEO base", "Publicación"],
        priorityPrice: "desde $600.000",
        priorityTime: "2 semanas"
      },
      commerce: {
        price: "$650.000",
        time: "6–8",
        timeA11y: "6 a 8 semanas",
        weeks: 8,
        title: "Ecommerce personalizado",
        copy: "Catálogo, carrito, checkout, pagos, pedidos y una administración base. Si el proyecto incorpora inventario avanzado, logística o reglas operacionales, se cotiza según alcance.",
        includes: ["Catálogo", "Carrito", "Webpay", "Pedidos", "Administración"],
        priorityPrice: "desde $900.000",
        priorityTime: "4 semanas"
      },
      software: {
        price: "$1.5M",
        time: "10–14",
        timeA11y: "10 a 14 semanas",
        weeks: 14,
        title: "Sistema web a medida / SaaS",
        copy: "Para operaciones que necesitan lógica propia: roles, estados, reglas de negocio, datos, dashboards, integraciones y módulos diseñados alrededor del proceso real.",
        includes: ["Arquitectura", "Modelado de datos", "Roles", "Integraciones", "Puesta en producción"],
        priorityPrice: "desde $2.000.000",
        priorityTime: "6–8 semanas*"
      }
    };

    const shell = investment.querySelector(".zd-investment-shell");
    const buttons = [...investment.querySelectorAll("[data-investment-tab]")];
    const detailToggle = investment.querySelector(".zd-investment-detail-toggle");

    const price = investment.querySelector("[data-investment-price]");
    const timeValue = investment.querySelector("[data-investment-time-value]");
    const timeA11y = investment.querySelector("[data-investment-time]");
    const bars = investment.querySelector("[data-investment-weeks]");
    const title = investment.querySelector("[data-investment-title]");
    const copy = investment.querySelector("[data-investment-copy]");
    const includes = investment.querySelector("[data-investment-includes]");
    const priorityPrice = investment.querySelector("[data-investment-priority-price]");
    const priorityTime = investment.querySelector("[data-investment-priority-time]");

    const renderInvestment = (key) => {
      const data = investmentData[key] || investmentData.presence;

      if (price) price.textContent = data.price;
      if (timeValue) timeValue.textContent = data.time;
      if (timeA11y) timeA11y.textContent = data.timeA11y;

      if (bars) {
        bars.innerHTML = Array.from(
          { length: data.weeks },
          (_, index) => `<i style="--week-index:${index}"></i>`
        ).join("");
      }

      if (title) title.textContent = data.title;
      if (copy) copy.textContent = data.copy;

      if (includes) {
        includes.innerHTML = data.includes
          .map((item) => `<span>${item}</span>`)
          .join("");
      }

      if (priorityPrice) priorityPrice.textContent = data.priorityPrice;
      if (priorityTime) priorityTime.textContent = data.priorityTime;

      buttons.forEach((button) => {
        const active = button.dataset.investmentTab === key;
        button.classList.toggle("is-active", active);
        button.setAttribute("aria-selected", active ? "true" : "false");
      });

      if (shell?.classList.contains("is-detail-open")) {
        shell.classList.remove("is-detail-open");
        detailToggle?.setAttribute("aria-expanded", "false");
        const label = detailToggle?.querySelector("span");
        if (label) label.textContent = "Ver más";
      }
    };

    buttons.forEach((button) => {
      button.setAttribute("role", "tab");
      button.addEventListener("click", () => {
        renderInvestment(button.dataset.investmentTab);
      });
    });

    if (detailToggle && shell) {
      detailToggle.addEventListener("click", () => {
        const open = shell.classList.toggle("is-detail-open");
        detailToggle.setAttribute("aria-expanded", String(open));

        const label = detailToggle.querySelector("span");
        if (label) {
          label.textContent = open ? "Ver menos" : "Ver más";
        }
      });
    }

    renderInvestment("presence");
  }

  const form = document.getElementById("contactForm");
  if (form) {
    const loading = form.querySelector(".loading");
    const errorMessage = form.querySelector(".error-message");
    const sentMessage = form.querySelector(".sent-message");
    const submitButton = form.querySelector("button[type='submit']");

    form.addEventListener("submit", async (event) => {
      event.preventDefault();
      loading.style.display = "block";
      errorMessage.style.display = "none";
      sentMessage.style.display = "none";
      submitButton.disabled = true;
      try {
        const response = await fetch(form.action, { method: "POST", body: new FormData(form) });
        const result = await response.text();
        loading.style.display = "none";
        submitButton.disabled = false;
        if (result.trim() === "OK") {
          sentMessage.style.display = "block";
          form.reset();
          if (typeof grecaptcha !== "undefined") grecaptcha.reset();
        } else {
          errorMessage.textContent = result;
          errorMessage.style.display = "block";
        }
      } catch (error) {
        loading.style.display = "none";
        submitButton.disabled = false;
        errorMessage.textContent = "No se pudo enviar el mensaje. Intenta nuevamente.";
        errorMessage.style.display = "block";
      }
    });
  }

/* =========================================================
   PROJECT CASES CAROUSEL
   Desktop: botones.
   Mobile: swipe nativo + drag con mouse + botones + estado sincronizado.
========================================================= */

document.querySelectorAll("[data-case-carousel]").forEach((carousel) => {
  const rail = carousel.querySelector("[data-case-rail]");
  const cards = [...carousel.querySelectorAll(".zd-case")];
  const prev = carousel.querySelector("[data-case-prev]");
  const next = carousel.querySelector("[data-case-next]");
  const current = carousel.querySelector("[data-case-current]");
  const dots = [...carousel.querySelectorAll(".zd-case-dots i")];
  const mobileCases = window.matchMedia("(max-width: 767.98px)");

  if (!rail || !cards.length) return;

  let activeIndex = 0;
  let scrollTimer = null;
  let dragging = false;
  let dragStartX = 0;
  let dragStartScroll = 0;
  let dragged = false;

  const updateCaseState = (index) => {
    activeIndex = (index + cards.length) % cards.length;

    if (current) {
      current.textContent = String(activeIndex + 1).padStart(2, "0");
    }

    dots.forEach((dot, dotIndex) => {
      dot.classList.toggle("is-active", dotIndex === activeIndex);
    });
  };

  const scrollToCase = (index) => {
    const normalizedIndex = (index + cards.length) % cards.length;
    const card = cards[normalizedIndex];

    updateCaseState(normalizedIndex);

    rail.scrollTo({
      left: card.offsetLeft,
      behavior: reducedMotion.matches ? "auto" : "smooth"
    });
  };

  const syncCaseFromScroll = () => {
    const railRect = rail.getBoundingClientRect();
    const railCenter = railRect.left + (railRect.width / 2);

    let closestIndex = activeIndex;
    let closestDistance = Number.POSITIVE_INFINITY;

    cards.forEach((card, index) => {
      const rect = card.getBoundingClientRect();
      const cardCenter = rect.left + (rect.width / 2);
      const distance = Math.abs(cardCenter - railCenter);

      if (distance < closestDistance) {
        closestDistance = distance;
        closestIndex = index;
      }
    });

    updateCaseState(closestIndex);
  };

  prev?.addEventListener("click", () => {
    scrollToCase(activeIndex - 1);
  });

  next?.addEventListener("click", () => {
    scrollToCase(activeIndex + 1);
  });

  rail.addEventListener(
    "scroll",
    () => {
      if (!mobileCases.matches) return;

      window.clearTimeout(scrollTimer);
      scrollTimer = window.setTimeout(syncCaseFromScroll, 100);
    },
    { passive: true }
  );

  rail.addEventListener("pointerdown", (event) => {
    if (!mobileCases.matches || event.pointerType !== "mouse") return;

    dragging = true;
    dragged = false;
    dragStartX = event.clientX;
    dragStartScroll = rail.scrollLeft;
    rail.setPointerCapture?.(event.pointerId);
  });

  rail.addEventListener("pointermove", (event) => {
    if (!dragging) return;

    const delta = event.clientX - dragStartX;

    if (Math.abs(delta) > 4) {
      dragged = true;
    }

    rail.scrollLeft = dragStartScroll - delta;
  });

  const finishDrag = (event) => {
    if (!dragging) return;
    dragging = false;
    rail.releasePointerCapture?.(event.pointerId);
    syncCaseFromScroll();
  };

  rail.addEventListener("pointerup", finishDrag);
  rail.addEventListener("pointercancel", finishDrag);

  rail.addEventListener(
    "click",
    (event) => {
      if (!dragged) return;
      event.preventDefault();
      event.stopPropagation();
      dragged = false;
    },
    true
  );

  updateCaseState(0);
});

/* =========================================================
   VALUES / PRINCIPLES
   Desktop: autoplay pausado.
   Mobile: interacción manual para evitar saltos mientras se lee.
========================================================= */

document.querySelectorAll("[data-values-pillars]").forEach((group) => {
  const pillars = [...group.querySelectorAll(".zd-value-pillar")];
  const mobileValues = window.matchMedia("(max-width: 767.98px)");

  if (!pillars.length) return;

  let activeIndex = Math.max(
    0,
    pillars.findIndex((pillar) => pillar.classList.contains("is-active"))
  );

  let autoplay = null;
  let autoplayDelay = null;
  const AUTOPLAY_INTERVAL = 10000;
  const INITIAL_DELAY = 7000;
  const MANUAL_DELAY = 12000;

  const activatePillar = (index) => {
    activeIndex = (index + pillars.length) % pillars.length;

    pillars.forEach((pillar, pillarIndex) => {
      const trigger = pillar.querySelector(".zd-value-pillar-trigger");
      const isActive = pillarIndex === activeIndex;

      pillar.classList.toggle("is-active", isActive);
      trigger?.setAttribute("aria-expanded", String(isActive));
    });
  };

  const stopAutoplay = () => {
    if (autoplayDelay) {
      window.clearTimeout(autoplayDelay);
      autoplayDelay = null;
    }

    if (autoplay) {
      window.clearInterval(autoplay);
      autoplay = null;
    }
  };

  const startAutoplay = (delay = AUTOPLAY_INTERVAL) => {
    stopAutoplay();

    if (reducedMotion.matches || mobileValues.matches) return;

    autoplayDelay = window.setTimeout(() => {
      autoplayDelay = null;
      activatePillar(activeIndex + 1);

      autoplay = window.setInterval(() => {
        activatePillar(activeIndex + 1);
      }, AUTOPLAY_INTERVAL);
    }, delay);
  };

  pillars.forEach((pillar, index) => {
    const trigger = pillar.querySelector(".zd-value-pillar-trigger");

    trigger?.addEventListener("click", () => {
      activatePillar(index);
      startAutoplay(MANUAL_DELAY);
    });
  });

  group.addEventListener("mouseenter", stopAutoplay);
  group.addEventListener("mouseleave", () => startAutoplay(AUTOPLAY_INTERVAL));

  group.addEventListener("focusin", stopAutoplay);
  group.addEventListener("focusout", () => {
    window.setTimeout(() => {
      if (!group.contains(document.activeElement)) {
        startAutoplay(AUTOPLAY_INTERVAL);
      }
    }, 0);
  });

  mobileValues.addEventListener?.("change", () => {
    if (mobileValues.matches) {
      stopAutoplay();
    } else {
      startAutoplay(INITIAL_DELAY);
    }
  });

  activatePillar(activeIndex);
  startAutoplay(INITIAL_DELAY);
});
})();
