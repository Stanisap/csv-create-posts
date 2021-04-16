<?php


class CSVImporter extends WP_Importer {
	/** Sheet columns
	 * @value array
	 */
	public $column_indexes = [
		'post_name' => 0,
		'post_content' => 1,
		'post_category' => 2,
		'post_date' => 3
	];
	public $column_keys = ['post_name', 'post_content', 'post_category', 'post_date'];

	// User interface wrapper start
	function header() {
		echo '<div class="wrap">';
		echo '<h2>'.__('Import CSV', 'csv-create-posts').'</h2>';
	}

	// User interface wrapper end
	function footer() {
		echo '</div>';
	}

	// Step 1
	function greet() {
		echo '<p>'.__( 'Choose a CSV (.csv) file to upload, then click Upload file and import.', 'csv-create-posts' ).'</p>';
		echo '<p>'.__( 'Excel-style CSV file is unconventional and not recommended. LibreOffice has enough export options and recommended for most users.', 'csv-create-posts' ).'</p>';
		echo '<p>'.__( 'Requirements:', 'csv-create-posts' ).'</p>';
		echo '<ol>';
		echo '<li>'.__( 'Select UTF-8 as charset.', 'csv-create-posts' ).'</li>';
		echo '<li>'.sprintf( __( 'You must use field delimiter as "%s"', 'csv-create-posts'), CSVFileWorker::DELIMITER ).'</li>';
		echo '<li>'.__( 'You must quote all text cells.', 'csv-create-posts' ).'</li>';
		echo '<li>'.__( 'The data must be entered in the following order: "post_name", "post_content", "post_category1, post_category2, post_category3", "post_date".', 'csv-create-posts' ).'</li>';
		echo '</ol>';
		echo '<p>'.__( 'Download example CSV files:', 'csv-create-posts' );
		echo ' <a href="'.plugin_dir_url( __FILE__ ).'../examples/example.csv">'.__( 'csv', 'csv-create-posts' ).'</a>,';
		echo ' '.__('(OpenDocument Spreadsheet file format for LibreOffice. Please export as csv before import)', 'csv-create-posts' );
		echo '</p>';

		wp_import_upload_form( add_query_arg('step', 1) );
	}

	// Step 2
	function import() {
		$file = wp_import_handle_upload();

		if ( isset( $file['error'] ) ) {
			echo '<p><strong>' . __( 'Sorry, there has been an error.', 'csv-create-posts' ) . '</strong><br />';
			echo esc_html( $file['error'] ) . '</p>';
			return false;
		} else if ( ! file_exists( $file['file'] ) ) {
			echo '<p><strong>' . __( 'Sorry, there has been an error.', 'csv-create-posts' ) . '</strong><br />';
			printf( __( 'The export file could not be found at <code>%s</code>. It is likely that this was caused by a permissions problem.', 'csv-create-posts' ), esc_html( $file['file'] ) );
			echo '</p>';
			return false;
		}

		$this->id = (int) $file['id'];
		$this->file = get_attached_file($this->id);
		$result = $this->process_posts();
		if ( is_wp_error( $result ) )
			return $result;
	}

	/**
	 * Insert post and categories using `CSVImportPost` class.
	 *
	 * @param array $post
	 * @param array $terms
	 *
	 * @return CSVImportPost
	 */
	public function save_post( array $post, array $terms = []): CSVImportPost {

		$h = CSVImportPost::add($post);

		// Set terms
		if (count($terms) > 0) {
			foreach ($terms as $key => $value) {
				$h->setObjectTerms($key, $value);
			}
		}
 		return $h;
	}

	// process parse csv ind insert posts
	function process_posts() {
		$h = new CSVFileWorker();

		$handle = $h->fopen($this->file, 'r');

		if ( $handle == false ) {
			echo '<p><strong>'.__( 'Failed to open file.', 'csv-create-posts' ).'</strong></p>';
			wp_import_cleanup($this->id);
			return false;
		}

		echo '<ol>';

		while (($data = $h->fgetcsv($handle)) !== FALSE) {
			echo '<li>';

			$post = [
			    'post_type' => 'post',
				'post_status' => 'publish',
            ];
			$error = new WP_Error();

			// (string) post slug
			$post_name = $h->get_data($this,$data,'post_name');
			if ($post_name) {
				$post['post_name'] = $post_name;
				$post['post_title'] = $post_name;
			}
			$post_name = ($post_name) ? $post_name : __('Untitled', 'csv-create-posts');
			// (string) post content
			$post_content = $h->get_data($this,$data,'post_content');
			if ($post_content) {
				$post['post_content'] = $post_content;
			}

			// (string) publish date
			$post_date = $h->get_data($this,$data,'post_date');
			if ($post_date) {
				$post['post_date'] = date("Y-m-d H:i:s", strtotime($post_date));
			}

			// (string, comma separated) slug of post categories
			$post_category = $h->get_data($this,$data,'post_category');
			if ($post_category) {
				$categories = preg_split("/,+/", $post_category); // от 1 до 3 категорий, остальные отбрасывать
				if ($categories) {
				    if (is_array($categories) && count($categories) > 3) {
				        $newCategories = [];
				        array_push($newCategories, $categories[0], $categories[1], $categories[2]);
				        echo '<p><strong>' .
                             sprintf(__('Note: Allowed from 1 to 3 categories for a post, only 3 first categories will be registered. pots_name: %s', 'csv-create-posts'), $post_name) .
                             '</strong></p>';
                    }
				    $categories = (isset($newCategories) && count($newCategories) == 3) ? $newCategories : $categories;
					$post['post_category'] = wp_create_categories($categories);
				}
			}
			/**
			 * Filter post data.
			 *
			 * @param array $post (required)
			 * @param bool $is_update
			 */
			$post = apply_filters( 'csv_cp_save_post', $post );

			/**
			 * Option for dry run testing
			 *
			 * @param bool false
			 */
			$dry_run = apply_filters( 'csv_cp_dry_run', false );

			if (!$error->get_error_codes() && $dry_run == false) {

				/**
				 * Get Alternative Importer Class name.
				 *
				 * @param string Class name to override Importer class. Default to null (do not override).
				 */
				$class = apply_filters( 'csv_cp_class', null );

				// save post data
				if ($class && class_exists($class,false)) {
					$importer = new $class;
					$result = $importer->save_post($post);
				} else {
					$result = $this->save_post($post);
				}

				if ($result->isError()) {
					$error = $result->getError();
				} else {
					$post_object = $result->getPost();

					if (is_object($post_object)) {
						/**
						 * Fires adter the post imported.
						 *
						 * @param WP_Post $post_object
						 */
						do_action( 'scv_cp_post_saved', $post_object );
					}

					echo esc_html(sprintf(__('Processing "%s" done.', 'csv-create-posts'), $post_name));
				}
			}

			// show error messages
			foreach ($error->get_error_messages() as $message) {
				echo esc_html($message).'<br>';
			}

			echo '</li>';

			wp_cache_flush();
		}

		echo '</ol>';

		$h->fclose($handle);

		wp_import_cleanup($this->id);

		echo '<h3>'.__('All Done.', 'csv-create-posts').'</h3>';
	}

	// dispatcher
	function dispatch() {
		$this->header();

		if (empty ($_GET['step']))
			$step = 0;
		else
			$step = (int) $_GET['step'];

		switch ($step) {
			case 0 :
				$this->greet();
				break;
			case 1 :
				check_admin_referer('import-upload');
				set_time_limit(0);
				$result = $this->import();
				if ( is_wp_error( $result ) )
					echo $result->get_error_message();
				break;
		}

		$this->footer();
	}
}