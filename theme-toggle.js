(() => {
    "use strict";

    const buttons = document.querySelectorAll("[data-theme-toggle]");

    function updateButtons(theme) {
        const isDark = theme === "dark";
        for (const button of buttons) {
            button.setAttribute("aria-pressed", String(isDark));
            button.setAttribute("aria-label", `Switch to ${isDark ? "light" : "dark"} mode`);
            button.title = `Switch to ${isDark ? "light" : "dark"} mode`;
            const label = button.querySelector("[data-theme-label]");
            if (label) label.textContent = `${isDark ? "Light" : "Dark"} mode`;
        }
    }

    updateButtons(document.documentElement.dataset.theme === "dark" ? "dark" : "light");
    for (const button of buttons) {
        button.addEventListener("click", () => {
            const theme = document.documentElement.dataset.theme === "dark" ? "light" : "dark";
            document.documentElement.dataset.theme = theme;
            updateButtons(theme);
            try {
                localStorage.setItem("bamstay-theme", theme);
            } catch {}
        });
    }
})();
