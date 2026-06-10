document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll(".eye-btn").forEach(function (btn) {
    btn.addEventListener("click", function () {
      const input = btn.parentElement.querySelector("input");
      if (!input) return;
      input.type = input.type === "password" ? "text" : "password";
      btn.textContent = input.type === "password" ? "👁" : "🙈";
    });
  });

  document.querySelectorAll(".dark-package input").forEach(function (radio) {
    radio.addEventListener("change", function () {
      document.querySelectorAll(".dark-package").forEach(function (label) {
        label.classList.remove("selected");
      });
      radio.closest(".dark-package").classList.add("selected");
    });
  });
});
