function initializeTabContent(tabId, tabElement) {
   if (tabElement.getAttribute('data-initialized') === 'true') return;

   const template = document.getElementById(`template-${tabId}`);
   if (template) {
      tabElement.innerHTML = template.innerHTML;
      tabElement.setAttribute('data-initialized', 'true');
   } else {
      console.warn(`Template not found for ${tabId}`);
   }
}
