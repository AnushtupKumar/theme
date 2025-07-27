import React from 'react';
import ReactDOM from 'react-dom/client';
import { Provider } from 'react-redux';
import { BrowserRouter } from 'react-router-dom';
import { store } from './store/store';
import App from './App';
import './styles/main.scss';

// Initialize the app when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  const appContainer = document.getElementById('ai-commerce-app');
  
  if (appContainer) {
    const root = ReactDOM.createRoot(appContainer);
    
    root.render(
      <React.StrictMode>
        <Provider store={store}>
          <BrowserRouter>
            <App />
          </BrowserRouter>
        </Provider>
      </React.StrictMode>
    );
    
    // Initialize AI behavior tracking
    initializeAITracking();
    
    // Initialize cart recovery
    initializeCartRecovery();
    
    // Initialize chatbot
    initializeChatbot();
  }
});

// AI Behavior Tracking
function initializeAITracking() {
  // Track page views
  trackPageView();
  
  // Track product views
  document.addEventListener('click', (e) => {
    const productLink = e.target.closest('a[data-product-id]');
    if (productLink) {
      const productId = productLink.dataset.productId;
      trackBehavior('view_product', { product_id: productId });
    }
  });
  
  // Track searches
  const searchForms = document.querySelectorAll('.ai-search-form');
  searchForms.forEach(form => {
    form.addEventListener('submit', (e) => {
      const searchInput = form.querySelector('input[type="search"]');
      if (searchInput && searchInput.value) {
        trackBehavior('search', { query: searchInput.value });
      }
    });
  });
}

// Track user behavior
function trackBehavior(action, data) {
  if (!window.aiCommerce) return;
  
  fetch(window.aiCommerce.ajaxUrl, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams({
      action: 'ai_commerce_analyze_behavior',
      nonce: window.aiCommerce.nonce,
      behavior_action: action,
      data: JSON.stringify(data)
    })
  });
}

// Track page views
function trackPageView() {
  const pageData = {
    url: window.location.href,
    title: document.title,
    timestamp: Date.now()
  };
  
  trackBehavior('page_view', pageData);
}

// Initialize cart recovery
function initializeCartRecovery() {
  if (!window.aiCommerce || !window.aiCommerce.cartRecoveryEnabled) return;
  
  let exitIntentShown = false;
  
  // Exit intent detection
  document.addEventListener('mouseleave', (e) => {
    if (e.clientY <= 0 && !exitIntentShown && hasItemsInCart()) {
      showCartRecoveryModal();
      exitIntentShown = true;
    }
  });
  
  // Idle time detection
  let idleTimer;
  const idleTime = 60000; // 1 minute
  
  function resetIdleTimer() {
    clearTimeout(idleTimer);
    idleTimer = setTimeout(() => {
      if (hasItemsInCart() && !exitIntentShown) {
        showCartRecoveryModal();
        exitIntentShown = true;
      }
    }, idleTime);
  }
  
  ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
    document.addEventListener(event, resetIdleTimer, true);
  });
  
  resetIdleTimer();
}

// Check if cart has items
function hasItemsInCart() {
  // Check if WooCommerce cart has items
  const cartCount = document.querySelector('.ai-cart-count');
  return cartCount && parseInt(cartCount.textContent) > 0;
}

// Show cart recovery modal
function showCartRecoveryModal() {
  const modal = document.getElementById('ai-cart-recovery');
  if (modal) {
    modal.style.display = 'block';
    
    // Close button
    const closeBtn = modal.querySelector('.ai-cart-recovery-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', () => {
        modal.style.display = 'none';
      });
    }
    
    // Auto-hide after 10 seconds
    setTimeout(() => {
      modal.style.display = 'none';
    }, 10000);
  }
}

// Initialize chatbot
function initializeChatbot() {
  if (!window.aiCommerce || !window.aiCommerce.chatbotEnabled) return;
  
  const chatWidget = document.getElementById('ai-chat-widget');
  if (!chatWidget) return;
  
  const chatButton = chatWidget.querySelector('.ai-chat-button');
  const chatWindow = chatWidget.querySelector('.ai-chat-window');
  const chatClose = chatWidget.querySelector('.ai-chat-close');
  const chatForm = chatWidget.querySelector('.ai-chat-input-form');
  const chatInput = chatWidget.querySelector('.ai-chat-input');
  const chatMessages = chatWidget.querySelector('.ai-chat-messages');
  
  // Toggle chat window
  chatButton.addEventListener('click', () => {
    chatWindow.style.display = chatWindow.style.display === 'none' ? 'block' : 'none';
    if (chatWindow.style.display === 'block') {
      chatInput.focus();
    }
  });
  
  // Close chat
  chatClose.addEventListener('click', () => {
    chatWindow.style.display = 'none';
  });
  
  // Handle chat form submission
  chatForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const message = chatInput.value.trim();
    if (!message) return;
    
    // Add user message
    addChatMessage(message, 'user');
    chatInput.value = '';
    
    // Show typing indicator
    const typingIndicator = addChatMessage('...', 'bot');
    
    try {
      // Send message to AI
      const response = await sendChatMessage(message);
      
      // Remove typing indicator
      typingIndicator.remove();
      
      // Add bot response
      addChatMessage(response, 'bot');
    } catch (error) {
      typingIndicator.remove();
      addChatMessage('Sorry, I encountered an error. Please try again.', 'bot');
    }
  });
  
  // Add message to chat
  function addChatMessage(message, sender) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `ai-chat-message ai-chat-${sender}`;
    messageDiv.innerHTML = `<p>${message}</p>`;
    chatMessages.appendChild(messageDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
    return messageDiv;
  }
  
  // Send message to AI
  async function sendChatMessage(message) {
    const response = await fetch(window.aiCommerce.restUrl + '/chat', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': window.aiCommerce.nonce
      },
      body: JSON.stringify({ message })
    });
    
    const data = await response.json();
    return data.response || 'I can help you find products, track orders, or answer questions about our store.';
  }
}

// Export for use in other modules
export { trackBehavior, trackPageView };