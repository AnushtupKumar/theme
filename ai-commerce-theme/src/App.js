import React, { useEffect, useState } from 'react';
import { Routes, Route, useLocation } from 'react-router-dom';
import { useDispatch } from 'react-redux';
import HomePage from './components/pages/HomePage';
import ShopPage from './components/pages/ShopPage';
import ProductPage from './components/pages/ProductPage';
import CartPage from './components/pages/CartPage';
import CheckoutPage from './components/pages/CheckoutPage';
import AccountPage from './components/pages/AccountPage';
import SearchResults from './components/pages/SearchResults';
import AIRecommendations from './components/AIRecommendations';
import { fetchProducts } from './store/slices/productsSlice';
import { fetchCart } from './store/slices/cartSlice';
import { trackPageView } from './index';

function App() {
  const dispatch = useDispatch();
  const location = useLocation();
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    // Initial data fetch
    const initializeApp = async () => {
      try {
        await Promise.all([
          dispatch(fetchProducts()),
          dispatch(fetchCart())
        ]);
      } catch (error) {
        console.error('Error initializing app:', error);
      } finally {
        setIsLoading(false);
      }
    };

    initializeApp();
  }, [dispatch]);

  useEffect(() => {
    // Track page views on route change
    trackPageView();
    
    // Scroll to top on route change
    window.scrollTo(0, 0);
  }, [location]);

  if (isLoading) {
    return (
      <div className="ai-loading-container ai-flex-center" style={{ minHeight: '60vh' }}>
        <div className="ai-loading"></div>
      </div>
    );
  }

  return (
    <div className="ai-commerce-app">
      <Routes>
        <Route path="/" element={<HomePage />} />
        <Route path="/shop" element={<ShopPage />} />
        <Route path="/product/:id" element={<ProductPage />} />
        <Route path="/cart" element={<CartPage />} />
        <Route path="/checkout" element={<CheckoutPage />} />
        <Route path="/my-account/*" element={<AccountPage />} />
        <Route path="/search" element={<SearchResults />} />
      </Routes>
      
      {/* AI Recommendations Widget */}
      <AIRecommendations />
    </div>
  );
}

export default App;