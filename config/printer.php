<?php
/**
 * Thermal Printer Configuration
 * Settings for ESC/POS compatible thermal printers
 */

// Printer connection settings
$printer_config = [
    // Connection type: 'usb', 'network', 'windows', 'mock'
    'connection_type' => 'mock', // Set to 'mock' for development/testing
    
    // USB/Serial printer settings
    'usb' => [
        'device_path' => '/dev/usb/lp0', // Linux
        'windows_port' => 'USB001', // Windows
        'baud_rate' => 9600,
        'data_bits' => 8,
        'stop_bits' => 1,
        'parity' => 'none'
    ],
    
    // Network printer settings
    'network' => [
        'ip' => '192.168.1.100',
        'port' => 9100,
        'timeout' => 10
    ],
    
    // Windows printer settings
    'windows' => [
        'printer_name' => 'Epson TM-T20III',
        'use_raw' => true
    ],
    
    // Paper settings
    'paper' => [
        'width' => 80, // mm (80mm thermal paper)
        'max_chars_per_line' => 48, // Characters per line for 80mm paper
        'font_size' => 12, // Base font size
        'line_spacing' => 30, // Default line spacing
        'margins' => [
            'left' => 2,
            'right' => 2
        ]
    ],
    
    // Formatting settings
    'formatting' => [
        'header_font_size' => 14,
        'title_font_size' => 16,
        'normal_font_size' => 12,
        'small_font_size' => 10,
        'bold' => true,
        'underline' => false,
        'double_width' => false,
        'double_height' => false
    ],
    
    // Auto-print settings
    'auto_print' => [
        'enabled' => true,
        'delay' => 500, // milliseconds before auto-print
        'close_after_print' => true,
        'close_delay' => 1000 // milliseconds after print
    ],
    
    // Cash drawer settings
    'cash_drawer' => [
        'enabled' => false,
        'pin' => 0, // Pin number for cash drawer (0 or 1)
        'pulse_time' => 100 // Pulse duration in milliseconds
    ],
    
    // Barcode settings
    'barcode' => [
        'enabled' => true,
        'type' => '73', // CODE128
        'width' => 2,
        'height' => 64,
        'position' => 0, // 0=below, 1=above, 2=both, 3=none
        'font' => 0
    ],
    
    // QR code settings
    'qrcode' => [
        'enabled' => false,
        'model' => 2, // Model 2
        'size' => 6, // Size
        'error_correction' => 0 // 0=L, 1=M, 2=Q, 3=H
    ],
    
    // Logo settings
    'logo' => [
        'enabled' => true,
        'max_width' => 40, // mm
        'max_height' => 20, // mm
        'center' => true
    ],
    
    // Debug settings
    'debug' => [
        'enabled' => false,
        'log_file' => '../logs/printer.log',
        'echo_commands' => false
    ]
];

// Helper function to get printer config
function getPrinterConfig($key = null) {
    global $printer_config;
    
    if ($key === null) {
        return $printer_config;
    }
    
    return isset($printer_config[$key]) ? $printer_config[$key] : null;
}

// Helper function to check if printer is available
function isPrinterAvailable() {
    $config = getPrinterConfig();
    
    switch ($config['connection_type']) {
        case 'usb':
            return file_exists($config['usb']['device_path']);
            
        case 'network':
            $socket = @fsockopen($config['network']['ip'], $config['network']['port'], $errno, $errstr, $config['network']['timeout']);
            if ($socket) {
                fclose($socket);
                return true;
            }
            return false;
            
        case 'windows':
            // Check if printer exists in Windows
            return true; // Simplified for now
            
        case 'mock':
            return true; // Always available for testing
            
        default:
            return false;
    }
}

// Helper function to get paper width in characters
function getCharsPerLine() {
    return getPrinterConfig('paper')['max_chars_per_line'];
}

// Helper function to format text for receipt
function formatReceiptText($text, $align = 'left', $max_length = null) {
    if ($max_length === null) {
        $max_length = getCharsPerLine();
    }
    
    $text = trim($text);
    
    if (strlen($text) <= $max_length) {
        return $text;
    }
    
    // Truncate text if too long
    return substr($text, 0, $max_length);
}

// Helper function to create table row
function createTableRow($left, $right, $separator = ' ') {
    $max_length = getCharsPerLine();
    $left_length = strlen($left);
    $right_length = strlen($right);
    $separator_length = strlen($separator);
    
    if ($left_length + $right_length + $separator_length > $max_length) {
        // Truncate left side if needed
        $available_length = $max_length - $right_length - $separator_length;
        $left = substr($left, 0, $available_length);
    }
    
    $padding = $max_length - $left_length - $right_length - $separator_length;
    
    return $left . str_repeat($separator, $padding) . $right;
}

// Helper function to center text
function centerText($text) {
    $max_length = getCharsPerLine();
    $text_length = strlen($text);
    
    if ($text_length >= $max_length) {
        return $text;
    }
    
    $padding = ($max_length - $text_length) / 2;
    $left_padding = floor($padding);
    $right_padding = ceil($padding);
    
    return str_repeat(' ', $left_padding) . $text . str_repeat(' ', $right_padding);
}

// Helper function to log printer activity
function logPrinterActivity($message, $level = 'INFO') {
    $config = getPrinterConfig('debug');
    
    if (!$config['enabled']) {
        return;
    }
    
    $log_file = $config['log_file'];
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] [$level] $message" . PHP_EOL;
    
    // Create log directory if it doesn't exist
    $log_dir = dirname($log_file);
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// Initialize printer configuration check
if (getPrinterConfig('debug')['enabled']) {
    logPrinterActivity('Printer configuration loaded');
    logPrinterActivity('Connection type: ' . getPrinterConfig('connection_type'));
    logPrinterActivity('Printer available: ' . (isPrinterAvailable() ? 'Yes' : 'No'));
}
?>
