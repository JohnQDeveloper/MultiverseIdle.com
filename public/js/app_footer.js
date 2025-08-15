const visibleModal = document.getElementById("messageModal");
const animationDuration = 400;
const isOpenClass = "modal-is-open";
const openingClass = "modal-is-opening";
const closingClass = "modal-is-closing";

const closeMessageModal = (modal) => {
    const closingClass = "modal-is-closing";
    const scrollbarWidthCssVar = "--pico-scrollbar-width";

    const { documentElement: html } = document;
    html.classList.add(closingClass);
    setTimeout(() => {
        html.classList.remove(closingClass, isOpenClass);
        html.style.removeProperty(scrollbarWidthCssVar);
        modal.close();
    }, animationDuration);
    modal.removeAttribute("open");
};

// Close with Esc key
document.addEventListener("keydown", (event) => {
  if (event.key === "Escape" && visibleModal) {
    closeMessageModal(visibleModal);
  }
});

// Close with a click outside
document.addEventListener("click", (event) => {
  if (visibleModal === null) return;
  const modalContent = visibleModal.querySelector("article");
  const isClickInside = modalContent.contains(event.target);
  !isClickInside && closeMessageModal(visibleModal);
});

// Close with close icon
document.getElementById("closeMessageModal").addEventListener("click", () => {
  closeMessageModal(visibleModal);
});

// Get scrollbar width
const getScrollbarWidth = () => {
  const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
  return scrollbarWidth;
};

// Is scrollbar visible
const isScrollbarVisible = () => {
  return document.body.scrollHeight > screen.height;
};
