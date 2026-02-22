<?php
/**
 * ESC/POS Thermal Printer Helper Class
 * Compatible with Epson TM-T20III and similar thermal printers
 * 
 * This class provides methods to generate ESC/POS commands for direct printer communication
 * Currently structured for future implementation - can be used with raw socket/USB printing
 */

class ESCPOSHelper {
    // ESC/POS Command constants
    const ESC = "\x1b";
    const GS = "\x1d";
    const LF = "\x0a";
    const CR = "\x0d";
    
    // Printer initialization
    public static function initialize() {
        return self::ESC . "@"; // Initialize printer
    }
    
    // Text formatting
    public static function bold($text) {
        return self::ESC . "E" . chr(1) . $text . self::ESC . "E" . chr(0);
    }
    
    public static function underline($text, $mode = 1) {
        return self::ESC . "-" . chr($mode) . $text . self::ESC . "-" . chr(0);
    }
    
    public static function doubleHeight($text) {
        return self::ESC . "!" . chr(16) . $text . self::ESC . "!" . chr(0);
    }
    
    public static function doubleWidth($text) {
        return self::ESC . "!" . chr(32) . $text . self::ESC . "!" . chr(0);
    }
    
    public static function doubleSize($text) {
        return self::ESC . "!" . chr(48) . $text . self::ESC . "!" . chr(0);
    }
    
    // Alignment
    public static function alignCenter() {
        return self::ESC . "a" . chr(1);
    }
    
    public static function alignLeft() {
        return self::ESC . "a" . chr(0);
    }
    
    public static function alignRight() {
        return self::ESC . "a" . chr(2);
    }
    
    // Line spacing
    public static function lineSpacing($n) {
        return self::ESC . "3" . chr($n);
    }
    
    // Margins
    public static function setMargins($left, $right) {
        return self::GS . "L" . chr($left) . chr($right);
    }
    
    // Paper cut
    public static function cutPaper($mode = 0) {
        return self::GS . "V" . chr($mode);
    }
    
    // Cash drawer
    public static function openCashDrawer($pin = 0) {
        return self::ESC . "p" . chr($pin) . chr(100) . chr(250);
    }
    
    // Barcode printing
    public static function barcode($code, $type = '73', $width = 2, $height = 64, $position = 0, $font = 0) {
        return self::GS . "k" . chr($type) . $code . chr(0) . 
               self::GS . "w" . chr($width) . 
               self::GS . "h" . chr($height) . 
               self::GS . "H" . chr($position) . 
               self::GS . "f" . chr($font);
    }
    
    // QR code printing
    public static function qrCode($data, $model = 2, $size = 6, $errorCorrection = 0) {
        $pL = strlen($data) + 3;
        $pH = floor($pL / 256);
        $pL = $pL % 256;
        
        return self::GS . "(" . chr(107) . chr(4) . chr(0) . chr(49) . chr(65) . chr(50) . chr(0) .
               self::GS . "(" . chr(107) . chr(3) . chr(0) . chr(49) . chr(67) . chr($errorCorrection) .
               self::GS . "(" . chr(107) . chr(3) . chr(0) . chr(49) . chr(67) . chr($size) .
               self::GS . "(" . chr(107) . chr($pH) . chr($pL) . chr(49) . chr(80) . chr(48) . $data .
               self::GS . "(" . chr(107) . chr(3) . chr(0) . chr(49) . chr(81) . chr(0);
    }
    
    // Image printing (for future implementation)
    public static function printImage($imageData, $width = null) {
        // This would require image processing and conversion to printer bitmap format
        // Complex implementation needed for future version
        return "";
    }
    
    // Complete receipt template generator
    public static function generateReceipt($saleData, $companyData, $items) {
        $commands = self::initialize();
        
        // Header with company info
        $commands .= self::alignCenter();
        $commands .= self::doubleSize(self::bold($companyData['name'])) . self::LF;
        $commands .= self::alignCenter();
        $commands .= $companyData['address'] . self::LF;
        $commands .= $companyData['phone'] . self::LF;
        if (!empty($companyData['email'])) {
            $commands .= $companyData['email'] . self::LF;
        }
        $commands .= self::LF;
        
        // Receipt info
        $commands .= self::alignLeft();
        $commands .= self::bold("RECEIPT #: " . $saleData['receipt_number']) . self::LF;
        $commands .= "Date: " . $saleData['date'] . self::LF;
        $commands .= "Cashier: " . $saleData['cashier'] . self::LF;
        if (!empty($saleData['customer'])) {
            $commands .= "Customer: " . $saleData['customer'] . self::LF;
        }
        $commands .= self::LF;
        
        // Items
        foreach ($items as $item) {
            $commands .= self::bold($item['name']) . self::LF;
            $commands .= sprintf("%d x %.2f    %.2f %s", 
                $item['quantity'], 
                $item['unit_price'], 
                $item['total'], 
                $companyData['currency']
            ) . self::LF;
        }
        
        // Totals
        $commands .= self::LF;
        $commands .= str_repeat("-", 32) . self::LF;
        $commands .= sprintf("Subtotal: %.2f %s", $saleData['subtotal'], $companyData['currency']) . self::LF;
        $commands .= sprintf("VAT (%.0f%%): %.2f %s", $saleData['tax_rate'], $saleData['tax'], $companyData['currency']) . self::LF;
        if ($saleData['discount'] > 0) {
            $commands .= sprintf("Discount: %.2f %s", $saleData['discount'], $companyData['currency']) . self::LF;
        }
        $commands .= self::doubleSize(self::bold(sprintf("TOTAL: %.2f %s", $saleData['total'], $companyData['currency']))) . self::LF;
        
        // Payment
        $commands .= self::LF;
        $commands .= "Payment: " . $saleData['payment_method'] . self::LF;
        if ($saleData['advance'] > 0) {
            $commands .= sprintf("Advance: %.2f %s", $saleData['advance'], $companyData['currency']) . self::LF;
            $commands .= sprintf("Remaining: %.2f %s", $saleData['remaining'], $companyData['currency']) . self::LF;
        }
        
        // Footer
        $commands .= self::LF;
        $commands .= self::alignCenter();
        $commands .= self::bold("THANK YOU FOR YOUR PURCHASE!") . self::LF;
        $commands .= "Please come again" . self::LF;
        if (!empty($companyData['phone'])) {
            $commands .= "For support: " . $companyData['phone'] . self::LF;
        }
        $commands .= self::LF;
        $commands .= date('Y') . " © " . $companyData['name'] . self::LF;
        
        // Cut paper
        $commands .= self::cutPaper();
        
        return $commands;
    }
    
    // Send commands to printer (for future implementation)
    public static function sendToPrinter($commands, $printerPath = '/dev/usb/lp0') {
        // This would be implemented for direct USB/network printing
        // Could use file_put_contents for USB printers or socket for network printers
        return false;
    }
    
    // Validate printer connection
    public static function testPrinter($printerPath = '/dev/usb/lp0') {
        // Test command to check if printer is responsive
        $test = self::initialize() . self::bold("TEST") . self::LF;
        return self::sendToPrinter($test, $printerPath);
    }
}

/**
 * Usage example for future implementation:
 * 
 * $receiptData = [
 *     'receipt_number' => 'R-2025-000123',
 *     'date' => '17/02/2026 16:52',
 *     'cashier' => 'John Doe',
 *     'customer' => 'Customer Name',
 *     'subtotal' => 100.00,
 *     'tax_rate' => 20,
 *     'tax' => 20.00,
 *     'discount' => 5.00,
 *     'total' => 115.00,
 *     'payment_method' => 'Cash',
 *     'advance' => 0,
 *     'remaining' => 0
 * ];
 * 
 * $companyData = [
 *     'name' => 'My Shop',
 *     'address' => '123 Main St',
 *     'phone' => '+212 123-456789',
 *     'email' => 'info@shop.com',
 *     'currency' => 'DH'
 * ];
 * 
 * $items = [
 *     ['name' => 'Product 1', 'quantity' => 2, 'unit_price' => 25.00, 'total' => 50.00],
 *     ['name' => 'Product 2', 'quantity' => 1, 'unit_price' => 50.00, 'total' => 50.00]
 * ];
 * 
 * $commands = ESCPOSHelper::generateReceipt($receiptData, $companyData, $items);
 * ESCPOSHelper::sendToPrinter($commands);
 */
?>
