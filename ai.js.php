function toggleAI() {
    let box = document.getElementById("ai-box");
    box.style.display = box.style.display === "flex" ? "none" : "flex";
}

function sendAI() {
    let text = document.getElementById("ai-text").value;
    if (text.trim() === "") return;

    let msgBox = document.getElementById("ai-messages");

    msgBox.innerHTML += `<div class="ai-msg user">${text}</div>`;
    document.getElementById("ai-text").value = "";

    fetch("ai_api.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "message=" + encodeURIComponent(text)
    })
    .then(res => res.text())
    .then(reply => {
        msgBox.innerHTML += `<div class="ai-msg bot">${reply}</div>`;
        msgBox.scrollTop = msgBox.scrollHeight;
    });
}
