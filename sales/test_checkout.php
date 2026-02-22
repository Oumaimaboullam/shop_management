<?php
require_once '../includes/functions.php';
require_once '../config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo "<h1>Not Logged In</h1>";
    echo "<p>Please <a href='../login.php'>log in</a> first.</p>";
    exit();
}

$pageTitle = 'Test Checkout';
require_once '../includes/header.php';
?>

<div class="min-h-screen bg-gray-50 p-4">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Test Checkout Process</h1>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Session Information</h2>
            <div class="bg-gray-50 p-4 rounded">
                <p><strong>User ID:</strong> <?php echo $_SESSION['user_id'] ?? 'Not set'; ?></p>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($_SESSION['user']['username'] ?? 'Not set'); ?></p>
                <p><strong>Role:</strong> <?php echo htmlspecialchars($_SESSION['user']['role'] ?? 'Not set'); ?></p>
                <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Test API Connection</h2>
            <button id="testConnection" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Test API Connection
            </button>
            <div id="connectionResult" class="mt-4"></div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Test Sale Creation</h2>
            <button id="testSale" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                Test Create Sale
            </button>
            <div id="saleResult" class="mt-4"></div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold mb-4">Test Cart Checkout</h2>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Sale Type</label>
                <select id="testSaleType" class="w-full px-3 py-2 border border-gray-300 rounded">
                    <option value="sale">Sale</option>
                    <option value="invoice">Invoice</option>
                    <option value="quote">Quote</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                <select id="testPaymentMode" class="w-full px-3 py-2 border border-gray-300 rounded">
                    <option value="1">Cash</option>
                    <option value="2">Credit Card</option>
                </select>
            </div>
            <button id="testCheckout" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">
                Test Full Checkout
            </button>
            <div id="checkoutResult" class="mt-4"></div>
        </div>
    </div>
</div>

<script>
document.getElementById('testConnection').addEventListener('click', async function() {
    const resultDiv = document.getElementById('connectionResult');
    resultDiv.innerHTML = '<p class="text-blue-600">Testing connection...</p>';
    
    try {
        const response = await fetch('../api/auth/check_session.php');
        const data = await response.json();
        
        resultDiv.innerHTML = `
            <div class="bg-gray-50 p-4 rounded">
                <h3 class="font-semibold mb-2">Session Check Result:</h3>
                <pre class="text-sm">${JSON.stringify(data, null, 2)}</pre>
                <p class="mt-2 ${data.logged_in ? 'text-green-600' : 'text-red-600'}">
                    ${data.logged_in ? '✓ User is logged in' : '✗ User is not logged in'}
                </p>
            </div>
        `;
    } catch (error) {
        resultDiv.innerHTML = `
            <div class="bg-red-50 p-4 rounded text-red-600">
                <h3 class="font-semibold mb-2">Connection Error:</h3>
                <p>${error.message}</p>
                <p class="text-sm mt-2">Please check if the server is running and the API endpoint exists.</p>
            </div>
        `;
    }
});

document.getElementById('testSale').addEventListener('click', async function() {
    const resultDiv = document.getElementById('saleResult');
    resultDiv.innerHTML = '<p class="text-blue-600">Creating test sale...</p>';
    
    try {
        // First check session
        const sessionResponse = await fetch('../api/auth/check_session.php');
        const sessionData = await sessionResponse.json();
        
        if (!sessionData.logged_in) {
            resultDiv.innerHTML = `
                <div class="bg-red-50 p-4 rounded text-red-600">
                    <p>User is not logged in. Please log in first.</p>
                </div>
            `;
            return;
        }
        
        // Test sale creation
        const saleData = {
            items: [
                { id: 3, name: 'Test Product', price: 17.60, quantity: 1 }
            ],
            payment_mode_id: 1,
            document_type: 'sale'
        };
        
        const response = await fetch('../api/sales/create.php', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(saleData)
        });
        
        const responseText = await response.text();
        
        try {
            const data = JSON.parse(responseText);
            
            if (data.success) {
                resultDiv.innerHTML = `
                    <div class="bg-green-50 p-4 rounded text-green-600">
                        <h3 class="font-semibold mb-2">✓ Sale Created Successfully!</h3>
                        <p>Sale ID: ${data.sale_id}</p>
                        <p class="mt-2">
                            <a href="print_receipt.php?sale_id=${data.sale_id}" 
                               class="text-blue-600 hover:underline" target="_blank">
                                View Receipt
                            </a>
                        </p>
                    </div>
                `;
            } else {
                resultDiv.innerHTML = `
                    <div class="bg-red-50 p-4 rounded text-red-600">
                        <h3 class="font-semibold mb-2">✗ Sale Creation Failed</h3>
                        <p>${data.message}</p>
                    </div>
                `;
            }
        } catch (parseError) {
            resultDiv.innerHTML = `
                <div class="bg-red-50 p-4 rounded text-red-600">
                    <h3 class="font-semibold mb-2">Response Parse Error</h3>
                    <p>${parseError.message}</p>
                    <p class="text-sm mt-2">Raw response:</p>
                    <pre class="text-xs bg-gray-100 p-2 rounded">${responseText}</pre>
                </div>
            `;
        }
        
    } catch (error) {
        resultDiv.innerHTML = `
            <div class="bg-red-50 p-4 rounded text-red-600">
                <h3 class="font-semibold mb-2">Network Error</h3>
                <p>${error.message}</p>
                <p class="text-sm mt-2">Please check your network connection and server status.</p>
            </div>
        `;
    }
});

document.getElementById('testCheckout').addEventListener('click', async function() {
    const resultDiv = document.getElementById('checkoutResult');
    resultDiv.innerHTML = '<p class="text-blue-600">Testing checkout process...</p>';
    
    try {
        const saleType = document.getElementById('testSaleType').value;
        const paymentMode = document.getElementById('testPaymentMode').value;
        
        const checkoutData = {
            items: [
                { id: 3, name: 'Test Product', price: 17.60, quantity: 2 }
            ],
            payment_mode_id: paymentMode,
            document_type: saleType
        };
        
        resultDiv.innerHTML += '<p class="text-sm text-gray-600">Sending: ' + JSON.stringify(checkoutData) + '</p>';
        
        const response = await fetch('../api/sales/create.php', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(checkoutData)
        });
        
        const responseText = await response.text();
        
        try {
            const data = JSON.parse(responseText);
            
            if (data.success) {
                resultDiv.innerHTML = `
                    <div class="bg-green-50 p-4 rounded text-green-600">
                        <h3 class="font-semibold mb-2">✓ Checkout Successful!</h3>
                        <p>Sale Type: ${saleType}</p>
                        <p>Payment Mode: ${paymentMode}</p>
                        <p>Sale ID: ${data.sale_id}</p>
                        <p class="mt-2">
                            <a href="print_receipt.php?sale_id=${data.sale_id}" 
                               class="text-blue-600 hover:underline" target="_blank">
                                View Receipt
                            </a>
                        </p>
                    </div>
                `;
                
                // Optionally redirect to receipt
                if (confirm('Would you like to view the receipt?')) {
                    window.open(`print_receipt.php?sale_id=${data.sale_id}`, '_blank');
                }
            } else {
                resultDiv.innerHTML = `
                    <div class="bg-red-50 p-4 rounded text-red-600">
                        <h3 class="font-semibold mb-2">✗ Checkout Failed</h3>
                        <p>${data.message}</p>
                    </div>
                `;
            }
        } catch (parseError) {
            resultDiv.innerHTML = `
                <div class="bg-red-50 p-4 rounded text-red-600">
                    <h3 class="font-semibold mb-2">Response Parse Error</h3>
                    <p>${parseError.message}</p>
                    <p class="text-sm mt-2">Raw response:</p>
                    <pre class="text-xs bg-gray-100 p-2 rounded">${responseText}</pre>
                </div>
            `;
        }
        
    } catch (error) {
        resultDiv.innerHTML = `
            <div class="bg-red-50 p-4 rounded text-red-600">
                <h3 class="font-semibold mb-2">Network Error</h3>
                <p>${error.message}</p>
                <p class="text-sm mt-2">Status: ${response?.status || 'Unknown'}</p>
            </div>
        `;
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>