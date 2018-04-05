<?php
    /*
     * Plugin Name: rapidmail newsletter marketing
     * Description: Widget für die Integration eines rapidmail Anmeldeformulars in der Sidebar sowie ein Plugin für die Gewinnung von Abonnenten über die Kommentarfunktion.
     * Author: rapidmail GmbH
     * Version: 2.0-beta1
     * Author URI: http://www.rapidmail.de
     * License: GPL2
     * License URI: http://www.gnu.org/licenses/gpl-2.0.html
     * Min WP Version: 4.6
     */

    require_once __DIR__ . '/classes/Rapidmail.php';
    Rapidmail\Rapidmail::instance();