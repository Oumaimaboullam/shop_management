<?php
// includes/js_translations.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include language system
require_once 'functions.php';

// Get JavaScript translations
$js_translations = [
    // Common messages
    'confirm_delete_supplier' => __('js_confirm_delete_supplier'),
    'confirm_delete_user' => __('js_confirm_delete_user'),
    'confirm_void_sale' => __('js_confirm_void_sale'),
    'prompt_void_reason' => __('js_prompt_void_reason'),
    'alert_sale_voided' => __('js_alert_sale_voided'),
    'alert_error' => __('js_alert_error'),
    'alert_network_error' => __('js_alert_network_error'),
    'prompt_view_receipt' => __('js_prompt_view_receipt'),
    'alert_select_items_return' => __('js_alert_select_items_return'),
    'confirm_process_return' => __('js_confirm_process_return'),
    'alert_return_processed' => __('js_alert_return_processed'),
    'alert_provide_return_reason' => __('js_alert_provide_return_reason'),
    'confirm_process_return_stock' => __('js_confirm_process_return_stock'),
    'alert_return_processed_stock' => __('js_alert_return_processed_stock'),
    'alert_error_processing_return' => __('js_alert_error_processing_return'),
    'prompt_payment_amount' => __('js_prompt_payment_amount'),
    'confirm_payment' => __('js_confirm_payment'),
    'alert_payment_recorded' => __('js_alert_payment_recorded'),
    'alert_payment_error' => __('js_alert_payment_error'),
    'alert_payment_try_again' => __('js_alert_payment_try_again'),
    'alert_empty_cart_draft' => __('js_alert_empty_cart_draft'),
    'alert_draft_saved' => __('js_alert_draft_saved'),
    'alert_draft_save_error' => __('js_alert_draft_save_error'),
    'alert_draft_try_again' => __('js_alert_draft_try_again'),
    'alert_draft_load_error' => __('js_alert_draft_load_error'),
    'alert_draft_load_try_again' => __('js_alert_draft_load_try_again'),
    'confirm_delete_draft' => __('js_confirm_delete_draft'),
    'alert_draft_deleted' => __('js_alert_draft_deleted'),
    'alert_draft_delete_error' => __('js_alert_draft_delete_error'),
    'alert_draft_loaded' => __('js_alert_draft_loaded'),
    'load_draft_order' => __('js_load_draft_order'),
    'no_draft_orders_found' => __('js_no_draft_orders_found'),
    'draft' => __('js_draft'),
    'customer' => __('js_customer'),
    'counter' => __('js_counter'),
    'delete_draft' => __('js_delete_draft'),
    'items' => __('js_items'),
    'total' => __('js_total'),
    'type' => __('js_type'),
    'load_draft' => __('js_load_draft'),
    'discount' => __('js_discount'),
    'save' => __('js_save'),
];

// Output as JavaScript
echo '<script>';
echo 'window.JSTranslations = ' . json_encode($js_translations) . ';';
echo '</script>';
?>
