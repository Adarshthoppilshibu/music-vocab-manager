<?php
/**
 * Plugin Name: Music Vocabulary Manager
 * Description: A simple plugin to manage music terms and display them on your site via shortcode. Built to demonstrate WordPress, PHP, MySQL, and custom admin UI skills.
 * Version: 1.0.0
 * Author: Your Name
 */

// Safety check: prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ─────────────────────────────────────────────
// 1. CREATE THE DATABASE TABLE ON ACTIVATION
// ─────────────────────────────────────────────

function mvm_create_table() {
    global $wpdb;

    $table_name      = $wpdb->prefix . 'music_terms';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id         MEDIUMINT(9)  NOT NULL AUTO_INCREMENT,
        term       VARCHAR(100)  NOT NULL,
        definition TEXT          NOT NULL,
        category   VARCHAR(50)   NOT NULL DEFAULT 'General',
        created_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'mvm_create_table' );


// ─────────────────────────────────────────────
// 2. ADD THE ADMIN MENU
// ─────────────────────────────────────────────

function mvm_add_admin_menu() {
    add_menu_page(
        'Music Vocabulary Manager', // Page title
        'Music Vocab',              // Menu label
        'manage_options',           // Required capability
        'music-vocab-manager',      // Menu slug
        'mvm_admin_page',           // Callback function
        'dashicons-playlist-audio', // Icon
        30                          // Menu position
    );
}
add_action( 'admin_menu', 'mvm_add_admin_menu' );


// ─────────────────────────────────────────────
// 3. HANDLE FORM SUBMISSIONS (ADD / DELETE)
// ─────────────────────────────────────────────

function mvm_handle_form_actions() {
    // Only run on our plugin's admin page
    if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'music-vocab-manager' ) {
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'music_terms';

    // --- ADD a new term ---
    if ( isset( $_POST['mvm_add_term'] ) ) {
        // Verify the security nonce
        check_admin_referer( 'mvm_add_term_action' );

        $term       = sanitize_text_field( $_POST['term'] );
        $definition = sanitize_textarea_field( $_POST['definition'] );
        $category   = sanitize_text_field( $_POST['category'] );

        if ( ! empty( $term ) && ! empty( $definition ) ) {
            $wpdb->insert(
                $table_name,
                array(
                    'term'       => $term,
                    'definition' => $definition,
                    'category'   => $category,
                ),
                array( '%s', '%s', '%s' )
            );
        }
    }

    // --- DELETE a term ---
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) ) {
        check_admin_referer( 'mvm_delete_term' );

        $id = intval( $_GET['id'] );
        $wpdb->delete( $table_name, array( 'id' => $id ), array( '%d' ) );
    }
}
add_action( 'admin_init', 'mvm_handle_form_actions' );


// ─────────────────────────────────────────────
// 4. RENDER THE ADMIN PAGE
// ─────────────────────────────────────────────

function mvm_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'music_terms';

    // Fetch all terms, newest first
    $terms = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY created_at DESC" );

    ?>
    <div class="wrap">
        <h1>🎵 Music Vocabulary Manager</h1>
        <p style="color:#666;">Use the shortcode <code>[music_terms]</code> on any page or post to display all terms publicly.</p>

        <!-- ADD NEW TERM FORM -->
        <div style="background:#fff; border:1px solid #ccd0d4; border-radius:4px; padding:20px; max-width:600px; margin:20px 0;">
            <h2 style="margin-top:0;">Add a New Term</h2>
            <form method="POST">
                <?php wp_nonce_field( 'mvm_add_term_action' ); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th><label for="term">Term</label></th>
                        <td><input type="text" name="term" id="term" class="regular-text" placeholder="e.g. Staccato" required /></td>
                    </tr>
                    <tr>
                        <th><label for="definition">Definition</label></th>
                        <td><textarea name="definition" id="definition" class="large-text" rows="3" placeholder="e.g. A note played short and detached from the notes around it." required></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="category">Category</label></th>
                        <td>
                            <select name="category" id="category">
                                <option value="General">General</option>
                                <option value="Rhythm">Rhythm</option>
                                <option value="Dynamics">Dynamics</option>
                                <option value="Tempo">Tempo</option>
                                <option value="Pitch">Pitch</option>
                                <option value="Notation">Notation</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <input type="submit" name="mvm_add_term" class="button button-primary" value="Add Term" />
            </form>
        </div>

        <!-- TERMS TABLE -->
        <h2>All Terms (<?php echo count( $terms ); ?>)</h2>

        <?php if ( empty( $terms ) ) : ?>
            <p>No terms added yet. Use the form above to get started.</p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width:40px;">ID</th>
                        <th style="width:160px;">Term</th>
                        <th>Definition</th>
                        <th style="width:100px;">Category</th>
                        <th style="width:150px;">Added</th>
                        <th style="width:80px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $terms as $term ) : ?>
                        <tr>
                            <td><?php echo esc_html( $term->id ); ?></td>
                            <td><strong><?php echo esc_html( $term->term ); ?></strong></td>
                            <td><?php echo esc_html( $term->definition ); ?></td>
                            <td><?php echo esc_html( $term->category ); ?></td>
                            <td><?php echo esc_html( date( 'M j, Y', strtotime( $term->created_at ) ) ); ?></td>
                            <td>
                                <a href="<?php echo esc_url( wp_nonce_url(
                                    admin_url( 'admin.php?page=music-vocab-manager&action=delete&id=' . $term->id ),
                                    'mvm_delete_term'
                                ) ); ?>"
                                   style="color:#b32d2e;"
                                   onclick="return confirm('Delete this term?')">
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}


// ─────────────────────────────────────────────
// 5. SHORTCODE: [music_terms]
// ─────────────────────────────────────────────

function mvm_shortcode( $atts ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'music_terms';

    // Allow optional category filter: [music_terms category="Rhythm"]
    $atts = shortcode_atts( array( 'category' => '' ), $atts );

    if ( ! empty( $atts['category'] ) ) {
        $category = sanitize_text_field( $atts['category'] );
        $terms    = $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM $table_name WHERE category = %s ORDER BY term ASC", $category )
        );
    } else {
        $terms = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY term ASC" );
    }

    if ( empty( $terms ) ) {
        return '<p>No music terms found.</p>';
    }

    // Group terms by category for a clean display
    $grouped = array();
    foreach ( $terms as $term ) {
        $grouped[ $term->category ][] = $term;
    }

    ob_start();
    ?>
    <div class="mvm-terms-wrapper" style="font-family:inherit;">
        <?php foreach ( $grouped as $category => $items ) : ?>
            <h3 style="border-bottom:2px solid #0073aa; padding-bottom:6px; color:#0073aa;">
                <?php echo esc_html( $category ); ?>
            </h3>
            <dl style="margin:0 0 24px;">
                <?php foreach ( $items as $item ) : ?>
                    <dt style="font-weight:700; margin-top:12px;"><?php echo esc_html( $item->term ); ?></dt>
                    <dd style="margin:4px 0 0 16px; color:#444;"><?php echo esc_html( $item->definition ); ?></dd>
                <?php endforeach; ?>
            </dl>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'music_terms', 'mvm_shortcode' );


// ─────────────────────────────────────────────
// 6. CLEAN UP THE TABLE ON PLUGIN DELETION
// ─────────────────────────────────────────────

function mvm_uninstall() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'music_terms';
    $wpdb->query( "DROP TABLE IF EXISTS $table_name" );
}
register_uninstall_hook( __FILE__, 'mvm_uninstall' );
