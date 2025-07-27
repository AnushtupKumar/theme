import React, { useState, useEffect, useCallback, useRef } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import { debounce } from '../utils/helpers';
import axios from 'axios';

const PredictiveSearch = ({ placeholder = 'Search for products...', showCategories = true, showSuggestions = true }) => {
  const [query, setQuery] = useState('');
  const [isOpen, setIsOpen] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [results, setResults] = useState({
    suggestions: [],
    categories: [],
    products: [],
    ai_insights: {},
    spell_correction: null,
    related_searches: []
  });
  const [selectedIndex, setSelectedIndex] = useState(-1);
  
  const searchRef = useRef(null);
  const dropdownRef = useRef(null);
  const navigate = useNavigate();
  const dispatch = useDispatch();
  
  const userSegment = useSelector(state => state.user.segment);
  const isPersonalizationEnabled = window.aiCommerce?.personalizationEnabled;
  
  // Close dropdown when clicking outside
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (searchRef.current && !searchRef.current.contains(event.target)) {
        setIsOpen(false);
      }
    };
    
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);
  
  // Debounced search function
  const performSearch = useCallback(
    debounce(async (searchQuery) => {
      if (searchQuery.length < 2) {
        setResults({
          suggestions: [],
          categories: [],
          products: [],
          ai_insights: {},
          spell_correction: null,
          related_searches: []
        });
        setIsOpen(false);
        return;
      }
      
      setIsLoading(true);
      
      try {
        const response = await axios.post(
          window.aiPredictiveSearch.searchUrl,
          new URLSearchParams({
            action: 'ai_commerce_predictive_search',
            nonce: window.aiPredictiveSearch.nonce,
            query: searchQuery
          }),
          {
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            }
          }
        );
        
        if (response.data.success) {
          setResults(response.data.data);
          setIsOpen(true);
          
          // Track search analytics
          if (window.aiCommerce) {
            window.aiCommerce.trackEvent('search', {
              query: searchQuery,
              results_count: response.data.data.products.length,
              has_ai_insights: !!response.data.data.ai_insights.intent
            });
          }
        }
      } catch (error) {
        console.error('Search error:', error);
      } finally {
        setIsLoading(false);
      }
    }, window.aiPredictiveSearch.debounceDelay),
    []
  );
  
  // Handle input change
  const handleInputChange = (e) => {
    const value = e.target.value;
    setQuery(value);
    performSearch(value);
  };
  
  // Handle keyboard navigation
  const handleKeyDown = (e) => {
    if (!isOpen) return;
    
    const totalItems = 
      results.suggestions.length + 
      results.categories.length + 
      results.products.length;
    
    switch (e.key) {
      case 'ArrowDown':
        e.preventDefault();
        setSelectedIndex(prev => (prev + 1) % totalItems);
        break;
      case 'ArrowUp':
        e.preventDefault();
        setSelectedIndex(prev => (prev - 1 + totalItems) % totalItems);
        break;
      case 'Enter':
        e.preventDefault();
        if (selectedIndex >= 0) {
          handleItemClick(getItemByIndex(selectedIndex));
        } else {
          handleSearch();
        }
        break;
      case 'Escape':
        setIsOpen(false);
        break;
    }
  };
  
  // Get item by index
  const getItemByIndex = (index) => {
    const allItems = [
      ...results.suggestions.map(s => ({ type: 'suggestion', ...s })),
      ...results.categories.map(c => ({ type: 'category', ...c })),
      ...results.products.map(p => ({ type: 'product', ...p }))
    ];
    return allItems[index];
  };
  
  // Handle item click
  const handleItemClick = (item) => {
    if (!item) return;
    
    switch (item.type) {
      case 'suggestion':
        setQuery(item.text);
        performSearch(item.text);
        break;
      case 'category':
        navigate(`/shop/category/${item.slug}`);
        setIsOpen(false);
        break;
      case 'product':
        navigate(`/product/${item.id}`);
        setIsOpen(false);
        break;
      default:
        handleSearch();
    }
  };
  
  // Handle search submission
  const handleSearch = () => {
    if (query.trim()) {
      navigate(`/search?q=${encodeURIComponent(query)}`);
      setIsOpen(false);
    }
  };
  
  // Render search intent badge
  const renderIntentBadge = (intent) => {
    const badges = {
      purchase: { text: 'Ready to Buy', color: 'green' },
      research: { text: 'Researching', color: 'blue' },
      support: { text: 'Need Help', color: 'orange' },
      browse: { text: 'Browsing', color: 'gray' }
    };
    
    const badge = badges[intent] || badges.browse;
    
    return (
      <span className={`ai-intent-badge ai-intent-${badge.color}`}>
        {badge.text}
      </span>
    );
  };
  
  // Render personalization indicator
  const renderPersonalizationIndicator = (item) => {
    if (!item.is_personalized || !isPersonalizationEnabled) return null;
    
    return (
      <span className="ai-personalized-indicator" title="Personalized for you">
        ✨
      </span>
    );
  };
  
  return (
    <div className="ai-predictive-search" ref={searchRef}>
      <form onSubmit={(e) => { e.preventDefault(); handleSearch(); }} className="ai-search-form">
        <div className="ai-search-input-wrapper">
          <input
            type="search"
            className="ai-search-input"
            placeholder={placeholder}
            value={query}
            onChange={handleInputChange}
            onKeyDown={handleKeyDown}
            onFocus={() => query.length >= 2 && setIsOpen(true)}
            autoComplete="off"
          />
          
          {isLoading ? (
            <div className="ai-search-loading">
              <div className="ai-spinner"></div>
            </div>
          ) : (
            <button type="submit" className="ai-search-submit">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
              </svg>
            </button>
          )}
        </div>
        
        {isOpen && (
          <div className="ai-search-dropdown" ref={dropdownRef}>
            {/* Spell Correction */}
            {results.spell_correction && (
              <div className="ai-search-spell-correction">
                Did you mean: 
                <button 
                  onClick={() => {
                    setQuery(results.spell_correction);
                    performSearch(results.spell_correction);
                  }}
                  className="ai-spell-correction-link"
                >
                  {results.spell_correction}
                </button>
              </div>
            )}
            
            {/* AI Insights */}
            {results.ai_insights.intent && (
              <div className="ai-search-insights">
                {renderIntentBadge(results.ai_insights.intent)}
                {results.ai_insights.urgency === 'high' && (
                  <span className="ai-urgency-indicator">⚡ Urgent</span>
                )}
              </div>
            )}
            
            {/* Suggestions */}
            {showSuggestions && results.suggestions.length > 0 && (
              <div className="ai-search-suggestions">
                <h4>Suggestions</h4>
                {results.suggestions.map((suggestion, index) => (
                  <div
                    key={index}
                    className={`ai-search-suggestion-item ${selectedIndex === index ? 'selected' : ''}`}
                    onClick={() => handleItemClick({ type: 'suggestion', ...suggestion })}
                  >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                      <circle cx="11" cy="11" r="8"></circle>
                      <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <span>{suggestion.text}</span>
                    {suggestion.type === 'ai' && (
                      <span className="ai-badge">AI</span>
                    )}
                    {suggestion.type === 'popular' && (
                      <span className="ai-popular-badge">Popular</span>
                    )}
                  </div>
                ))}
              </div>
            )}
            
            {/* Categories */}
            {showCategories && results.categories.length > 0 && (
              <div className="ai-search-categories">
                <h4>Categories</h4>
                {results.categories.map((category, index) => {
                  const itemIndex = results.suggestions.length + index;
                  return (
                    <div
                      key={category.id}
                      className={`ai-search-category-item ${selectedIndex === itemIndex ? 'selected' : ''}`}
                      onClick={() => handleItemClick({ type: 'category', ...category })}
                    >
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                        <path d="M3 3h18v18H3z"></path>
                        <path d="M3 9h18"></path>
                        <path d="M9 3v18"></path>
                      </svg>
                      <span>{category.name}</span>
                      <span className="ai-category-count">({category.count})</span>
                      {category.confidence > 0.8 && (
                        <span className="ai-confidence-high">High Match</span>
                      )}
                    </div>
                  );
                })}
              </div>
            )}
            
            {/* Products */}
            {results.products.length > 0 && (
              <div className="ai-search-products">
                <h4>Products</h4>
                {results.products.map((product, index) => {
                  const itemIndex = results.suggestions.length + results.categories.length + index;
                  return (
                    <div
                      key={product.id}
                      className={`ai-search-product-item ${selectedIndex === itemIndex ? 'selected' : ''}`}
                      onClick={() => handleItemClick({ type: 'product', ...product })}
                    >
                      {product.image && (
                        <img src={product.image} alt={product.title} />
                      )}
                      <div className="ai-product-info">
                        <h5>
                          {product.title}
                          {renderPersonalizationIndicator(product)}
                        </h5>
                        <p className="ai-product-price" dangerouslySetInnerHTML={{ __html: product.price }} />
                        {!product.in_stock && (
                          <span className="ai-out-of-stock">Out of Stock</span>
                        )}
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
            
            {/* AI Recommendations */}
            {results.ai_insights.recommendations && results.ai_insights.recommendations.length > 0 && (
              <div className="ai-search-recommendations">
                {results.ai_insights.recommendations.map((rec, index) => (
                  <div key={index} className={`ai-recommendation ai-rec-${rec.type}`}>
                    {rec.type === 'tip' && '💡'}
                    {rec.type === 'offer' && '🎁'}
                    <span>{rec.message}</span>
                  </div>
                ))}
              </div>
            )}
            
            {/* Related Searches */}
            {results.related_searches.length > 0 && (
              <div className="ai-related-searches">
                <h4>Related Searches</h4>
                <div className="ai-related-tags">
                  {results.related_searches.map((search, index) => (
                    <button
                      key={index}
                      onClick={() => {
                        setQuery(search);
                        performSearch(search);
                      }}
                      className="ai-related-tag"
                    >
                      {search}
                    </button>
                  ))}
                </div>
              </div>
            )}
            
            {/* User Segment Indicator */}
            {userSegment && isPersonalizationEnabled && (
              <div className="ai-search-footer">
                <span className="ai-segment-indicator">
                  Personalized for: {userSegment}
                </span>
              </div>
            )}
          </div>
        )}
      </form>
    </div>
  );
};

export default PredictiveSearch;