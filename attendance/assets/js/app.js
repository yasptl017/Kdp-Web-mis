'use strict';

/* ===== Enable Bootstrap Popover (on element  ====== */
const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]')
const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl))

/* ==== Enable Bootstrap Alert ====== */
//var alertList = document.querySelectorAll('.alert')
//alertList.forEach(function (alert) {
//  new bootstrap.Alert(alert)
//});

const alertList = document.querySelectorAll('.alert')
const alerts = [...alertList].map(element => new bootstrap.Alert(element))


/* ===== Responsive Sidepanel (mobile only, desktop handled in sidebar-toggle.js) ====== */
const sidePanelToggler = document.getElementById('sidepanel-toggler'); 
const sidePanel = document.getElementById('app-sidepanel');  
const sidePanelDrop = document.getElementById('sidepanel-drop'); 
const sidePanelClose = document.getElementById('sidepanel-close'); 
const hasDesktopToggle = !!document.getElementById('sidepanel-toggler-desktop');

function responsiveSidePanel() {
    if (!sidePanel) return;
    const w = window.innerWidth;
    if (hasDesktopToggle && w >= 1200) {
        // desktop state handled in sidebar-toggle.js
        return;
    }
    if (w >= 1200) {
        sidePanel.classList.remove('sidepanel-hidden');
        sidePanel.classList.add('sidepanel-visible');
    } else {
        sidePanel.classList.remove('sidepanel-visible');
        sidePanel.classList.add('sidepanel-hidden');
    }
}

window.addEventListener('load', responsiveSidePanel);
window.addEventListener('resize', responsiveSidePanel);

if (sidePanelToggler) {
    sidePanelToggler.addEventListener('click', () => {
        if (sidePanel.classList.contains('sidepanel-visible')) {
            sidePanel.classList.remove('sidepanel-visible');
            sidePanel.classList.add('sidepanel-hidden');
        } else {
            sidePanel.classList.remove('sidepanel-hidden');
            sidePanel.classList.add('sidepanel-visible');
        }
    });
}

if (sidePanelClose) {
    sidePanelClose.addEventListener('click', (e) => {
        e.preventDefault();
        if (sidePanelToggler) sidePanelToggler.click();
    });
}

if (sidePanelDrop) {
    sidePanelDrop.addEventListener('click', (e) => {
        if (sidePanelToggler) sidePanelToggler.click();
    });
}



/* ====== Mobile search ======= */
const searchMobileTrigger = document.querySelector('.search-mobile-trigger');
const searchBox = document.querySelector('.app-search-box');

searchMobileTrigger.addEventListener('click', () => {

	searchBox.classList.toggle('is-visible');
	
	let searchMobileTriggerIcon = document.querySelector('.search-mobile-trigger-icon');
	
	if(searchMobileTriggerIcon.classList.contains('fa-magnifying-glass')) {
		searchMobileTriggerIcon.classList.remove('fa-magnifying-glass');
		searchMobileTriggerIcon.classList.add('fa-xmark');
	} else {
		searchMobileTriggerIcon.classList.remove('fa-xmark');
		searchMobileTriggerIcon.classList.add('fa-magnifying-glass');
	}
	
		
	
});
