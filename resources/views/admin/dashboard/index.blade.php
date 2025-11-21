@extends('admin.layouts.app')

@section('title', 'Dashboard')

@push('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.7.2/css/all.min.css">
    <style>
        .dashboard-card {
            transition: all 0.3s ease;
        }
        .dashboard-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .alert-item {
            transition: all 0.2s ease;
        }
        .alert-item:hover {
            background-color: #f9fafb;
        }
        .chart-container {
            position: relative;
            width: 100%;
        }
    </style>
@endpush

@section('content')
<div x-data="dashboardData()" x-init="init()" class="p-6">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Dashboard Overview</h1>
            <p class="text-gray-600">Rental & Sales Management System</p>
        </div>

        <!-- Section 1: Task Badges -->
        <div class="mb-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Block 1: Schedule -->
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-300">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-clock w-6 h-6 text-blue-600 mr-3"></i>
                        <h3 class="text-lg font-semibold text-gray-800">Schedule</h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <!-- Deliveries - Truck -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-300 p-5 cursor-pointer hover:shadow-lg hover:border-blue-400 transition-all duration-300 group">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-2.5 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 group-hover:scale-110 transition-transform duration-200">
                                    <i class="fas fa-truck text-white"></i>
                                </div>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-800 mb-4 leading-tight">Deliveries - Truck</h3>
                            <div class="flex items-center">
                                <div class="flex-1 flex flex-col items-center justify-center">
                                    <div class="text-2xl font-bold text-gray-400 mb-1">0</div>
                                    <div class="text-xs text-gray-500 font-medium tracking-wide text-center">DUE</div>
                                </div>
                                <div class="h-12 w-px bg-gray-300"></div>
                                <div class="flex-1 flex flex-col items-center justify-center">
                                    <div class="text-2xl font-bold text-green-600 mb-1">15</div>
                                    <div class="text-xs text-gray-500 font-medium tracking-wide text-center">COMPLETED</div>
                                </div>
                            </div>
                            <div class="mt-4 pt-3 border-t border-gray-300">
                                <div class="flex items-center justify-center">
                                    <div class="flex items-center bg-gradient-to-r from-yellow-400 to-amber-500 px-3 py-1.5 rounded-full shadow-sm">
                                        <i class="fas fa-trophy text-white mr-1.5"></i>
                                        <span class="text-white font-semibold text-xs tracking-wide">ALL DONE!</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Deliveries - In Store -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-300 p-5 cursor-pointer hover:shadow-lg hover:border-blue-400 transition-all duration-300 group">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-2.5 rounded-lg bg-gradient-to-br from-green-500 to-green-600 group-hover:scale-110 transition-transform duration-200">
                                    <i class="fas fa-store text-white"></i>
                                </div>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-800 mb-4 leading-tight">Deliveries - In Store</h3>
                            <div class="flex items-center">
                                <div class="flex-1 flex flex-col items-center justify-center">
                                    <div class="text-2xl font-bold text-red-600 mb-1">3</div>
                                    <div class="text-xs text-gray-500 font-medium tracking-wide text-center">DUE</div>
                                </div>
                                <div class="h-12 w-px bg-gray-300"></div>
                                <div class="flex-1 flex flex-col items-center justify-center">
                                    <div class="text-2xl font-bold text-green-600 mb-1">8</div>
                                    <div class="text-xs text-gray-500 font-medium tracking-wide text-center">COMPLETED</div>
                                </div>
                            </div>
                            <div class="mt-4 pt-3 border-t border-gray-300">
                                <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                                    <span>Progress</span>
                                    <span>73%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full bg-gradient-to-r from-green-400 to-green-500 transition-all duration-500" style="width: 73%"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Returns - Truck -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-300 p-5 cursor-pointer hover:shadow-lg hover:border-blue-400 transition-all duration-300 group">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-2.5 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 group-hover:scale-110 transition-transform duration-200">
                                    <i class="fas fa-truck text-white"></i>
                                </div>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-800 mb-4 leading-tight">Returns - Truck</h3>
                            <div class="flex items-center">
                                <div class="flex-1 flex flex-col items-center justify-center">
                                    <div class="text-2xl font-bold text-red-600 mb-1">2</div>
                                    <div class="text-xs text-gray-500 font-medium tracking-wide text-center">DUE</div>
                                </div>
                                <div class="h-12 w-px bg-gray-300"></div>
                                <div class="flex-1 flex flex-col items-center justify-center">
                                    <div class="text-2xl font-bold text-green-600 mb-1">12</div>
                                    <div class="text-xs text-gray-500 font-medium tracking-wide text-center">COMPLETED</div>
                                </div>
                            </div>
                            <div class="mt-4 pt-3 border-t border-gray-300">
                                <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                                    <span>Progress</span>
                                    <span>86%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full bg-gradient-to-r from-purple-400 to-purple-500 transition-all duration-500" style="width: 86%"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Returns - In Store -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-300 p-5 cursor-pointer hover:shadow-lg hover:border-blue-400 transition-all duration-300 group">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-2.5 rounded-lg bg-gradient-to-br from-indigo-500 to-indigo-600 group-hover:scale-110 transition-transform duration-200">
                                    <i class="fas fa-store text-white"></i>
                                </div>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-800 mb-4 leading-tight">Returns - In Store</h3>
                            <div class="flex items-center">
                                <div class="flex-1 flex flex-col items-center justify-center">
                                    <div class="text-2xl font-bold text-red-600 mb-1">1</div>
                                    <div class="text-xs text-gray-500 font-medium tracking-wide text-center">DUE</div>
                                </div>
                                <div class="h-12 w-px bg-gray-300"></div>
                                <div class="flex-1 flex flex-col items-center justify-center">
                                    <div class="text-2xl font-bold text-green-600 mb-1">6</div>
                                    <div class="text-xs text-gray-500 font-medium tracking-wide text-center">COMPLETED</div>
                                </div>
                            </div>
                            <div class="mt-4 pt-3 border-t border-gray-300">
                                <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                                    <span>Progress</span>
                                    <span>86%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full bg-gradient-to-r from-indigo-400 to-indigo-500 transition-all duration-500" style="width: 86%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Block 2: Operations -->
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-300">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-wrench w-6 h-6 text-orange-600 mr-3"></i>
                        <h3 class="text-lg font-semibold text-gray-800">Operations</h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <!-- Maintenance Hold -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-300 p-5 cursor-pointer hover:shadow-lg hover:border-blue-400 transition-all duration-300 group">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-2.5 rounded-lg bg-gradient-to-br from-orange-500 to-orange-600 group-hover:scale-110 transition-transform duration-200">
                                    <i class="fas fa-wrench text-white"></i>
                                </div>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-800 mb-4 leading-tight">Maintenance Hold</h3>
                            <div class="flex items-center">
                                <div class="flex-1 flex flex-col items-center justify-center">
                                    <div class="text-2xl font-bold text-red-600 mb-1">4</div>
                                    <div class="text-xs text-gray-500 font-medium tracking-wide text-center">DUE</div>
                                </div>
                                <div class="h-12 w-px bg-gray-300"></div>
                                <div class="flex-1 flex flex-col items-center justify-center">
                                    <div class="text-2xl font-bold text-green-600 mb-1">3</div>
                                    <div class="text-xs text-gray-500 font-medium tracking-wide text-center">COMPLETED</div>
                                </div>
                            </div>
                            <div class="mt-4 pt-3 border-t border-gray-300">
                                <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                                    <span>Progress</span>
                                    <span>43%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full bg-gradient-to-r from-orange-400 to-orange-500 transition-all duration-500" style="width: 43%"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Damaged Items -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-300 p-5 cursor-pointer hover:shadow-lg hover:border-blue-400 transition-all duration-300 group">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-2.5 rounded-lg bg-gradient-to-br from-red-500 to-red-600 group-hover:scale-110 transition-transform duration-200">
                                    <i class="fas fa-exclamation-triangle text-white"></i>
                                </div>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-800 mb-4 leading-tight">Damaged Items</h3>
                            <div class="flex items-center">
                                <div class="flex-1 flex flex-col items-center justify-center">
                                    <div class="text-2xl font-bold text-red-600 mb-1">1</div>
                                    <div class="text-xs text-gray-500 font-medium tracking-wide text-center">DUE</div>
                                </div>
                                <div class="h-12 w-px bg-gray-300"></div>
                                <div class="flex-1 flex flex-col items-center justify-center">
                                    <div class="text-2xl font-bold text-green-600 mb-1">2</div>
                                    <div class="text-xs text-gray-500 font-medium tracking-wide text-center">COMPLETED</div>
                                </div>
                            </div>
                            <div class="mt-4 pt-3 border-t border-gray-300">
                                <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                                    <span>Progress</span>
                                    <span>67%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full bg-gradient-to-r from-red-400 to-red-500 transition-all duration-500" style="width: 67%"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Tasks Overdue -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-300 p-5 cursor-pointer hover:shadow-lg hover:border-blue-400 transition-all duration-300 group">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-2.5 rounded-lg bg-gradient-to-br from-red-500 to-red-600 group-hover:scale-110 transition-transform duration-200">
                                    <i class="fas fa-clock text-white"></i>
                                </div>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-800 mb-4 leading-tight">Tasks Overdue</h3>
                            <div class="flex items-center">
                                <div class="flex-1 flex flex-col items-center justify-center">
                                    <div class="text-2xl font-bold text-red-600 mb-1">2</div>
                                    <div class="text-xs text-gray-500 font-medium tracking-wide text-center">DUE</div>
                                </div>
                                <div class="h-12 w-px bg-gray-300"></div>
                                <div class="flex-1 flex flex-col items-center justify-center">
                                    <div class="text-2xl font-bold text-green-600 mb-1">0</div>
                                    <div class="text-xs text-gray-500 font-medium tracking-wide text-center">COMPLETED</div>
                                </div>
                            </div>
                            <div class="mt-4 pt-3 border-t border-gray-300">
                                <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                                    <span>Progress</span>
                                    <span>0%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full bg-gradient-to-r from-red-400 to-red-500 transition-all duration-500" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Tasks Due Today -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-300 p-5 cursor-pointer hover:shadow-lg hover:border-blue-400 transition-all duration-300 group">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-2.5 rounded-lg bg-gradient-to-br from-yellow-500 to-yellow-600 group-hover:scale-110 transition-transform duration-200">
                                    <i class="fas fa-check-circle text-white"></i>
                                </div>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-800 mb-4 leading-tight">Tasks Due Today</h3>
                            <div class="flex items-center">
                                <div class="flex-1 flex flex-col items-center justify-center">
                                    <div class="text-2xl font-bold text-red-600 mb-1">5</div>
                                    <div class="text-xs text-gray-500 font-medium tracking-wide text-center">DUE</div>
                                </div>
                                <div class="h-12 w-px bg-gray-300"></div>
                                <div class="flex-1 flex flex-col items-center justify-center">
                                    <div class="text-2xl font-bold text-green-600 mb-1">18</div>
                                    <div class="text-xs text-gray-500 font-medium tracking-wide text-center">COMPLETED</div>
                                </div>
                            </div>
                            <div class="mt-4 pt-3 border-t border-gray-300">
                                <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                                    <span>Progress</span>
                                    <span>78%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full bg-gradient-to-r from-yellow-400 to-yellow-500 transition-all duration-500" style="width: 78%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Customer Alerts -->
        <div class="mb-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Fuel Charge Alerts -->
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-300">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="p-2.5 rounded-lg bg-gradient-to-br from-orange-500 to-orange-600 mr-3">
                                <i class="fas fa-gas-pump text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800">Fuel Charge Alerts</h3>
                                <p class="text-sm text-gray-600"><span x-text="fuelAlerts.length"></span> pending alert<span x-text="fuelAlerts.length !== 1 ? 's' : ''"></span></p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="px-3 py-1 rounded-full text-sm font-medium" 
                                  :class="fuelAlerts.length > 0 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'"
                                  x-text="fuelAlerts.length > 0 ? fuelAlerts.length + ' Active' : 'All Clear'"></span>
                        </div>
                    </div>
                    
                    <div class="space-y-3 max-h-80 overflow-y-auto" x-show="fuelAlerts.length > 0">
                        <template x-for="alert in fuelAlerts.slice(0, 5)" :key="alert.id">
                            <div class="alert-item bg-gray-50 rounded-lg border border-gray-200 hover:bg-gray-100 transition-colors">
                                <div class="flex items-center justify-between p-3">
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-semibold text-gray-800 truncate" x-text="alert.customerName"></h4>
                                    </div>
                                    <div class="flex-1 min-w-0 px-2">
                                        <button @click="handleOrderClick(alert.orderId)" class="flex items-center text-sm text-blue-600 hover:text-blue-800 font-medium transition-colors">
                                            <span x-text="alert.orderId"></span>
                                            <i class="fas fa-external-link-alt ml-1 text-xs"></i>
                                        </button>
                                    </div>
                                    <div class="px-1">
                                        <button @click="editingAlert = alert; tempAmount = alert.amountOwed === 'Pending' ? '' : alert.amountOwed.replace('$', ''); showAmountModal = true" 
                                                class="p-1.5 text-blue-600 hover:text-blue-800 hover:bg-blue-100 rounded transition-colors" title="Edit Amount">
                                            <i class="fas fa-dollar-sign"></i>
                                        </button>
                                    </div>
                                    <div class="px-1 relative">
                                        <button @click="showUpdateDropdown = showUpdateDropdown === alert.id ? null : alert.id" 
                                                class="p-1.5 text-green-600 hover:text-green-800 hover:bg-green-100 rounded transition-colors" title="Update Status">
                                            <i class="fas fa-chevron-down"></i>
                                        </button>
                                        <div x-show="showUpdateDropdown === alert.id" @click.away="showUpdateDropdown = null" 
                                             class="absolute top-full right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-10 min-w-[140px]">
                                            <button @click="handleStatusUpdate('fuel', alert.id, 'paid')" 
                                                    class="w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 rounded-t-lg">
                                                Mark as Paid
                                            </button>
                                            <button @click="handleStatusUpdate('fuel', alert.id, 'uncollectible')" 
                                                    class="w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 rounded-b-lg">
                                                Mark as Uncollectible
                                            </button>
                                        </div>
                                    </div>
                                    <div class="px-1">
                                        <button @click="editingAlert = alert; tempNotes = alert.notes || ''; showNotesModal = true" 
                                                class="p-1.5 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded transition-colors" title="Edit Notes">
                                            <i class="fas fa-file-alt"></i>
                                        </button>
                                    </div>
                                    <div class="flex-shrink-0 text-right min-w-[80px]">
                                        <span :class="alert.amountOwed === 'Pending' ? 'text-sm font-semibold text-orange-600 bg-orange-100 px-2 py-1 rounded-full' : 'text-sm font-semibold text-green-700'" 
                                              x-text="alert.amountOwed"></span>
                                    </div>
                                </div>
                                <div x-show="alert.notes" class="px-3 pb-3">
                                    <div class="p-2 bg-blue-50 border border-blue-200 rounded text-xs text-blue-800">
                                        <strong>Notes:</strong> <span x-text="alert.notes"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div x-show="fuelAlerts.length > 5" class="text-center pt-2 border-t border-gray-200">
                            <span class="text-sm text-gray-500" x-text="'+' + (fuelAlerts.length - 5) + ' more alert' + ((fuelAlerts.length - 5) !== 1 ? 's' : '')"></span>
                        </div>
                    </div>
                    
                    <div x-show="fuelAlerts.length === 0" class="text-center py-8">
                        <div class="text-gray-400 mb-2">
                            <i class="fas fa-check-circle text-5xl"></i>
                        </div>
                        <p class="text-gray-500">No pending alerts</p>
                    </div>
                </div>

                <!-- New Damage Alerts -->
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-300">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="p-2.5 rounded-lg bg-gradient-to-br from-red-500 to-red-600 mr-3">
                                <i class="fas fa-exclamation-triangle text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800">New Damage Alerts</h3>
                                <p class="text-sm text-gray-600"><span x-text="damageAlerts.length"></span> pending alert<span x-text="damageAlerts.length !== 1 ? 's' : ''"></span></p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="px-3 py-1 rounded-full text-sm font-medium" 
                                  :class="damageAlerts.length > 0 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'"
                                  x-text="damageAlerts.length > 0 ? damageAlerts.length + ' Active' : 'All Clear'"></span>
                        </div>
                    </div>
                    
                    <div class="space-y-3 max-h-80 overflow-y-auto" x-show="damageAlerts.length > 0">
                        <template x-for="alert in damageAlerts.slice(0, 5)" :key="alert.id">
                            <div class="alert-item bg-gray-50 rounded-lg border border-gray-200 hover:bg-gray-100 transition-colors">
                                <div class="flex items-center justify-between p-3">
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-semibold text-gray-800 truncate" x-text="alert.customerName"></h4>
                                    </div>
                                    <div class="flex-1 min-w-0 px-2">
                                        <button @click="handleOrderClick(alert.orderId)" class="flex items-center text-sm text-blue-600 hover:text-blue-800 font-medium transition-colors">
                                            <span x-text="alert.orderId"></span>
                                            <i class="fas fa-external-link-alt ml-1 text-xs"></i>
                                        </button>
                                    </div>
                                    <div class="px-1">
                                        <button @click="editingAlert = alert; tempAmount = alert.amountOwed === 'Pending' ? '' : alert.amountOwed.replace('$', ''); showAmountModal = true" 
                                                class="p-1.5 text-blue-600 hover:text-blue-800 hover:bg-blue-100 rounded transition-colors" title="Edit Amount">
                                            <i class="fas fa-dollar-sign"></i>
                                        </button>
                                    </div>
                                    <div class="px-1 relative">
                                        <button @click="showUpdateDropdown = showUpdateDropdown === alert.id ? null : alert.id" 
                                                class="p-1.5 text-green-600 hover:text-green-800 hover:bg-green-100 rounded transition-colors" title="Update Status">
                                            <i class="fas fa-chevron-down"></i>
                                        </button>
                                        <div x-show="showUpdateDropdown === alert.id" @click.away="showUpdateDropdown = null" 
                                             class="absolute top-full right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-10 min-w-[140px]">
                                            <button @click="handleStatusUpdate('damage', alert.id, 'paid')" 
                                                    class="w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 rounded-t-lg">
                                                Mark as Paid
                                            </button>
                                            <button @click="handleStatusUpdate('damage', alert.id, 'uncollectible')" 
                                                    class="w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 rounded-b-lg">
                                                Mark as Uncollectible
                                            </button>
                                        </div>
                                    </div>
                                    <div class="px-1">
                                        <button @click="editingAlert = alert; tempNotes = alert.notes || ''; showNotesModal = true" 
                                                class="p-1.5 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded transition-colors" title="Edit Notes">
                                            <i class="fas fa-file-alt"></i>
                                        </button>
                                    </div>
                                    <div class="flex-shrink-0 text-right min-w-[80px]">
                                        <span :class="alert.amountOwed === 'Pending' ? 'text-sm font-semibold text-orange-600 bg-orange-100 px-2 py-1 rounded-full' : 'text-sm font-semibold text-green-700'" 
                                              x-text="alert.amountOwed"></span>
                                    </div>
                                </div>
                                <div x-show="alert.notes" class="px-3 pb-3">
                                    <div class="p-2 bg-blue-50 border border-blue-200 rounded text-xs text-blue-800">
                                        <strong>Notes:</strong> <span x-text="alert.notes"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div x-show="damageAlerts.length > 5" class="text-center pt-2 border-t border-gray-200">
                            <span class="text-sm text-gray-500" x-text="'+' + (damageAlerts.length - 5) + ' more alert' + ((damageAlerts.length - 5) !== 1 ? 's' : '')"></span>
                        </div>
                    </div>
                    
                    <div x-show="damageAlerts.length === 0" class="text-center py-8">
                        <div class="text-gray-400 mb-2">
                            <i class="fas fa-check-circle text-5xl"></i>
                        </div>
                        <p class="text-gray-500">No pending alerts</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: Sales Trend Analysis -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8 border border-gray-300">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold text-gray-800">Sales Trend Analysis</h2>
                <div class="flex items-center space-x-2">
                    <button class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <button class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
            </div>
            
            <!-- Sales Metrics Cards -->
            <div class="flex justify-center mb-6">
                <div class="flex flex-wrap justify-center gap-4 max-w-5xl">
                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-200 min-w-[168px] text-center">
                        <div class="text-xs text-gray-600 font-medium mb-1">Total Sales</div>
                        <div class="text-lg font-semibold text-blue-800 whitespace-nowrap">$616,900</div>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg border border-green-200 min-w-[168px] text-center">
                        <div class="text-xs text-gray-600 font-medium mb-1">Previous Period</div>
                        <div class="text-lg font-semibold text-green-800 whitespace-nowrap">$537,600</div>
                    </div>
                    <div class="bg-emerald-50 p-4 rounded-lg border border-emerald-200 min-w-[168px] text-center">
                        <div class="text-xs text-gray-600 font-medium mb-1 text-center">Growth Rate</div>
                        <div class="text-lg font-semibold text-emerald-800 flex items-center justify-center whitespace-nowrap">
                            <i class="fas fa-arrow-trend-up mr-1"></i> 14.8%
                        </div>
                    </div>
                    <div class="bg-purple-50 p-4 rounded-lg border border-purple-200 min-w-[168px] text-center">
                        <div class="text-xs text-gray-600 font-medium mb-1">Daily Average</div>
                        <div class="text-lg font-semibold text-purple-800 whitespace-nowrap">$20,563</div>
                    </div>
                </div>
            </div>
            
            <!-- Period Selector -->
            <div class="flex space-x-2 mb-6">
                <button @click="salesPeriod = 'rolling30'" 
                        :class="salesPeriod === 'rolling30' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'" 
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                    Rolling 30 Days
                </button>
                <button @click="salesPeriod = 'currentMonth'" 
                        :class="salesPeriod === 'currentMonth' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'" 
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                    Current Month
                </button>
                <button @click="salesPeriod = 'lastMonth'" 
                        :class="salesPeriod === 'lastMonth' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'" 
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                    Last Month
                </button>
            </div>
            
            <div id="salesChart" class="chart-container" style="height: 400px;"></div>
        </div>

        <!-- Section 4: Maintenance & Damaged Items Tracking -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Maintenance Hold -->
            <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-300">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-800">Maintenance Hold - 2 Week Trend</h2>
                    <i class="fas fa-wrench text-orange-600"></i>
                </div>
                <div id="maintenanceChart" class="chart-container" style="height: 300px;"></div>
            </div>

            <!-- Damaged Items -->
            <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-300">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-800">Damaged Items - 2 Week Trend</h2>
                    <i class="fas fa-exclamation-triangle text-red-600"></i>
                </div>
                <div id="damagedChart" class="chart-container" style="height: 300px;"></div>
            </div>
        </div>

    </div>

    <!-- Amount Modal -->
    <div x-show="showAmountModal" 
         x-cloak
         @keydown.escape.window="showAmountModal = false"
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-96 max-w-90vw" @click.away="showAmountModal = false">
            <h3 class="text-lg font-semibold mb-4">
                <span x-text="editingAlert?.amountOwed === 'Pending' ? 'Add Amount' : 'Edit Amount'"></span>
            </h3>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Amount ($)</label>
                <input type="number" step="0.01" x-model="tempAmount" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                       placeholder="0.00" autofocus>
            </div>
            <div class="flex justify-end space-x-3">
                <button @click="showAmountModal = false" 
                        class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors">
                    Cancel
                </button>
                <button @click="saveAmount()" 
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                    Save
                </button>
            </div>
        </div>
    </div>

    <!-- Notes Modal -->
    <div x-show="showNotesModal" 
         x-cloak
         @keydown.escape.window="showNotesModal = false"
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-96 max-w-90vw" @click.away="showNotesModal = false">
            <h3 class="text-lg font-semibold mb-4">
                <span x-text="editingAlert?.notes ? 'Edit Notes' : 'Add Notes'"></span>
            </h3>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                <textarea x-model="tempNotes" 
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                          rows="4" placeholder="Add notes about this alert..." autofocus></textarea>
            </div>
            <div class="flex justify-end space-x-3">
                <button @click="showNotesModal = false" 
                        class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors">
                    Cancel
                </button>
                <button @click="saveNotes()" 
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                    Save
                </button>
            </div>
        </div>
        </div>
    </div>
@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
function dashboardData() {
    return {
        salesPeriod: 'rolling30',
        fuelAlerts: [
            { id: 1, customerName: 'ABC Events LLC', orderId: 'ORD-2024-001', amountOwed: 'Pending', date: '2024-01-27', type: 'fuel', notes: '' },
            { id: 2, customerName: 'Wedding Bliss Co', orderId: 'ORD-2024-002', amountOwed: '$45.00', date: '2024-01-26', type: 'fuel', notes: 'Customer disputed charge initially' },
            { id: 3, customerName: 'Corporate Solutions', orderId: 'ORD-2024-003', amountOwed: 'Pending', date: '2024-01-25', type: 'fuel', notes: '' },
            { id: 4, customerName: 'Party Time Rentals', orderId: 'ORD-2024-004', amountOwed: '$32.50', date: '2024-01-24', type: 'fuel', notes: 'Awaiting payment confirmation' },
            { id: 5, customerName: 'Elite Celebrations', orderId: 'ORD-2024-005', amountOwed: 'Pending', date: '2024-01-23', type: 'fuel', notes: 'Need to calculate mileage' },
            { id: 6, customerName: 'Dream Weddings Inc', orderId: 'ORD-2024-006', amountOwed: '$28.75', date: '2024-01-22', type: 'fuel', notes: '' }
        ],
        damageAlerts: [
            { id: 1, customerName: 'Luxury Events Co', orderId: 'ORD-2024-007', amountOwed: '$150.00', date: '2024-01-27', type: 'damage', notes: 'Tablecloth stain - professional cleaning required' },
            { id: 2, customerName: 'Premier Parties', orderId: 'ORD-2024-008', amountOwed: 'Pending', date: '2024-01-26', type: 'damage', notes: '' },
            { id: 3, customerName: 'Celebration Central', orderId: 'ORD-2024-009', amountOwed: '$89.99', date: '2024-01-25', type: 'damage', notes: 'Chair leg broken during event' },
            { id: 4, customerName: 'Event Masters LLC', orderId: 'ORD-2024-010', amountOwed: 'Pending', date: '2024-01-24', type: 'damage', notes: 'Assessing tent damage from wind' },
            { id: 5, customerName: 'Perfect Day Events', orderId: 'ORD-2024-011', amountOwed: '$225.00', date: '2024-01-23', type: 'damage', notes: 'Multiple items damaged - invoice sent' },
            { id: 6, customerName: 'Grand Occasions', orderId: 'ORD-2024-012', amountOwed: 'Pending', date: '2024-01-22', type: 'damage', notes: '' }
        ],
        showAmountModal: false,
        showNotesModal: false,
        showUpdateDropdown: null,
        editingAlert: null,
        tempAmount: '',
        tempNotes: '',
        salesChart: null,
        maintenanceChart: null,
        damagedChart: null,

        init() {
            this.$nextTick(() => {
                // Destroy existing charts if they exist
                if (this.salesChart) {
                    this.salesChart.destroy();
                }
                if (this.maintenanceChart) {
                    this.maintenanceChart.destroy();
                }
                if (this.damagedChart) {
                    this.damagedChart.destroy();
                }
                
                this.initSalesChart();
                this.initMaintenanceChart();
                this.initDamagedChart();
            });

            this.$watch('salesPeriod', () => {
                this.updateSalesChart();
            });
        },

        handleOrderClick(orderId) {
            console.log('Navigate to order:', orderId);
            // Add navigation logic here
        },

        handleStatusUpdate(alertType, alertId, status) {
            console.log(`Mark alert ${alertId} as ${status}`);
            
            if (alertType === 'fuel') {
                this.fuelAlerts = this.fuelAlerts.filter(alert => alert.id !== alertId);
            } else {
                this.damageAlerts = this.damageAlerts.filter(alert => alert.id !== alertId);
            }
            this.showUpdateDropdown = null;
        },

        saveAmount() {
            if (this.editingAlert) {
                const formattedAmount = this.tempAmount ? `$${parseFloat(this.tempAmount).toFixed(2)}` : 'Pending';
                
                if (this.editingAlert.type === 'fuel') {
                    const index = this.fuelAlerts.findIndex(a => a.id === this.editingAlert.id);
                    if (index !== -1) {
                        this.fuelAlerts[index].amountOwed = formattedAmount;
                    }
                } else {
                    const index = this.damageAlerts.findIndex(a => a.id === this.editingAlert.id);
                    if (index !== -1) {
                        this.damageAlerts[index].amountOwed = formattedAmount;
                    }
                }
            }
            this.showAmountModal = false;
            this.editingAlert = null;
            this.tempAmount = '';
        },

        saveNotes() {
            if (this.editingAlert) {
                if (this.editingAlert.type === 'fuel') {
                    const index = this.fuelAlerts.findIndex(a => a.id === this.editingAlert.id);
                    if (index !== -1) {
                        this.fuelAlerts[index].notes = this.tempNotes;
                    }
                } else {
                    const index = this.damageAlerts.findIndex(a => a.id === this.editingAlert.id);
                    if (index !== -1) {
                        this.damageAlerts[index].notes = this.tempNotes;
                    }
                }
            }
            this.showNotesModal = false;
            this.editingAlert = null;
            this.tempNotes = '';
        },

        getSalesData() {
            const data = {
                rolling30: {
                    categories: ['6/1', '6/2', '6/3', '6/4', '6/5', '6/6', '6/7', '6/8', '6/9', '6/10', '6/11', '6/12', '6/13', '6/14', '6/15', '6/16', '6/17', '6/18', '6/19', '6/20', '6/21', '6/22', '6/23', '6/24', '6/25', '6/26', '6/27', '6/28', '6/29', '6/30'],
                    current: [8500, 12500, 15200, 9800, 18500, 22000, 25500, 19200, 13500, 16800, 14200, 19500, 28000, 31500, 29200, 17500, 12800, 15200, 18500, 22800, 26500, 24200, 16800, 13500, 17200, 20500, 35000, 38500, 32500, 21500],
                    previous: [7200, 9800, 11200, 8500, 14200, 18500, 21000, 16800, 12100, 14500, 13200, 17200, 24500, 27800, 25200, 15800, 11500, 13800, 16200, 19500, 23200, 21800, 14500, 12200, 15500, 18200, 31500, 34200, 28800, 19200]
                },
                currentMonth: {
                    categories: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                    current: [45000, 52000, 48000, 55000],
                    previous: [42000, 48000, 51000, 47000]
                },
                lastMonth: {
                    categories: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                    current: [42000, 48000, 51000, 47000],
                    previous: [38000, 44000, 46000, 43000]
                }
            };
            return data[this.salesPeriod];
        },

        initSalesChart() {
            const chartElement = document.querySelector("#salesChart");
            if (!chartElement) return;
            
            // Clear any existing chart content
            chartElement.innerHTML = '';
            
            const salesData = this.getSalesData();
            const options = {
                series: [{
                    name: 'Current Period',
                    data: salesData.current
                }, {
                    name: 'Previous Period',
                    data: salesData.previous
                }],
                chart: {
                    height: 400,
                    type: 'line',
                    toolbar: {
                        show: false
                    },
                    zoom: {
                        enabled: false
                    }
                },
                colors: ['#3B82F6', '#10B981'],
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    width: [3, 2],
                    curve: 'smooth',
                    dashArray: [0, 5]
                },
                grid: {
                    borderColor: '#f1f5f9',
                    strokeDashArray: 3
                },
                markers: {
                    size: [4, 3],
                    strokeWidth: 2,
                    hover: {
                        size: 6
                    }
                },
                xaxis: {
                    categories: salesData.categories,
                    labels: {
                        rotate: -45,
                        style: {
                            fontSize: '11px',
                            colors: '#64748b'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        formatter: function(value) {
                            return '$' + (value / 1000).toFixed(0) + 'k';
                        },
                        style: {
                            fontSize: '12px',
                            colors: '#64748b'
                        }
                    }
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right',
                    offsetY: 10
                }
            };

            this.salesChart = new ApexCharts(chartElement, options);
            this.salesChart.render();
        },

        updateSalesChart() {
            const salesData = this.getSalesData();
            this.salesChart.updateOptions({
                xaxis: {
                    categories: salesData.categories
                }
            });
            this.salesChart.updateSeries([{
                name: 'Current Period',
                data: salesData.current
            }, {
                name: 'Previous Period',
                data: salesData.previous
            }]);
        },

        initMaintenanceChart() {
            const chartElement = document.querySelector("#maintenanceChart");
            if (!chartElement) return;
            
            // Clear any existing chart content
            chartElement.innerHTML = '';
            
            const options = {
                series: [{
                    name: 'Due',
                    data: [5, 3, 7, 4, 6, 2, 1, 8, 5, 3, 6, 4, 2, 3]
                }, {
                    name: 'Completed',
                    data: [3, 4, 2, 6, 5, 3, 2, 4, 7, 5, 3, 8, 4, 2]
                }],
                chart: {
                    height: 300,
                    type: 'line',
                    toolbar: {
                        show: false
                    }
                },
                colors: ['#F59E0B', '#10B981'],
                stroke: {
                    width: 2,
                    curve: 'smooth'
                },
                grid: {
                    borderColor: '#f1f5f9',
                    strokeDashArray: 3
                },
                markers: {
                    size: 3
                },
                xaxis: {
                    categories: ['Mon 1/13', 'Tue 1/14', 'Wed 1/15', 'Thu 1/16', 'Fri 1/17', 'Sat 1/18', 'Sun 1/19', 'Mon 1/20', 'Tue 1/21', 'Wed 1/22', 'Thu 1/23', 'Fri 1/24', 'Sat 1/25', 'Sun 1/26'],
                    labels: {
                        rotate: -45,
                        style: {
                            fontSize: '10px',
                            colors: '#64748b'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            fontSize: '12px',
                            colors: '#64748b'
                        }
                    }
                },
                legend: {
                    position: 'top'
                }
            };

            this.maintenanceChart = new ApexCharts(chartElement, options);
            this.maintenanceChart.render();
        },

        initDamagedChart() {
            const chartElement = document.querySelector("#damagedChart");
            if (!chartElement) return;
            
            // Clear any existing chart content
            chartElement.innerHTML = '';
            
            const options = {
                series: [{
                    name: 'Due',
                    data: [2, 1, 4, 3, 2, 1, 0, 5, 3, 2, 4, 1, 2, 1]
                }, {
                    name: 'Completed',
                    data: [1, 3, 2, 4, 3, 2, 1, 2, 4, 3, 1, 5, 3, 2]
                }],
                chart: {
                    height: 300,
                    type: 'line',
                    toolbar: {
                        show: false
                    }
                },
                colors: ['#EF4444', '#10B981'],
                stroke: {
                    width: 2,
                    curve: 'smooth'
                },
                grid: {
                    borderColor: '#f1f5f9',
                    strokeDashArray: 3
                },
                markers: {
                    size: 3
                },
                xaxis: {
                    categories: ['Mon 1/13', 'Tue 1/14', 'Wed 1/15', 'Thu 1/16', 'Fri 1/17', 'Sat 1/18', 'Sun 1/19', 'Mon 1/20', 'Tue 1/21', 'Wed 1/22', 'Thu 1/23', 'Fri 1/24', 'Sat 1/25', 'Sun 1/26'],
                    labels: {
                        rotate: -45,
                        style: {
                            fontSize: '10px',
                            colors: '#64748b'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            fontSize: '12px',
                            colors: '#64748b'
                        }
                    }
                },
                legend: {
                    position: 'top'
                }
            };

            this.damagedChart = new ApexCharts(chartElement, options);
            this.damagedChart.render();
        }
    };
}
</script>
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush
