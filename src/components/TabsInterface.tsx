
import React from 'react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from './ui/tabs';
import { Card, CardContent, CardHeader, CardTitle } from './ui/card';
import { Button } from './ui/button';
import { Input } from './ui/input';
import { Skeleton } from './ui/skeleton';
import { Badge } from './ui/badge';
import { Hotel, Plane, Car, MapPin, Calendar, Users, Search, Star, MapIcon } from 'lucide-react';

const LoadingState: React.FC = () => (
  <div className="space-y-6">
    <Card className="shadow-lg border-0">
      <CardHeader>
        <Skeleton className="h-6 w-48" />
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div className="space-y-2">
            <Skeleton className="h-4 w-20" />
            <Skeleton className="h-12 w-full" />
          </div>
          <div className="space-y-2">
            <Skeleton className="h-4 w-24" />
            <Skeleton className="h-12 w-full" />
          </div>
        </div>
        <Skeleton className="h-12 w-full" />
      </CardContent>
    </Card>

    <div className="space-y-4">
      <Skeleton className="h-6 w-32" />
      {[1, 2, 3].map((i) => (
        <Card key={i}>
          <CardContent className="p-4">
            <div className="flex space-x-4">
              <Skeleton className="w-24 h-24 rounded-lg" />
              <div className="flex-1 space-y-2">
                <Skeleton className="h-5 w-32" />
                <Skeleton className="h-4 w-24" />
                <Skeleton className="h-4 w-40" />
                <div className="flex justify-between items-center">
                  <Skeleton className="h-6 w-16" />
                  <Skeleton className="h-8 w-20" />
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      ))}
    </div>
  </div>
);

export function TabsInterface() {
  const [activeTab, setActiveTab] = React.useState("hotels");
  const [isLoading, setIsLoading] = React.useState(false);

  const handleTabChange = (value: string) => {
    setActiveTab(value);
    setIsLoading(true);
    // Simulate loading
    setTimeout(() => setIsLoading(false), 800);
  };

  return (
    <div className="h-full bg-gradient-to-br from-gray-50 to-gray-100">
      <Tabs value={activeTab} onValueChange={handleTabChange} className="h-full flex flex-col">
        <div className="border-b border-gray-200 bg-white px-6 py-4 shadow-sm">
          <TabsList className="grid w-full grid-cols-5 bg-gray-100 p-1 rounded-lg">
            <TabsTrigger value="hotels" className="flex items-center space-x-2 data-[state=active]:bg-white data-[state=active]:shadow-sm">
              <Hotel className="h-4 w-4" />
              <span className="hidden sm:inline">Hotels</span>
            </TabsTrigger>
            <TabsTrigger value="flights" className="flex items-center space-x-2 data-[state=active]:bg-white data-[state=active]:shadow-sm">
              <Plane className="h-4 w-4" />
              <span className="hidden sm:inline">Flights</span>
            </TabsTrigger>
            <TabsTrigger value="cars" className="flex items-center space-x-2 data-[state=active]:bg-white data-[state=active]:shadow-sm">
              <Car className="h-4 w-4" />
              <span className="hidden sm:inline">Cars</span>
            </TabsTrigger>
            <TabsTrigger value="guide" className="flex items-center space-x-2 data-[state=active]:bg-white data-[state=active]:shadow-sm">
              <MapPin className="h-4 w-4" />
              <span className="hidden sm:inline">Guide</span>
            </TabsTrigger>
            <TabsTrigger value="summary" className="flex items-center space-x-2 data-[state=active]:bg-white data-[state=active]:shadow-sm">
              <Calendar className="h-4 w-4" />
              <span className="hidden sm:inline">Summary</span>
            </TabsTrigger>
          </TabsList>
        </div>

        <div className="flex-1 overflow-y-auto p-6">
          <TabsContent value="hotels" className="mt-0">
            {isLoading ? (
              <LoadingState />
            ) : (
              <div className="space-y-6">
                <Card className="shadow-lg border-0">
                  <CardHeader className="bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-t-lg">
                    <CardTitle className="flex items-center space-x-2">
                      <Hotel className="h-5 w-5" />
                      <span>Find Your Perfect Stay</span>
                    </CardTitle>
                  </CardHeader>
                  <CardContent className="p-6 space-y-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                          Destination
                        </label>
                        <div className="relative">
                          <MapIcon className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                          <Input
                            type="text"
                            placeholder="Where are you going?"
                            className="pl-10 h-12"
                          />
                        </div>
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                          Check-in / Check-out
                        </label>
                        <Input
                          type="text"
                          placeholder="Select dates"
                          className="h-12"
                        />
                      </div>
                    </div>
                    <div className="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                      <div className="flex items-center space-x-2">
                        <Users className="h-4 w-4 text-gray-500" />
                        <span className="text-sm text-gray-700">2 guests • 1 room</span>
                      </div>
                      <Button variant="outline" size="sm">
                        Edit
                      </Button>
                    </div>
                    <Button className="w-full h-12 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800">
                      <Search className="h-4 w-4 mr-2" />
                      Search Hotels
                    </Button>
                  </CardContent>
                </Card>

                {/* Sample Hotel Results */}
                <div className="space-y-4">
                  <h3 className="text-lg font-semibold text-gray-900">Popular Hotels in Manila</h3>
                  <div className="grid gap-4">
                    {[
                      { name: "Luxury Hotel Manila", features: ["WiFi", "Pool", "Spa"], rating: 4.5 },
                      { name: "Business Center Hotel", features: ["WiFi", "Gym", "Restaurant"], rating: 4.2 },
                      { name: "Boutique Hotel Makati", features: ["WiFi", "Bar", "Rooftop"], rating: 4.0 }
                    ].map((hotel, i) => (
                      <Card key={i} className="hover:shadow-lg transition-shadow cursor-pointer border-l-4 border-l-blue-500">
                        <CardContent className="p-4">
                          <div className="flex space-x-4">
                            <div className="w-24 h-24 bg-gradient-to-br from-blue-100 to-blue-200 rounded-lg flex-shrink-0 flex items-center justify-center">
                              <Hotel className="h-8 w-8 text-blue-600" />
                            </div>
                            <div className="flex-1">
                              <h4 className="font-semibold text-gray-900">{hotel.name}</h4>
                              <div className="flex items-center space-x-1 mt-1">
                                {[...Array(5)].map((_, j) => (
                                  <Star key={j} className={`h-3 w-3 ${j < Math.floor(hotel.rating) ? 'text-yellow-400 fill-current' : 'text-gray-300'}`} />
                                ))}
                                <span className="text-xs text-gray-600 ml-1">{hotel.rating}</span>
                              </div>
                              <p className="text-sm text-gray-600 mt-1">Makati City • 0.5 km from center</p>
                              <div className="flex flex-wrap gap-1 mt-2">
                                {hotel.features.map((feature, idx) => (
                                  <Badge key={idx} variant="secondary" className="text-xs">
                                    {feature}
                                  </Badge>
                                ))}
                              </div>
                              <div className="flex items-center justify-between mt-3">
                                <div>
                                  <span className="text-lg font-bold text-blue-600">₱{2500 + i * 500}</span>
                                  <span className="text-sm text-gray-500 ml-1">per night</span>
                                </div>
                                <Button size="sm" className="bg-blue-600 hover:bg-blue-700">
                                  View Details
                                </Button>
                              </div>
                            </div>
                          </div>
                        </CardContent>
                      </Card>
                    ))}
                  </div>
                </div>
              </div>
            )}
          </TabsContent>

          <TabsContent value="flights" className="mt-0">
            {isLoading ? (
              <LoadingState />
            ) : (
              <div className="space-y-6">
                <Card className="shadow-lg border-0">
                  <CardHeader className="bg-gradient-to-r from-green-600 to-green-700 text-white rounded-t-lg">
                    <CardTitle className="flex items-center space-x-2">
                      <Plane className="h-5 w-5" />
                      <span>Find Your Flight</span>
                    </CardTitle>
                  </CardHeader>
                  <CardContent className="p-6 space-y-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                          From
                        </label>
                        <Input
                          type="text"
                          placeholder="Departure city"
                          className="h-12"
                        />
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                          To
                        </label>
                        <Input
                          type="text"
                          placeholder="Destination city"
                          className="h-12"
                        />
                      </div>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                          Departure
                        </label>
                        <Input
                          type="date"
                          className="h-12"
                        />
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                          Return
                        </label>
                        <Input
                          type="date"
                          className="h-12"
                        />
                      </div>
                    </div>
                    <div className="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                      <div className="flex items-center space-x-2">
                        <Users className="h-4 w-4 text-gray-500" />
                        <span className="text-sm text-gray-700">1 passenger • Economy</span>
                      </div>
                      <Button variant="outline" size="sm">
                        Edit
                      </Button>
                    </div>
                    <Button className="w-full h-12 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800">
                      <Search className="h-4 w-4 mr-2" />
                      Search Flights
                    </Button>
                  </CardContent>
                </Card>

                {/* Sample Flight Results */}
                <div className="space-y-4">
                  <h3 className="text-lg font-semibold text-gray-900">Popular Routes</h3>
                  <div className="grid gap-4">
                    {[
                      { from: "Manila", to: "Cebu", price: "₱3,500", airline: "Philippine Airlines" },
                      { from: "Manila", to: "Davao", price: "₱4,200", airline: "Cebu Pacific" },
                      { from: "Manila", to: "Iloilo", price: "₱2,800", airline: "Philippine Airlines" }
                    ].map((flight, i) => (
                      <Card key={i} className="hover:shadow-lg transition-shadow cursor-pointer">
                        <CardContent className="p-4">
                          <div className="flex items-center justify-between">
                            <div className="flex-1">
                              <div className="flex items-center space-x-4">
                                <div className="text-center">
                                  <p className="font-semibold">{flight.from}</p>
                                  <p className="text-sm text-gray-600">MNL</p>
                                </div>
                                <div className="flex-1 flex items-center justify-center">
                                  <div className="w-full h-px bg-gray-300 relative">
                                    <Plane className="h-4 w-4 absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white text-gray-600" />
                                  </div>
                                </div>
                                <div className="text-center">
                                  <p className="font-semibold">{flight.to}</p>
                                  <p className="text-sm text-gray-600">CEB</p>
                                </div>
                              </div>
                              <p className="text-sm text-gray-600 mt-2">{flight.airline}</p>
                            </div>
                            <div className="text-right ml-4">
                              <p className="text-lg font-bold text-green-600">{flight.price}</p>
                              <Button size="sm">Select</Button>
                            </div>
                          </div>
                        </CardContent>
                      </Card>
                    ))}
                  </div>
                </div>
              </div>
            )}
          </TabsContent>

          <TabsContent value="cars" className="mt-0">
            {isLoading ? (
              <LoadingState />
            ) : (
              <Card className="shadow-lg border-0">
                <CardHeader className="bg-gradient-to-r from-purple-600 to-purple-700 text-white rounded-t-lg">
                  <CardTitle className="flex items-center space-x-2">
                    <Car className="h-5 w-5" />
                    <span>Car Rental</span>
                  </CardTitle>
                </CardHeader>
                <CardContent className="p-8">
                  <div className="text-center space-y-4">
                    <div className="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto">
                      <Car className="h-8 w-8 text-purple-600" />
                    </div>
                    <h3 className="text-lg font-semibold text-gray-900">Car Rental Coming Soon</h3>
                    <p className="text-gray-600 max-w-md mx-auto">
                      We're working on bringing you the best car rental deals. Stay tuned for updates!
                    </p>
                    <Button variant="outline" className="mt-4">
                      Notify Me When Available
                    </Button>
                  </div>
                </CardContent>
              </Card>
            )}
          </TabsContent>

          <TabsContent value="guide" className="mt-0">
            {isLoading ? (
              <LoadingState />
            ) : (
              <Card className="shadow-lg border-0">
                <CardHeader className="bg-gradient-to-r from-orange-600 to-orange-700 text-white rounded-t-lg">
                  <CardTitle className="flex items-center space-x-2">
                    <MapPin className="h-5 w-5" />
                    <span>Travel Guide</span>
                  </CardTitle>
                </CardHeader>
                <CardContent className="p-8">
                  <div className="text-center space-y-4">
                    <div className="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto">
                      <MapPin className="h-8 w-8 text-orange-600" />
                    </div>
                    <h3 className="text-lg font-semibold text-gray-900">Travel Guide & Attractions</h3>
                    <p className="text-gray-600 max-w-md mx-auto">
                      Discover amazing destinations, local attractions, and hidden gems. Coming soon!
                    </p>
                    <Button variant="outline" className="mt-4">
                      Explore Destinations
                    </Button>
                  </div>
                </CardContent>
              </Card>
            )}
          </TabsContent>

          <TabsContent value="summary" className="mt-0">
            {isLoading ? (
              <LoadingState />
            ) : (
              <Card className="shadow-lg border-0">
                <CardHeader className="bg-gradient-to-r from-indigo-600 to-indigo-700 text-white rounded-t-lg">
                  <CardTitle className="flex items-center space-x-2">
                    <Calendar className="h-5 w-5" />
                    <span>Trip Summary</span>
                  </CardTitle>
                </CardHeader>
                <CardContent className="p-8">
                  <div className="text-center space-y-4">
                    <div className="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto">
                      <Calendar className="h-8 w-8 text-indigo-600" />
                    </div>
                    <h3 className="text-lg font-semibold text-gray-900">Your Trip Summary</h3>
                    <p className="text-gray-600 max-w-md mx-auto">
                      Once you start planning, your complete itinerary and bookings will appear here.
                    </p>
                    <Button variant="outline" className="mt-4">
                      Start Planning
                    </Button>
                  </div>
                </CardContent>
              </Card>
            )}
          </TabsContent>
        </div>
      </Tabs>
    </div>
  );
}
