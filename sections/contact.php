<section id="contact" class="zd-section zd-contact">
  <div class="container-xl">
    <div id="company" class="zd-company-intro">
      <div>
        <span class="zd-eyebrow">05 · Empresa y dirección técnica</span>
        <h2>Ingeniería aplicada.</h2>
      </div>

      <div class="zd-company-copy">
        <p>Somos una empresa de ingeniería de software con base en Concepción, enfocada en sistemas, datos e integraciones para operaciones que necesitan una solución propia.</p>
        <p>La dirección técnica está a cargo de Jeanette Zenteno, Ingeniera de Software, manteniendo continuidad desde el requerimiento y la arquitectura hasta la implementación y puesta en producción.</p>
        <div class="zd-company-meta" aria-label="Información de ZentDigital">
          <span><i class="bi bi-geo-alt" aria-hidden="true"></i> Concepción, Chile</span>
          <span><i class="bi bi-globe2" aria-hidden="true"></i> Proyectos remotos</span>
          <a href="https://www.linkedin.com/in/jeanette-zenteno/" target="_blank" rel="noopener noreferrer"><i class="bi bi-linkedin" aria-hidden="true"></i> Dirección técnica</a>
        </div>
      </div>
    </div>

    <div class="zd-contact-shell">
      <div class="zd-contact-copy">
        <span class="zd-eyebrow">Hablemos</span>
        <h2>Cuéntanos el problema.</h2>
        <p>Qué está pasando, qué necesitas controlar o qué parte de la operación ya no escala. La solución técnica la definimos después.</p>

        <div class="zd-contact-quickfaq">
          <details>
            <summary>¿Necesito llegar con el sistema definido?</summary>
            <p>No. Parte de mi trabajo es ayudarte a identificar qué necesitas realmente.</p>
          </details>
          <details>
            <summary>¿Trabajas con sistemas existentes?</summary>
            <p>Sí. Puedo revisar una base actual y definir cómo mejorarla, integrarla o hacerla evolucionar.</p>
          </details>
          <details>
            <summary>¿Por qué los tiempos suelen ser más cortos?</summary>
            <p>Porque reduzco traspasos de contexto: quien entiende el problema también toma las decisiones técnicas y desarrolla la solución.</p>
          </details>
        </div>

        <a href="mailto:contacto@zentdigital.cl" class="zd-contact-email">contacto@zentdigital.cl <i class="bi bi-arrow-up-right"></i></a>
      </div>

      <form id="contactForm" action="forms/contact.php" method="post" class="contact-form">
        <div class="zd-contact-form-head">
          <span class="zd-contact-form-icon" aria-hidden="true"><i class="bi bi-send"></i></span>
          <div><small>Empecemos</small><strong>Cuéntanos el contexto</strong></div>
        </div>
        <div class="row gy-3">
          <div class="col-md-6"><label for="name"><i class="bi bi-person" aria-hidden="true"></i> Nombre</label><input id="name" type="text" name="name" class="form-control" placeholder="Tu nombre" required></div>
          <div class="col-md-6"><label for="email"><i class="bi bi-envelope" aria-hidden="true"></i> Email</label><input id="email" type="email" name="email" class="form-control" placeholder="tu@empresa.cl" required></div>
          <div class="col-md-6"><label for="phone"><i class="bi bi-whatsapp" aria-hidden="true"></i> WhatsApp</label><input id="phone" type="tel" name="phone" class="form-control" placeholder="+56 9 1234 5678" required></div>
          <div class="col-md-6"><label for="subject"><i class="bi bi-boxes" aria-hidden="true"></i> Proyecto</label><input id="subject" type="text" name="subject" class="form-control" placeholder="¿Qué necesitas resolver?" required></div>
          <div class="col-12"><label for="message"><i class="bi bi-chat-left-text" aria-hidden="true"></i> Contexto</label><textarea id="message" class="form-control" name="message" rows="6" placeholder="Cuéntanos brevemente qué está pasando y qué necesitas mejorar" required></textarea></div>
          <div class="col-12">
            <div class="form-status">
              <div class="loading"><span>Enviando...</span></div>
              <div class="error-message"></div>
              <div class="sent-message"><span>✓ Mensaje enviado correctamente. Te responderemos pronto.</span></div>
            </div>
          </div>
          <div class="col-12">
            <div class="zd-recaptcha-wrap">
              <div class="g-recaptcha" data-sitekey="6LfSgiMtAAAAAGSP0DE1leNhS3s0t-eWZ_KtnFq6"></div>
            </div>
          </div>
          <div class="col-12"><button type="submit" class="zd-btn zd-btn-primary">Enviar proyecto <i class="bi bi-arrow-up-right"></i></button></div>
        </div>
      </form>
    </div>
  </div>
</section>
