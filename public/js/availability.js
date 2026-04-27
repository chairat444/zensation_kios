(function () {
    const form = document.getElementById("availForm");
    const defaults = {
        checkin: form?.dataset.checkin || "",
        checkout: form?.dataset.checkout || "",
        minCheckout: form?.dataset.minCheckout || "",
    };
    const today = new Date();

    const fpOut = flatpickr("#checkout", {
        dateFormat: "Y-m-d",
        minDate: defaults.minCheckout || today,
        defaultDate: defaults.checkout || "",
    });

    flatpickr("#checkin", {
        dateFormat: "Y-m-d",
        minDate: today,
        defaultDate: defaults.checkin || "",
        onChange: (selectedDates) => fpOut.set("minDate", selectedDates[0] || today),
    });

    form?.addEventListener("submit", () => {
        const loading = document.getElementById("loading");
        if (loading) loading.style.display = "flex";
    });
})();
