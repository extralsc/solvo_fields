<?php

/**
 * Solvo Plugin - Main
 *
 * @package Solvo_Plugin
 */

/**
 * Plugin Name: Solvo Fields
 * Plugin URI: https://www.example.com/
 * Description: This plugin integrates with the Solvo API to handle licensing and support for Acme Plugins.
 * Version: 1.0.0
 * Author: John Doe
 * Author URI: https://www.example.com
 */

add_filter('woocommerce_product_data_tabs', 'solvo_tab');
add_action('woocommerce_product_data_panels', 'solvo_tab_form');
add_action('woocommerce_process_product_meta', 'solvo_tab_save');
add_action('woocommerce_rest_insert_product_object', 'solvo_rest_api_fields', 10, 1);

add_action('woocommerce_admin_order_data_after_order_details', 'add_plocklista_to_order', 10, 1);

// Kolla om TailwindCSS UI redan har inkluderats
function solvo_load_custom_wp_admin_style($hook)
{
    if ('post.php' != $hook)
        return;
    wp_register_style('tailwind_css', 'https://unpkg.com/tailwindcss@^2.2.7/dist/tailwind.min.css', false, '1.0.0');
    wp_enqueue_style('tailwind_css');
}
add_action('admin_enqueue_scripts', 'solvo_load_custom_wp_admin_style');





function solvo_tab($tabs)
{
    $tabs['solvo_tab'] = array(
        'label'    => __('Solvo Lagerhantering', 'woocommerce'),
        'target'   => 'solvo_tab_data',
        'priority' => 21,
        'class'    => array('show_if_simple', 'show_if_variable'),
    );
    return $tabs;
}

function solvo_tab_form()
{
    global $woocommerce, $post;
    echo '<div id="solvo_tab_data" class="panel woocommerce_options_panel">';
    woocommerce_wp_text_input(
        array(
            'id'                => '_solvo_lagerplats',
            'label'             => __('Lagerplats', 'woocommerce'),
            'placeholder'       => __('Lagerplats', 'woocommerce'),
            'description'       => __('Enter the lagerplats for this product.', 'woocommerce'),
            'desc_tip'          => true,
            // 'custom_attributes' => array( 'readonly' => 'readonly' ),
        )
    );
    woocommerce_wp_text_input(
        array(
            'id'                => '_solvo_lager_kod',
            'label'             => __('Kod', 'woocommerce'),
            'placeholder'       => __('Kod', 'woocommerce'),
            'description'       => __('Enter the kod/QR/Sträckkod for this product.', 'woocommerce'),
            'desc_tip'          => true,
            // 'custom_attributes' => array( 'readonly' => 'readonly' ),
        )
    );
    echo '</div>';

    echo '<div id="solvo_plocklista_data" class="panel woocommerce_options_panel">';
    echo '<div class="options_group">';
    echo '<p class="form-field">';
    echo '<a href="#" id="solvo-generate-plocklista" class="button button-secondary">Generera Plocklista</a>';
    echo '</p>';
    echo '</div>';
    echo '</div>';
}

function solvo_tab_save($post_id)
{
    update_post_meta($post_id, '_solvo_lagerplats', sanitize_text_field($_POST['_solvo_lagerplats']));
    update_post_meta($post_id, '_solvo_lager_kod', sanitize_text_field($_POST['_solvo_lager_kod']));
}

function solvo_rest_api_fields($product)
{
    $solvo_lagerplats = get_post_meta(get_the_ID(), '_solvo_lagerplats', true);
    $product->update_meta_data('_solvo_lagerplats', $solvo_lagerplats);
    $product->save();

    $solvo_lagerplats = get_post_meta(get_the_ID(), '_solvo_lager_kod', true);
    $product->update_meta_data('_solvo_lager_kod', $solvo_lagerplats);
    $product->save();
}

function add_plocklista_to_order($order)
{
    $order_items = $order->get_items();
    $plocklista = array();
    foreach ($order_items as $item_id => $item) {
        $product = wc_get_product($item->get_product_id());
        $lagerplats = get_post_meta($product->get_id(), '_solvo_lagerplats', true);
        $qty = $item->get_quantity();
        $plocklista[] = array(
            'produkt' => $product->get_name(),
            'antal'     => $qty,
            'lagerplats' => $lagerplats ? $lagerplats : 'N/O',
        );
    }
    if (!empty($plocklista)) {
        echo '<div class="options_group">';
        echo '<p class="form-field"  style="border-top: 1px solid #DDD;">';
        echo '<a href="#" id="solvo-generate-plocklista" class="button button-secondary w-100" style="margin-top: 5px;">Generera Plocklista</a>';
        echo '<input type="hidden" name="order_id" value="' . $order->get_id() . '">';
        echo '</p>';
        echo '</div>';

?>

        <div id="solvo-plocklista-modal" class="relative z-10" aria-labelledby="modal-title" role="dialog" aria-modal="true" style="display: none;">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

            <div class="fixed inset-0 z-10 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">

                    <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                                    <?php
                                    echo '<h2 class="text-2xl font-semibold me-5">Plocklista för beställning #' . $order->get_id() . '</h2>';
                                    echo '<table class="w-full text-left mb-4">';
                                    echo '<thead class="bg-gray-200">';
                                    echo '<tr>';
                                    echo '<th class="px-4 py-2">Check</th>';
                                    echo '<th class="px-4 py-2">#</th>';
                                    echo '<th class="px-4 py-2">Produkt</th>';
                                    echo '<th class="px-4 py-2">Antal</th>';
                                    echo '<th class="px-4 py-2">Lagerplats</th>';
                                    echo '</tr>';
                                    echo '</thead>';
                                    echo '<tbody>';
                                    foreach ($plocklista as $key => $item) {
                                        echo '<tr>';
                                        echo '<td class="border px-4 py-2"><input type="checkbox"></td>';
                                        echo '<td class="border px-4 py-2">' . ($key + 1) . '</td>';
                                        echo '<td class="border px-4 py-2">' . $item['produkt'] . '</td>';
                                        echo '<td class="border px-4 py-2">' . $item['antal'] . '</td>';
                                        echo '<td class="border px-4 py-2">' . $item['lagerplats'] . '</td>';
                                        echo '</tr>';
                                    }
                                    echo '</tbody>';
                                    echo '</table>';
                                    ?>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button type="button" class="flex-start inline-flex w-full justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 sm:ml-3 sm:w-auto">Klar</button>
                            <button type="button" id="solvo-plocklista-close" class="flex-end mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Skriv ut</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<?php

    }
}


function solvo_generate_pdf($order_id = null)
{
    if (is_null($order_id) || empty($order_id)) {
        $order_id = sanitize_text_field($_POST["order_id"]);
    } else {
        $order_id = sanitize_text_field($order_id);
    }
    // Hämta ordern
    $order = wc_get_order($order_id);
    $count = $order->get_item_count();


    // wp_send_json_success($order->get_items(), 200);

    // Skapa ett nytt PDF-dokument
    require_once('tfpdf/tfpdf.php');
    $pdf = new tFPDF();

    $pdf->AddPage();

    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10,  $order->get_date_created()->format('j F Y H:i'), 0, 0);

    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(0, 4, (!is_null($order->get_shipping_country()) ? $order->get_shipping_country() : "-"), 0, 1, "R", false);
    $pdf->Cell(0, 4, (!is_null($order->get_shipping_method()) ? $order->get_shipping_method() : "-"), 0, 1, "R", false);



    $pdf->SetFont('Arial', 'B', 16);

    // Skriv ut ordernummer och datum
    $pdf->Cell(0, 10, utf8_decode('Plocklista för order #') . $order->get_id(), 0, 0);
    $pdf->Cell(0, 10, $count . " produkter", 0, 1, "R");

    $notes = wc_get_order_notes([
        'order_id' => $order->get_id(),
        'type' => 'internal',
    ]);

    if (count($notes) > 0) {
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(0, 2,  "Anteckningar:", 0, 1);
        $pdf->Ln();
    }

    foreach ($notes as $note) {
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(0, 5, $note->added_by . ": "  . utf8_decode(strip_tags($note->content)), 0, 0, "L");
        $pdf->Cell(0, 5, "" . Date("Y-m-d H:i", strtotime($note->date_created)) . "", 0, 0, "R");
        $pdf->Ln();
    }



    // Skriv ut produktinformation
    foreach ($order->get_items() as $item) {
        $pdf->Cell(0, 1, "", "T", 1);
        $pdf->SetFont('Arial', 'B', 12);


        $pdf->Cell(10, 10, "[  ]  " . $item->get_quantity() . ' x ' . utf8_decode($item->get_name()) . ' ' . strip_tags(wc_get_formatted_variation($item)) . '', 0, 1);



        $product = $order->get_product_from_item($item);
        $main_product = wc_get_product($item->get_product_id());




        $pdf->SetFont('Arial', '', 10);

        if ($item->get_meta('_pick_slip_notes')) {
            $pdf->Cell(10);
            $pdf->Cell(0, 5, 'Notering: ' . $item->get_meta('_pick_slip_notes'), 0, 1);
        }

        $meta_data = "";
        $meta_data_count = 0;

        if ($product->get_meta('_sku')) {
            $meta_data_count++;
            $meta_data .= ($meta_data_count > 1 ? " | " : "") . "SKU: " . $product->get_meta('_sku');
        }
        if ($main_product->get_meta('_solvo_lagerplats')) {
            $meta_data_count++;
            $meta_data .= ($meta_data_count > 1 ? " | " : "") . "Lagerplats: " . $main_product->get_meta('_solvo_lagerplats');
        }
        if ($main_product->get_meta('_solvo_lager_kod')) {
            $meta_data_count++;
            $meta_data .= ($meta_data_count > 1 ? " | " : "") . "Lagerkod: " . $main_product->get_meta('_solvo_lager_kod');
        }

        if (!empty($meta_data)) {
            $pdf->Cell(10);
            $pdf->Cell(0, 2, $meta_data, 0, 1);
        }

        $pdf->Ln();
    }

    $pdf->Cell(0, 1, "", "T", 1);


    // Spara PDF-filen och skicka till webbläsaren

    $pdf_file = 'plocklista-order-' . $order_id . '.pdf';
    $pdf_path = ABSPATH . '/wp-content/uploads/pdf-files/' . $pdf_file;

    if (!file_exists(ABSPATH . '/wp-content/uploads/pdf-files')) {
        mkdir(ABSPATH . '/wp-content/uploads/pdf-files', 0777, true);
    }

    if (file_exists($pdf_path)) {
        unlink($pdf_path);
    }

    $pdf->Output('F', $pdf_path);

    $response["url"] = site_url('wp-content/uploads/pdf-files/plocklista-order-' . $order_id . '.pdf', __FILE__);
    $response["path"] = $pdf_path;

    wp_send_json_success($response, 200);

    exit;
}
add_action('wp_ajax_solvo_generate_pdf', 'solvo_generate_pdf');
add_action('wp_ajax_nopriv_solvo_generate_pdf', 'solvo_generate_pdf');

// Lägg till AJAX-hanterare
add_action('wp_ajax_solvo_generate_plocklista', 'solvo_generate_plocklista_callback');

function solvo_generate_plocklista_callback()
{
    $order_id = $_POST['order_id'];
    // Generera PDF och skicka tillbaka URL
    solvo_generate_pdf($order_id);
    echo json_encode(array(
        'url' => plugins_url('plocklista-order-' . $order_id . '.pdf', __FILE__)
    ));
    exit();
}

// Lägg till skript för AJAX-begäran
function add_solvo_order_actions_script()
{
    global $pagenow, $post;

    if ($pagenow !== 'post.php' || get_post_type($post->ID) != 'shop_order')
        return;

    // Lägg till script-fil
    wp_enqueue_script('solvo-order-actions-script', plugin_dir_url(__FILE__) . 'js/solvo-order-actions.js', array('jquery'));

    // Skapa SolvoAjax-objektet och skicka det till JavaScript
    $solvo_ajax_object = array('ajax_url' => admin_url('admin-ajax.php'));
    wp_localize_script('solvo-order-actions-script', 'solvo_ajax', $solvo_ajax_object);
}
add_action('admin_enqueue_scripts', 'add_solvo_order_actions_script');
