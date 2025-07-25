# AI Commerce Theme

A modern, AI-powered WordPress theme designed for WooCommerce with personalization, cart abandonment recovery, and lightning-fast SPA architecture.

## Features

### 🤖 AI-Powered Features
- **Personalized Product Recommendations**: AI analyzes user behavior to suggest relevant products
- **Smart Cart Recovery**: Intelligent exit-intent detection and idle time monitoring to recover abandoned carts
- **AI Chatbot**: Built-in shopping assistant to help customers find products and answer questions
- **Dynamic Layout Optimization**: AI-driven layout changes based on user preferences and behavior

### ⚡ Performance & Architecture
- **Single Page Application (SPA)**: React-based frontend for seamless navigation
- **SEO Optimized**: Server-side rendering support and structured data
- **Lightning Fast**: Optimized asset loading, code splitting, and lazy loading
- **Progressive Web App Ready**: Offline support and app-like experience

### 🛒 WooCommerce Integration
- Full WooCommerce compatibility
- Custom product layouts
- Enhanced checkout experience
- AJAX cart updates
- Quick view functionality

### 🎨 Customization
- **WordPress Customizer Integration**: Live preview of all changes
- **Color Schemes**: Customizable primary, secondary, and accent colors
- **Typography Options**: Choose from popular web fonts
- **Layout Controls**: Adjust container width, products per row, etc.
- **Component-based Architecture**: Easy to extend and modify

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- WooCommerce 5.0 or higher
- Node.js 14+ and npm (for development)

## Installation

1. **Download the theme** and upload to your WordPress `/wp-content/themes/` directory

2. **Activate the theme** from WordPress Admin > Appearance > Themes

3. **Install dependencies** (for development):
   ```bash
   cd wp-content/themes/ai-commerce-theme
   npm install
   ```

4. **Build assets**:
   ```bash
   npm run build
   ```

5. **Configure AI Settings**:
   - Go to Appearance > Customize > AI Settings
   - Add your OpenAI API key
   - Enable/disable AI features as needed

## Development

### Setup Development Environment

1. Install dependencies:
   ```bash
   npm install
   ```

2. Start development server with hot reload:
   ```bash
   npm run dev
   ```

3. Build for production:
   ```bash
   npm run build
   ```

### Project Structure

```
ai-commerce-theme/
├── assets/              # Compiled assets
├── inc/                 # PHP includes
│   ├── ai/             # AI-related classes
│   ├── customizer/     # Customizer settings
│   ├── woocommerce/    # WooCommerce customizations
│   └── api/            # REST API endpoints
├── src/                 # React source files
│   ├── components/     # React components
│   ├── store/          # Redux store
│   ├── styles/         # SCSS files
│   └── utils/          # Utility functions
├── templates/           # PHP templates
├── style.css           # Theme information
├── functions.php       # Theme functions
├── package.json        # Node dependencies
└── webpack.config.js   # Webpack configuration
```

## Configuration

### AI Settings

Configure AI features in WordPress Customizer:

1. **API Key**: Add your OpenAI API key
2. **Personalization**: Enable/disable AI-powered product recommendations
3. **Cart Recovery**: Configure abandoned cart recovery settings
4. **Chatbot**: Enable/disable AI shopping assistant

### Performance Settings

1. **Lazy Loading**: Enable for images
2. **Resource Preloading**: Preload critical resources
3. **Cache Duration**: Set AI recommendation cache time

## Customization Guide

### Adding Custom Components

1. Create component in `src/components/`
2. Import in relevant page component
3. Build assets: `npm run build`

### Modifying AI Behavior

Edit AI logic in `inc/ai/class-ai-engine.php`:
- Customize recommendation algorithms
- Adjust behavior tracking
- Modify chatbot responses

### Styling

1. Global styles: `src/styles/main.scss`
2. Component styles: Use CSS modules or styled-components
3. Theme variables: Customize in WordPress Customizer

## API Endpoints

The theme provides REST API endpoints for SPA functionality:

- `GET /wp-json/ai-commerce/v1/products` - Get products
- `GET /wp-json/ai-commerce/v1/recommendations` - Get AI recommendations
- `POST /wp-json/ai-commerce/v1/chat` - Chat with AI assistant
- `POST /wp-json/ai-commerce/v1/track` - Track user behavior

## Hooks and Filters

### Actions
- `ai_commerce_before_recommendations` - Before displaying recommendations
- `ai_commerce_after_cart_recovery` - After cart recovery attempt
- `ai_commerce_chatbot_init` - When chatbot initializes

### Filters
- `ai_commerce_recommendation_count` - Number of recommendations to show
- `ai_commerce_cart_recovery_delay` - Delay before showing recovery modal
- `ai_commerce_ai_prompt` - Modify AI prompts

## Troubleshooting

### Common Issues

1. **AI features not working**
   - Check API key in Customizer
   - Verify API endpoint is accessible
   - Check browser console for errors

2. **Build errors**
   - Delete `node_modules` and reinstall
   - Check Node.js version (14+ required)
   - Clear npm cache: `npm cache clean --force`

3. **Performance issues**
   - Enable caching plugins
   - Optimize images
   - Check server resources

## Support

- Documentation: [Link to docs]
- Support Forum: [Link to forum]
- GitHub Issues: [Link to repo]

## License

GPL v2 or later

## Credits

- Built with React, Redux, and WordPress
- AI powered by OpenAI
- Icons from Heroicons
- Fonts from Google Fonts

## Changelog

### Version 1.0.0
- Initial release
- AI-powered recommendations
- Cart abandonment recovery
- SPA architecture
- WooCommerce integration
- Customizer options