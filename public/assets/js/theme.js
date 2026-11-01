const themeToggle = document.querySelector("[data-theme-toggle]");
const themeStorageKey = "mais-batik-theme";
const systemTheme = window.matchMedia("(prefers-color-scheme: dark)");

const isStorageUnavailable = (error) =>
  error instanceof DOMException
  && ["SecurityError", "QuotaExceededError"].includes(error.name);

const readSavedTheme = () => {
  try {
    const savedTheme = window.localStorage.getItem(themeStorageKey);
    return savedTheme === "light" || savedTheme === "dark" ? savedTheme : null;
  } catch (error) {
    if (isStorageUnavailable(error)) {
      console.warn("Theme preference storage is unavailable; using the system preference.");
      return null;
    }

    throw error;
  }
};

const saveTheme = (theme) => {
  try {
    window.localStorage.setItem(themeStorageKey, theme);
  } catch (error) {
    if (isStorageUnavailable(error)) {
      console.warn("Theme changed for this page, but could not be saved in browser storage.");
      return;
    }

    throw error;
  }
};

let savedTheme = readSavedTheme();

const applyTheme = (theme) => {
  document.documentElement.dataset.theme = theme;

  if (themeToggle instanceof HTMLButtonElement) {
    const isDark = theme === "dark";
    themeToggle.setAttribute("aria-pressed", String(isDark));
    themeToggle.setAttribute("aria-label", isDark ? "Aktifkan mode terang" : "Aktifkan mode gelap");
    themeToggle.title = isDark ? "Aktifkan mode terang" : "Aktifkan mode gelap";

    const icon = themeToggle.querySelector(".theme-icon");
    if (icon) {
      icon.textContent = isDark ? "☀" : "☾";
    }
  }
};

applyTheme(savedTheme ?? (systemTheme.matches ? "dark" : "light"));

themeToggle?.addEventListener("click", () => {
  savedTheme = document.documentElement.dataset.theme === "dark" ? "light" : "dark";
  applyTheme(savedTheme);
  saveTheme(savedTheme);
});

systemTheme.addEventListener("change", (event) => {
  if (savedTheme === null) {
    applyTheme(event.matches ? "dark" : "light");
  }
});
