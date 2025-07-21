import React from 'react';
import { CardContent, CardHeader } from './ui/card';
import { Button } from './ui/button';
import { Input } from './ui/input';
import { Avatar, AvatarFallback, AvatarImage } from './ui/avatar';
import { Badge } from './ui/badge';
import { Send, X, Bot, User } from 'lucide-react';

interface Message {
  id: number;
  text: string;
  isUser: boolean;
  timestamp: Date;
}

declare global {
  interface Window {
    APP_CONFIG?: {
      API_BASE: string;
    };
  }
}

export function ChatInterface() {
  const [message, setMessage] = React.useState('');
  const [isTyping, setIsTyping] = React.useState(false);
  const [messages, setMessages] = React.useState<Message[]>([
    {
      id: 1,
      text: "Welcome! I'm here to help you plan your dream trip around the Philippines. Whether you're looking for the best destinations, flights, hotels, or local tips — just type your question and let's start exploring!",
      isUser: false,
      timestamp: new Date()
    }
  ]);
  const messagesEndRef = React.useRef<HTMLDivElement>(null);

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  };

  React.useEffect(() => {
    scrollToBottom();
  }, [messages, isTyping]);

  const handleSend = async () => {
    if (!message.trim()) return;

    const newMessage: Message = {
      id: Date.now(),
      text: message,
      isUser: true,
      timestamp: new Date()
    };

    setMessages(prev => [...prev, newMessage]);
    setMessage('');
    setIsTyping(true);

    // Integrate with existing chat API
    try {
      const response = await fetch(window.APP_CONFIG?.API_BASE + '/chat', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ message: newMessage.text })
      });

      if (response.ok) {
        const data = await response.json();
        const botResponse: Message = {
          id: Date.now() + 1,
          text: data.response || "I understand you're interested in that! Let me help you with some recommendations...",
          isUser: false,
          timestamp: new Date()
        };
        setMessages(prev => [...prev, botResponse]);
      }
    } catch (error) {
      console.error('Chat API error:', error);
      // Fallback response
      const fallbackResponse: Message = {
        id: Date.now() + 1,
        text: "I'm sorry, I'm having trouble connecting right now. Please try again in a moment.",
        isUser: false,
        timestamp: new Date()
      };
      setMessages(prev => [...prev, fallbackResponse]);
    } finally {
      setIsTyping(false);
    }
  };

  const handleKeyPress = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleSend();
    }
  };

  const TypingIndicator = () => (
    <div className="flex justify-start">
      <div className="flex items-center space-x-2 bg-gray-100 rounded-lg px-4 py-2 max-w-xs">
        <Bot className="h-4 w-4 text-gray-500" />
        <div className="flex space-x-1">
          <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
          <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{ animationDelay: '0.1s' }}></div>
          <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{ animationDelay: '0.2s' }}></div>
        </div>
      </div>
    </div>
  );

  return (
    <div className="flex flex-col h-full bg-white rounded-lg shadow-sm">
      {/* Chat Header */}
      <CardHeader className="border-b border-gray-200 pb-4 rounded-t-lg">
        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-3">
            <div className="relative">
              <Avatar className="h-12 w-12 border-2 border-green-400">
                <AvatarImage src="/image/delai.png" alt="Delai" />
                <AvatarFallback className="bg-blue-100 text-blue-600">AI</AvatarFallback>
              </Avatar>
              <div className="absolute bottom-0 right-0 w-3 h-3 bg-green-400 rounded-full border-2 border-white"></div>
            </div>
            <div>
              <h3 className="font-semibold text-gray-900">Delai</h3>
              <div className="flex items-center space-x-2">
                <Badge variant="secondary" className="bg-green-100 text-green-700 text-xs">
                  Online
                </Badge>
                <span className="text-sm text-gray-600">Your AI Travel Companion</span>
              </div>
            </div>
          </div>
          <Button
            variant="ghost"
            size="icon"
            onClick={() => {
              const closeBtn = document.getElementById('closeChatBtn');
              if (closeBtn) closeBtn.click();
            }}
            className="hover:bg-gray-100"
          >
            <X className="h-5 w-5" />
          </Button>
        </div>
      </CardHeader>

      {/* Messages */}
      <CardContent className="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50">
        {messages.map((msg) => (
          <div
            key={msg.id}
            className={`flex items-start space-x-2 ${msg.isUser ? 'justify-end' : 'justify-start'}`}
          >
            {!msg.isUser && (
              <div className="flex-shrink-0">
                <Bot className="h-6 w-6 text-gray-500 mt-1" />
              </div>
            )}
            <div
              className={`max-w-xs lg:max-w-md px-4 py-3 rounded-2xl shadow-sm ${
                msg.isUser
                  ? 'bg-blue-600 text-white rounded-br-md'
                  : 'bg-white text-gray-900 rounded-bl-md border border-gray-200'
              }`}
            >
              <p className="text-sm leading-relaxed">{msg.text}</p>
              <p className={`text-xs mt-2 ${
                msg.isUser ? 'text-blue-100' : 'text-gray-500'
              }`}>
                {msg.timestamp.toLocaleTimeString([], {
                  hour: '2-digit',
                  minute: '2-digit'
                })}
              </p>
            </div>
            {msg.isUser && (
              <div className="flex-shrink-0">
                <User className="h-6 w-6 text-gray-500 mt-1" />
              </div>
            )}
          </div>
        ))}

        {isTyping && <TypingIndicator />}
        <div ref={messagesEndRef} />
      </CardContent>

      {/* Input Area */}
      <div className="border-t border-gray-200 p-4 bg-white rounded-b-lg">
        <div className="flex space-x-3">
          <div className="flex-1 relative">
            <Input
              value={message}
              onChange={(e) => setMessage(e.target.value)}
              onKeyPress={handleKeyPress}
              placeholder="Ask anything, the more you share the better I can help..."
              className="pr-12 py-3 rounded-full border-gray-300 focus:border-blue-500 focus:ring-blue-500"
              disabled={isTyping}
            />
            <Button
              onClick={handleSend}
              size="icon"
              className="absolute right-1 top-1 h-8 w-8 rounded-full"
              disabled={!message.trim() || isTyping}
            >
              <Send className="h-4 w-4" />
            </Button>
          </div>
        </div>

        {/* Quick Actions */}
        <div className="flex flex-wrap gap-2 mt-4">
          <Button
            variant="outline"
            size="sm"
            onClick={() => setMessage("Find me hotels in Manila")}
            className="rounded-full text-xs px-3 py-1 hover:bg-blue-50 hover:border-blue-300"
            disabled={isTyping}
          >
            🏨 Hotels in Manila
          </Button>
          <Button
            variant="outline"
            size="sm"
            onClick={() => setMessage("Show me flights to Cebu")}
            className="rounded-full text-xs px-3 py-1 hover:bg-blue-50 hover:border-blue-300"
            disabled={isTyping}
          >
            ✈️ Flights to Cebu
          </Button>
          <Button
            variant="outline"
            size="sm"
            onClick={() => setMessage("Plan a 3-day itinerary for Palawan")}
            className="rounded-full text-xs px-3 py-1 hover:bg-blue-50 hover:border-blue-300"
            disabled={isTyping}
          >
            🗺️ Palawan Itinerary
          </Button>
          <Button
            variant="outline"
            size="sm"
            onClick={() => setMessage("What's the weather like in Boracay?")}
            className="rounded-full text-xs px-3 py-1 hover:bg-blue-50 hover:border-blue-300"
            disabled={isTyping}
          >
            🌤️ Boracay Weather
          </Button>
        </div>
      </div>
    </div>
  );
}
