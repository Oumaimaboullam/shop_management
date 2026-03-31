<?php
require_once '../includes/functions.php';
require_once '../config/database.php';
requireLogin();
$pageTitle = __('point_of_sale');
require_once '../includes/header.php';

// Fetch Payment Modes
$modes = $pdo->query("SELECT * FROM payment_modes")->fetchAll();
?>

<div class="min-h-screen bg-gray-50 p-4">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900"><?php echo __('point_of_sale'); ?></h1>
                    <p class="text-gray-600 mt-1"><?php echo __('pos_description'); ?></p>
                </div>
                <div class="flex items-center space-x-4">
                    <button id="wholesaleToggle" 
                            onclick="toggleWholesale()"
                            class="px-4 py-2 bg-purple-600 text-white rounded-lg font-medium hover:bg-purple-700 active:bg-purple-800 transform hover:scale-105 transition-all duration-200 shadow-sm hover:shadow-md">
                        <i class="fas fa-percentage mr-2"></i>
                        <span id="wholesaleText"><?php echo __('enable_wholesale', 'Activer le grossiste'); ?></span>
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 h-[calc(100vh-200px)]">
            <!-- Left: Products Section (2/3 width) -->
            <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <!-- Search Bar -->
                <div class="mb-6">
                    <div class="relative">
                        <input type="text" 
                               id="searchInput" 
                               class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-lg"
                               placeholder="<?php echo __('scan_barcode'); ?>" 
                               autofocus>
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search h-5 w-5 text-gray-400"></i>
                        </div>
                        <button onclick="handleSearchButton()" 
                                class="absolute inset-y-0 right-0 px-4 bg-blue-600 text-white rounded-r-lg hover:bg-blue-700 active:bg-blue-800 transform hover:scale-105 transition-all duration-200 shadow-sm hover:shadow-md">
                            <?php echo __('search_product'); ?>
                        </button>
                    </div>
                </div>

                <!-- Products Grid -->
                <div id="productGrid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 overflow-y-auto max-h-[calc(100vh-350px)] scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100">
                    <!-- Products will be loaded here -->
                    <div class="col-span-full text-center text-gray-500 py-12">
                        <div class="flex flex-col items-center justify-center space-y-4">
                            <div class="relative">
                                <i class="fas fa-shopping-basket text-6xl text-gray-300"></i>
                                <i class="fas fa-search text-2xl text-blue-400 absolute -top-2 -right-2"></i>
                            </div>
                            <div>
                                <p class="text-lg font-medium"><?php echo __('type_search'); ?></p>
                                <p class="text-sm text-gray-400 mt-1">Scan barcode or type to search products</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Cart Section (1/3 width) -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 flex flex-col">
                <!-- Cart Header -->
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900"><?php echo __('current_sale'); ?></h2>
                    <p class="text-sm text-gray-600"><?php echo __('cart_items'); ?></p>
                </div>

                <!-- Cart Items -->
                <div id="cartItems" class="flex-1 overflow-y-auto p-6">
                    <!-- Cart items here -->
                    <div class="text-center text-gray-500 py-8">
                        <i class="fas fa-shopping-cart mx-auto h-12 w-12 text-gray-400 mb-4 text-4xl"></i>
                        <p><?php echo __('your_cart_is_empty'); ?></p>
                    </div>
                </div>

                <!-- Cart Footer -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    <!-- Client/Counter Selection -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('customer_type', 'Type de client'); ?></label>
                        <div class="flex space-x-4">
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" name="customerType" value="counter" checked 
                                       class="mr-2 text-blue-600 focus:ring-blue-500"
                                       onchange="handleCustomerTypeChange()">
                                <span class="text-sm font-medium text-gray-700"><?php echo __('counter_sale', 'Vente comptant'); ?></span>
                            </label>
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" name="customerType" value="client" 
                                       class="mr-2 text-blue-600 focus:ring-blue-500"
                                       onchange="handleCustomerTypeChange()">
                                <span class="text-sm font-medium text-gray-700"><?php echo __('customer', 'Client'); ?></span>
                            </label>
                        </div>
                    </div>

                    <!-- Client Selection (hidden by default) -->
                    <div id="clientSelection" class="mb-4 hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('select_customer', 'Sélectionner un client'); ?></label>
                        <div class="relative">
                            <!-- Searchable Customer Input -->
                            <div class="relative">
                                <input type="text" 
                                       id="customerSearch" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="<?php echo __('search_customer_placeholder', 'Rechercher client par nom, téléphone ou email...'); ?>"
                                       autocomplete="off">
                                <div id="customerDropdown" class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto hidden">
                                    <!-- Customer options will be loaded here -->
                                </div>
                            </div>
                            
                            <!-- Selected Customer Display -->
                            <div id="selectedClientInfo" class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg hidden">
                                <div class="text-sm">
                                    <p class="font-medium text-blue-900"><?php echo __('customer_information', 'Informations client'); ?></p>
                                    <div id="clientDetails" class="mt-1 text-blue-700"></div>
                                </div>
                            </div>
                            
                            <!-- Customer Transaction History -->
                            <div id="customerTransactions" class="mt-4 hidden">
                                <div class="border-t border-gray-200 pt-4">
                                    <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo __('transaction_history', 'Historique des transactions'); ?></h4>
                                    <div id="transactionsList" class="space-y-2 max-h-60 overflow-y-auto">
                                        <!-- Transactions will be loaded here -->
                                    </div>
                                    <div id="transactionSummary" class="mt-3 p-3 bg-gray-50 rounded-lg hidden">
                                        <div class="text-xs text-gray-600">
                                            <div class="flex justify-between">
                                                <span><?php echo __('total_advance', 'Avance totale'); ?>:</span>
                                                <span id="totalAdvance" class="font-medium">0.00 DH</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span><?php echo __('total_remaining', 'Solde restant'); ?>:</span>
                                                <span id="totalRemaining" class="font-medium text-red-600">0.00 DH</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Advance Payment (hidden by default, also hidden for cashiers) -->
                    <div id="advancePaymentSection" class="mb-4 hidden <?php echo hasRole(['cashier']) ? 'hidden' : ''; ?>">
                        <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('amount_paid_now', 'Montant payé maintenant'); ?></label>
                        <div class="flex space-x-2">
                            <input type="number" 
                                   id="advancePaymentInput" 
                                   min="0" 
                                   step="0.01" 
                                   placeholder="<?php echo __('advance_payment_placeholder', '0.00'); ?>"
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   onchange="updateTotal()">
                            <button onclick="clearAdvancePayment()" 
                                    class="px-3 py-2 bg-gray-100 hover:bg-gray-200 active:bg-gray-300 text-gray-700 rounded-lg transform hover:scale-105 transition-all duration-200 shadow-sm hover:shadow-md">
                                <?php echo __('clear', 'Effacer'); ?>
                            </button>
                        </div>
                        <div id="advanceDisplay" class="mt-2 hidden">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600"><?php echo __('amount_paid_now_display', 'Montant payé maintenant:'); ?></span>
                                <span id="advanceAmount" class="font-medium text-green-600">+0.00<?php echo getSetting('currency_symbol', 'DH'); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Sale Type -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('sale_type'); ?></label>
                        <select id="saleType" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="sale"><?php echo __('sale_normal'); ?></option>
                            <option value="invoice"><?php echo __('invoice'); ?></option>
                            <option value="quote"><?php echo __('quote'); ?></option>
                        </select>
                    </div>

                    <!-- Payment Method -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('payment_method'); ?></label>
                        <select id="paymentMode" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <?php foreach ($modes as $mode): ?>
                                <?php 
                                // Translate payment method names
                                $translatedName = $mode['name'];
                                switch (strtolower($mode['name'])) {
                                    case 'cash':
                                        $translatedName = __('cash_payment', 'Espèces');
                                        break;
                                    case 'credit card':
                                        $translatedName = __('credit_card_payment', 'Carte de crédit');
                                        break;
                                    case 'bank transfer':
                                        $translatedName = __('bank_transfer_payment', 'Virement bancaire');
                                        break;
                                    case 'check':
                                        $translatedName = __('check_payment', 'Chèque');
                                        break;
                                    case 'mobile':
                                        $translatedName = __('mobile_payment', 'Paiement mobile');
                                        break;
                                    case 'other':
                                        $translatedName = __('other_payment', 'Autre');
                                        break;
                                }
                                ?>
                                <option value="<?php echo $mode['id']; ?>"><?php echo htmlspecialchars($translatedName); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Discount -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('discount_label', 'Remise %'); ?></label>
                        <div class="flex space-x-2">
                            <input type="number" 
                                   id="discountInput" 
                                   min="0" 
                                   max="100" 
                                   step="0.1" 
                                   placeholder="<?php echo __('discount_placeholder', '0.0'); ?>"
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   onchange="applyDiscount()">
                            <button onclick="clearDiscount()" 
                                    class="px-3 py-2 bg-gray-100 hover:bg-gray-200 active:bg-gray-300 text-gray-700 rounded-lg transform hover:scale-105 transition-all duration-200 shadow-sm hover:shadow-md">
                                <?php echo __('clear', 'Effacer'); ?>
                            </button>
                        </div>
                    </div>

                    <!-- Discount Display -->
                    <div id="discountDisplay" class="mb-4 hidden">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-600"><?php echo __('discount_display', 'Remise:'); ?></span>
                            <span id="discountAmount" class="font-medium text-red-600">-0.00<?php echo getSetting('currency_symbol', 'DH'); ?></span>
                        </div>
                    </div>

                    <!-- Draft Actions -->
                    <div class="mb-4 flex space-x-2">
                        <button id="saveDraftBtn" 
                                onclick="saveDraft()" 
                                class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-blue-700 active:bg-blue-800 transform hover:scale-105 transition-all duration-200 shadow-sm hover:shadow-md">
                            <i class="fas fa-save mr-2"></i>
                            <?php echo __('save_as_draft', 'Enregistrer comme brouillon'); ?>
                        </button>
                        <button id="loadDraftBtn" 
                                onclick="loadDraftsModal()" 
                                class="flex-1 bg-purple-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-purple-700 active:bg-purple-800 transform hover:scale-105 transition-all duration-200 shadow-sm hover:shadow-md">
                            <i class="fas fa-folder-open mr-2"></i>
                            <?php echo __('load_draft', 'Charger le brouillon'); ?>
                        </button>
                    </div>

                    <!-- Total -->
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-lg font-medium text-gray-900"><?php echo __('total'); ?>:</span>
                        <span id="cartTotal" class="text-2xl font-bold text-gray-900">0.00<?php echo getSetting('currency_symbol', 'DH'); ?></span>
                    </div>

                    <!-- Checkout Button -->
                    <div class="flex space-x-2">
                        <button id="clearCartBtn" 
                                onclick="clearCart()" 
                                class="flex-1 bg-gray-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-gray-700 active:bg-gray-800 transform hover:scale-105 transition-all duration-200 shadow-sm hover:shadow-md">
                            <i class="fas fa-trash-alt mr-2"></i>
                            <?php echo __('clear_cart', 'Vider le panier'); ?>
                        </button>
                        <button id="checkoutBtn" 
                                onclick="processSale()" 
                                disabled
                                class="flex-1 bg-green-600 text-white py-3 px-4 rounded-lg font-semibold text-lg hover:bg-green-700 active:bg-green-800 transform hover:scale-105 transition-all duration-200 shadow-sm hover:shadow-md disabled:bg-gray-300 disabled:cursor-not-allowed disabled:text-gray-500 disabled:transform-none disabled:shadow-none">
                            <i class="fas fa-credit-card mr-2"></i>
                            <?php echo __('checkout'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let cart = [];
let products = [];
let isWholesaleMode = false;

// Translation strings for JavaScript
const translations = {
    product_added_to_cart: '<?php echo addslashes(__('product_added_to_cart', 'Product added to cart!')); ?>',
    barcode_not_found: '<?php echo addslashes(__('barcode_not_found', 'Barcode not found')); ?>',
    error_searching_barcode: '<?php echo addslashes(__('error_searching_barcode', 'Error searching barcode')); ?>',
    no_customers_found: '<?php echo addslashes(__('no_customers_found', 'No customers found')); ?>',
    try_adjusting_search: '<?php echo addslashes(__('try_adjusting_search', 'Try adjusting your search terms')); ?>',
    retail: '<?php echo addslashes(__('retail', 'Retail')); ?>'
};

// Toggle wholesale mode
function toggleWholesale() {
    isWholesaleMode = !isWholesaleMode;
    const btn = document.getElementById('wholesaleToggle');
    const text = document.getElementById('wholesaleText');
    
    if (isWholesaleMode) {
        btn.classList.remove('bg-purple-600', 'hover:bg-purple-700');
        btn.classList.add('bg-green-600', 'hover:bg-green-700');
        text.textContent = '<?php echo __('disable_wholesale', 'Désactiver le grossiste'); ?>';
    } else {
        btn.classList.remove('bg-green-600', 'hover:bg-green-700');
        btn.classList.add('bg-purple-600', 'hover:bg-purple-700');
        text.textContent = '<?php echo __('enable_wholesale', 'Activer le grossiste'); ?>';
    }
    
    // Re-render products and cart with new pricing
    renderProducts(products);
    renderCart();
}

// Initial Load
document.addEventListener('DOMContentLoaded', () => {
    searchProducts(''); // Load all/some products initially
    loadClients(); // Load clients for selection
    
    const searchInput = document.getElementById('searchInput');
    let searchTimeout;
    
    searchInput.addEventListener('input', (e) => {
        const query = e.target.value.trim();
        
        // Clear previous timeout
        clearTimeout(searchTimeout);
        
        // Check if input looks like a barcode (numeric and 6+ digits)
        if (query.length >= 6 && /^\d+$/.test(query)) {
            // This looks like a barcode, search immediately
            searchBarcode(query);
        } else if (query.length === 0) {
            // Empty search - load all products
            searchProducts('');
        } else if (query.length > 2) {
            // Text search - debounce for better performance
            searchTimeout = setTimeout(() => {
                searchProducts(query);
            }, 300);
        }
    });
    
    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            handleSearchButton();
        }
    });
    
    // Customer search functionality
    const customerSearchInput = document.getElementById('customerSearch');
    let customerSearchTimeout;
    
    customerSearchInput.addEventListener('input', (e) => {
        const query = e.target.value.trim();
        
        // Clear previous timeout
        clearTimeout(customerSearchTimeout);
        
        // Debounce customer search
        customerSearchTimeout = setTimeout(() => {
            searchCustomers(query);
        }, 200);
    });
    
    customerSearchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault(); // Prevent form submission
            // If there's only one result, select it
            const dropdown = document.getElementById('customerDropdown');
            const options = dropdown.querySelectorAll('.customer-option');
            if (options.length === 1) {
                const clientId = options[0].getAttribute('onclick').match(/selectCustomer\((\d+)\)/)[1];
                selectCustomer(parseInt(clientId));
            }
        }
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        const customerSearch = document.getElementById('customerSearch');
        const dropdown = document.getElementById('customerDropdown');
        
        if (!customerSearch.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });
});

// Handle client selection
document.addEventListener('DOMContentLoaded', () => {
    const clientSelect = document.getElementById('clientSelect');
    if (clientSelect) {
        clientSelect.addEventListener('change', handleClientSelection);
    }
});

// Handle search button click
function handleSearchButton() {
    const query = document.getElementById('searchInput').value.trim();
    
    // Check if it's a barcode first
    if (query.length >= 6 && /^\d+$/.test(query)) {
        searchBarcode(query);
    } else {
        // Try to find exact barcode match first
        const exactBarcodeMatch = products.find(p => p.barcode === query);
        if (exactBarcodeMatch) {
            addToCart(exactBarcodeMatch);
            document.getElementById('searchInput').value = '';
            showNotification(translations.product_added_to_cart, 'success');
        } else {
            // Try to find exact name match (case-insensitive)
            const exactNameMatch = products.find(p => 
                p.name.toLowerCase() === query.toLowerCase()
            );
            if (exactNameMatch) {
                addToCart(exactNameMatch);
                document.getElementById('searchInput').value = '';
                showNotification(translations.product_added_to_cart, 'success');
            } else {
                // No exact match found, show search results
                searchProducts(query);
            }
        }
    }
}

// Search for barcode and add to cart directly
async function searchBarcode(barcode) {
    try {
        console.log('Searching for barcode:', barcode);
        
        const response = await fetch(`../api/products/search.php?q=${encodeURIComponent(barcode)}`);
        const data = await response.json();
        
        if (data.success && data.data.length > 0) {
            // Find exact barcode match
            const exactMatch = data.data.find(p => p.barcode === barcode);
            
            if (exactMatch) {
                addToCart(exactMatch);
                document.getElementById('searchInput').value = '';
                showNotification(`${exactMatch.name} added to cart!`, 'success');
                console.log('Barcode found and added:', exactMatch.name);
            } else {
                // Barcode-like input but no exact match found
                showNotification(translations.barcode_not_found, 'error');
                console.log('Barcode not found:', barcode);
            }
        } else {
            showNotification(translations.barcode_not_found, 'error');
            console.log('No products found for barcode:', barcode);
        }
    } catch (error) {
        console.error('Barcode search error:', error);
        showNotification(translations.error_searching_barcode, 'error');
    }
}

// Show notification feedback
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existing = document.querySelector('.search-notification');
    if (existing) {
        existing.remove();
    }
    
    const notification = document.createElement('div');
    notification.className = `search-notification fixed top-4 right-4 px-4 py-2 rounded-lg text-white font-medium z-50 transition-all duration-300`;
    
    // Set color based on type
    if (type === 'success') {
        notification.classList.add('bg-green-500');
    } else if (type === 'error') {
        notification.classList.add('bg-red-500');
    } else {
        notification.classList.add('bg-blue-500');
    }
    
    notification.textContent = message;
    document.body.appendChild(notification);
    
    // Auto-remove after 2 seconds
    setTimeout(() => {
        notification.remove();
    }, 2000);
}

// Load clients from database
let allClients = []; // Store all clients for searching

async function loadClients() {
    try {
        const res = await fetch('../api/clients/list.php');
        const data = await res.json();
        
        if (data.success) {
            allClients = data.data; // Store all clients
            console.log('Loaded clients:', allClients.length);
        }
    } catch (err) {
        console.error('Failed to load clients:', err);
    }
}

// Search and display customers
function searchCustomers(query) {
    const dropdown = document.getElementById('customerDropdown');
    
    if (!query || query.length < 2) {
        dropdown.classList.add('hidden');
        return;
    }
    
    // Filter clients based on search query
    const filteredClients = allClients.filter(client => {
        const searchStr = query.toLowerCase();
        return (
            client.name.toLowerCase().includes(searchStr) ||
            (client.phone && client.phone.includes(searchStr)) ||
            (client.email && client.email.toLowerCase().includes(searchStr))
        );
    });
    
    // Display filtered results
    displayCustomerResults(filteredClients);
}

function displayCustomerResults(clients) {
    const dropdown = document.getElementById('customerDropdown');
    
    if (clients.length === 0) {
        dropdown.innerHTML = `
            <div class="p-3 text-gray-500 text-sm">
                ${translations.no_customers_found}
            </div>
        `;
    } else {
        dropdown.innerHTML = clients.map(client => `
            <div class="customer-option px-3 py-2 hover:bg-gray-100 cursor-pointer border-b border-gray-100 last:border-b-0"
                 onclick="selectCustomer(${client.id})"
                 data-client='${JSON.stringify(client)}'>
                <div class="font-medium text-gray-900">${client.name}</div>
                <div class="text-sm text-gray-500">
                    ${client.phone ? `<i class="fas fa-phone text-xs mr-1"></i> ${client.phone}` : ''}
                    ${client.email ? `<i class="fas fa-envelope text-xs mr-1"></i> ${client.email}` : ''}
                </div>
                <div class="text-xs text-gray-400">
                    Balance: <?php echo getSetting('currency_symbol', 'DH'); ?>${parseFloat(client.balance || 0).toFixed(2)}
                </div>
            </div>
        `).join('');
    }
    
    dropdown.classList.remove('hidden');
}

function selectCustomer(clientId) {
    const client = allClients.find(c => c.id === clientId);
    if (!client) return;
    
    // Update search input with selected customer name
    document.getElementById('customerSearch').value = client.name;
    
    // Hide dropdown
    document.getElementById('customerDropdown').classList.add('hidden');
    
    // Display customer info
    displayCustomerInfo(client);
    
    // Load customer transactions
    loadCustomerTransactions(clientId);
}

function displayCustomerInfo(client) {
    const selectedClientInfo = document.getElementById('selectedClientInfo');
    const clientDetails = document.getElementById('clientDetails');
    
    let detailsHTML = `
        <div class="space-y-1">
            <p><strong>Name:</strong> ${client.name}</p>
            ${client.phone ? `<p><strong>Phone:</strong> ${client.phone}</p>` : ''}
            ${client.email ? `<p><strong>Email:</strong> ${client.email}</p>` : ''}
            ${client.address ? `<p><strong>Address:</strong> ${client.address}</p>` : ''}
            <p><strong>Balance:</strong> <?php echo getSetting('currency_symbol', 'DH'); ?>${parseFloat(client.balance || 0).toFixed(2)}</p>
        </div>
    `;
    
    clientDetails.innerHTML = detailsHTML;
    selectedClientInfo.classList.remove('hidden');
    
    // Store selected client ID for use in sale processing
    selectedClientInfo.dataset.clientId = client.id;
}

// Clear customer selection
function clearCustomerSelection() {
    document.getElementById('customerSearch').value = '';
    document.getElementById('customerDropdown').classList.add('hidden');
    document.getElementById('selectedClientInfo').classList.add('hidden');
    document.getElementById('customerTransactions').classList.add('hidden');
}

// Handle customer type change (counter vs client)
function handleCustomerTypeChange() {
    const customerType = document.querySelector('input[name="customerType"]:checked').value;
    const clientSelection = document.getElementById('clientSelection');
    const selectedClientInfo = document.getElementById('selectedClientInfo');
    const advancePaymentSection = document.getElementById('advancePaymentSection');
    
    // Check if current user is cashier (hide advance payment)
    const isCashier = <?php echo hasRole(['cashier']) ? 'true' : 'false'; ?>;
    
    if (customerType === 'client') {
        clientSelection.classList.remove('hidden');
        // Only show advance payment if user is NOT a cashier
        if (!isCashier) {
            advancePaymentSection.classList.remove('hidden');
        }
    } else {
        clientSelection.classList.add('hidden');
        advancePaymentSection.classList.add('hidden');
        selectedClientInfo.classList.add('hidden');
        // Clear customer search and selection
        clearCustomerSelection();
        clearAdvancePayment();
    }
}


async function loadCustomerTransactions(clientId) {
    if (!clientId) return;
    
    try {
        const response = await fetch(`../api/customers/transactions.php?client_id=${clientId}`);
        const result = await response.json();
        
        if (result.success) {
            displayCustomerTransactions(result.data);
        } else {
            console.error('Error loading transactions:', result.message);
        }
    } catch (error) {
        console.error('Error loading transactions:', error);
    }
}

function displayCustomerTransactions(data) {
    const transactionsContainer = document.getElementById('customerTransactions');
    const transactionsList = document.getElementById('transactionsList');
    const summaryContainer = document.getElementById('transactionSummary');
    
    if (data.transactions.length === 0) {
        transactionsContainer.classList.add('hidden');
        return;
    }
    
    transactionsContainer.classList.remove('hidden');
    
    // Clear existing transactions
    transactionsList.innerHTML = '';
    
    // Display each transaction
    data.transactions.forEach(transaction => {
        const transactionEl = document.createElement('div');
        transactionEl.className = 'p-3 border border-gray-200 rounded-lg bg-white';
        
        const isFullyPaid = transaction.remaining_amount <= 0;
        const statusColor = isFullyPaid ? 'text-green-600' : 'text-red-600';
        const statusText = isFullyPaid ? '<?php echo __('paid_status'); ?>' : '<?php echo __('remaining_status'); ?>';
        
        transactionEl.innerHTML = `
            <div class="flex justify-between items-start mb-2">
                <div class="flex-1">
                    <div class="text-sm font-medium text-gray-900">
                        #${transaction.transaction_id} - ${transaction.document_type}
                    </div>
                    <div class="text-xs text-gray-500">
                        ${new Date(transaction.created_at).toLocaleDateString()}
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-sm font-medium ${statusColor}">
                        ${statusText}
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <div>
                    <span class="text-gray-500"><?php echo __('total_label'); ?>:</span>
                    <span class="font-medium ml-1">${parseFloat(transaction.total_amount).toFixed(2)} DH</span>
                </div>
                <div>
                    <span class="text-gray-500"><?php echo __('advance_label'); ?>:</span>
                    <span class="font-medium ml-1 text-green-600">${parseFloat(transaction.advance_payment).toFixed(2)} DH</span>
                </div>
                <div>
                    <span class="text-gray-500"><?php echo __('remaining_label'); ?>:</span>
                    <span class="font-medium ml-1 ${statusColor}">${parseFloat(transaction.remaining_amount).toFixed(2)} DH</span>
                </div>
                <div>
                    ${!isFullyPaid ? `
                        <button onclick="payRemainingAmount(${transaction.transaction_id}, ${transaction.remaining_amount})" 
                                class="px-2 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 transition-colors">
                            <?php echo __('pay_rest_button'); ?>
                        </button>
                    ` : ''}
                </div>
            </div>
        `;
        
        transactionsList.appendChild(transactionEl);
    });
    
    // Update summary
    document.getElementById('totalAdvance').textContent = `${parseFloat(data.summary.total_advance).toFixed(2)} DH`;
    document.getElementById('totalRemaining').textContent = `${parseFloat(data.summary.total_remaining).toFixed(2)} DH`;
    summaryContainer.classList.remove('hidden');
}

async function payRemainingAmount(transactionId, remainingAmount) {
    const paymentAmount = prompt(t('prompt_payment_amount', {amount: remainingAmount.toFixed(2), currency: 'DH'}), remainingAmount.toFixed(2));
    
    if (!paymentAmount || parseFloat(paymentAmount) <= 0) {
        return;
    }
    
    const paymentMode = document.getElementById('paymentMode').value;
    
    if (!confirm(t('confirm_payment', {amount: parseFloat(paymentAmount).toFixed(2), currency: 'DH', id: transactionId}))) {
        return;
    }
    
    try {
        const response = await fetch('../api/customers/pay_remaining.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                transaction_id: transactionId,
                payment_amount: parseFloat(paymentAmount),
                payment_mode_id: parseInt(paymentMode)
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(t('alert_payment_recorded'));
            
            // Reload transactions for the current customer
            const selectedClientInfo = document.getElementById('selectedClientInfo');
            const clientId = selectedClientInfo.dataset.clientId;
            if (clientId) {
                loadCustomerTransactions(clientId);
            }
        } else {
            alert(t('alert_payment_error') + ' ' + result.message);
        }
    } catch (error) {
        console.error('Error processing payment:', error);
        alert(t('alert_payment_try_again'));
    }
}

async function searchProducts(query = '') {
    console.log('Searching for:', query);
    try {
        const url = `../api/products/search.php?q=${encodeURIComponent(query)}`;
        console.log('Fetching URL:', url);
        
        const res = await fetch(url);
        console.log('Response status:', res.status, res.statusText);
        
        const data = await res.json();
        console.log('Response data:', data);
        
        if (data.success) {
            products = data.data;
            console.log('Products loaded:', products.length);
            renderProducts(products);
        } else {
            console.error('API returned error:', data.message);
            const grid = document.getElementById('productGrid');
            grid.innerHTML = `
                <div class="col-span-full text-center text-red-500 py-12">
                    <p class="text-lg">Error: ${data.message}</p>
                </div>
            `;
        }
    } catch (err) {
        console.error('Search failed', err);
        const grid = document.getElementById('productGrid');
        grid.innerHTML = `
            <div class="col-span-full text-center text-red-500 py-12">
                <p class="text-lg">Search failed: ${err.message}</p>
            </div>
        `;
    }
}

function renderProducts(list) {
    const grid = document.getElementById('productGrid');
    grid.innerHTML = '';

    if (list.length === 0) {
        grid.innerHTML = `
            <div class="col-span-full text-center text-gray-500 py-16">
                <div class="flex flex-col items-center justify-center space-y-4">
                    <div class="relative">
                        <i class="fas fa-search text-6xl text-gray-300"></i>
                        <i class="fas fa-exclamation-triangle text-2xl text-yellow-400 absolute -top-2 -right-2"></i>
                    </div>
                    <div>
                        <p class="text-lg font-medium"><?php echo __('no_products_found'); ?></p>
                        <p class="text-sm text-gray-400 mt-1">${translations.try_adjusting_search}</p>
                    </div>
                </div>
            </div>
        `;
        return;
    }

    list.forEach(p => {
        const stock = parseInt(p.stock || 0);
        const stockStatus = stock === 0 ? 'out-of-stock' :
                           stock <= 5 ? 'low-stock' : 'in-stock';
        const stockColor = stock === 0 ? 'text-red-600 bg-red-50' :
                          stock <= 5 ? 'text-yellow-700 bg-yellow-50' : 'text-green-600 bg-green-50';
        const stockIcon = stock === 0 ? 'fas fa-times-circle' :
                         stock <= 5 ? 'fas fa-exclamation-triangle' : 'fas fa-check-circle';

        const el = document.createElement('div');
        el.className = 'group bg-white border border-gray-200 rounded-xl p-4 cursor-pointer hover:shadow-xl hover:border-blue-300 transition-all duration-300 hover:scale-105 hover:-translate-y-1 relative overflow-hidden';

        // Add subtle gradient overlay on hover
        el.innerHTML = `
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-indigo-50 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
            <div class="relative z-10">
                <!-- Stock Status Badge -->
                <div class="flex justify-between items-start mb-3">
                    <div class="flex items-center space-x-1 px-2 py-1 rounded-full text-xs font-medium ${stockColor}">
                        <i class="${stockIcon}"></i>
                        <span>${stock === 0 ? '<?php echo __('out_of_stock'); ?>' : stock <= 5 ? '<?php echo __('low_stock'); ?>' : '<?php echo __('in_stock'); ?>'}</span>
                    </div>
                    <div class="text-xs text-gray-500 font-medium">
                        ${stock > 0 ? `${stock} <?php echo __('items_left'); ?>` : '<?php echo __('unavailable'); ?>'}
                    </div>
                </div>

                <!-- Product Name -->
                <h3 class="font-bold text-gray-900 text-sm line-clamp-2 mb-2 leading-tight group-hover:text-blue-700 transition-colors duration-200">
                    ${p.name}
                </h3>

                <!-- Price Display -->
                <div class="space-y-1">
                    <div class="flex items-baseline space-x-2">
                        <span class="text-lg font-bold ${isWholesaleMode ? 'text-green-600' : 'text-blue-600'} group-hover:scale-110 transition-transform duration-200">
                            ${parseFloat(isWholesaleMode ? p.wholesale : p.sale_price).toFixed(2)}<?php echo getSetting('currency_symbol', 'DH'); ?>
                        </span>
                        ${isWholesaleMode ? '<span class="text-xs text-green-600 font-medium">Wholesale</span>' : ''}
                    </div>

                    <!-- Original Price (if wholesale mode) -->
                    ${isWholesaleMode && p.sale_price != p.wholesale ? `
                        <div class="text-xs text-gray-500 line-through">
                            ${translations.retail} ${parseFloat(p.sale_price).toFixed(2)}<?php echo getSetting('currency_symbol', 'DH'); ?>
                        </div>
                    ` : ''}

                    <!-- Stock Level Indicator -->
                    <div class="flex items-center space-x-2 mt-2">
                        <div class="flex-1 bg-gray-200 rounded-full h-1.5">
                            <div class="bg-gradient-to-r ${stock === 0 ? 'from-red-400 to-red-600' :
                                                         stock <= 5 ? 'from-yellow-400 to-yellow-600' :
                                                         'from-green-400 to-green-600'} h-1.5 rounded-full transition-all duration-500"
                                 style="width: ${Math.min((stock / 50) * 100, 100)}%"></div>
                        </div>
                        <span class="text-xs text-gray-600 font-medium">${stock}</span>
                    </div>
                </div>

                <!-- Quick Action Indicator -->
                <div class="absolute bottom-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center shadow-lg">
                        <i class="fas fa-cart-plus text-white text-xs"></i>
                    </div>
                </div>
            </div>
        `;
        el.onclick = () => addToCart(p);
        grid.appendChild(el);
    });
}

function addToCart(product) {
    const existing = cart.find(item => item.id === product.id);
    if (existing) {
        existing.quantity++;
    } else {
        cart.push({
            id: product.id,
            name: product.name,
            unit_price: parseFloat(isWholesaleMode ? product.wholesale : product.sale_price),
            quantity: 1
        });
    }
    renderCart();
}

function removeFromCart(index) {
    cart.splice(index, 1);
    renderCart();
}

function updateQty(index, delta) {
    cart[index].quantity += delta;
    if (cart[index].quantity <= 0) {
        cart.splice(index, 1);
    }
    renderCart();
}

function renderCart() {
    const container = document.getElementById('cartItems');
    container.innerHTML = '';
    let total = 0;

    if (cart.length === 0) {
        container.innerHTML = `
            <div class="text-center text-gray-500 py-8">
                <i class="fas fa-shopping-cart mx-auto h-12 w-12 text-gray-400 mb-4 text-4xl"></i>
                <p><?php echo __('your_cart_is_empty'); ?></p>
            </div>
        `;
    } else {
        cart.forEach((item, index) => {
            const itemDiscount = item.discount_percent || 0;
            const discountedPrice = item.unit_price * (1 - itemDiscount / 100);
            const itemTotal = discountedPrice * item.quantity;
            total += itemTotal;
            
            const li = document.createElement('div');
            li.className = 'flex justify-between items-start py-3 border-b border-gray-100 last:border-b-0';
            li.innerHTML = `
                <div class="flex-1">
                    <h4 class="font-medium text-gray-900">${item.name}</h4>
                    <div class="flex items-center space-x-2 mt-1">
                        <p class="text-sm text-gray-500">
                            ${item.unit_price.toFixed(2)}<?php echo getSetting('currency_symbol', 'DH'); ?> × ${item.quantity}
                        </p>
                        ${itemDiscount > 0 ? `<span class="text-xs text-green-600 font-medium">-${itemDiscount}%</span>` : ''}
                    </div>
                    ${itemDiscount > 0 ? `<p class="text-xs text-gray-400 line-through">${(item.unit_price * item.quantity).toFixed(2)}<?php echo getSetting('currency_symbol', 'DH'); ?></p>` : ''}
                    <div class="flex items-center space-x-2 mt-1">
                        <input type="number" 
                               id="itemDiscount-${index}"
                               min="0" 
                               max="100" 
                               step="0.1" 
                               placeholder="0%"
                               value="${itemDiscount}"
                               class="w-16 px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-transparent"
                               onchange="updateItemDiscount(${index}, this.value)">
                        <span class="text-xs text-gray-500">${t('discount')} %</span>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="text-right">
                        <span class="font-semibold text-gray-900">${itemTotal.toFixed(2)}<?php echo getSetting('currency_symbol', 'DH'); ?></span>
                        ${itemDiscount > 0 ? `<p class="text-xs text-green-600">${t('save')} ${(item.unit_price * item.quantity - itemTotal).toFixed(2)}<?php echo getSetting('currency_symbol', 'DH'); ?></p>` : ''}
                    </div>
                    <div class="flex items-center space-x-1">
                        <button onclick="updateQty(${index}, -1)" 
                                class="w-8 h-8 bg-gray-100 text-gray-600 rounded-full hover:bg-gray-200 flex items-center justify-center text-sm font-bold transition-colors">
                            -
                        </button>
                        <button onclick="updateQty(${index}, 1)" 
                                class="w-8 h-8 bg-gray-100 text-gray-600 rounded-full hover:bg-gray-200 flex items-center justify-center text-sm font-bold transition-colors">
                            +
                        </button>
                        <button onclick="removeFromCart(${index})" 
                                class="w-8 h-8 bg-red-100 text-red-600 rounded-full hover:bg-red-200 flex items-center justify-center text-sm font-bold transition-colors">
                            ×
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(li);
        });
    }

    // Apply discount and update total
    updateCartTotal();
}

// Save draft function
async function saveDraft() {
    if (cart.length === 0) {
        alert(t('alert_empty_cart_draft'));
        return;
    }

    const customerType = document.querySelector('input[name="customerType"]:checked').value;
    // Get client ID from the new searchable customer selection
    const selectedClientInfo = document.getElementById('selectedClientInfo');
    const clientId = customerType === 'client' && selectedClientInfo.dataset.clientId ? parseInt(selectedClientInfo.dataset.clientId) : null;
    const saleType = document.getElementById('saleType').value;
    const paymentMode = document.getElementById('paymentMode').value;
    const discountPercent = parseFloat(document.getElementById('discountInput').value) || 0;

    const draftData = {
        items: cart,
        client_id: clientId,
        sale_type: saleType,
        payment_mode_id: paymentMode,
        discount_percent: discountPercent,
        created_at: new Date().toISOString()
    };

    try {
        const response = await fetch('../api/sales/save_draft.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(draftData)
        });

        const result = await response.json();

        if (result.success) {
            alert(t('alert_draft_saved'));
            console.log('Draft saved with ID:', result.draft_id);
            
            // Clear cart after successful draft save
            cart = [];
            renderCart();
            updateCartTotal();
            
            // Reset customer selection
            document.querySelector('input[name="customerType"][value="counter"]').checked = true;
            handleCustomerTypeChange();
            
            // Reset other fields
            document.getElementById('saleType').value = 'sale';
            document.getElementById('paymentMode').value = '1';
            document.getElementById('discountInput').value = '';
        } else {
            alert(t('alert_draft_save_error') + ' ' + result.message);
        }
    } catch (error) {
        console.error('Error saving draft:', error);
        alert(t('alert_draft_try_again'));
    }
}

// Load drafts modal
async function loadDraftsModal() {
    try {
        const response = await fetch('../api/sales/list_drafts.php');
        const result = await response.json();

        if (result.success) {
            showDraftsModal(result.data);
        } else {
            alert(t('alert_draft_load_error') + ' ' + result.message);
        }
    } catch (error) {
        console.error('Error loading drafts:', error);
        alert(t('alert_draft_load_try_again'));
    }
}

// Show drafts modal
function showDraftsModal(drafts) {
    // Remove existing modal if any
    const existingModal = document.getElementById('draftsModal');
    if (existingModal) {
        existingModal.remove();
    }

    const modal = document.createElement('div');
    modal.id = 'draftsModal';
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center';
    modal.innerHTML = `
        <div class="bg-white rounded-xl shadow-xl p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900"><?php echo __('load_draft_order'); ?></h3>
                <button onclick="closeDraftsModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times w-6 h-6"></i>
                </button>
            </div>
            <div class="space-y-4">
                ${drafts.length === 0 ? `
                    <div class="text-center py-8">
                        <i class="fas fa-file-alt mx-auto h-12 w-12 text-gray-400 mb-4 text-4xl"></i>
                        <p class="text-gray-500"><?php echo __('no_draft_orders_found'); ?></p>
                    </div>
                ` : drafts.map(draft => `
                    <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900"><?php echo __('draft'); ?> #${draft.id}</p>
                                <p class="text-sm text-gray-500">${new Date(draft.created_at).toLocaleString()}</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="px-2 py-1 text-xs font-medium rounded-full ${draft.client_id ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'}">
                                    ${draft.client_id ? '<?php echo __('customer'); ?>' : '<?php echo __('counter'); ?>'}
                                </span>
                                <button onclick="deleteDraft(${draft.id})" class="text-red-500 hover:text-red-700 p-1" title="<?php echo __('delete_draft'); ?>">
                                    <i class="fas fa-trash w-5 h-5"></i>
                                </button>
                            </div>
                        </div>
                        <div class="text-sm text-gray-600 mb-3">
                            <p><strong><?php echo __('items'); ?>:</strong> ${draft.items_count} items</p>
                            <p><strong><?php echo __('total'); ?>:</strong> <?php echo getSetting('currency_symbol', 'DH'); ?>${parseFloat(draft.total_amount).toFixed(2)}</p>
                            <p><strong><?php echo __('type'); ?>:</strong> ${draft.document_type}</p>
                            ${draft.client_name ? `<p><strong><?php echo __('customer'); ?>:</strong> ${draft.client_name}</p>` : ''}
                        </div>
                        <div class="flex space-x-2">
                            <button onclick="loadDraft(${draft.id})" class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                                <i class="fas fa-download inline-block w-4 h-4 mr-2"></i>
                                <?php echo __('load_draft'); ?>
                            </button>
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    `;

    document.body.appendChild(modal);
}

// Close drafts modal
function closeDraftsModal() {
    const modal = document.getElementById('draftsModal');
    if (modal) {
        modal.remove();
    }
}

// Delete draft function
async function deleteDraft(draftId) {
    if (!confirm(t('confirm_delete_draft'))) {
        return;
    }

    try {
        const response = await fetch(`../api/sales/delete_draft.php?id=${draftId}`, {
            method: 'DELETE'
        });
        
        const result = await response.json();

        if (result.success) {
            alert(t('alert_draft_deleted'));
            // Refresh the drafts list
            loadDraftsModal();
        } else {
            alert(t('alert_draft_delete_error') + ' ' + result.message);
        }
    } catch (error) {
        console.error('Error deleting draft:', error);
        alert(t('alert_draft_load_try_again'));
    }
}

// Load specific draft
async function loadDraft(draftId) {
    try {
        const response = await fetch(`../api/sales/load_draft.php?id=${draftId}`);
        const result = await response.json();

        if (result.success) {
            const draft = result.data;
            
            // Clear current cart
            cart = [];
            
            // Load draft items into cart
            draft.items.forEach(item => {
                cart.push({
                    id: item.article_id,
                    name: item.product_name,
                    unit_price: parseFloat(item.unit_price),
                    quantity: parseInt(item.quantity),
                    discount_percent: parseFloat(item.discount_percent || 0)
                });
            });
            
            // Set customer type and client
            const customerType = draft.client_id ? 'client' : 'counter';
            document.querySelector(`input[name="customerType"][value="${customerType}"]`).checked = true;
            handleCustomerTypeChange();
            
            if (draft.client_id) {
                // Load customer using the new system
                const client = allClients.find(c => c.id === draft.client_id);
                if (client) {
                    selectCustomer(client.id);
                }
            }
            
            // Set other fields
            document.getElementById('saleType').value = draft.document_type || 'sale';
            document.getElementById('paymentMode').value = draft.payment_mode_id || 1;
            document.getElementById('discountInput').value = draft.discount_percent || 0;
            
            // Update display
            renderCart();
            updateCartTotal();
            
            // Close modal
            closeDraftsModal();
            
            // Delete the draft from database after loading
            try {
                await fetch(`../api/sales/delete_draft.php?id=${draftId}`, {
                    method: 'DELETE'
                });
                console.log('Draft deleted from database after loading');
            } catch (deleteError) {
                console.error('Error deleting draft after loading:', deleteError);
                // Don't show error to user as the draft was loaded successfully
            }
            
            alert(t('alert_draft_loaded'));
        } else {
            alert(t('alert_draft_load_error') + ' ' + result.message);
        }
    } catch (error) {
        console.error('Error loading draft:', error);
    }
}

// Clear cart function
function clearCart() {
    if (confirm(t('confirm_clear_cart'))) {
        cart = [];
        renderCart();
        updateCartTotal();
    }
}

function updateItemDiscount(index, discountValue) {
    const discount = parseFloat(discountValue) || 0;
    if (discount >= 0 && discount <= 100) {
        cart[index].discount_percent = discount;
        renderCart();
        updateCartTotal();
    }
}

function applyDiscount() {
    updateCartTotal();
}

function clearDiscount() {
    document.getElementById('discountInput').value = '';
    updateCartTotal();
}

async function processSale() {
    if (cart.length === 0) {
        alert('<?php echo __('alert_empty_cart'); ?>');
        return;
    }
    
    const btn = document.getElementById('checkoutBtn');
    btn.disabled = true;
    btn.innerHTML = `
        <i class="fas fa-spinner fa-spin mr-2"></i>
        <?php echo __('processing'); ?>...
    `;
    
    const paymentMode = document.getElementById('paymentMode').value;
    const saleType = document.getElementById('saleType').value;
    const discountPercent = parseFloat(document.getElementById('discountInput').value) || 0;
    const customerType = document.querySelector('input[name="customerType"]:checked').value;
    const advancePayment = parseFloat(document.getElementById('advancePaymentInput').value) || 0;
    
    // Get client information if customer type is 'client'
    let clientId = null;
    if (customerType === 'client') {
        // Get client ID from the new searchable customer selection
        const selectedClientInfo = document.getElementById('selectedClientInfo');
        clientId = selectedClientInfo.dataset.clientId ? parseInt(selectedClientInfo.dataset.clientId) : null;
        
        if (!clientId) {
            alert('Please select a customer for this sale type.');
            btn.disabled = false;
            btn.innerHTML = `
                <i class="fas fa-credit-card mr-2"></i>
                Checkout
            `;
            return;
        }
    }
    
    console.log('Checkout initiated:', { items: cart, paymentMode, saleType, discount: discountPercent, customerType, clientId });
    
    try {
        // First check if user is logged in
        const sessionCheck = await fetch('../api/auth/check_session.php');
        const sessionData = await sessionCheck.json();
        
        if (!sessionData.logged_in) {
            alert('<?php echo __('alert_login'); ?>');
            btn.disabled = false;
            btn.innerHTML = `
                <i class="fas fa-credit-card mr-2"></i>
                Checkout
            `;
            return;
        }
        
        const requestData = {
            items: cart,
            payment_mode_id: paymentMode,
            document_type: saleType,
            discount_percent: discountPercent,
            client_id: clientId,
            advance_payment: advancePayment
        };
        
        console.log('Sending request to API:', JSON.stringify(requestData));
        
        const res = await fetch('../api/sales/create.php', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(requestData)
        });
        
        console.log('Response status:', res.status);
        console.log('Response headers:', res.headers);
        
        const responseText = await res.text();
        console.log('Raw Response:', responseText);

        let result;
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            throw new Error(`Invalid Server Response: ${responseText.substring(0, 100)}...`);
        }
        
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status} - ${result.message || responseText}`);
        }
        
        console.log('API Response:', result);
        
        if (result.success) {
            // Clear cart and redirect to print receipt page
            cart = [];
            renderCart();
            window.location.href = `print_receipt.php?sale_id=${result.sale_id}`;
        } else {
            alert('<?php echo __('error'); ?>: ' + result.message);
            btn.disabled = false;
            btn.innerHTML = `
                <i class="fas fa-credit-card mr-2"></i>
                Checkout
            `;
        }
    } catch (err) {
        console.error('Checkout error:', err);
        alert('<?php echo __('network_error'); ?>: ' + err.message + '\n\n<?php echo __('check_internet'); ?>');
        btn.disabled = false;
        btn.innerHTML = `
            <i class="fas fa-credit-card mr-2"></i>
            Checkout
        `;
    }
}

// Advance payment functions
function updateTotal() {
    updateCartTotal();
}

function clearAdvancePayment() {
    document.getElementById('advancePaymentInput').value = '';
    document.getElementById('advanceDisplay').classList.add('hidden');
    updateCartTotal();
}

function updateCartTotal() {
    let subtotal = 0;
    let totalDiscount = 0;
    
    cart.forEach(item => {
        const itemDiscount = item.discount_percent || 0;
        const discountedPrice = item.unit_price * (1 - itemDiscount / 100);
        const itemTotal = discountedPrice * item.quantity;
        subtotal += itemTotal;
        totalDiscount += (item.unit_price * item.quantity - itemTotal);
    });
    
    // Get additional discount percentage
    const discountInput = document.getElementById('discountInput');
    const discountPercent = parseFloat(discountInput.value) || 0;
    const additionalDiscount = subtotal * (discountPercent / 100);
    totalDiscount += additionalDiscount;
    
    // Get advance payment (for customer record, not deducted from total)
    const advancePayment = parseFloat(document.getElementById('advancePaymentInput').value) || 0;
    
    // Calculate final total (sale amount - discounts only, advance NOT deducted)
    const finalTotal = subtotal - totalDiscount;
    
    // Update discount display
    const discountDisplay = document.getElementById('discountDisplay');
    const discountAmount = document.getElementById('discountAmount');
    if (discountPercent > 0) {
        discountDisplay.classList.remove('hidden');
        discountAmount.textContent = `-${additionalDiscount.toFixed(2)}<?php echo getSetting('currency_symbol', 'DH'); ?>`;
    } else {
        discountDisplay.classList.add('hidden');
    }
    
    // Update advance display (show as customer balance info, not deduction)
    const advanceDisplay = document.getElementById('advanceDisplay');
    const advanceAmountDisplay = document.getElementById('advanceAmount');
    if (advancePayment > 0) {
        advanceDisplay.classList.remove('hidden');
        advanceAmountDisplay.textContent = `${advancePayment.toFixed(2)}<?php echo getSetting('currency_symbol', 'DH'); ?> (stored for customer)`;
    } else {
        advanceDisplay.classList.add('hidden');
    }
    
    // Update total display (full sale amount, advance NOT deducted)
    document.getElementById('cartTotal').textContent = finalTotal.toFixed(2) + '<?php echo getSetting('currency_symbol', 'DH'); ?>';
    
    // Enable/disable checkout button
    const checkoutBtn = document.getElementById('checkoutBtn');
    checkoutBtn.disabled = cart.length === 0;
}

</script>

<?php require_once '../includes/footer.php'; ?>