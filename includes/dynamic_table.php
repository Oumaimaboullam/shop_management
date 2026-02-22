<?php
/**
 * Dynamic Table Component for Sales
 * Handles different column layouts based on sale type
 */

class DynamicSaleTable {
    private $sale_type;
    private $items;
    private $currency_symbol;
    private $tax_enabled;
    private $tax_rate;
    
    public function __construct($sale_type, $items, $currency_symbol = 'DH', $tax_enabled = true, $tax_rate = 20) {
        $this->sale_type = $sale_type;
        $this->items = $items;
        $this->currency_symbol = $currency_symbol;
        $this->tax_enabled = $tax_enabled;
        $this->tax_rate = $tax_rate;
    }
    
    /**
     * Get column configuration based on sale type
     */
    private function getColumns() {
        if ($this->sale_type === 'wholesale') {
            return [
                'quantity' => ['label' => 'Quantité', 'width' => '10%', 'align' => 'center'],
                'name' => ['label' => 'Nom du Produit', 'width' => '35%', 'align' => 'left'],
                'sale_price' => ['label' => 'Prix de Vente', 'width' => '15%', 'align' => 'right'],
                'wholesale_margin' => ['label' => 'Marge Grossiste (%)', 'width' => '15%', 'align' => 'right'],
                'wholesale_price' => ['label' => 'Prix Grossiste', 'width' => '15%', 'align' => 'right'],
                'total' => ['label' => 'Total', 'width' => '10%', 'align' => 'right']
            ];
        } else {
            // Normal sale columns
            return [
                'quantity' => ['label' => 'Quantité', 'width' => '15%', 'align' => 'center'],
                'name' => ['label' => 'Nom du Produit', 'width' => '45%', 'align' => 'left'],
                'unit_price' => ['label' => 'Prix Unitaire', 'width' => '20%', 'align' => 'right'],
                'total' => ['label' => 'Total', 'width' => '20%', 'align' => 'right']
            ];
        }
    }
    
    /**
     * Calculate wholesale price
     */
    private function calculateWholesalePrice($sale_price, $wholesale_margin) {
        return $sale_price - ($sale_price * $wholesale_margin / 100);
    }
    
    /**
     * Calculate item total based on sale type
     */
    private function calculateItemTotal($item) {
        if ($this->sale_type === 'wholesale') {
            $wholesale_price = $this->calculateWholesalePrice($item['sale_price'], $item['wholesale_percentage']);
            return $wholesale_price * $item['quantity'];
        } else {
            return $item['total_price'];
        }
    }
    
    /**
     * Get subtotal for all items
     */
    public function getSubtotal() {
        $subtotal = 0;
        foreach ($this->items as $item) {
            $subtotal += $this->calculateItemTotal($item);
        }
        return $subtotal;
    }
    
    /**
     * Get VAT amount
     */
    public function getVAT() {
        if (!$this->tax_enabled) {
            return 0;
        }
        return $this->getSubtotal() * ($this->tax_rate / 100);
    }
    
    /**
     * Get grand total
     */
    public function getGrandTotal() {
        return $this->getSubtotal() + $this->getVAT();
    }
    
    /**
     * Render table header
     */
    public function renderHeader($is_receipt = false) {
        $columns = $this->getColumns();
        $html = '';
        
        if ($is_receipt) {
            // Receipt format - hide margin column for space
            $html .= '<thead><tr>';
            foreach ($columns as $key => $col) {
                if ($this->sale_type === 'wholesale' && $key === 'wholesale_margin') {
                    continue; // Skip margin column in receipt
                }
                $html .= '<th style="text-align: ' . $col['align'] . '; font-weight: bold; font-size: 10px; padding: 2px; border-bottom: 1px solid #000;">';
                $html .= $col['label'];
                $html .= '</th>';
            }
            $html .= '</tr></thead>';
        } else {
            // A4 format - full table with all columns
            $html .= '<thead><tr>';
            foreach ($columns as $key => $col) {
                $html .= '<th style="width: ' . $col['width'] . '; text-align: ' . $col['align'] . '; font-weight: bold; padding: 8px; border: 1px solid #000; background-color: #f5f5f5;">';
                $html .= $col['label'];
                $html .= '</th>';
            }
            $html .= '</tr></thead>';
        }
        
        return $html;
    }
    
    /**
     * Render table body
     */
    public function renderBody($is_receipt = false) {
        $columns = $this->getColumns();
        $html = '';
        
        if ($is_receipt) {
            // Receipt format - hide margin column
            $html .= '<tbody>';
            foreach ($this->items as $item) {
                $html .= '<tr>';
                
                foreach ($columns as $key => $col) {
                    if ($this->sale_type === 'wholesale' && $key === 'wholesale_margin') {
                        continue; // Skip margin column in receipt
                    }
                    
                    $value = $this->getCellValue($key, $item);
                    $html .= '<td style="text-align: ' . $col['align'] . '; font-size: 10px; padding: 2px; border-bottom: 1px solid #ddd;">';
                    $html .= $value;
                    $html .= '</td>';
                }
                
                $html .= '</tr>';
            }
            $html .= '</tbody>';
        } else {
            // A4 format - full table
            $html .= '<tbody>';
            foreach ($this->items as $item) {
                $html .= '<tr>';
                
                foreach ($columns as $key => $col) {
                    $value = $this->getCellValue($key, $item);
                    $html .= '<td style="width: ' . $col['width'] . '; text-align: ' . $col['align'] . '; padding: 8px; border: 1px solid #000;">';
                    $html .= $value;
                    $html .= '</td>';
                }
                
                $html .= '</tr>';
            }
            $html .= '</tbody>';
        }
        
        return $html;
    }
    
    /**
     * Get cell value based on column and item data
     */
    private function getCellValue($column_key, $item) {
        switch ($column_key) {
            case 'quantity':
                return $item['quantity'];
                
            case 'name':
                return htmlspecialchars($item['article_name']);
                
            case 'unit_price':
                return number_format($item['unit_price'], 2) . ' ' . $this->currency_symbol;
                
            case 'sale_price':
                return number_format($item['sale_price'], 2) . ' ' . $this->currency_symbol;
                
            case 'wholesale_margin':
                return number_format($item['wholesale_percentage'], 1) . '%';
                
            case 'wholesale_price':
                $wholesale_price = $this->calculateWholesalePrice($item['sale_price'], $item['wholesale_percentage']);
                return number_format($wholesale_price, 2) . ' ' . $this->currency_symbol;
                
            case 'total':
                $total = $this->calculateItemTotal($item);
                return number_format($total, 2) . ' ' . $this->currency_symbol;
                
            default:
                return '';
        }
    }
    
    /**
     * Render totals section
     */
    public function renderTotals($is_receipt = false) {
        $subtotal = $this->getSubtotal();
        $vat = $this->getVAT();
        $grand_total = $this->getGrandTotal();
        
        $html = '';
        
        if ($is_receipt) {
            // Receipt format - compact table
            $html .= '<table class="receipt-totals" style="width: 100%; border-collapse: collapse; margin-top: 6px;">';
            $html .= '<tr><td style="text-align: left; font-size: 11px; padding: 2px; border-top: 1px dashed #000;">Sous-total:</td><td style="text-align: right; font-size: 11px; padding: 2px; border-top: 1px dashed #000;">' . number_format($subtotal, 2) . ' ' . $this->currency_symbol . '</td></tr>';
            
            if ($this->tax_enabled) {
                $html .= '<tr><td style="text-align: left; font-size: 11px; padding: 2px;">TVA (' . $this->tax_rate . '%):</td><td style="text-align: right; font-size: 11px; padding: 2px;">' . number_format($vat, 2) . ' ' . $this->currency_symbol . '</td></tr>';
            }
            
            $html .= '<tr><td style="text-align: left; font-weight: bold; font-size: 12px; padding: 2px; border-top: 1px solid #000;">TOTAL:</td><td style="text-align: right; font-weight: bold; font-size: 12px; padding: 2px; border-top: 1px solid #000;">' . number_format($grand_total, 2) . ' ' . $this->currency_symbol . '</td></tr>';
            $html .= '</table>';
        } else {
            // A4 format - proper totals table
            $colspan = $this->sale_type === 'wholesale' ? 5 : 3;
            
            $html .= '<table class="totals-table" style="width: 300px; border-collapse: collapse; float: right; margin-top: 20px;">';
            $html .= '<tr><td style="text-align: right; padding: 5px; border: 1px solid #000; font-weight: bold;">Sous-total:</td><td style="text-align: right; padding: 5px; border: 1px solid #000;">' . number_format($subtotal, 2, ',', ' ') . ' ' . $this->currency_symbol . '</td></tr>';
            
            if ($this->tax_enabled) {
                $html .= '<tr><td style="text-align: right; padding: 5px; border: 1px solid #000;">TVA (' . $this->tax_rate . '%):</td><td style="text-align: right; padding: 5px; border: 1px solid #000;">' . number_format($vat, 2, ',', ' ') . ' ' . $this->currency_symbol . '</td></tr>';
            }
            
            $html .= '<tr><td colspan="2" style="height: 2px; background-color: #000;"></td></tr>'; // Separator line
            $html .= '<tr><td style="text-align: right; padding: 8px; border: 1px solid #000; font-weight: bold; font-size: 14px;">TOTAL TTC:</td><td style="text-align: right; padding: 8px; border: 1px solid #000; font-weight: bold; font-size: 14px;">' . number_format($grand_total, 2, ',', ' ') . ' ' . $this->currency_symbol . '</td></tr>';
            $html .= '</table>';
        }
        
        return $html;
    }
    
    /**
     * Render complete table
     */
    public function renderTable($is_receipt = false) {
        $html = '';
        
        if ($is_receipt) {
            // Receipt format - compact table
            $html .= '<table class="receipt-table" style="width: 100%; border-collapse: collapse; font-size: 10px;">';
            $html .= $this->renderHeader(true);
            $html .= $this->renderBody(true);
            $html .= '</table>';
        } else {
            // A4 format - full table
            $html .= '<table class="sale-table" style="width: 100%; border-collapse: collapse; margin: 10px 0;">';
            $html .= $this->renderHeader(false);
            $html .= $this->renderBody(false);
            $html .= '</table>';
        }
        
        return $html;
    }
    
    /**
     * Get CSS styles for the table
     */
    public function getStyles($is_receipt = false) {
        $css = '';
        
        if ($is_receipt) {
            // Receipt styles for thermal printer
            $css .= '
            .receipt-table { margin-bottom: 6px; font-size: 10px; }
            .receipt-table th { font-weight: bold; padding: 2px; border-bottom: 1px solid #000; }
            .receipt-table td { padding: 2px; border-bottom: 1px solid #ddd; }
            .receipt-totals { margin-top: 6px; }
            .receipt-totals td { padding: 2px; }
            ';
        } else {
            // A4 styles for invoices/quotes
            $css .= '
            .sale-table { margin: 20px 0; border: 2px solid #000; }
            .sale-table th { background-color: #f5f5f5; font-weight: bold; padding: 8px; border: 1px solid #000; text-align: center; }
            .sale-table td { padding: 8px; border: 1px solid #000; text-align: center; }
            .sale-table td.text-right { text-align: right; }
            .sale-table td.text-left { text-align: left; }
            .totals-table { border: 2px solid #000; float: right; clear: both; }
            .totals-table td { padding: 5px 8px; border: 1px solid #000; }
            .totals-table td:first-child { font-weight: bold; text-align: right; }
            .totals-table td:last-child { text-align: right; }
            ';
        }
        
        return $css;
    }
}

/**
 * Helper function to create and render dynamic table
 */
function renderDynamicSaleTable($sale_type, $items, $currency_symbol = 'DH', $tax_enabled = true, $tax_rate = 20, $is_receipt = false) {
    $table = new DynamicSaleTable($sale_type, $items, $currency_symbol, $tax_enabled, $tax_rate);
    
    $html = '<style>' . $table->getStyles($is_receipt) . '</style>';
    $html .= $table->renderTable($is_receipt);
    $html .= $table->renderTotals($is_receipt);
    
    return $html;
}
?>
