var form = document.getElementById("my-form");
        
async function handleSubmit(event) {
    event.preventDefault();
    var status = document.getElementById("status");
    var data = new FormData(event.target);
    
    fetch(event.target.action, {
    method: form.method,
    body: data,
    headers: {
        'Accept': 'application/json'
    }
    }).then(response => {
    if (response.ok) {
        status.style.display = "block"; // Muestra el mensaje de gracias
        form.style.display = "none";    // Oculta el formulario
        form.reset();
    } else {
        response.json().then(data => {
        if (Object.hasOwn(data, 'errors')) {
            alert(data["errors"].map(error => error["message"]).join(", "));
        } else {
            alert("Huy! Hubo un problema al enviar el formulario");
        }
        })
    }
    }).catch(error => {
    alert("Huy! Hubo un problema al enviar el formulario");
    });
}
form.addEventListener("submit", handleSubmit)
   