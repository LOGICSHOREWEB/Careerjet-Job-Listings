<?php
/*
Plugin Name: Careerjet Job Listings Pro
Description: Display Careerjet job listings in a sidebar and after posts using the shortcode [careerjet_jobs]. Simple and Straightforward.
Version: 3.1
Author: <a href="https://peteradenuga.com/" target='_blank' title='Work With Peter Adenuga'>Peter Adenuga</a> | <strong>Settings:</strong> <a href="options-general.php?page=careerjet-settings">Edit Keywords</a>
*/

require_once "Careerjet_API.php"; // Ensure this file is included in your plugin folder

// Register settings
function careerjet_register_settings() {
    register_setting('careerjet_settings', 'careerjet_widget_keywords');
    register_setting('careerjet_settings', 'careerjet_after_post_keywords');
    register_setting('careerjet_settings', 'careerjet_total_display_limit');
    register_setting('careerjet_settings', 'careerjet_keyword_display_count');
    register_setting('careerjet_settings', 'careerjet_display_location');
}
add_action('admin_init', 'careerjet_register_settings');

// Add settings page to the admin menu
function careerjet_add_settings_page() {
    add_options_page(
        'Careerjet Job Listings Settings',
        'Careerjet Settings',
        'manage_options',
        'careerjet-settings',
        'careerjet_render_settings_page'
    );
}
add_action('admin_menu', 'careerjet_add_settings_page');

// Render the settings page
function careerjet_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>Careerjet Job Listings Pro Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('careerjet_settings');
            $widgetKeywords = get_option('careerjet_widget_keywords', 'warehouse, driver, caregiver, nurse');
            $afterPostKeywords = get_option('careerjet_after_post_keywords', 'mechanic, healthcare, nurse, data analyst');
            $totalDisplayLimit = get_option('careerjet_total_display_limit', 8);
            $keywordDisplayCount = get_option('careerjet_keyword_display_count', 1);
            $displayLocation = get_option('careerjet_display_location', 'after_post'); // Default to 'after_post'
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="careerjet_widget_keywords">Widget Keywords</label></th>
                    <td>
                        <textarea id="careerjet_widget_keywords" name="careerjet_widget_keywords" rows="5" cols="50"><?php echo esc_textarea($widgetKeywords); ?></textarea>
                        <p class="description">Enter keywords separated by commas. These keywords will be used to fetch job listings for the widget.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="careerjet_after_post_keywords">After Post Keywords</label></th>
                    <td>
                        <textarea id="careerjet_after_post_keywords" name="careerjet_after_post_keywords" rows="5" cols="50"><?php echo esc_textarea($afterPostKeywords); ?></textarea>
                        <p class="description">Enter keywords separated by commas. These keywords will be used to fetch job listings for after the post content.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="careerjet_total_display_limit">Total Number of Jobs to Display</label></th>
                    <td>
                        <input type="number" id="careerjet_total_display_limit" name="careerjet_total_display_limit" value="<?php echo esc_attr($totalDisplayLimit); ?>" min="1" />
                        <p class="description">Set the total number of job listings to display across all keywords.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="careerjet_keyword_display_count">Number of Jobs per Keyword</label></th>
                    <td>
                        <input type="number" id="careerjet_keyword_display_count" name="careerjet_keyword_display_count" value="<?php echo esc_attr($keywordDisplayCount); ?>" min="1" />
                        <p class="description">Set the number of job listings to display for each keyword.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="careerjet_display_location">Display Location</label></th>
                    <td>
                        <fieldset>
                            <label><input type="radio" name="careerjet_display_location" value="after_post" <?php checked($displayLocation, 'after_post'); ?> /> After Post Content</label><br>
                            <label><input type="radio" name="careerjet_display_location" value="sidebar" <?php checked($displayLocation, 'sidebar'); ?> /> Sidebar Widget</label><br>
                            <label><input type="radio" name="careerjet_display_location" value="shortcode" <?php checked($displayLocation, 'shortcode'); ?> /> Shortcode Only <em><strong>[careerjet_jobs]</strong></em></label><br>
                        </fieldset>
                        <p class="description">Choose where you want the job listings to appear.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Fetch and display job listings
function careerjet_fetch_job_listings($location = 'after_post') {
    $api = new Careerjet_API('en_CA');
    $page = 1;

    // Get the appropriate keywords based on the location
    if ($location == 'sidebar') {
        $keywords = get_option('careerjet_widget_keywords', 'warehouse, driver, caregiver, nurse');
    } else {
        $keywords = get_option('careerjet_after_post_keywords', 'mechanic, healthcare, nurse, data analyst');
    }

    $keywords = array_map('trim', explode(',', $keywords));

    $totalDisplayLimit = get_option('careerjet_total_display_limit', 8);
    $keywordDisplayCount = get_option('careerjet_keyword_display_count', 1);

    $currentDisplayCount = 0;

    ob_start();

    foreach ($keywords as $keyword) {
        if ($currentDisplayCount >= $totalDisplayLimit) {
            break;
        }

        $result = $api->search(array(
            'keywords' => $keyword,
            'page' => $page,
            'affid' => '3591a21e385f51a59313127f7381df3a',
        ));

        if ($result->type == 'JOBS') {
            $jobs = $result->jobs;
            $keywordCount = 0;

            echo "<ul>";

            foreach ($jobs as $job) {
                if (!empty($job->company) && !empty($job->salary)) {
                    echo "<h2 style='color:#0549e8;'>" . htmlspecialchars($job->title) . "</h2>";
                    echo "<p><strong>Company:</strong> " . htmlspecialchars($job->company) . "</p>";
                    echo "<p><strong>Location:</strong> " . htmlspecialchars($job->locations) . "</p>";
                    echo "<p><strong>Salary:</strong> " . htmlspecialchars($job->salary) . "</p>";
                    echo "<p><strong>How to Apply:</strong> <a href='" . htmlspecialchars($job->url) . "' target='_blank'>Apply on company website</a></p>";
                    echo "<hr style='width:50%;text-align:left;margin-left:0'><br>";

                    $currentDisplayCount++;
                    $keywordCount++;

                    if ($currentDisplayCount >= $totalDisplayLimit || $keywordCount >= $keywordDisplayCount) {
                        break;
                    }
                }
            }

            echo "</ul>";
        }
    }

    if ($currentDisplayCount === 0) {
        echo "<p>No job listings found.</p>";
    }

    return ob_get_clean();
}

// Define the widget class
class Careerjet_Job_Listings_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'careerjet_job_listings_widget',
            __('Careerjet Job Listings', 'text_domain'),
            array('description' => __('Displays Careerjet job listings in the sidebar.', 'text_domain'))
        );
    }

    public function widget($args, $instance) {
        echo $args['before_widget'];
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        try {
            echo careerjet_fetch_job_listings('sidebar');
        } catch (Exception $e) {
            echo '<p>Error fetching job listings. Please try again later.</p>';
        }
        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Job Listings', 'text_domain');
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        return $instance;
    }
}

// Register the widget
function register_careerjet_widget() {
    register_widget('Careerjet_Job_Listings_Widget');
}
add_action('widgets_init', 'register_careerjet_widget');

// Display job listings based on settings
function careerjet_display_job_listings() {
    $displayLocation = get_option('careerjet_display_location', 'after_post');

    if ($displayLocation === 'after_post') {
        add_filter('the_content', function ($content) {
            if (is_single()) {
                $content .= '<div class="careerjet-jobs"><h3>More Jobs From Careerjet</h3>' . careerjet_fetch_job_listings('after_post') . '</div>';
            }
            return $content;
        });
    }
}
add_action('init', 'careerjet_display_job_listings');
