<?php
/*
Plugin Name: ALL JOB POSTING
Description: A secure WordPress plugin for Job Posting.
Version: 1.0
Author: Rajesh
*/

// Security check to prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin Activation - Create Database Table
function my_crud_install()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'all_jobs';
    $charset_collate = $wpdb->get_charset_collate();

    // SQL statement to create the table
    $sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
        id MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT,
        jobtitle VARCHAR(150) NOT NULL,
        lastdatetoapply DATE NOT NULL,
        noofvacancy INT UNSIGNED NOT NULL,
        applylink VARCHAR(255) NOT NULL,
        officialNotification varchar(500) not null,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'my_crud_install');

// Admin Menu
function my_crud_menu()
{
    add_menu_page(
        'Secure CRUD',
        'Add Jobs',
        'manage_options',
        'secure-crud',
        'my_crud_admin_page',
        'dashicons-database'
    );
}
add_action('admin_menu', 'my_crud_menu');

//Adding external cdn link for DataTable 



function custom_enqueue_scripts()
{
    // external CSS CDN
    wp_enqueue_style('datatable-css', '//cdn.datatables.net/2.2.2/css/dataTables.dataTables.min.css', array(), null);
    // jQuery (Required for DataTables)
    wp_enqueue_script('jquery');
    // external JavaScript CDN
    wp_enqueue_script('datatable-jquery', '//cdn.datatables.net/2.2.2/js/dataTables.min.js', array(), null, true);
}
add_action('wp_enqueue_scripts', 'custom_enqueue_scripts');




// Display Admin Page
function my_crud_admin_page()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'all_jobs';

    // Handle Insert
    try {
        if (isset($_POST['add_entry']) && check_admin_referer('my_crud_nonce_action', 'my_crud_nonce')) {
            $jobtitle = sanitize_text_field($_POST['jobtitle']);
            $lastdatetoapply = date('Y-m-d', strtotime($_POST['lastdatetoapply']));
            $noofvacancy = intval($_POST['noofvacancy']);
            $applylink = esc_url_raw($_POST['applylink']);
            $officialNotification = esc_url_raw($_POST['officialNotification']);
            if (!empty($jobtitle) && !empty($lastdatetoapply) && $noofvacancy > 0 && !empty($applylink) && !empty($officialNotification)) {
                $wpdb->insert(
                    $table_name,
                    [
                        'jobtitle' => $jobtitle,
                        'lastdatetoapply' => $lastdatetoapply,
                        'noofvacancy' => $noofvacancy,
                        'applylink' => $applylink,
                        'officialNotification' => $officialNotification
                    ],
                    ['%s', '%s', '%d', '%s', '%s']
                );

                echo "<div class='notice notice-success'><p>Entry added successfully!</p></div>";
            } else {
                echo "<div class='notice notice-error'><p>Invalid input! Please check your entries.</p></div>";
            }
        }
    } catch (Exception $e) {
        throw $e->getMessage();
    }
    // Handle Delete
    if (isset($_GET['delete']) && check_admin_referer('delete_entry_' . $_GET['delete'])) {
        $id = intval($_GET['delete']);
        $wpdb->delete($table_name, ['id' => $id]);
        echo "<div class='notice notice-success'><p>Entry deleted successfully!</p></div>";
    }

    // Handle Update
    if (isset($_POST['update_entry']) && check_admin_referer('update_entry_' . $_POST['entry_id'])) {
        $id = intval($_POST['entry_id']);
        $jobtitle = sanitize_text_field($_POST['jobtitle']);
        $lastdatetoapply = sanitize_text_field($_POST['lastdatetoapply']);
        $noofvacancy = intval($_POST['noofvacancy']);
        $applylink = esc_url_raw($_POST['applylink']);
        $officialNotification = esc_url_raw($_POST['officialNotification']);
        $updated = $wpdb->update(
            $table_name,
            [
                'jobtitle' => $jobtitle,
                'lastdatetoapply' => $lastdatetoapply,
                'noofvacancy' => $noofvacancy,
                'applylink' => $applylink,
                'officialNotification' => $officialNotification
            ],
            ['id' => $id],
            ['%s', '%s', '%d', '%s', '%s'],
            ['%d']
        );

        if ($updated !== false) {
            echo "<div class='notice notice-success'><p>Entry updated successfully!</p></div>";
        } else {
            echo "<div class='notice notice-error'><p>Failed to update entry or no changes were made.</p></div>";
        }
    }

    // Fetch Entries
    $entries = $wpdb->get_results("SELECT * FROM $table_name Limit 10");
?>

    <div class="wrap">
        <h2>Secure CRUD Plugin</h2>

        <!-- Form for adding new entry in backend-->
        <form method="POST">
            <?php wp_nonce_field('my_crud_nonce_action', 'my_crud_nonce'); ?>
            <table>
                <tr>
                    <td><input type="text" name="jobtitle" placeholder="Job Title" required></td>
                    <td><input type="date" name="lastdatetoapply" required></td>
                    <td><input type="number" name="noofvacancy" placeholder="No. of Vacancy" required></td>
                    <td><input type="url" name="applylink" placeholder="Apply Link" required></td>
                    <td><input type="url" name="officialNotification" placeholder="Official Notification" required></td>
                    <td><input type="submit" name="add_entry" value="Add Entry"></td>
                </tr>
            </table>
        </form>

        <!-- Display existing entries  in backend-->
        <h3>Entries:</h3>
        <table class="widefat fixed" id="backendjobTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Job Title</th>
                    <th>Last Date to Apply</th>
                    <th>No of Vacancies</th>
                    <th>Official Notification</th>
                    <th>Apply Link</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $entry) : ?>
                    <tr>
                        <td><?php echo esc_html($entry->id); ?></td>
                        <td><?php echo esc_html($entry->jobtitle); ?></td>
                        <td><?php echo esc_html($entry->lastdatetoapply); ?></td>
                        <td><?php echo esc_html($entry->noofvacancy); ?></td>
                        <td><a href="<?php echo esc_url($entry->officialNotification); ?>" target="_blank">view</a></td>


                        <td><a href="<?php echo esc_url($entry->applylink); ?>" target="_blank">Apply</a></td>
                        <td>

                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=secure-crud&delete=' . $entry->id), 'delete_entry_' . $entry->id); ?>" onclick="return confirm('Are you sure?');">Delete</a>
                            |
                            <a href="javascript:void(0);" onclick="document.getElementById('editForm<?php echo $entry->id; ?>').style.display='block';">Edit</a>
                        </td>
                    </tr>
                    <tr id="editForm<?php echo $entry->id; ?>" style="display:none;">
                        <td colspan="6">
                            <form method="POST">
                                <?php wp_nonce_field('update_entry_' . $entry->id); ?>
                                <input type="hidden" name="entry_id" value="<?php echo $entry->id; ?>">
                                <input type="text" name="jobtitle" value="<?php echo esc_attr($entry->jobtitle); ?>" required>
                                <input type="number" name="noofvacancy" value="<?php echo esc_attr($entry->noofvacancy); ?>" required>
                                <input type="date" name="lastdatetoapply" value="<?php echo esc_attr($entry->lastdatetoapply); ?>" required>
                                <input type="url" name="applylink" value="<?php echo esc_attr($entry->applylink); ?>" required>
                                <input type="url" name="officialNotification"  value="<?php echo esc_attr($entry->officialNotification);?>" required>
                                <input type="submit" name="update_entry" value="Update">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <script>
            jQuery(document).ready(function($) {
                $('#backendjobTable').DataTable();
            });
        </script>
    </div>

<?php
}

// Register Shortcode

add_shortcode('view_job_details', 'view_all_jobs');
function view_all_jobs()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'all_jobs';

    // Fetch job entries
    $entries = $wpdb->get_results("SELECT * FROM $table_name ORDER BY updated_at DESC");

    // Start output buffering
    ob_start();
?>
 <style>
        .new-badge {
            color: red;
            font-weight: bold;
            animation: blink 1s infinite;
        }

        @keyframes blink {
            50% { opacity: 0; }
        }
    </style>
    <div class="job-listings-container">
        <?php if (!empty($entries)) : ?>
            <table class="job-listings-table" id="jobTable">
                <thead>
                    <tr>
                        <th>Job Title</th>
                        <th>Last Date to Apply</th>
                        <th>No of Vacancies</th>
                        <th>Official Notification</th>
                        <th>Apply Link</th>
                    </tr>
                </thead>
                <tbody>
   

    <?php foreach ($entries as $entry) : ?>
        <tr>
            <td>
                <?php 
                echo esc_html($entry->jobtitle);

                if (!empty($entry->created_at)) {
                    $createdDate = new DateTime($entry->created_at);
                    $currentDate = new DateTime(); // Today's date
                    $interval = $createdDate->diff($currentDate);
                    $daysAgo = $interval->days; // Number of days difference

                    // If less than 3 days old, show blinking "New!" badge
                    if ($daysAgo < 3) {
                        echo ' <span class="new-badge">New!</span>';
                    }

                  
                }
                ?>
            </td>
            <td><?php echo esc_html(date('d M, Y', strtotime($entry->lastdatetoapply))); ?></td>
            <td><?php echo esc_html($entry->noofvacancy); ?></td>

            <td>
                <?php if (!empty($entry->officialNotification)) : ?>
                    <a href="<?php echo esc_url($entry->officialNotification); ?>" target="_blank">View</a>
                <?php else : ?>
                    <span>N/A</span>
                <?php endif; ?>
            </td>

            <td>
                <?php if (!empty($entry->applylink)) : ?>
                    <a href="<?php echo esc_url($entry->applylink); ?>" target="_blank">Apply Now</a>
                <?php else : ?>
                    <span>Not Available</span>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>

            </table>
            <script>
                jQuery(document).ready(function($) {
                    $('#jobTable').DataTable();
                });
            </script>
        <?php else : ?>
            <p>No job listings available.</p>
        <?php endif; ?>
    </div>


<?php
    return ob_get_clean(); // Return the buffered output
}
