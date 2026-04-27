(function () {
    function updateClock() {
        const now = new Date();
        const h = now.getHours();
        const m = String(now.getMinutes()).padStart(2, "0");
        const s = String(now.getSeconds()).padStart(2, "0");
        const hh = String(h % 12 || 12).padStart(2, "0");
        const ampm = h >= 12 ? "PM" : "AM";

        document.querySelectorAll(".js-hours").forEach((el) => { el.textContent = hh; });
        document.querySelectorAll(".js-minutes").forEach((el) => { el.textContent = m; });
        document.querySelectorAll(".js-seconds").forEach((el) => { el.textContent = s; });
        document.querySelectorAll(".js-period").forEach((el) => { el.textContent = ampm; });
        document.querySelectorAll(".js-date").forEach((el) => {
            el.textContent = now.toLocaleDateString("en-US", {
                weekday: "long",
                month: "long",
                day: "numeric",
                year: "numeric",
            });
        });
    }

    setInterval(updateClock, 1000);
    updateClock();
})();
