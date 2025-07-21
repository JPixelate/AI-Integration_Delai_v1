
import ReactDOM from 'react-dom/client'
import './index.css'

// Import components that will be used in the PHP templates
import { ChatInterface } from './components/ChatInterface'
import { ModernHeader } from './components/ModernHeader'
import { TabsInterface } from './components/TabsInterface'

// Make components available globally for PHP templates
declare global {
  interface Window {
    ReactComponents: {
      ChatInterface: typeof ChatInterface;
      ModernHeader: typeof ModernHeader;
      TabsInterface: typeof TabsInterface;
    };
  }
}

window.ReactComponents = {
  ChatInterface,
  ModernHeader,
  TabsInterface,
};

// Initialize React components when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  console.log('React Main: DOM loaded, initializing components...');

  // Wait a bit for the DOM to be fully ready
  setTimeout(() => {
    initializeReactComponents();
  }, 100);
});

function initializeReactComponents() {
  console.log('React Main: Starting component initialization...');

  try {
    // Initialize header if element exists
    const headerElement = document.getElementById('react-header');
    if (headerElement) {
      console.log('React Main: Initializing header...');
      const root = ReactDOM.createRoot(headerElement);
      root.render(<ModernHeader />);
    } else {
      console.log('React Main: Header element not found');
    }

    // Initialize chat interface if element exists
    const chatElement = document.getElementById('react-chat');
    if (chatElement) {
      console.log('React Main: Initializing chat...');
      const root = ReactDOM.createRoot(chatElement);
      root.render(<ChatInterface />);
    } else {
      console.log('React Main: Chat element not found');
    }

    // Initialize tabs interface if element exists
    const tabsElement = document.getElementById('react-tabs');
    if (tabsElement) {
      console.log('React Main: Initializing tabs...');
      const root = ReactDOM.createRoot(tabsElement);
      root.render(<TabsInterface />);
    } else {
      console.log('React Main: Tabs element not found');
    }

    console.log('React Main: Component initialization complete');
  } catch (error) {
    console.error('React Main: Error initializing components:', error);
  }
}
