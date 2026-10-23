<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>ZentDigital · Empresa de ingeniería de software</title>
  <meta name="description"
    content="ZentDigital es una empresa de ingeniería de software enfocada en arquitectura, modelado de datos, sistemas a medida, SaaS e integraciones para operaciones reales.">
  <meta name="keywords"
    content="ingeniería de software, arquitectura de software, modelado de datos, SaaS, sistemas a medida, ecommerce, integraciones, Chile, ZentDigital">
  <link rel="canonical" href="https://zentdigital.cl/">
  <link rel="icon" type="image/png" href="assets/img/favicon.ico">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600&display=swap"
    rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/main-redesign.css?v=9" rel="stylesheet">
  <link href="assets/css/home.css?v=2" rel="stylesheet">
</head>

<body class="index-page zd-home-page">
  <header id="header" class="header fixed-top">
    <div class="container-xl zd-nav-shell">
      <a href="#hero" class="logo" aria-label="ZentDigital, volver al inicio">
        <img src="assets/img/logo1.png" alt="ZentDigital">
      </a>
      <nav id="navmenu" class="navmenu" aria-label="Navegación principal">
        <ul>
          <li><a href="#engineering" class="active">Ingeniería</a></li>
          <li><a href="#cases">Casos</a></li>
          <li><a href="#process">Proceso</a></li>
          <li><a href="#values">Principios</a></li>
          <li><a href="#company">Empresa</a></li>
        </ul>
      </nav>
      <a class="zd-nav-cta d-none d-xl-inline-flex" href="#contact">Hablemos <i class="bi bi-arrow-up-right"></i></a>
      <button class="mobile-nav-toggle d-xl-none bi bi-list" type="button" aria-label="Abrir navegación"
        aria-controls="navmenu"></button>
    </div>
  </header>

  <main>
    <?php require __DIR__ . '/sections/hero.php'; ?>
    <?php require __DIR__ . '/sections/engineering.php'; ?>
    <?php require __DIR__ . '/sections/cases.php'; ?>
    <?php require __DIR__ . '/sections/process.php'; ?>
    <?php require __DIR__ . '/sections/values.php'; ?>
    <?php require __DIR__ . '/sections/contact.php'; ?>
  </main>

  <footer class="zd-footer">
    <div class="container-xl zd-footer-grid">
      <div>
        <a href="#hero" class="zd-footer-brand">ZentDigital</a>
        <p>Ingeniería de software para operaciones que necesitan estructura.</p>
      </div>
      <div>
        <span>Navegación</span>
        <a href="#engineering">Ingeniería</a>
        <a href="/software-a-medida/">Software a medida</a>
        <a href="/desarrollo-saas/">Desarrollo SaaS</a>
        <a href="/desarrollo-ecommerce/">Desarrollo Ecommerce</a>
        <a href="#cases">Casos</a>
      </div>
      <div>
        <span>Contacto</span>
        <a href="mailto:contacto@zentdigital.cl">contacto@zentdigital.cl</a>
        <a href="https://www.linkedin.com/in/jeanette-zenteno/" target="_blank" rel="noopener noreferrer">LinkedIn <i class="bi bi-arrow-up-right"></i></a>
        <a href="#company">Dirección técnica</a>
      </div>
    </div>
    <div class="container-xl zd-footer-bottom">
      <span>© 2026 ZentDigital</span>
      <span>Concepción, Chile · Proyectos en modalidad remota</span>
    </div>
  </footer>

  <a href="#" id="scroll-top" class="scroll-top"><i class="bi bi-arrow-up"></i></a>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/main-redesign.js?v=4"></script>
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</body>

</html>
