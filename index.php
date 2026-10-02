<?php
/*
Plugin Name: txt edit
Description: Create, edit and delete .txt files in the WordPress root (llms.txt, robots.txt, ads.txt, ...).
Version: 1.0
*/

if (!defined('ABSPATH')) exit;

add_action('admin_menu', function () {
    add_management_page('txt edit', 'txt edit', 'manage_options', 'txt-edit', 'rtf_page');
});

function rtf_valid($name)
{
    if (in_array(strtolower($name), ['license.txt', 'readme.txt'], true)) return false;
    return preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.txt$/', $name) === 1;
}

function rtf_page()
{
    if (!current_user_can('manage_options')) return;

    $msg  = '';
    $name = '';
    $text = '';

    // Save / delete
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_admin_referer('rtf');
        $name = trim(wp_unslash($_POST['name'] ?? ''));

        if (!rtf_valid($name)) {
            $msg = 'Invalid filename. Use letters, digits, . _ - and end with .txt';
        } else if (isset($_POST['delete'])) {
            $msg = @unlink(ABSPATH . $name) ? "Deleted $name" : "Could not delete $name";
            $name = '';
        } else {
            $text = str_replace("\r\n", "\n", wp_unslash($_POST['content'] ?? ''));
            $msg  = (@file_put_contents(ABSPATH . $name, $text) !== false)
                ? "Saved $name"
                : "Could not write $name (is " . ABSPATH . " writable?)";
        }
    }
    // Load for editing
    else if (isset($_GET['edit']) && rtf_valid($_GET['edit']) && is_file(ABSPATH . $_GET['edit'])) {
        $name = $_GET['edit'];
        $text = file_get_contents(ABSPATH . $name);
    }

    $files = array_filter(glob(ABSPATH . '*.txt') ?: [], function ($f) {
        return rtf_valid(basename($f));
    });
    $self  = admin_url('tools.php?page=txt-edit');
?>
    <div class="wrap">
        <h1>Txt Edit</h1>
        <?php if ($msg) echo '<div class="notice notice-info"><p>' . esc_html($msg) . '</p></div>'; ?>

        <p>
            <?php foreach ($files as $f): $b = basename($f); ?>
                <a href="<?php echo esc_url($self . '&edit=' . urlencode($b)); ?>"><?php echo esc_html($b); ?></a> &nbsp;
            <?php endforeach; ?>
            <a href="<?php echo esc_url($self); ?>">[new]</a>
        </p>

        <form method="post">
            <?php wp_nonce_field('rtf'); ?>
            <p><input type="text" name="name" value="<?php echo esc_attr($name); ?>" placeholder="llms.txt" class="regular-text"></p>
            <p><textarea name="content" rows="20" class="large-text code"><?php echo esc_textarea($text); ?></textarea></p>
            <p>
                <button type="submit" class="button button-primary">Save</button>
                <button type="submit" name="delete" value="1" class="button" onclick="return confirm('Delete this file?')">Delete</button>
            </p>
        </form>
    </div>
<?php
}
