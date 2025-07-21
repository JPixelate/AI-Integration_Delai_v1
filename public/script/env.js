(function () {
    const hostname = window.location.hostname;
  
    let API_BASE = '';
  
    if (hostname === 'localhost' || hostname === '127.0.0.1') {
      // Localhost
      API_BASE = '/ai-agent-integration/public/api';
    } else {
      // Staging or production
      API_BASE = '/public/api';
    }
  
    window.APP_CONFIG = {
      API_BASE: API_BASE
    };
  })();
  