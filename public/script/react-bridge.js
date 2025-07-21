// React-Legacy Bridge Script
// This script ensures compatibility between React components and legacy JavaScript

document.addEventListener('DOMContentLoaded', () => {
    console.log('React Bridge: Initializing...');
    
    // Wait for React components to mount
    setTimeout(() => {
        initializeBridge();
    }, 1000);
});

function initializeBridge() {
    console.log('React Bridge: Setting up compatibility layer...');
    
    // Check if React components are loaded
    const reactHeader = document.getElementById('react-header');
    const reactChat = document.getElementById('react-chat');
    const reactTabs = document.getElementById('react-tabs');
    
    if (!reactHeader || !reactChat || !reactTabs) {
        console.log('React Bridge: React components not found, falling back to legacy mode...');
        enableLegacyMode();
        return;
    }
    
    console.log('React Bridge: React components found, setting up modern mode...');
    setupModernMode();
}

function enableLegacyMode() {
    console.log('React Bridge: Enabling legacy mode...');

    // Show fallback header
    const fallbackHeader = document.getElementById('fallback-header');
    if (fallbackHeader) {
        fallbackHeader.style.display = 'block';
    }

    // Show the noscript fallbacks
    const noscriptElements = document.querySelectorAll('noscript');
    noscriptElements.forEach(noscript => {
        const content = noscript.innerHTML;
        if (content.trim()) {
            const wrapper = document.createElement('div');
            wrapper.innerHTML = content;
            noscript.parentNode.insertBefore(wrapper, noscript);
        }
    });

    // Hide React containers that are empty
    const reactContainers = ['react-header', 'react-chat', 'react-tabs'];
    reactContainers.forEach(id => {
        const element = document.getElementById(id);
        if (element && !element.hasChildNodes()) {
            element.style.display = 'none';
        }
    });

    console.log('React Bridge: Legacy mode enabled');
}

function setupModernMode() {
    console.log('React Bridge: Setting up modern mode...');

    // Hide fallback header
    const fallbackHeader = document.getElementById('fallback-header');
    if (fallbackHeader) {
        fallbackHeader.style.display = 'none';
    }

    // Hide noscript fallbacks
    const noscriptElements = document.querySelectorAll('noscript');
    noscriptElements.forEach(noscript => {
        noscript.style.display = 'none';
    });

    // Ensure the chat functionality works
    setupChatBridge();

    console.log('React Bridge: Modern mode enabled');
}

function setupChatBridge() {
    // Find the open chat button in the new design
    const openChatBtn = document.getElementById('openChatBtn');
    const initialScreen = document.getElementById('initialPromptScreen');
    const chatScreen = document.querySelector('.aspect-ratio-wrapper');
    
    if (openChatBtn && initialScreen && chatScreen) {
        openChatBtn.addEventListener('click', () => {
            console.log('React Bridge: Opening chat...');
            initialScreen.style.display = 'none';
            chatScreen.style.display = 'flex';
            chatScreen.classList.remove('fade-down');
            chatScreen.classList.add('fade-up');
        });
        
        console.log('React Bridge: Chat bridge setup complete');
    } else {
        console.log('React Bridge: Chat elements not found:', {
            openChatBtn: !!openChatBtn,
            initialScreen: !!initialScreen,
            chatScreen: !!chatScreen
        });
    }
    
    // Setup close button
    const closeChatBtn = document.getElementById('closeChatBtn');
    if (closeChatBtn && initialScreen && chatScreen) {
        closeChatBtn.addEventListener('click', () => {
            console.log('React Bridge: Closing chat...');
            chatScreen.classList.remove('fade-up');
            chatScreen.classList.add('fade-down');
            
            setTimeout(() => {
                chatScreen.style.display = 'none';
                initialScreen.style.display = 'flex';
            }, 300);
        });
    }
}

// Global function to check system status
window.checkSystemStatus = function() {
    console.log('=== System Status ===');
    console.log('React Header:', !!document.getElementById('react-header'));
    console.log('React Chat:', !!document.getElementById('react-chat'));
    console.log('React Tabs:', !!document.getElementById('react-tabs'));
    console.log('Open Chat Button:', !!document.getElementById('openChatBtn'));
    console.log('Initial Screen:', !!document.getElementById('initialPromptScreen'));
    console.log('Chat Screen:', !!document.querySelector('.aspect-ratio-wrapper'));
    console.log('React Components Available:', !!window.ReactComponents);
    console.log('==================');
};
