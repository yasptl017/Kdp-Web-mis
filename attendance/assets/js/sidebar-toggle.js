// Sidebar toggle (desktop + mobile)
document.addEventListener("DOMContentLoaded", () => {
  const sidepanelTogglerDesktop = document.getElementById("sidepanel-toggler-desktop");
  const sidepanelTogglerMobile = document.getElementById("sidepanel-toggler");
  const sidepanelClose = document.getElementById("sidepanel-close");
  const appSidepanel = document.getElementById("app-sidepanel");
  const appWrapper = document.querySelector(".app-wrapper");
  const headerInner = document.querySelector(".app-header-inner");
  const bodyEl = document.body;

  if (!appSidepanel) return;

  const SIDEBAR_STATE_KEY = "sidebar_collapsed";
  const EXPANDED_MARGIN = "250px";
  const COLLAPSED_MARGIN = "0px";

  function applyLayout(isCollapsed) {
    if (appWrapper) {
      appWrapper.style.marginLeft = isCollapsed ? COLLAPSED_MARGIN : EXPANDED_MARGIN;
      appWrapper.style.maxWidth = "100%";
    }
    if (headerInner) {
      headerInner.style.marginLeft = isCollapsed ? COLLAPSED_MARGIN : EXPANDED_MARGIN;
    }
    if (bodyEl) {
      bodyEl.classList.toggle("sidebar-collapsed", isCollapsed);
      bodyEl.classList.toggle("sidebar-expanded", !isCollapsed);
    }
  }

  function collapseSidebar() {
    appSidepanel.classList.add("collapsed");
    appSidepanel.classList.remove("sidepanel-visible");
    appSidepanel.classList.add("sidepanel-hidden");
    applyLayout(true);
    localStorage.setItem(SIDEBAR_STATE_KEY, "true");
  }

  function expandSidebar() {
    appSidepanel.classList.remove("collapsed");
    appSidepanel.classList.remove("sidepanel-hidden");
    appSidepanel.classList.add("sidepanel-visible");
    applyLayout(false);
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
    const isCollapsed = localStorage.getItem(SIDEBAR_STATE_KEY) === "true";
    if (window.innerWidth >= 1200 && isCollapsed) {
      collapseSidebar();
    } else {
      expandSidebar();
    }
  }

  if (sidepanelTogglerDesktop) {
    sidepanelTogglerDesktop.addEventListener("click", (e) => {
      e.preventDefault();
      toggleSidebar();
    });
  }

  if (sidepanelTogglerMobile) {
    sidepanelTogglerMobile.addEventListener("click", (e) => {
      e.preventDefault();
      appSidepanel.classList.toggle("show");
    });
  }

  if (sidepanelClose) {
    sidepanelClose.addEventListener("click", (e) => {
      e.preventDefault();
      if (window.innerWidth < 1200) {
        appSidepanel.classList.remove("show");
      } else {
        toggleSidebar();
      }
    });
  }

  window.addEventListener("resize", () => {
    if (window.innerWidth < 1200) {
      appSidepanel.classList.remove("collapsed");
      appSidepanel.classList.remove("sidepanel-hidden");
      if (appWrapper) {
        appWrapper.style.marginLeft = "";
        appWrapper.style.maxWidth = "";
      }
      if (headerInner) headerInner.style.marginLeft = "";
      if (bodyEl) {
        bodyEl.classList.remove("sidebar-collapsed");
        bodyEl.classList.remove("sidebar-expanded");
      }
    } else {
      const isCollapsed = localStorage.getItem(SIDEBAR_STATE_KEY) === "true";
      if (isCollapsed) collapseSidebar(); else expandSidebar();
    }
  });

  initSidebar();
});
