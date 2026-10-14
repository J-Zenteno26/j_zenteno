<section id="contact" class="zd-section zd-contact">
  <div class="container-xl">
    <div class="zd-contact-shell">
      <div class="zd-contact-copy">
        <span class="zd-eyebrow">08 · Conversemos</span>
        <h2>
          <span class="zd-contact-title-desktop">Cuéntame el problema.<br>No la solución.</span>
          <span class="zd-contact-title-mobile">¿Qué necesitas resolver?</span>
        </h2>
        <p class="zd-contact-desktop-copy">No necesitas llegar con una especificación técnica. Cuéntame qué está pasando, qué necesitas controlar o
          qué parte de la operación ya no escala.</p>
        <p class="zd-contact-mobile-copy">Cuéntame el contexto. Yo te ayudo a convertirlo en una solución técnica clara.</p>
        <div class="zd-contact-prompts"><span>Un proceso que se volvió inmanejable</span><span>Información
            dispersa</span><span>Un sistema que necesita crecer</span><span>Una operación que necesita software
            propio</span></div>
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
            <p>Porque reduzco traspasos de contexto: quien entiende el problema también toma las decisiones técnicas
              y desarrolla la solución.</p>
          </details>
        </div>
        <a href="mailto:contacto@zentdigital.cl" class="zd-contact-email">contacto@zentdigital.cl <i
            class="bi bi-arrow-up-right"></i></a>
      </div>
      <form id="contactForm" action="forms/contact.php" method="post" class="contact-form">
        <div class="zd-contact-form-head">
          <span class="zd-contact-form-icon" aria-hidden="true"><i class="bi bi-send"></i></span>
          <div><small>Empecemos</small><strong>Cuéntame tu proyecto</strong></div>
        </div>
        <div class="row gy-3">
          <div class="col-md-6"><label for="name"><i class="bi bi-person" aria-hidden="true"></i> Nombre</label><input id="name" type="text" name="name"
              class="form-control" placeholder="Tu nombre" required></div>
          <div class="col-md-6"><label for="email"><i class="bi bi-envelope" aria-hidden="true"></i> Email</label><input id="email" type="email" name="email"
              class="form-control" placeholder="tu@empresa.cl" required></div>
          <div class="col-md-6"><label for="phone"><i class="bi bi-whatsapp" aria-hidden="true"></i> WhatsApp</label><input id="phone" type="tel" name="phone"
              class="form-control" placeholder="+56 9 1234 5678" required></div>
          <div class="col-md-6"><label for="subject"><i class="bi bi-boxes" aria-hidden="true"></i> Proyecto</label><input id="subject" type="text" name="subject"
              class="form-control" placeholder="¿Qué necesitas resolver?" required></div>
          <div class="col-12"><label for="message"><i class="bi bi-chat-left-text" aria-hidden="true"></i> Contexto</label><textarea id="message" class="form-control"
              name="message" rows="6" placeholder="Cuéntame brevemente qué está pasando y qué necesitas mejorar"
              required></textarea></div>
          <div class="col-12">
            <div class="form-status">
              <div class="loading"><span>Enviando...</span></div>
              <div class="error-message"></div>
              <div class="sent-message"><span>✓ Mensaje enviado correctamente. Te responderé pronto.</span></div>
            </div>
          </div>
          <div class="col-12">
            <div class="zd-recaptcha-wrap">
              <div class="g-recaptcha" data-sitekey="6LfSgiMtAAAAAGSP0DE1leNhS3s0t-eWZ_KtnFq6"></div>
            </div>
          </div>
          <div class="col-12"><button type="submit" class="zd-btn zd-btn-primary">Enviar proyecto <i
                class="bi bi-arrow-up-right"></i></button></div>
        </div>
      </form>
    </div>
  </div>
</section>
