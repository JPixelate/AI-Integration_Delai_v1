import React from 'react';
import { Button } from './ui/button';
import { Menu, X, MessageCircle } from 'lucide-react';

export function ModernHeader() {
  const [isMenuOpen, setIsMenuOpen] = React.useState(false);

  return (
    <header className="bg-white border-b border-gray-200 sticky top-0 z-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-16">
          {/* Logo */}
          <div className="flex items-center">
            <img 
              src="/icon/delightful_ph_logo-01.png" 
              alt="Delightful Philippines" 
              className="h-8 w-auto"
            />
          </div>

          {/* Desktop Navigation */}
          <nav className="hidden md:flex items-center space-x-8">
            <a href="#" className="text-gray-600 hover:text-gray-900 transition-colors">
              Home
            </a>
            <a href="#" className="text-gray-600 hover:text-gray-900 transition-colors">
              About us
            </a>
            <a href="#" className="text-gray-600 hover:text-gray-900 transition-colors">
              Travel News
            </a>
            <a href="#" className="text-gray-600 hover:text-gray-900 transition-colors">
              Trip Planner
            </a>
            <a href="#" className="text-gray-600 hover:text-gray-900 transition-colors">
              Destinations
            </a>
            <a href="#" className="text-gray-600 hover:text-gray-900 transition-colors">
              Contact us
            </a>
            <a href="#" className="text-gray-600 hover:text-gray-900 transition-colors">
              Gallery
            </a>
          </nav>

          {/* CTA Button */}
          <div className="hidden md:flex items-center space-x-4">
            <Button 
              variant="outline" 
              className="flex items-center space-x-2"
              onClick={() => {
                const chatBtn = document.getElementById('openChatBtn');
                if (chatBtn) chatBtn.click();
              }}
            >
              <MessageCircle className="h-4 w-4" />
              <span>Start chatting</span>
            </Button>
          </div>

          {/* Mobile menu button */}
          <div className="md:hidden">
            <Button
              variant="ghost"
              size="icon"
              onClick={() => setIsMenuOpen(!isMenuOpen)}
            >
              {isMenuOpen ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
            </Button>
          </div>
        </div>

        {/* Mobile Navigation */}
        {isMenuOpen && (
          <div className="md:hidden">
            <div className="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-white border-t border-gray-200">
              <a href="#" className="block px-3 py-2 text-gray-600 hover:text-gray-900">
                Home
              </a>
              <a href="#" className="block px-3 py-2 text-gray-600 hover:text-gray-900">
                About us
              </a>
              <a href="#" className="block px-3 py-2 text-gray-600 hover:text-gray-900">
                Travel News
              </a>
              <a href="#" className="block px-3 py-2 text-gray-600 hover:text-gray-900">
                Trip Planner
              </a>
              <a href="#" className="block px-3 py-2 text-gray-600 hover:text-gray-900">
                Destinations
              </a>
              <a href="#" className="block px-3 py-2 text-gray-600 hover:text-gray-900">
                Contact us
              </a>
              <a href="#" className="block px-3 py-2 text-gray-600 hover:text-gray-900">
                Gallery
              </a>
              <div className="px-3 py-2">
                <Button 
                  className="w-full flex items-center justify-center space-x-2"
                  onClick={() => {
                    const chatBtn = document.getElementById('openChatBtn');
                    if (chatBtn) chatBtn.click();
                    setIsMenuOpen(false);
                  }}
                >
                  <MessageCircle className="h-4 w-4" />
                  <span>Start chatting</span>
                </Button>
              </div>
            </div>
          </div>
        )}
      </div>
    </header>
  );
}
