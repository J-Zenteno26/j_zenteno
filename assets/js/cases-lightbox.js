(() => {
  "use strict";

  const dialog = document.querySelector("[data-case-lightbox]");
  if (!dialog) return;

  const dialogImage = dialog.querySelector("[data-case-lightbox-image]");
  const dialogKicker = dialog.querySelector("[data-case-lightbox-kicker]");
  const dialogTitle = dialog.querySelector("[data-case-lightbox-title]");
  const closeButton = dialog.querySelector("[data-case-lightbox-close]");
  const figures = [...document.querySelectorAll(".zd-cases .zd-case-media figure")];

  const openFigure = (figure) => {
    const image = figure.querySelector("img");
    if (!image || !dialogImage) return;

    const small = figure.querySelector("figcaption small");
    const strong = figure.querySelector("figcaption strong");

    dialogImage.src = image.currentSrc || image.src;
    dialogImage.alt = image.alt || "Captura ampliada del proyecto";
    if (dialogKicker) dialogKicker.textContent = small?.textContent?.trim() || "";
    if (dialogTitle) dialogTitle.textContent = strong?.textContent?.trim() || "";

    if (typeof dialog.showModal === "function") dialog.showModal();
  };

  figures.forEach((figure) => {
    figure.dataset.caseZoom = "";
    figure.tabIndex = 0;
    figure.setAttribute("role", "button");

    const image = figure.querySelector("img");
    figure.setAttribute(
      "aria-label",
      image?.alt ? `Ampliar: ${image.alt}` : "Ampliar captura"
    );

    figure.addEventListener("click", (event) => {
      event.preventDefault();
      openFigure(figure);
    });

    figure.addEventListener("keydown", (event) => {
      if (event.key !== "Enter" && event.key !== " ") return;
      event.preventDefault();
      openFigure(figure);
    });
  });

  closeButton?.addEventListener("click", () => dialog.close());

  dialog.addEventListener("click", (event) => {
    if (event.target === dialog) dialog.close();
  });

  dialog.addEventListener("close", () => {
    if (!dialogImage) return;
    dialogImage.src = "";
    dialogImage.alt = "";
  });
})();
