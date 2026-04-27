function openQRModal() {
    const modal = document.getElementById("customModal");
    if (!modal) return;

    modal.style.display = "flex";
    const qrContainer = document.getElementById("qrcode_canvas");
    if (!qrContainer) return;

    qrContainer.innerHTML = "";
    new QRCode(qrContainer, {
        text: "https://live.ipms247.com/booking/book-rooms-zensationtheresidence",
        width: 320,
        height: 320,
        colorDark: "#1e3c72",
        colorLight: "#f8f9fa",
        correctLevel: QRCode.CorrectLevel.H,
    });
}

function closeQRModal() {
    const modal = document.getElementById("customModal");
    if (modal) modal.style.display = "none";
}

window.addEventListener("load", function () {
    if (window.Swiper) {
        new window.Swiper(".swiper", {
            loop: true,
            effect: "fade",
            autoplay: { delay: 8000 },
            speed: 3000,
        });
    }
});
