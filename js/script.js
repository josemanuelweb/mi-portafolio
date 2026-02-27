const form = document.querySelector(".contact-form");
const statusEl = document.getElementById("form-status");
let statusTimeoutId;

function setStatus(message, type) {
    if (!statusEl) return;
    if (statusTimeoutId) {
        clearTimeout(statusTimeoutId);
    }

    statusEl.textContent = message;
    statusEl.classList.remove("is-success", "is-error");
    statusEl.classList.add(type === "success" ? "is-success" : "is-error");

    statusTimeoutId = setTimeout(() => {
        statusEl.textContent = "";
        statusEl.classList.remove("is-success", "is-error");
    }, 5000);
}

async function handleSubmit(event) {
    event.preventDefault();
    const data = new FormData(event.target);
    const submitButton = form.querySelector("button[type='submit']");

    if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = "Enviando...";
    }

    try {
        const response = await fetch(event.target.action, {
            method: form.method,
            body: data,
            headers: {
                Accept: "application/json"
            }
        });

        if (response.ok) {
            form.reset();
            setStatus("Mensaje enviado correctamente. Gracias por contactarme.", "success");
            return;
        }

        let payload = null;
        const contentType = response.headers.get("content-type") || "";
        if (contentType.includes("application/json")) {
            payload = await response.json();
        } else {
            const rawError = await response.text();
            if (rawError) {
                setStatus(`Error del servidor (${response.status}).`, "error");
                return;
            }
        }

        if (payload && payload.message) {
            setStatus(payload.message, "error");
            return;
        }

        if (Object.hasOwn(payload, "errors")) {
            setStatus(payload.errors.map((error) => error.message).join(", "), "error");
            return;
        }

        setStatus(`Hubo un problema al enviar el formulario (HTTP ${response.status}).`, "error");
    } catch (_error) {
        setStatus("No se pudo conectar con el servidor. Abre el sitio desde MAMP (localhost), no con file://.", "error");
    } finally {
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.textContent = "Enviar Mensaje";
        }
    }
}

if (form) {
    form.addEventListener("submit", handleSubmit);
}
