<?php


namespace Genpak\Plugins\Migration;


class MigrateProductMedia
{
    /**
     * @var string
     */
    protected $directory_name = 'product-images';

    /**
     *
     */
    public function __invoke()
    {
        echo "Media Migration Started.\n";

        if (!$this->directoryExists()) {
            echo "\e[0;31;40mInvalid directory path provided.\e[0m\n";
        }

        if ($this->directoryExists()) {
            $this->migrate();
        }

        echo "Media Migration Completed.\n";
    }

    /**
     * @return bool
     */
    public function directoryExists()
    {
        $uploads = wp_upload_dir();
        return is_dir($uploads['basedir'] . '/' . $this->directory_name);
    }

    /**
     *
     */
    public function migrate()
    {
        $files = $this->getFiles();

        if (empty($files)) {
            echo "\e[0;31;40mThe directory seems to be empty.\e[0m\n";
        }

        if (!empty($files)) {
            foreach ($files as $file) {
                $this->upload($file);
            }
        }
    }

    /**
     * @return array
     */
    public function getFiles()
    {
        $uploads = wp_upload_dir();

        $files = [];

        if ($dir = opendir($uploads['basedir'] . '/' . $this->directory_name)) {
            while (false !== ($file = readdir($dir))) {
                if ($file != "." && $file != "..") {
                    $files[] = $file;
                }
            }
            closedir($dir);
        }

        return $files;
    }

    /**
     * @param $filename
     */
    public function upload($filename)
    {
        $uploads = wp_upload_dir();

        $file = $uploads['basedir'] . '/' . $this->directory_name . '/' . $filename;

        $upload_file = wp_upload_bits($filename, null, file_get_contents($file));

        if (!$upload_file['error']) {
            $wp_file_type = wp_check_filetype($filename, null);
            $attachment = array(
                'post_mime_type' => $wp_file_type['type'],
                'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
                'post_name' => preg_replace('/\.[^.]+$/', '', $filename),
                'post_content' => '',
                'post_status' => 'inherit'
            );
            $attachment_id = wp_insert_attachment($attachment, $upload_file['file']);

            if (!is_wp_error($attachment_id)) {
                $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload_file['file']);
                wp_update_attachment_metadata($attachment_id, $attachment_data);

                echo $filename . " migrated.\n";
            }
        }
    }
}
