// Sidebar toggle (desktop + mobile)
document.addEventListener("DOMContentLoaded", () => {
  const sidepanelTogglerDesktop = document.getElementById("sidepanel-toggler-desktop");
  const sidepanelTogglerMobile = document.getElementById("sidepanel-toggler");
  const sidepanelClose = document.getElementById("sidepanel-close");
  const appSidepanel = document.getElementById("app-sidepanel");
  const appWrapper = document.querySelector(".app-wrapper");

  if (!appSidepanel) return;

  const SIDEBAR_STATE_KEY = "sidebar_collapsed";

  function collapseSidebar() {
    appSidepanel.classList.add("collapsed");
    appSidepanel.classList.remove("sidepanel-visible");
    appSidepanel.classList.add("sidepanel-hidden");
    if (appWrapper) {
      appWrapper.classList.add("sidebar-collapsed");
    }
    localStorage.setItem(SIDEBAR_STATE_KEY, "true");
  }

  function expandSidebar() {
    appSidepanel.classList.remove("collapsed");
    appSidepanel.classList.remove("sidepanel-hidden");
    appSidepanel.classList.add("sidepanel-visible");
    if (appWrapper) {
      appWrapper.classList.remove("sidebar-collapsed");
    }
    localStorage.setItem(SIDEBAR_STATE_KEY, "false");
  }

  function toggleSidebar() {
    if (appSidepanel.classList.contains("collapsed")) {
      expandSidebar();
    } else {
      collapseSidebar();
    }
  }

  function initSidebar() {
    // Only collapse on desktop (1200px+)
    if (window.innerWidth >= 1200) {
      const isCollapsed = localStorage.getItem(SIDEBAR_STATE_KEY) === "true";
      if (isCollapsed) {
        collapseSidebar();
      } else {
        expandSidebar();
      }
    } else {
      // Mobile/tablet always expanded
      expandSidebar();
    }
  }

  function resetMobileStyles() {
    appSidepanel.classList.remove("collapsed");
    appSidepanel.classList.remove("sidepanel-hidden");
    if (appWrapper) {
      appWrapper.classList.remove("sidebar-collapsed");
    }
  }

  // Desktop toggle button
  if (sidepanelTogglerDesktop) {
    sidepanelTogglerDesktop.addEventListener("click", (e) => {
      e.preventDefault();
      toggleSidebar();
    });
  }

  // Mobile toggle button (hamburger menu)
  if (sidepanelTogglerMobile) {
    sidepanelTogglerMobile.addEventListener("click", (e) => {
      e.preventDefault();
      appSidepanel.classList.toggle("show");
    });
  }

  // Close button (X icon)
  if (sidepanelClose) {
    sidepanelClose.addEventListener("click", (e) => {
      e.preventDefault();
      if (window.innerWidth < 1200) {
        // Mobile: just hide the sidebar
        appSidepanel.classList.remove("show");
      } else {
        // Desktop: toggle collapse
        toggleSidebar();
      }
    });
  }

  // Handle window resize
  window.addEventListener("resize", () => {
    if (window.innerWidth < 1200) {
      // Switch to mobile layout
      resetMobileStyles();
      appSidepanel.classList.remove("show");
    } else {
      // Switch to desktop layout - restore saved state
      const isCollapsed = localStorage.getItem(SIDEBAR_STATE_KEY) === "true";
      if (isCollapsed) {
        collapseSidebar();
      } else {
        expandSidebar();
      }
    }
  });

  // Initialize on page load
  initSidebar();
});
