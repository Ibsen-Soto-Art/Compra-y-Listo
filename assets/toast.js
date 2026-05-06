/**
 * Toast notification system — Compra y Listo
 * Usage:
 *   toast.success("Mensaje")
 *   toast.error("Mensaje")
 *   toast.warning("Mensaje")
 *   toast.info("Mensaje")
 */
const toast = (() => {
    const DURATION = 4000;

    const TYPES = {
        success: { icon: "bi-check-circle-fill", title: "Éxito" },
        error:   { icon: "bi-x-circle-fill",     title: "Error" },
        warning: { icon: "bi-exclamation-triangle-fill", title: "Atención" },
        info:    { icon: "bi-info-circle-fill",   title: "Info" },
    };

    function getContainer() {
        let c = document.getElementById("toast-container");
        if (!c) {
            c = document.createElement("div");
            c.id = "toast-container";
            document.body.appendChild(c);
        }
        return c;
    }

    function show(type, message, duration = DURATION) {
        const cfg = TYPES[type] || TYPES.info;
        const container = getContainer();

        const el = document.createElement("div");
        el.className = `toast toast-${type}`;
        el.innerHTML = `
            <i class="bi ${cfg.icon} toast-icon"></i>
            <div class="toast-body">
                <div class="toast-title">${cfg.title}</div>
                <div class="toast-msg">${message}</div>
            </div>
            <span class="toast-close" title="Cerrar">&times;</span>
            <div class="toast-progress" style="animation-duration:${duration}ms"></div>
        `;

        function dismiss() {
            el.classList.remove("toast-show");
            el.classList.add("toast-hide");
            setTimeout(() => el.remove(), 280);
        }

        el.querySelector(".toast-close").addEventListener("click", dismiss);
        el.addEventListener("click", dismiss);

        container.appendChild(el);
        requestAnimationFrame(() => requestAnimationFrame(() => el.classList.add("toast-show")));
        setTimeout(dismiss, duration);
    }

    return {
        success: (msg, dur) => show("success", msg, dur),
        error:   (msg, dur) => show("error",   msg, dur),
        warning: (msg, dur) => show("warning", msg, dur),
        info:    (msg, dur) => show("info",    msg, dur),
    };
})();
