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

  if (typeof AOS !== "undefined") {
    AOS.init({ duration: 650, easing: "ease-out-cubic", once: true, offset: 40, disable: reducedMotion.matches });
  }

  document.querySelectorAll(".zd-interactive").forEach((surface) => {
    surface.addEventListener("pointermove", (event) => {
      const rect = surface.getBoundingClientRect();
      surface.style.setProperty("--spot-x", `${event.clientX - rect.left}px`);
      surface.style.setProperty("--spot-y", `${event.clientY - rect.top}px`);
    });
  });

  const domain = document.querySelector("[data-hero-domain]");
  const model = document.querySelector("[data-hero-model]");
  const heroScenarios = [
    {
      domain: "Comercio & operación",
      cards: [
        ["CORE", "Pedido", "estado · total · cliente"],
        ["DATA", "Producto", "variantes · stock · precio"],
        ["RULE", "Inventario", "lotes · movimientos · saldo"],
        ["INTEGRATION", "Pago", "transacción · estado · confirmación"]
      ]
    },
    {
      domain: "Agenda & atención",
      cards: [
        ["CORE", "Reserva", "fecha · hora · modalidad"],
        ["DATA", "Profesional", "agenda · especialidad · estado"],
        ["RULE", "Disponibilidad", "bloque · intervalo · excepción"],
        ["AUTOMATION", "Recordatorio", "destinatario · canal · envío"]
      ]
    },
    {
      domain: "Activos industriales",
      cards: [
        ["CORE", "Activo", "TAG · tipo · criticidad"],
        ["DATA", "Atributo", "valor · unidad · requisito"],
        ["RULE", "Integridad", "estado · vigencia · evidencia"],
        ["STORAGE", "Documento", "versión · acceso · vencimiento"]
      ]
    }
  ];

  if (domain && model && !reducedMotion.matches) {
    let scenarioIndex = 0;
    window.setInterval(() => {
      scenarioIndex = (scenarioIndex + 1) % heroScenarios.length;
      const scenario = heroScenarios[scenarioIndex];
      model.classList.add("is-changing");
      window.setTimeout(() => {
        domain.textContent = scenario.domain;
        model.querySelectorAll(".zd-model-card").forEach((card, index) => {
          const content = scenario.cards[index];
          if (!content) return;
          card.querySelector("small").textContent = content[0];
          card.querySelector("strong").textContent = content[1];
          card.querySelector("span").textContent = content[2];
        });
        model.classList.remove("is-changing");
      }, 240);
    }, 4200);
  }

  const speedData = {
    landing: { value: "2", weeks: 2, unit: "semanas", kicker: "Alcance habitual", title: "Landing o sitio corporativo personalizado", text: "Diseño, desarrollo responsive, SEO técnico base y publicación.", phases: ["Definición", "Diseño", "Desarrollo", "Publicación"] },
    platform: { value: "4", weeks: 4, unit: "semanas", kicker: "Alcance intermedio", title: "Plataforma web o ecommerce conectado", text: "Arquitectura, modelado, interfaces, lógica de negocio e integraciones principales.", phases: ["Arquitectura", "Datos", "Interfaz", "Integración"] },
    system: { value: "6–8", weeks: 8, unit: "semanas", kicker: "Sistema complejo", title: "Sistema a medida con múltiples reglas y módulos", text: "Arquitectura completa, datos, permisos, procesos, integraciones, validación y estabilización.", phases: ["Modelado", "Núcleo", "Módulos", "Estabilización"] }
  };

  document.querySelectorAll("[data-speed-tab]").forEach((button) => {
    button.addEventListener("click", () => {
      const data = speedData[button.dataset.speedTab];
      if (!data) return;
      document.querySelectorAll("[data-speed-tab]").forEach((item) => item.classList.toggle("is-active", item === button));
      document.querySelector("[data-speed-value]").textContent = data.value;
      document.querySelector("[data-speed-unit]").textContent = data.unit;

      const weeks = document.querySelector("[data-speed-weeks]");
      weeks.innerHTML = Array.from(
        { length: data.weeks },
        (_, index) => `<i style="--week-index:${index}"></i>`
      ).join("");

      document.querySelector("[data-speed-kicker]").textContent = data.kicker;
      document.querySelector("[data-speed-title]").textContent = data.title;
      document.querySelector("[data-speed-text]").textContent = data.text;
      const phases = document.querySelector("[data-speed-phases]");
      phases.innerHTML = data.phases.map((phase) => `<span>${phase}</span>`).join("");
    });
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
        title: "Landing personalizada + Webpay",
        copy: "Una presencia digital diseñada a medida, responsive y preparada para recibir pagos. Si no necesitas pagos, una landing simple puede partir desde $280.000.",
        includes: ["Diseño personalizado", "Responsive", "Webpay", "SEO técnico", "Publicación"],
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
          .map((item) => `<span><i class="bi ${item.icon}" aria-hidden="true"></i>${item.label}</span>`)
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

const processData = {
  understand: {
    number: "01",
    kicker: "Entender",
    title: "Necesidad y contexto",
    text: "Objetivos, personas, operación, restricciones y decisiones que el sistema debe soportar.",
    meta: [{ label: "Objetivos", icon: "bi-bullseye" }, { label: "Operación", icon: "bi-gear" }, { label: "Restricciones", icon: "bi-sliders" }]
  },
  model: {
    number: "02",
    kicker: "Modelar",
    title: "Datos y reglas",
    text: "Entidades, relaciones, estados, permisos, validaciones y comportamiento del negocio.",
    meta: [{ label: "Entidades", icon: "bi-box" }, { label: "Relaciones", icon: "bi-diagram-3" }, { label: "Reglas", icon: "bi-list-check" }]
  },
  design: {
    number: "03",
    kicker: "Diseñar",
    title: "Arquitectura y experiencia",
    text: "Estructura técnica, módulos, flujos, interfaz y decisiones de implementación.",
    meta: [{ label: "Arquitectura", icon: "bi-bounding-box" }, { label: "Flujos", icon: "bi-signpost-split" }, { label: "UX / UI", icon: "bi-window" }]
  },
  build: {
    number: "04",
    kicker: "Construir",
    title: "Desarrollo e integración",
    text: "Código, base de datos, APIs, servicios externos, pruebas y puesta en producción.",
    meta: [{ label: "Código", icon: "bi-code-slash" }, { label: "Base de datos", icon: "bi-database" }, { label: "Integraciones", icon: "bi-plug" }]
  },
  evolve: {
    number: "05",
    kicker: "Evolucionar",
    title: "Mejora continua",
    text: "Medición, soporte, ajustes y nuevas capacidades cuando el sistema realmente las necesita.",
    meta: [{ label: "Medición", icon: "bi-graph-up" }, { label: "Soporte", icon: "bi-life-preserver" }, { label: "Evolución", icon: "bi-arrow-repeat" }]
  }
};

const processTabs = [...document.querySelectorAll("[data-process-step]")];
const processPanel = document.querySelector(".zd-process-panel");
const processNav = document.querySelector(".zd-process-nav");

if (processTabs.length && processPanel && processNav) {
  const processKeys = processTabs.map((tab) => tab.dataset.processStep);

  let processIndex = 0;
  let processTimer = null;

  const changeProcessStep = (index) => {
    processIndex = index;

    const key = processKeys[index];
    const data = processData[key];

    if (!data) return;

    processTabs.forEach((tab, tabIndex) => {
      const isActive = tabIndex === index;
      const isComplete = tabIndex < index;

      tab.classList.toggle("is-active", isActive);
      tab.classList.toggle("is-complete", isComplete);
      tab.setAttribute("aria-selected", isActive ? "true" : "false");
    });

    processNav.style.setProperty(
      "--process-progress",
      `${index * 20}%`
    );

    processPanel.classList.add("is-changing");

    window.setTimeout(() => {
      document.querySelector("[data-process-number]").textContent = data.number;
      document.querySelector("[data-process-kicker]").textContent = data.kicker;
      document.querySelector("[data-process-title]").textContent = data.title;
      document.querySelector("[data-process-text]").textContent = data.text;

      document.querySelector("[data-process-meta]").innerHTML = data.meta
      .map((item) => `
        <span>
          <i class="bi ${item.icon}" aria-hidden="true"></i>
          ${item.label}
        </span>
      `)
      .join("");

      processPanel.classList.remove("is-changing");
    }, 180);
  };

  const startProcessAuto = () => {
    window.clearInterval(processTimer);

    processTimer = window.setInterval(() => {
      const nextIndex = (processIndex + 1) % processKeys.length;
      changeProcessStep(nextIndex);
    }, 3600);
  };

  processTabs.forEach((tab, index) => {
    tab.addEventListener("click", () => {
      changeProcessStep(index);
      startProcessAuto();
    });
  });

  changeProcessStep(0);
  startProcessAuto();
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
========================================================= */

document.querySelectorAll("[data-values-pillars]").forEach((group) => {
  const pillars = [...group.querySelectorAll(".zd-value-pillar")];

  if (!pillars.length) return;

  let activeIndex = Math.max(
    0,
    pillars.findIndex((pillar) => pillar.classList.contains("is-active"))
  );

  let autoplay = null;

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
    if (!autoplay) return;

    window.clearInterval(autoplay);
    autoplay = null;
  };

  const startAutoplay = () => {
    stopAutoplay();

    if (reducedMotion.matches) return;

    window.setTimeout(() => {
      activatePillar(activeIndex + 1);

      autoplay = window.setInterval(() => {
        activatePillar(activeIndex + 1);
      }, 5500);
    }, 2200);
  };

  pillars.forEach((pillar, index) => {
    const trigger = pillar.querySelector(".zd-value-pillar-trigger");

    trigger?.addEventListener("click", () => {
      activatePillar(index);
      startAutoplay();
    });
  });

  group.addEventListener("mouseenter", stopAutoplay);
  group.addEventListener("mouseleave", startAutoplay);

  group.addEventListener("focusin", stopAutoplay);
  group.addEventListener("focusout", () => {
    window.setTimeout(() => {
      if (!group.contains(document.activeElement)) {
        startAutoplay();
      }
    }, 0);
  });

  activatePillar(activeIndex);
  startAutoplay();
});
})();
